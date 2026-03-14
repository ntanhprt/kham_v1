# Stack Công Nghệ Đề Xuất

## 1. Lựa Chọn Công Nghệ

### Tiêu Chí Lựa Chọn
- **Đơn giản**: Một người có thể làm được
- **Phổ biến**: Tài liệu nhiều, cộng đồng lớn
- **Tốc độ phát triển**: Nhanh prototype, dễ mở rộng
- **Chi phí**: Thấp nhất có thể giai đoạn đầu

---

## 2. Stack Đề Xuất (Fullstack JavaScript)

```
┌─────────────────────────────────────────────────────────┐
│  FRONTEND                                                │
│  Next.js 14+ (React) + TypeScript + Tailwind CSS        │
│  → Giao diện chat, hiển thị kết quả                    │
├─────────────────────────────────────────────────────────┤
│  BACKEND                                                 │
│  Next.js API Routes (hoặc Express.js riêng)             │
│  → Logic Engine, Session Management                     │
├─────────────────────────────────────────────────────────┤
│  DATABASE                                                │
│  PostgreSQL (Supabase hoặc Railway)                     │
│  + Redis (Upstash) cho cache/session                    │
│  → Knowledge Base, Decision Trees                       │
├─────────────────────────────────────────────────────────┤
│  DEPLOY                                                  │
│  Vercel (free tier đủ dùng giai đoạn đầu)              │
└─────────────────────────────────────────────────────────┘
```

---

## 3. Chi Tiết Từng Lớp

### 3.1 Frontend: Next.js + TypeScript

**Lý do chọn:**
- Fullstack trong một project (API + UI)
- File-based routing đơn giản
- Server-side rendering tốt cho SEO
- Ecosystem React khổng lồ

**Thư viện UI:**
```json
{
  "dependencies": {
    "next": "^14.0.0",
    "react": "^18.0.0",
    "typescript": "^5.0.0",
    "tailwindcss": "^3.0.0",
    "@radix-ui/react-*": "latest",     // UI components
    "lucide-react": "latest",           // Icons
    "framer-motion": "latest",          // Animation cho chat
    "react-markdown": "latest"          // Render kết quả markdown
  }
}
```

**Giao Diện Chat:**
- Kiểu chat bubble như ChatGPT/Zalo
- Bên trái: tin nhắn của "bác sĩ" (câu hỏi)
- Bên phải: câu trả lời của người dùng (dạng button/chip chọn)
- Thanh tiến trình phía trên (Bước X/Y)
- Kết quả hiển thị dạng card có icon, màu sắc rõ ràng

### 3.2 Backend: Next.js API Routes

**Cấu Trúc Thư Mục:**
```
src/
├── app/
│   ├── page.tsx              # Trang chủ
│   ├── exam/
│   │   └── page.tsx          # Trang khám bệnh
│   └── api/
│       ├── session/
│       │   ├── start/route.ts
│       │   └── [id]/route.ts
│       └── exam/
│           └── [sessionId]/
│               ├── question/route.ts
│               ├── answer/route.ts
│               └── result/route.ts
├── lib/
│   ├── db.ts                 # Kết nối PostgreSQL
│   ├── redis.ts              # Kết nối Redis
│   ├── engine/
│   │   ├── session.ts        # Session Service
│   │   ├── decision-tree.ts  # Decision Tree traversal
│   │   ├── pattern-match.ts  # Pattern matching
│   │   └── result-builder.ts # Tổng hợp kết quả
│   └── types.ts              # TypeScript types
└── components/
    ├── ChatWindow.tsx
    ├── QuestionCard.tsx
    ├── ResultCard.tsx
    └── ProgressBar.tsx
```

### 3.3 Database: PostgreSQL + Prisma ORM

**Lý do chọn Prisma:**
- Type-safe database queries
- Migration tự động
- Schema as code

**Cấu Hình:**
```prisma
// prisma/schema.prisma
datasource db {
  provider = "postgresql"
  url      = env("DATABASE_URL")
}

generator client {
  provider = "prisma-client-js"
}
```

**Hosting Options:**

