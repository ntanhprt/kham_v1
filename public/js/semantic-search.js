/**
 * semantic-search.js — Browser-side semantic search for YHCT
 *
 * Architecture: Xenova/Transformers.js → float[384] → 48-byte binary (sign-bit hash)
 *               Binary stored in SQLite (via admin API), served as {type}.bin static files
 *               Search: Hamming distance scan in O(N) — fast for N < 10,000
 *
 * Usage:
 *   await SemanticSearch.loadModel();                        // one-time ~25MB download
 *   const results = await SemanticSearch.search(query, 'k02_symptom', 10);
 *   // results: [{id, src, preview, similarity}, ...]
 *
 *   // Hybrid search (semantic + token fallback merge):
 *   const merged = await SemanticSearch.hybridSearch(query, tokenResults);
 */

'use strict';

(function (global) {

    // ── Popcount lookup table (XOR byte → bit-count) ─────────────────────────
    const POPCOUNT = new Uint8Array(256);
    for (let i = 0; i < 256; i++) {
        let x = i;
        x = x - ((x >> 1) & 0x55555555);
        x = (x & 0x33333333) + ((x >> 2) & 0x33333333);
        POPCOUNT[i] = ((x + (x >> 4)) & 0x0f);
    }

    /** Hamming distance between 48-byte segment at offsetA vs offsetB in full dataset buffer */
    function hamming48(dataset, offsetA, queryBuf, offsetB) {
        let dist = 0;
        for (let i = 0; i < 48; i++) {
            dist += POPCOUNT[dataset[offsetA + i] ^ queryBuf[offsetB + i]];
        }
        return dist;
    }

    /** top-K linear scan */
    function topKScan(queryBuf, dataset, k) {
        const n   = dataset.length / 48;
        const out = [];
        for (let i = 0; i < n; i++) {
            const dist = hamming48(dataset, i * 48, queryBuf, 0);
            out.push({ idx: i, dist });
        }
        out.sort((a, b) => a.dist - b.dist);
        return out.slice(0, k);
    }

    /** Convert 384-float output → 48-byte Uint8Array sign-bit hash */
    function floatToBinary(floats) {
        const bits = new Uint8Array(48);
        for (let i = 0; i < 384; i++) {
            if (floats[i] > 0) bits[Math.floor(i / 8)] |= (1 << (7 - (i % 8)));
        }
        return bits;
    }

    // ── SemanticSearch singleton ──────────────────────────────────────────────

    const SemanticSearch = {
        _embedder:   null,
        _datasets:   {},   // { docType: Uint8Array }
        _metadata:   {},   // { docType: Array<{id, src, preview}> }
        _modelReady: false,
        _loading:    false,
        _BASE_URL:   window.__BASE_URL__ || '/y/kham/',

        /**
         * Load the embedding model (Xenova/all-MiniLM-L6-v2 or multilingual variant).
         * @param {string} [modelName] override model id
         */
        async loadModel(modelName) {
            if (this._modelReady) return true;
            if (this._loading) {
                // Wait for existing load to complete
                return new Promise(resolve => {
                    const poll = setInterval(() => {
                        if (this._modelReady || !this._loading) {
                            clearInterval(poll);
                            resolve(this._modelReady);
                        }
                    }, 200);
                });
            }

            this._loading = true;
            try {
                const { pipeline } = await import(
                    'https://cdn.jsdelivr.net/npm/@xenova/transformers@2.17.2/dist/transformers.min.js'
                );
                const model = modelName || 'Xenova/all-MiniLM-L6-v2';
                this._embedder  = await pipeline('feature-extraction', model);
                this._modelReady = true;
                console.log('[SemanticSearch] Model ready:', model);
                return true;
            } catch (err) {
                console.error('[SemanticSearch] Model load failed:', err);
                return false;
            } finally {
                this._loading = false;
            }
        },

        /** Embed a text string → 48-byte Uint8Array */
        async embed(text) {
            if (!this._modelReady) throw new Error('Model not loaded');
            const out    = await this._embedder(text, { pooling: 'mean', normalize: true });
            return floatToBinary(Array.from(out.data));
        },

        /** Load binary index + metadata for a doc_type (lazy, cached) */
        async _loadIndex(docType) {
            if (this._datasets[docType]) return;  // already cached

            const base = this._BASE_URL + 'public/embeddings/';
            const [binRes, metaRes] = await Promise.all([
                fetch(base + docType + '.bin'),
                fetch(base + docType + '_meta.json'),
            ]);

            if (!binRes.ok || !metaRes.ok) {
                throw new Error(`Index not found for doc_type=${docType}. Build it via /admin/embeddings.`);
            }

            this._datasets[docType] = new Uint8Array(await binRes.arrayBuffer());
            this._metadata[docType] = await metaRes.json();
        },

        /**
         * Semantic search within one doc_type.
         * @param {string} query   — user text
         * @param {string} docType — e.g. 'k02_symptom'
         * @param {number} topK    — max results (default 10)
         * @returns {Array<{id, src, preview, similarity}>}
         */
        async search(query, docType, topK = 10) {
            await this._loadIndex(docType);
            const queryBuf = await this.embed(query);
            const hits     = topKScan(queryBuf, this._datasets[docType], topK);
            const meta     = this._metadata[docType];

            return hits.map(h => ({
                ...meta[h.idx],
                doc_type:   docType,
                similarity: parseFloat((1 - h.dist / 384).toFixed(4)),
            })).filter(r => r.similarity > 0);
        },

        /**
         * Search across multiple doc_types and merge results.
         * @param {string}   query
         * @param {string[]} docTypes
         * @param {number}   topK per type
         * @returns merged + sorted by similarity
         */
        async searchMulti(query, docTypes, topK = 5) {
            const results = await Promise.all(docTypes.map(t => this.search(query, t, topK)));
            return results.flat().sort((a, b) => b.similarity - a.similarity);
        },

        /**
         * Hybrid search: merge semantic results with token-match results.
         * Token-match items already have a 'score' field (0-1).
         * Semantic items get a bonus when both agree.
         *
         * @param {string} query             — raw user text
         * @param {Array}  tokenResults      — [{code, symptom: {name_vi,...}, score}, ...]
         * @param {string} [docType='k02_symptom']
         * @returns {Array} merged, sorted by combined score descending
         */
        async hybridSearch(query, tokenResults, docType = 'k02_symptom') {
            let semResults = [];

            try {
                if (this._modelReady) {
                    semResults = await this.search(query, docType, 20);
                }
            } catch (e) {
                console.warn('[SemanticSearch] hybridSearch semantic failed, using token-only:', e.message);
            }

            // Build lookup: source_id → similarity
            const semMap = {};
            for (const r of semResults) {
                semMap[r.src] = r.similarity;
            }

            // Boost token results that also appear in semantic results
            const merged = tokenResults.map(item => {
                const semScore = semMap[item.code] ?? 0;
                return {
                    ...item,
                    sem_score:     semScore,
                    combined_score: item.score * 0.6 + semScore * 0.4,
                };
            });

            // Add semantic-only results (not in token list) if similarity > 0.6
            const tokenCodes = new Set(tokenResults.map(r => r.code));
            for (const r of semResults) {
                if (!tokenCodes.has(r.src) && r.similarity >= 0.6) {
                    merged.push({
                        code:          r.src,
                        symptom:       { name_vi: r.preview },
                        score:         0,
                        sem_score:     r.similarity,
                        combined_score: r.similarity * 0.4,
                        source:        'semantic',
                    });
                }
            }

            merged.sort((a, b) => b.combined_score - a.combined_score);
            return merged;
        },

        /** True if binary index exists for a given doc_type (cached or just loaded) */
        hasIndex(docType) {
            return !!this._datasets[docType];
        },

        /** True after loadModel() succeeds */
        get isReady() {
            return this._modelReady;
        },
    };

    // Expose globally
    global.SemanticSearch = SemanticSearch;

})(window);
