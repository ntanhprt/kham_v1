/**
 * YHCT Medical Diagnosis App — Main JavaScript
 * PHP 8 / SQLite / Bootstrap 5 / Vanilla JS
 */

'use strict';

/* ============================================================
   1. GLOBAL APP UTILITIES
   ============================================================ */
const App = {
    baseUrl: '/y/kham/',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

    /**
     * POST JSON to a URL, returns parsed response.
     * @param {string} url
     * @param {Object} data
     * @returns {Promise<Object>}
     */
    async post(url, data = {}) {
        const res = await fetch(this.baseUrl + url.replace(/^\//, ''), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(data),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        return res.json();
    },

    /**
     * GET JSON from a URL, returns parsed response.
     * @param {string} url
     * @returns {Promise<Object>}
     */
    async get(url) {
        const res = await fetch(this.baseUrl + url.replace(/^\//, ''), {
            headers: {
                'X-CSRF-Token': this.csrfToken,
                'Accept': 'application/json',
            },
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        return res.json();
    },

    showLoading(msg = 'Đang xử lý...') {
        const overlay = document.getElementById('loading-overlay');
        if (!overlay) {
            const el = document.createElement('div');
            el.id = 'loading-overlay';
            el.className = 'loading-overlay active';
            el.innerHTML = `<div class="spinner-yhct"></div><div class="loading-text">${msg}</div>`;
            document.body.appendChild(el);
            return;
        }
        const txt = overlay.querySelector('.loading-text');
        if (txt) txt.textContent = msg;
        overlay.classList.add('active');
    },

    hideLoading() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) overlay.classList.remove('active');
    },

    /**
     * Show a floating toast alert.
     * @param {string} msg
     * @param {'success'|'warning'|'danger'|'info'} type
     * @param {number} duration  ms before auto-dismiss
     */
    showAlert(msg, type = 'success', duration = 4000) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const icons = { success: '✓', warning: '⚠', danger: '✕', info: 'ℹ' };
        const toast = document.createElement('div');
        toast.className = `toast-item ${type}`;
        toast.innerHTML = `<strong>${icons[type] || ''}</strong> ${msg}`;
        container.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(20px)';
                toast.style.transition = 'all .3s ease';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
        return toast;
    },
};

/* ============================================================
   2. CONTRADICTIONS DATA
   ============================================================ */
const CONTRADICTIONS = [
    { pair: ['cold_aversion', 'heat_aversion'],              label: 'Sợ lạnh vs Sợ nóng' },
    { pair: ['excessive_sweating', 'no_sweating'],           label: 'Ra nhiều mồ hôi vs Không ra mồ hôi' },
    { pair: ['excessive_appetite', 'poor_appetite_loss'],    label: 'Ăn nhiều vs Chán ăn' },
    { pair: ['constipation_hard_stool', 'loose_stools'],     label: 'Táo bón vs Tiêu lỏng' },
    { pair: ['cold_extremities', 'hot_palms_soles'],         label: 'Tay chân lạnh vs Lòng bàn tay nóng' },
];

/* ============================================================
   3. SYMPTOM PICKER MODULE
   ============================================================ */
const SymptomPicker = {
    selectedCodes: [],
    sessionId: null,
    rerankTimer: null,
    MAX_SYMPTOMS: 15,

    /**
     * @param {string} sessionId
     * @param {string[]} initialCodes  already-selected symptom codes
     */
    init(sessionId, initialCodes = []) {
        this.sessionId = sessionId;
        this.selectedCodes = [...initialCodes];

        // Reflect initial state on UI
        initialCodes.forEach(code => {
            const card = document.querySelector(`.symptom-card[data-code="${code}"]`);
            if (card) this._markSelected(card, true);
        });
        this.updateCounter(this.selectedCodes.length);

        // Search / filter
        const searchInput = document.getElementById('symptom-search');
        if (searchInput) {
            searchInput.addEventListener('input', () => this._filterCards(searchInput.value));
        }

        // Tab switching
        document.querySelectorAll('.symptom-tab').forEach(tab => {
            tab.addEventListener('click', () => this._switchTab(tab));
        });

        // Background-load semantic model (non-blocking; shows badge only on success)
        if (window.SemanticSearch) {
            if (SemanticSearch.isReady) {
                const indicator = document.getElementById('semModelIndicator');
                if (indicator) indicator.style.display = '';
            } else {
                SemanticSearch.loadModel()
                    .then(ok => {
                        if (ok) {
                            const indicator = document.getElementById('semModelIndicator');
                            if (indicator) indicator.style.display = '';
                        }
                    })
                    .catch(() => { /* model unavailable — silent */ });
            }
        }
    },

    toggleSymptom(code, card) {
        const idx = this.selectedCodes.indexOf(code);
        if (idx === -1) {
            // Adding
            const conflict = this.checkContradictions([...this.selectedCodes, code]);
            if (conflict) {
                const labels = conflict.pair.map(c => {
                    const el = document.querySelector(`.symptom-card[data-code="${c}"] .symptom-label`);
                    return el ? el.textContent.trim() : c;
                });
                this.showContradictionPopup(conflict, labels, code, card);
                return;
            }

            this.selectedCodes.push(code);
            this._markSelected(card, true);
            this.checkOverSelection(this.selectedCodes);
        } else {
            // Removing
            this.selectedCodes.splice(idx, 1);
            this._markSelected(card, false);
        }

        this.updateCounter(this.selectedCodes.length);
        this._scheduleRerank();
    },

    /**
     * Check if adding any code in `codes` creates a contradiction.
     * @param {string[]} codes
     * @returns {Object|null}  contradiction definition or null
     */
    checkContradictions(codes) {
        for (const c of CONTRADICTIONS) {
            if (c.pair.every(p => codes.includes(p))) return c;
        }
        return null;
    },

    /**
     * @param {Object} conflict  contradiction definition
     * @param {string[]} labels  human-readable labels for the pair
     * @param {string} pendingCode  code being added (to allow forced add)
     * @param {HTMLElement} pendingCard
     */
    showContradictionPopup(conflict, labels, pendingCode, pendingCard) {
        // Remove existing popup
        document.getElementById('contradiction-overlay')?.remove();

        const overlay = document.createElement('div');
        overlay.id = 'contradiction-overlay';
        overlay.className = 'contradiction-overlay';
        overlay.innerHTML = `
            <div class="contradiction-popup" role="dialog" aria-modal="true">
                <div class="conflict-icon">⚠️</div>
                <h3>Triệu chứng mâu thuẫn</h3>
                <p>Hai triệu chứng sau đây thường không xuất hiện cùng nhau trong YHCT:</p>
                <div class="contradiction-pair">
                    <span class="pair-badge">${labels[0]}</span>
                    <span class="pair-vs">vs</span>
                    <span class="pair-badge">${labels[1]}</span>
                </div>
                <p style="font-size:.82rem;color:#666;">
                    Nếu bệnh nhân thực sự có cả hai, hãy chọn triệu chứng nổi bật hơn.
                </p>
                <div class="contradiction-actions">
                    <button class="btn btn-outline-secondary" id="conflict-cancel">Hủy</button>
                    <button class="btn btn-warning" id="conflict-remove-other">Xóa triệu chứng kia &amp; thêm</button>
                    <button class="btn btn-danger" id="conflict-force">Thêm dù mâu thuẫn</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        // Cancel
        overlay.querySelector('#conflict-cancel').addEventListener('click', () => overlay.remove());

        // Remove conflicting symptom, add new
        overlay.querySelector('#conflict-remove-other').addEventListener('click', () => {
            const otherCode = conflict.pair.find(p => p !== pendingCode);
            const idx = this.selectedCodes.indexOf(otherCode);
            if (idx !== -1) this.selectedCodes.splice(idx, 1);
            const otherCard = document.querySelector(`.symptom-card[data-code="${otherCode}"]`);
            if (otherCard) this._markSelected(otherCard, false);

            this.selectedCodes.push(pendingCode);
            this._markSelected(pendingCard, true);
            this.updateCounter(this.selectedCodes.length);
            this._scheduleRerank();
            overlay.remove();
        });

        // Force add
        overlay.querySelector('#conflict-force').addEventListener('click', () => {
            this.selectedCodes.push(pendingCode);
            this._markSelected(pendingCard, true);
            this.updateCounter(this.selectedCodes.length);
            this._scheduleRerank();
            overlay.remove();
            App.showAlert('Đã thêm triệu chứng mâu thuẫn — lưu ý khi đọc kết quả.', 'warning');
        });

        // Click outside to cancel
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.remove();
        });
    },

    checkOverSelection(codes) {
        if (codes.length > this.MAX_SYMPTOMS) {
            App.showAlert(
                `Đã chọn ${codes.length} triệu chứng — nên chọn tối đa ${this.MAX_SYMPTOMS} để kết quả chính xác nhất.`,
                'warning'
            );
        }
    },

    /** AJAX call to /exam/api_rank to get updated hypothesis scores */
    async rerank() {
        if (!this.selectedCodes.length) {
            this.updateHypothesisBars([]);
            return;
        }
        try {
            const data = await App.post('exam/api_rank', {
                session_id: this.sessionId,
                codes: this.selectedCodes,
            });
            if (data.hypotheses) this.updateHypothesisBars(data.hypotheses);
        } catch (err) {
            console.warn('Rerank failed:', err);
        }
    },

    /**
     * Update sidebar hypothesis bars.
     * @param {Array<{name:string, score:number}>} hypotheses  sorted desc by score
     */
    updateHypothesisBars(hypotheses) {
        const container = document.getElementById('hypothesis-list');
        if (!container) return;

        if (!hypotheses.length) {
            container.innerHTML = '<p class="text-muted-yhct" style="font-size:.82rem;">Chọn triệu chứng để xem gợi ý...</p>';
            return;
        }

        const maxScore = hypotheses[0]?.score || 1;
        container.innerHTML = hypotheses.slice(0, 6).map((h, i) => {
            const pct = Math.round((h.score / maxScore) * 100);
            const rankClass = i < 3 ? `rank-${i + 1}` : '';
            return `
                <div class="hypothesis-item">
                    <div class="hypothesis-name">
                        <span>${h.name}</span>
                        <span class="hyp-score">${h.score.toFixed ? h.score.toFixed(1) : h.score}</span>
                    </div>
                    <div class="hypothesis-bar">
                        <div class="hypothesis-fill ${rankClass}" style="width:${pct}%"></div>
                    </div>
                </div>
            `;
        }).join('');
    },

    updateCounter(count) {
        const el = document.getElementById('selected-count');
        if (el) el.textContent = count;
        const numEl = document.querySelector('.count-number');
        if (numEl) numEl.textContent = count;

        // Update hidden form field
        const codesInput = document.getElementById('selected-codes-input');
        if (codesInput) codesInput.value = this.selectedCodes.join(',');
    },

    submit() {
        if (this.selectedCodes.length === 0) {
            App.showAlert('Vui lòng chọn ít nhất 1 triệu chứng.', 'danger');
            return false;
        }
        const form = document.getElementById('symptom-form');
        if (form) {
            App.showLoading('Đang phân tích...');
            form.submit();
        }
        return true;
    },

    /* ── Private helpers ── */

    _markSelected(card, selected) {
        if (!card) return;
        const icon = card.querySelector('.symptom-checkbox');
        if (selected) {
            card.classList.add('selected');
            if (icon) {
                icon.classList.remove('bi-circle');
                icon.classList.add('bi-check-circle-fill');
            }
        } else {
            card.classList.remove('selected');
            if (icon) {
                icon.classList.remove('bi-check-circle-fill');
                icon.classList.add('bi-circle');
            }
        }
    },

    _scheduleRerank() {
        clearTimeout(this.rerankTimer);
        this.rerankTimer = setTimeout(() => this.rerank(), 600);
    },

    _filterCards(query) {
        const q = query.trim();
        const qLower = q.toLowerCase();

        // Immediate text match (always runs, class fix: .symptom-name not .symptom-label)
        document.querySelectorAll('.symptom-card').forEach(card => {
            const label = (card.querySelector('.symptom-name')?.textContent || '').toLowerCase();
            card.style.display = (!q || label.includes(qLower)) ? '' : 'none';
        });

        if (!q) {
            document.getElementById('semSearchBadge')?.setAttribute('style', 'display:none');
            return;
        }

        // Semantic enhancement — async, only if model + index ready
        if (q.length < 2) return;
        clearTimeout(this._semTimer);
        this._semTimer = setTimeout(async () => {
            if (!window.SemanticSearch || !SemanticSearch.isReady) return;
            try {
                const results = await SemanticSearch.search(q, 'k02_symptom', 20);
                if (!results.length) return;

                const scoreMap = {};
                for (const r of results) { scoreMap[r.src] = r.similarity; }

                // Show/hide by semantic score when text search found nothing meaningful
                const textVisible = [...document.querySelectorAll('.symptom-card')]
                    .filter(c => c.style.display !== 'none').length;

                document.querySelectorAll('.symptom-card').forEach(card => {
                    const code  = card.dataset.code;
                    const score = scoreMap[code] || 0;
                    const textMatch = (card.querySelector('.symptom-name')?.textContent || '').toLowerCase().includes(qLower);
                    card.style.display = (textMatch || score > 0.35) ? '' : 'none';
                    // Show score badge
                    if (score > 0.5 && !textMatch) {
                        card.style.borderColor = '#4CAF50';
                        card.title = `Độ tương đồng: ${Math.round(score * 100)}%`;
                    }
                });

                const badge = document.getElementById('semSearchBadge');
                if (badge) badge.style.display = '';
            } catch (_) { /* index not built yet — silent */ }
        }, 350);
    },

    _switchTab(tab) {
        document.querySelectorAll('.symptom-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        const group = tab.dataset.group || 'all';
        document.querySelectorAll('.symptom-card').forEach(card => {
            card.style.display = (group === 'all' || card.dataset.group === group) ? '' : 'none';
        });
    },
};

/* ============================================================
   4. CONTEXT EXTRACTOR (Phase 0 in browser)
   ============================================================ */
const ContextExtractor = {
    triggers: {
        'uống bia':        { symptom: 'throbbing_headache',    boost: 0.85 },
        'uống rượu':       { symptom: 'nausea_vomiting',       boost: 0.80 },
        'kỳ kinh':         { symptom: 'lower_abdominal_pain',  boost: 0.90 },
        'hành kinh':       { symptom: 'lower_abdominal_pain',  boost: 0.90 },
        'mang thai':       { symptom: 'nausea_vomiting',       boost: 0.95 },
        'đang bầu':        { symptom: 'nausea_vomiting',       boost: 0.95 },
        'căng thẳng':      { symptom: 'throbbing_headache',    boost: 0.80 },
        'thiếu ngủ':       { symptom: 'throbbing_headache',    boost: 0.75 },
        'ngồi lâu':        { symptom: 'lower_back_pain',       boost: 0.70 },
        'nóng trong người':{ symptom: 'heat_excess_pattern',   boost: 0.90 },
        'stress':          { symptom: 'throbbing_headache',    boost: 0.78 },
        'kinh nguyệt':     { symptom: 'lower_abdominal_pain',  boost: 0.88 },
        'sau sinh':        { symptom: 'deficiency_pattern',    boost: 0.82 },
        'không ngủ được':  { symptom: 'insomnia',              boost: 0.85 },
        'hay quên':        { symptom: 'memory_decline',        boost: 0.75 },
        'tiểu đêm':        { symptom: 'nocturia',              boost: 0.88 },
        'đau vai gáy':     { symptom: 'neck_shoulder_pain',    boost: 0.85 },
        'đau mỏi cổ':      { symptom: 'neck_shoulder_pain',    boost: 0.82 },
    },

    /**
     * Find context triggers in free text.
     * @param {string} text
     * @returns {Array<{symptom:string, boost:number, phrase:string}>}
     */
    extract(text) {
        const lower = text.toLowerCase();
        const results = [];
        const seen = new Set();

        for (const [phrase, meta] of Object.entries(this.triggers)) {
            if (lower.includes(phrase) && !seen.has(meta.symptom)) {
                results.push({ symptom: meta.symptom, boost: meta.boost, phrase });
                seen.add(meta.symptom);
            }
        }

        // Sort by boost descending
        return results.sort((a, b) => b.boost - a.boost);
    },
};

/* ============================================================
   5. QUICK QUESTIONS MODULE
   ============================================================ */
const QuickQuestions = {
    answers: {},
    requiredIds: [],

    init(requiredIds = []) {
        this.requiredIds = requiredIds;
    },

    selectOption(question, value, element) {
        this.answers[question] = value;

        // Update UI: deselect siblings, select this
        const group = element.closest('.question-options');
        if (group) {
            group.querySelectorAll('.option-btn').forEach(btn => btn.classList.remove('selected'));
        }
        element.classList.add('selected');

        // Update hidden input
        const input = document.getElementById(`q_${question}`);
        if (input) input.value = value;

        // Remove error state
        const card = element.closest('.question-card');
        if (card) card.classList.remove('question-error');
    },

    validateAndSubmit() {
        let valid = true;
        const missing = [];

        this.requiredIds.forEach(qid => {
            if (!this.answers[qid]) {
                const card = document.querySelector(`.question-card[data-question="${qid}"]`);
                if (card) {
                    card.classList.add('question-error');
                    card.style.borderColor = 'var(--color-warning)';
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                missing.push(qid);
                valid = false;
            }
        });

        if (!valid) {
            App.showAlert(`Vui lòng trả lời ${missing.length} câu hỏi còn thiếu.`, 'warning');
            return false;
        }

        const form = document.getElementById('questions-form');
        if (form) {
            App.showLoading('Đang phân tích...');
            form.submit();
        }
        return true;
    },
};

/* ============================================================
   6. RESULT PAGE MODULE
   ============================================================ */
const ResultPage = {
    init() {
        this._setupPrintButton();
        this._setupCopyButton();
        this._setupCollapsibles();

        // Animate bars after a short delay for visual effect
        setTimeout(() => this.animateBars(), 200);
    },

    animateBars() {
        // Bat cuong axis bars
        document.querySelectorAll('.axis-fill[data-value]').forEach(bar => {
            const val = parseFloat(bar.dataset.value) || 0;
            bar.style.width = Math.min(100, Math.abs(val)) + '%';
        });

        // Hypothesis / alt-pattern bars
        document.querySelectorAll('.hypothesis-fill[data-pct], .alt-pattern-bar-fill[data-pct]').forEach(bar => {
            const pct = parseFloat(bar.dataset.pct) || 0;
            bar.style.width = pct + '%';
        });

        // Generic progress bars with data-target
        document.querySelectorAll('[data-animate-width]').forEach(el => {
            const target = el.dataset.animateWidth;
            el.style.width = target;
        });
    },

    printResult() {
        // Brief delay to ensure styles are rendered
        window.print();
    },

    copyResult() {
        const resultEl = document.getElementById('result-summary-text');
        const text = resultEl
            ? resultEl.innerText
            : document.querySelector('.result-hero')?.innerText || 'Kết quả chẩn đoán YHCT';

        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                App.showAlert('Đã sao chép kết quả vào clipboard.', 'success');
            }).catch(() => this._fallbackCopy(text));
        } else {
            this._fallbackCopy(text);
        }
    },

    _fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
        App.showAlert('Đã sao chép kết quả.', 'success');
    },

    _setupPrintButton() {
        document.querySelectorAll('[data-action="print"]').forEach(btn => {
            btn.addEventListener('click', () => this.printResult());
        });
    },

    _setupCopyButton() {
        document.querySelectorAll('[data-action="copy-result"]').forEach(btn => {
            btn.addEventListener('click', () => this.copyResult());
        });
    },

    _setupCollapsibles() {
        document.querySelectorAll('.result-section-header.collapse-toggle').forEach(header => {
            header.addEventListener('click', () => {
                const section = header.closest('.collapsible-section');
                if (section) section.classList.toggle('collapsed');
                const body = section?.querySelector('.collapse-body');
                if (body) {
                    body.style.display = section.classList.contains('collapsed') ? 'none' : '';
                }
            });
        });
    },
};

/* ============================================================
   7. START PAGE MODULE
   ============================================================ */
const StartPage = {
    examples: [
        'đau đầu kèm chóng mặt buồn nôn',
        'mệt mỏi toàn thân, chán ăn không muốn ăn',
        'mất ngủ, hay lo lắng, hồi hộp',
        'đau lưng dưới, mỏi gối, tiểu đêm nhiều',
        'đau bụng vùng hông sườn phải, dễ cáu',
        'ho khan kéo dài, khó thở nhẹ',
    ],
    MAX_CHARS: 500,

    init() {
        this._setupTextarea();
        this._renderExampleChips();
        this._setupFormSubmit();
    },

    useExample(text) {
        const ta = document.getElementById('chief-complaint');
        if (ta) {
            ta.value = text;
            ta.dispatchEvent(new Event('input'));
            ta.focus();
        }
    },

    detectContextTriggers(text) {
        const matches = ContextExtractor.extract(text);
        const preview = document.getElementById('context-preview');
        if (!preview) return;

        if (!matches.length) {
            preview.classList.remove('visible');
            return;
        }

        const tags = matches.map(m =>
            `<span class="context-trigger-tag" title="boost ${Math.round(m.boost * 100)}%">🔍 ${m.phrase}</span>`
        ).join(' ');

        preview.innerHTML = `<strong>Phát hiện ngữ cảnh:</strong> ${tags}
            <br><small>Các từ khóa này sẽ tự động tăng trọng số cho triệu chứng liên quan.</small>`;
        preview.classList.add('visible');
    },

    /* ── Private ── */

    _setupTextarea() {
        const ta = document.getElementById('chief-complaint');
        const counter = document.getElementById('char-counter');
        if (!ta) return;

        ta.addEventListener('input', () => {
            const len = ta.value.length;
            if (counter) {
                counter.textContent = `${len}/${this.MAX_CHARS}`;
                counter.className = 'char-counter';
                if (len > this.MAX_CHARS * 0.85) counter.classList.add('warn');
                if (len > this.MAX_CHARS) counter.classList.add('over');
            }
            this.detectContextTriggers(ta.value);
        });
    },

    _renderExampleChips() {
        const container = document.getElementById('example-chips');
        if (!container) return;

        container.innerHTML = this.examples.map(ex => `
            <button type="button" class="example-chip" onclick="StartPage.useExample(${JSON.stringify(ex)})">
                ${ex.length > 40 ? ex.substring(0, 38) + '…' : ex}
            </button>
        `).join('');
    },

    _setupFormSubmit() {
        const form = document.getElementById('start-form');
        if (!form) return;
        form.addEventListener('submit', e => {
            const ta = document.getElementById('chief-complaint');
            if (!ta || !ta.value.trim()) {
                e.preventDefault();
                App.showAlert('Vui lòng mô tả triệu chứng chính của bệnh nhân.', 'danger');
                return;
            }
            App.showLoading('Đang phân tích triệu chứng...');
        });
    },
};

/* ============================================================
   8. ADMIN EMBEDDING MANAGEMENT
   ============================================================ */
const EmbeddingAdmin = {
    modelReady: false,
    embedder: null,
    cancelled: false,
    BATCH_SIZE: 10,
    CHECKPOINT_PREFIX: 'yhct_embed_',

    async loadModel() {
        const statusEl = document.getElementById('model-status');
        const providerEl = document.getElementById('embed-provider');
        const provider = providerEl?.value || 'browser_minilm';

        if (statusEl) {
            statusEl.textContent = 'Đang tải mô hình...';
            statusEl.className = 'badge bg-warning text-dark';
        }

        try {
            if (provider === 'openai_3small') {
                // OpenAI — no browser model needed
                this.modelReady = true;
                if (statusEl) {
                    statusEl.textContent = 'OpenAI API (sẵn sàng)';
                    statusEl.className = 'badge bg-success';
                }
                return;
            }

            // Dynamically import @xenova/transformers
            const { pipeline } = await import('https://cdn.jsdelivr.net/npm/@xenova/transformers@2.17.2/dist/transformers.min.js');

            const modelName = provider === 'browser_vietnamese'
                ? 'Xenova/paraphrase-multilingual-MiniLM-L12-v2'
                : 'Xenova/all-MiniLM-L6-v2';

            this.embedder = await pipeline('feature-extraction', modelName, {
                progress_callback: (info) => {
                    if (info.status === 'progress' && statusEl) {
                        const pct = Math.round(info.progress || 0);
                        statusEl.textContent = `Đang tải: ${pct}%`;
                    }
                },
            });

            this.modelReady = true;
            if (statusEl) {
                statusEl.textContent = `Sẵn sàng (${modelName.split('/').pop()})`;
                statusEl.className = 'badge bg-success';
            }
            App.showAlert('Mô hình đã sẵn sàng.', 'success');
        } catch (err) {
            console.error('Model load error:', err);
            if (statusEl) {
                statusEl.textContent = 'Lỗi tải mô hình';
                statusEl.className = 'badge bg-danger';
            }
            App.showAlert('Không thể tải mô hình: ' + err.message, 'danger');
        }
    },

    async generateEmbeddings(docType) {
        if (!this.modelReady) {
            App.showAlert('Vui lòng tải mô hình trước.', 'warning');
            return;
        }

        this.cancelled = false;
        const providerEl = document.getElementById('embed-provider');
        const provider = providerEl?.value || 'browser_minilm';

        const progressBar = document.getElementById('embed-progress-bar');
        const progressWrap = document.getElementById('embed-progress-wrap');
        const docPreview = document.getElementById('doc-preview');
        const cancelBtn = document.getElementById('cancel-embed-btn');

        if (progressWrap) progressWrap.style.display = 'block';
        if (cancelBtn) {
            cancelBtn.style.display = 'inline-block';
            cancelBtn.onclick = () => { this.cancelled = true; App.showAlert('Đã hủy.', 'warning'); };
        }

        try {
            // Load checkpoint
            const ckpt = this.loadCheckpoint(docType);
            let offset = ckpt?.offset || 0;

            // Fetch documents to embed
            const resp = await App.get(`admin/api/docs-pending?doc_type=${encodeURIComponent(docType)}&offset=${offset}`);
            const docs = resp.docs || [];
            const total = resp.total || docs.length;

            if (!docs.length) {
                App.showAlert('Không có tài liệu nào cần nhúng.', 'info');
                return;
            }

            let processed = offset;

            for (let i = 0; i < docs.length; i += this.BATCH_SIZE) {
                if (this.cancelled) break;

                const batch = docs.slice(i, i + this.BATCH_SIZE);
                const embeddings = [];

                for (const doc of batch) {
                    if (this.cancelled) break;
                    if (docPreview) docPreview.textContent = doc.text?.substring(0, 120) + '…';

                    let vector;
                    if (provider === 'openai_3small') {
                        vector = await this._embedOpenAI(doc.text);
                    } else {
                        const out = await this.embedder(doc.text, { pooling: 'mean', normalize: true });
                        vector = Array.from(out.data);
                    }
                    embeddings.push({ id: doc.id, vector });
                }

                if (embeddings.length) {
                    await App.post('admin/api/save-embeddings', { doc_type: docType, embeddings, provider });
                }

                processed += batch.length;
                const pct = Math.round((processed / total) * 100);

                if (progressBar) {
                    progressBar.style.width = pct + '%';
                    progressBar.querySelector('.embed-progress-label').textContent = `${pct}% (${processed}/${total})`;
                }

                this.saveCheckpoint(docType, processed, embeddings.map(e => e.id));
            }

            if (!this.cancelled) {
                App.showAlert(`Hoàn thành: ${processed} tài liệu đã được nhúng.`, 'success');
                this._clearCheckpoint(docType);
                // Notify caller instead of reloading (preserves model state)
                if (typeof this.onComplete === 'function') {
                    this.onComplete(docType, processed);
                }
            }
        } catch (err) {
            console.error('Embed error:', err);
            App.showAlert('Lỗi trong quá trình nhúng: ' + err.message, 'danger');
        } finally {
            if (cancelBtn) cancelBtn.style.display = 'none';
        }
    },

    async buildIndex() {
        const btn = document.getElementById('build-index-btn');
        if (btn) btn.disabled = true;
        App.showLoading('Đang xây dựng chỉ mục...');
        try {
            const res = await App.post('admin/api/build-index', {});
            App.showAlert(res.message || 'Đã xây dựng chỉ mục thành công.', 'success');
        } catch (err) {
            App.showAlert('Lỗi xây dựng chỉ mục: ' + err.message, 'danger');
        } finally {
            App.hideLoading();
            if (btn) btn.disabled = false;
        }
    },

    saveCheckpoint(docType, offset, processedIds) {
        const data = { offset, processedIds, ts: Date.now() };
        localStorage.setItem(this.CHECKPOINT_PREFIX + docType, JSON.stringify(data));
    },

    loadCheckpoint(docType) {
        try {
            const raw = localStorage.getItem(this.CHECKPOINT_PREFIX + docType);
            return raw ? JSON.parse(raw) : null;
        } catch { return null; }
    },

    _clearCheckpoint(docType) {
        localStorage.removeItem(this.CHECKPOINT_PREFIX + docType);
    },

    async _embedOpenAI(text) {
        const res = await App.post('admin/api/openai-embed', { text });
        return res.vector || [];
    },
};

/* ============================================================
   9. DOM READY — INIT
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {

    // ── Determine current page ──
    const page = document.body.dataset.page || '';

    // ── Start page ──
    if (page === 'start') {
        StartPage.init();
    }

    // ── Symptom picker ──
    if (page === 'symptoms') {
        const sessionId  = document.body.dataset.sessionId || '';
        const initCodes  = (document.body.dataset.selectedCodes || '')
            .split(',').filter(Boolean);
        SymptomPicker.init(sessionId, initCodes);

        // Wire up symptom cards
        document.querySelectorAll('.symptom-card').forEach(card => {
            card.addEventListener('click', () => {
                SymptomPicker.toggleSymptom(card.dataset.code, card);
            });
        });

        // Submit button
        const submitBtn = document.getElementById('submit-symptoms-btn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => SymptomPicker.submit());
        }
    }

    // ── Quick questions ──
    if (page === 'questions') {
        const required = Array.from(
            document.querySelectorAll('.question-card[data-required="true"]')
        ).map(c => c.dataset.question).filter(Boolean);
        QuickQuestions.init(required);

        const submitBtn = document.getElementById('submit-questions-btn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => QuickQuestions.validateAndSubmit());
        }
    }

    // ── Result page ──
    if (page === 'result') {
        ResultPage.init();
    }

    // ── Admin embeddings ──
    if (page === 'admin-embeddings') {
        const loadModelBtn = document.getElementById('load-model-btn');
        if (loadModelBtn) loadModelBtn.addEventListener('click', () => EmbeddingAdmin.loadModel());

        const buildIndexBtn = document.getElementById('build-index-btn');
        if (buildIndexBtn) buildIndexBtn.addEventListener('click', () => EmbeddingAdmin.buildIndex());

        // Restore checkpoint notice
        document.querySelectorAll('[data-doc-type]').forEach(btn => {
            const dt = btn.dataset.docType;
            const ckpt = EmbeddingAdmin.loadCheckpoint(dt);
            if (ckpt) {
                const notice = document.getElementById(`ckpt-notice-${dt}`);
                if (notice) {
                    notice.textContent = `Tiếp tục từ offset ${ckpt.offset} (${new Date(ckpt.ts).toLocaleTimeString()})`;
                    notice.style.display = 'inline';
                }
            }
        });
    }

    // ── Auto-dismiss flash alerts after 5 s ──
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity .4s';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    // ── Bootstrap tooltips ──
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
    }

    // ── CSRF injection into all standard fetch (patch global) ──
    const origFetch = window.fetch;
    window.fetch = function (url, opts = {}) {
        // Only inject for same-origin, non-GET requests
        if (opts.method && opts.method.toUpperCase() !== 'GET') {
            opts.headers = opts.headers || {};
            if (!opts.headers['X-CSRF-Token'] && App.csrfToken) {
                opts.headers['X-CSRF-Token'] = App.csrfToken;
            }
        }
        return origFetch(url, opts);
    };

    // ── Emergency banner dismiss ──
    const emergencyBtn = document.querySelector('.emergency-banner .btn-dismiss');
    if (emergencyBtn) {
        emergencyBtn.addEventListener('click', () => {
            const banner = emergencyBtn.closest('.emergency-banner');
            if (banner) {
                banner.style.opacity = '0';
                banner.style.transition = 'opacity .3s';
                setTimeout(() => banner.remove(), 300);
            }
        });
    }

    // ── Bat cuong bar animation on scroll ──
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const bar = entry.target;
                    bar.style.width = (bar.dataset.value || '0') + '%';
                    observer.unobserve(bar);
                }
            });
        }, { threshold: 0.3 });

        document.querySelectorAll('.axis-fill[data-value]').forEach(bar => {
            bar.style.width = '0';
            observer.observe(bar);
        });
    }
});

/* ============================================================
   10. GLOBAL HELPERS (callable from inline HTML)
   ============================================================ */

/** Expose option selection for inline onclick */
function selectOption(question, value, element) {
    QuickQuestions.selectOption(question, value, element);
}

/** Expose symptom toggle for inline onclick */
function toggleSymptom(code, card) {
    SymptomPicker.toggleSymptom(code, card);
}

/** Generate embeddings for a doc type */
function generateEmbeddings(docType) {
    EmbeddingAdmin.generateEmbeddings(docType);
}