| Service | Free Tier | Đề Xuất |
|---------|-----------|---------|
| Supabase | 500MB, 2 projects | ✓ Tốt nhất cho MVP |
| Railway | $5/tháng sau trial | ✓ Khi cần scale |
| Neon | 512MB free | ✓ Tốt cho dev |
| Self-hosted | VPS | Khi có traffic cao |

### 3.4 Session Cache: Redis (Upstash)

**Lý do:**
- Session khám bệnh cần real-time, không cần persist lâu dài
- TTL tự động (2 giờ không hoạt động → tự xóa)
- Upstash có free tier: 10,000 request/ngày

```typescript
// Cấu trúc session trong Redis
interface ExamSession {
  sessionId: string;
  phase: 'yhct_vong' | 'yhct_van_1' | 'yhct_van_2' | 'yhct_thiet' | 'yhhd';
  currentNodeId: string;
  chiefComplaint: string;
  patientInfo: { age?: number; gender?: 'M' | 'F' | 'O' };
  collectedSymptoms: Array<{
    symptomId: string;
    value: string;
    confidence: number;
  }>;
  answeredNodes: string[];
  createdAt: string;
}
```

---

## 4. Vai Trò AI Trong Hệ Thống

### 4.1 AI KHÔNG được dùng cho:
- Sinh câu hỏi (gây không nhất quán)
- Đưa ra chẩn đoán trực tiếp (không traceable)
- Kê đơn thuốc

### 4.2 AI CÓ THỂ dùng cho (tùy chọn):

**Option A: Không dùng AI** (Đề xuất cho giai đoạn đầu)
- Toàn bộ logic là rule-based từ CSDL
- 100% tái hiện
- Dễ debug, dễ kiểm tra

**Option B: AI hỗ trợ phân loại chief complaint**
- User nhập triệu chứng tự do bằng tiếng Việt
- AI (Claude API hoặc model nhỏ) phân loại → nhóm triệu chứng → chọn cây QĐ phù hợp
- Ví dụ: "đầu tôi nặng nề, mắt mờ" → nhóm "đau đầu + mắt"
- Chỉ dùng để NLP, không quyết định y tế

**Option C: AI tổng hợp kết quả** (Nâng cao)
- Pattern matching xong → đưa kết quả vào prompt Claude
- Claude trình bày kết quả dạng văn xuôi dễ đọc
- Vẫn đảm bảo nhất quán vì input (triệu chứng) nhất quán
- Cần test kỹ để đảm bảo output ổn định

**Khuyến nghị**: Bắt đầu với Option B (AI chỉ NLP), sau đó thêm Option C khi cần.

---

## 5. Chi Phí Vận Hành (Giai Đoạn MVP)

| Dịch Vụ | Gói | Chi Phí/Tháng |
|---------|-----|---------------|
| Vercel | Hobby (free) | $0 |
| Supabase | Free tier | $0 |
| Upstash Redis | Free tier | $0 |
| Domain .com | - | ~$10/năm |
| Claude API (nếu dùng) | Pay-per-use | ~$5-20 |
| **Tổng** | | **~$0-20/tháng** |

---

## 6. Môi Trường Phát Triển

```bash
# Yêu cầu cài đặt
node >= 18.0
npm >= 9.0
postgresql >= 15 (local dev)
redis (local: Docker hoặc Redis Desktop)

# Khởi tạo project
npx create-next-app@latest kham-tong-quan --typescript --tailwind --app
cd kham-tong-quan
npm install prisma @prisma/client ioredis

# Chạy development
npm run dev
```

---

## 7. Lựa Chọn Thay Thế (Nếu Không Biết JS)

Nếu bạn quen Python hơn:

| Layer | Thay Thế |
|-------|---------|
| Backend | FastAPI (Python) |
| Frontend | Streamlit (đơn giản) hoặc React riêng |
| Database | Vẫn dùng PostgreSQL |
| Deploy | Railway hoặc Render |

**Lưu ý**: Streamlit rất phù hợp cho prototype nhanh nhưng không đẹp bằng Next.js cho sản phẩm cuối.
