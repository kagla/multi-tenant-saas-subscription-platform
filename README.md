# Multi-Tenant SaaS Subscription Platform

## 이 프로젝트가 뭔가요?

**하나의 웹 애플리케이션으로 여러 고객사(테넌트)에게 각각 독립된 서비스를 제공하는 B2B SaaS 플랫폼**입니다.

Slack, Notion, Jira 같은 서비스를 떠올려 보세요. 회사마다 `mycompany.slack.com` 형태의 고유 주소를 받고, 그 안에서 자기 팀원들만 보이고, 자기 데이터만 접근 가능합니다. 이 프로젝트가 바로 그런 구조입니다.

### 핵심 개념

```
                        ┌─────────────────────────────────┐
                        │    tenant.gnuboard.net (메인)     │
                        │    슈퍼 관리자 대시보드             │
                        └──────────┬──────────────────────┘
                                   │
              ┌────────────────────┼────────────────────┐
              │                    │                    │
   ┌──────────▼──────────┐ ┌──────▼──────────┐ ┌──────▼──────────┐
   │ acme.tenant.         │ │ techcorp.tenant. │ │ newco.tenant.   │
   │ gnuboard.net         │ │ gnuboard.net     │ │ gnuboard.net    │
   │                      │ │                  │ │                 │
   │ - 자기 멤버만 보임    │ │ - 자기 멤버만    │ │ - 자기 멤버만   │
   │ - 자기 데이터만 접근  │ │ - 자기 데이터만  │ │ - 자기 데이터만 │
   │ - 독립적 결제/구독    │ │ - 독립적 결제    │ │ - 독립적 결제   │
   │ - 브랜딩 커스터마이징 │ │ - 브랜딩 커스텀  │ │ - 브랜딩 커스텀 │
   └──────────────────────┘ └──────────────────┘ └─────────────────┘
```

### 이런 걸 만들 수 있습니다

- 프로젝트 관리 도구 (각 회사마다 별도 워크스페이스)
- CRM/고객 관리 시스템 (대리점/지점별 독립 운영)
- 온라인 교육 플랫폼 (학원/학교별 분리)
- 예약 시스템 (매장/지점별 독립)
- 고객 지원 티켓 시스템 (기업 고객별 분리)

### 주요 기능 요약

| 기능 | 설명 |
|---|---|
| **멀티테넌시** | 서브도메인(`acme.tenant.gnuboard.net`)으로 테넌트 자동 식별, DB 쿼리 자동 격리 |
| **인증/권한** | 테넌트별 회원가입/로그인, 역할(Owner/Admin/Member), 이메일 초대 시스템 |
| **구독 결제** | Stripe 연동 3단계 플랜(Free/Pro/Enterprise), Checkout, 웹훅, 취소/재개/업그레이드 |
| **사용량 추적** | API 호출/저장소 실시간 미터링, 플랜별 제한, 80%/100% 자동 경고 알림 |
| **화이트라벨** | 테넌트별 로고, 브랜드 색상, 이메일 발신자, 커스텀 도메인 |
| **관리자 대시보드** | 전체 테넌트/사용자 관리, 수익 분석(MRR/ARR/Churn), 감사 로그, Impersonate |
| **보안** | Rate Limiting, 데이터 암호화, TenantScope 격리, CSRF/XSS 방지 |
| **성능** | DB 인덱스, 테넌트 캐싱, 큐 처리, Supervisor 설정 |
| **모니터링** | Laravel Telescope(개발), Sentry(프로덕션) |
| **테스트** | 40개 테스트, 86 assertions — 핵심 기능 모두 커버 |

---

## 라이브 데모

현재 아래 주소에서 실제 동작하는 데모를 확인할 수 있습니다.

### 접속 URL

| URL | 역할 |
|---|---|
| https://tenant.gnuboard.net/login | 슈퍼 관리자 로그인 페이지 |
| https://tenant.gnuboard.net/admin | 슈퍼 관리자 대시보드 (로그인 필요) |
| https://acme.tenant.gnuboard.net | Acme Corp 테넌트 (Free 플랜) |
| https://techcorp.tenant.gnuboard.net | TechCorp 테넌트 (Pro 플랜) |

### 테스트 계정

| 이메일 | 비밀번호 | 역할 | 어디서 로그인? |
|---|---|---|---|
| `admin@app.test` | `password` | 슈퍼 관리자 | https://tenant.gnuboard.net/login |
| `owner@acme.test` | `password` | Acme Corp Owner | https://acme.tenant.gnuboard.net/login |
| `owner@techcorp.test` | `password` | TechCorp Owner | https://techcorp.tenant.gnuboard.net/login |

> 슈퍼 관리자는 메인 도메인(`tenant.gnuboard.net`)에서 로그인합니다.
> 테넌트 사용자는 각자의 서브도메인(`acme.tenant.gnuboard.net`)에서 로그인합니다.
> 서로 다른 서브도메인의 계정은 서로 접근할 수 없습니다 (데이터 격리).

### 데모에서 해볼 수 있는 것들

1. **슈퍼 관리자로 로그인** → 전체 테넌트 목록 확인, 수익 분석, 테넌트 정지/활성화, Impersonate
2. **Acme Owner로 로그인** → 대시보드, 멤버 관리, 초대, 사용량 확인, 브랜딩 변경, 파일 업로드
3. **TechCorp Owner로 로그인** → Pro 플랜 기능 체험, API 엔드포인트 호출, 사용량 추적
4. **회원가입** → 테넌트 서브도메인의 `/register`에서 새 계정 생성 (해당 테넌트에 자동 연결)

---

## 프로젝트 규모

| 구분 | 수치 |
|---|---:|
| **총 코드 줄 수** | **8,065줄** |
| **총 파일 수** | **134개** |

### 카테고리별 상세

| 카테고리 | 파일 수 | 줄 수 | 비중 |
|---|---:|---:|---:|
| Blade Views | 24 | 3,030 | 37.6% |
| Controllers | 12 | 1,521 | 18.9% |
| Tests | 8 | 663 | 8.2% |
| Migrations | 10 | 516 | 6.4% |
| Models | 7 | 493 | 6.1% |
| Docs (README, DEPLOYMENT, .env.example) | 3 | 436 | 5.4% |
| Routes | 4 | 261 | 3.2% |
| Middleware | 5 | 230 | 2.9% |
| Services | 2 | 219 | 2.7% |
| 기타 (Mail, Observers, Notifications, Scopes 등) | 59 | 696 | 8.6% |

---

## 기술 스택

| 분류 | 기술 |
|---|---|
| Backend | Laravel 13.x, PHP 8.3+ |
| Frontend | Blade, Tailwind CSS, Alpine.js, Chart.js |
| Database | SQLite (개발) / MySQL, PostgreSQL (프로덕션) |
| 결제 | Stripe PHP SDK v20 |
| 큐 | Database 드라이버 (프로덕션: Redis 권장) |
| 모니터링 | Laravel Telescope (개발), Sentry (프로덕션) |
| 웹서버 | Apache 2.4 + Let's Encrypt SSL |

---

## 설치 방법

### 1. 사전 요구사항

- PHP 8.3 이상 (BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, XML 확장)
- Composer 2.x
- Node.js 18 이상 + npm
- SQLite (개발용) 또는 MySQL/PostgreSQL (프로덕션)

### 2. 프로젝트 클론 및 의존성 설치

```bash
git clone <repo-url>
cd multi-tenant-saas-subscription-platform

# PHP 의존성 설치
composer install

# 환경 설정 파일 복사
cp .env.example .env

# 애플리케이션 키 생성
php artisan key:generate
```

### 3. 데이터베이스 설정

```bash
# SQLite 사용 시 (기본값)
touch database/database.sqlite

# MySQL 사용 시 .env에서 아래 설정 변경:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=saas_platform
# DB_USERNAME=root
# DB_PASSWORD=

# 마이그레이션 실행 (테이블 생성)
php artisan migrate

# 시드 데이터 삽입 (테스트 계정 생성)
php artisan db:seed
```

### 4. 스토리지 및 프론트엔드 빌드

```bash
# public/storage → storage/app/public 심볼릭 링크
php artisan storage:link

# 프론트엔드 빌드 (Tailwind CSS, Vite)
npm install
npm run build
```

### 5. 로컬 도메인 설정

`/etc/hosts` 파일에 아래 내용을 추가합니다:

```
127.0.0.1 app.test acme.app.test techcorp.app.test
```

> `.env`의 `APP_BASE_DOMAIN=app.test`와 일치해야 합니다.

### 6. 개발 서버 실행

```bash
php artisan serve
# 또는 전체 개발 환경 (서버 + 큐 + 로그 + Vite):
composer dev
```

이후 브라우저에서 접속합니다:

| URL | 설명 |
|---|---|
| `http://app.test:8000/admin` | 슈퍼 관리자 대시보드 |
| `http://acme.app.test:8000` | Acme Corp 테넌트 (Free 플랜) |
| `http://techcorp.app.test:8000` | TechCorp 테넌트 (Pro 플랜) |

### 7. 프로덕션 도메인으로 전환할 때

`.env` 파일에서 도메인 관련 값을 변경합니다:

```env
APP_URL=https://yourdomain.com
APP_BASE_DOMAIN=yourdomain.com
SESSION_DOMAIN=.yourdomain.com
```

Apache/Nginx에서 `*.yourdomain.com` 와일드카드 가상 호스트와 SSL 인증서를 설정합니다. 상세 내용은 [DEPLOYMENT.md](DEPLOYMENT.md)를 참조하세요.

---

## 사용법

### 테넌트 사용자 (Owner/Admin/Member)

#### 회원가입 및 로그인

1. 테넌트 서브도메인으로 접속합니다 (예: `acme.tenant.gnuboard.net`).
2. `/register` 페이지에서 이름, 이메일, 비밀번호를 입력하여 가입합니다.
3. 가입 시 자동으로 해당 테넌트에 `member` 역할로 연결됩니다.
4. 이후 `/login` 페이지에서 로그인합니다.

#### 대시보드

- 로그인 후 `/dashboard`에서 테넌트 현황을 확인합니다.
- 현재 플랜, 사용자 수, API/저장소 사용량, 기능 목록을 한눈에 볼 수 있습니다.

#### 멤버 관리 (Owner/Admin)

1. 상단 메뉴 **Members** 클릭
2. 현재 테넌트의 모든 멤버 목록을 확인합니다 (이름, 이메일, 역할, 가입일).
3. **역할 변경**: 드롭다운에서 Admin/Member를 선택하면 즉시 변경됩니다.
4. **멤버 제거**: Remove 버튼 클릭 → Confirm으로 멤버를 제거합니다.
5. Owner의 역할은 변경/삭제할 수 없습니다.

#### 멤버 초대 (Owner/Admin)

1. Members 페이지에서 **Invite Member** 버튼 클릭
2. 이메일 주소와 역할(Admin/Member)을 선택합니다.
3. **Send Invitation** 클릭 → 초대 이메일이 발송됩니다 (큐 처리).
4. 수신자는 이메일의 **Accept Invitation** 링크를 클릭합니다.
5. 기존 계정이 있으면 자동 연결, 없으면 이름/비밀번호를 입력하여 가입합니다.
6. 초대는 7일 후 만료됩니다.

#### 사용량 대시보드

1. 상단 메뉴 **Usage** 클릭
2. 실시간 사용량 미터 3개를 확인합니다:
   - **API Calls (today)**: 오늘 사용한 API 호출 / 일일 제한
   - **Storage**: 총 저장소 사용량 / 저장소 제한
   - **Team Members**: 현재 멤버 수 / 멤버 제한
3. **API Calls 바 차트**: 최근 30일간 일별 API 호출 추이 (Chart.js)
4. **Storage 도넛 차트**: 사용 vs 남은 용량 시각화
5. 80% 도달 시 Owner에게 경고 이메일이, 100% 도달 시 긴급 알림이 발송됩니다.

#### 파일 관리

1. `/files` 페이지에서 파일을 업로드/삭제할 수 있습니다.
2. 업로드 시 저장소 사용량이 자동으로 추적됩니다.
3. 플랜 저장소 제한을 초과하면 업로드가 거부됩니다.
4. 파일 삭제 시 사용량이 자동으로 차감됩니다.

#### 구독 및 결제 (Owner)

1. 상단 메뉴 **Billing** 클릭
2. **Subscription** 페이지에서 현재 플랜, 사용량, 기능 목록을 확인합니다.
3. **Change Plan** 버튼 클릭 → 프라이싱 페이지로 이동합니다.

##### 플랜 업그레이드

1. Plans 페이지에서 원하는 플랜의 **Start Trial** 또는 **Upgrade** 버튼 클릭
2. Stripe Checkout 페이지로 리다이렉트됩니다.
3. 테스트 카드 번호: `4242 4242 4242 4242` (만료일: 아무 미래 날짜, CVC: 아무 숫자)
4. 결제 완료 후 자동으로 플랜이 업그레이드됩니다.

##### 구독 취소/재개

- Billing 페이지에서 **Cancel Subscription** → 현재 결제 기간 종료 후 Free로 전환
- 취소 후 종료 전까지 **Resume Subscription** 버튼으로 재개 가능

#### 브랜딩 설정 (Owner)

1. 상단 메뉴 **Branding** 클릭
2. **로고**: JPG/PNG/SVG 파일 업로드 (최대 2MB). 네비게이션 바와 이메일에 표시됩니다.
3. **브랜드 색상**: 컬러피커로 Primary/Secondary 색상 선택. 우측에서 네비게이션, 버튼, 이메일이 실시간으로 미리보기됩니다.
4. **이메일 설정**: From Name/Address를 지정하면 초대/알림 이메일의 발신자가 변경됩니다.
5. **커스텀 도메인**: 원하는 도메인을 입력하면 DNS CNAME 설정 안내가 표시됩니다.
6. **Save Branding** 버튼으로 저장합니다.

#### 조직 설정 (Owner)

1. 상단 메뉴 **Settings** 클릭
2. 조직 이름과 서브도메인을 변경할 수 있습니다.
3. 현재 플랜 정보가 표시됩니다.

#### API 사용 (Pro/Enterprise 플랜만)

Free 플랜에서는 API 접근이 차단됩니다. Pro 이상 플랜에서 사용 가능합니다:

```bash
# API 상태 확인
GET https://techcorp.tenant.gnuboard.net/api/v1/status

# 사용량 조회
GET https://techcorp.tenant.gnuboard.net/api/v1/usage
```

응답 헤더에 Rate Limit 정보가 포함됩니다:
```
X-RateLimit-Limit: 10000
X-RateLimit-Remaining: 9993
```

일일 제한 초과 시 `429 Too Many Requests` 응답이 반환됩니다.

---

### 슈퍼 관리자

플랫폼 전체를 관리하는 관리자 기능입니다. 메인 도메인에서만 접근 가능합니다.

#### 로그인

1. `https://tenant.gnuboard.net/login`으로 접속합니다.
2. 관리자 계정 (`admin@app.test` / `password`)으로 로그인합니다.
3. 자동으로 `/admin` 대시보드로 이동합니다.

#### 관리자 대시보드

- **통계 카드 4개**: 전체 테넌트 수, 총 사용자 수, MRR (월간 반복 수익), 플랜별 분포
- **신규 가입 추이 차트**: 최근 30일간 일별 신규 테넌트 가입 (Line Chart)
- **플랜 분포 차트**: Free/Pro/Enterprise 비율 (Doughnut Chart)
- **DAU/MAU**: 일간/월간 활성 사용자 수
- **최근 활동 로그**: 테넌트 생성/수정, 사용자 변경 등 감사 이벤트 목록

#### 테넌트 관리

1. 좌측 사이드바 **Tenants** 클릭
2. 전체 테넌트 목록 (이름, 서브도메인, 플랜, 멤버 수, 상태, 생성일)
3. **검색**: 이름 또는 서브도메인으로 검색
4. **필터**: 플랜별 (Free/Pro/Enterprise), 상태별 (Active/Suspended)
5. **View**: 테넌트 상세 페이지 — 멤버 목록, 구독 히스토리, API/저장소 사용량, 활동 로그
6. **Impersonate**: 해당 테넌트의 Owner 계정으로 자동 전환 (디버깅/고객 지원용)
7. **Suspend**: 테넌트 비활성화 (접속 차단)
8. **Activate**: 비활성 테넌트 다시 활성화

#### Impersonate (가장 로그인)

고객의 문제를 직접 확인하거나 디버깅할 때 사용합니다.

1. 테넌트 목록에서 **Impersonate** 클릭
2. 해당 테넌트의 Owner 계정으로 자동 전환됩니다.
3. 테넌트의 서브도메인 대시보드로 리다이렉트됩니다.
4. 사이드바 하단의 **Stop Impersonating** 클릭 → 원래 관리자로 즉시 복귀합니다.

#### 사용자 관리

1. 좌측 사이드바 **Users** 클릭
2. 모든 테넌트의 사용자를 한 화면에서 확인합니다.
3. 이름/이메일 검색, 역할별 필터링
4. **View**: 사용자 상세 (소속 테넌트, 역할, 가입일, 활동 로그)

#### 수익 분석

1. 좌측 사이드바 **Revenue** 클릭
2. **MRR** (Monthly Recurring Revenue): 월간 반복 수익
3. **ARR** (Annual Recurring Revenue): 연간 반복 수익
4. **Active Subscriptions**: 현재 활성 구독 수
5. **Churn Rate**: 최근 30일 이탈률
6. **월별 수익 차트**: 최근 12개월 수익 추이 (Bar Chart)
7. **플랜별 수익 비율**: 원형 차트 (Doughnut Chart)
8. **플랜 분석 테이블**: 플랜별 테넌트 수, 단가, 총 수익

---

## 플랜 비교

| 기능 | Free | Pro ($29/월) | Enterprise ($99/월) |
|---|:---:|:---:|:---:|
| API 호출/일 | 100 | 10,000 | 무제한 |
| 저장소 | 100 MB | 10 GB | 무제한 |
| 팀 멤버 | 3명 | 20명 | 무제한 |
| 기본 대시보드 | O | O | O |
| 커뮤니티 지원 | O | O | O |
| 고급 분석 | - | O | O |
| 우선 지원 | - | O | O |
| 커스텀 도메인 | - | O | O |
| API 접근 | - | O | O |
| SSO | - | - | O |
| 감사 로그 | - | - | O |
| 전담 지원 | - | - | O |
| 무료 체험 | - | 14일 | 14일 |

---

## Stripe 설정 가이드

### 1. Stripe 계정 및 API 키

1. [Stripe Dashboard](https://dashboard.stripe.com)에서 계정을 생성합니다.
2. **Developers > API Keys**에서 테스트 키를 복사합니다.
3. `.env` 파일에 설정합니다:

```env
STRIPE_KEY=pk_test_your_publishable_key
STRIPE_SECRET=sk_test_your_secret_key
```

### 2. 상품 및 가격 생성

1. **Products** 메뉴에서 2개의 상품을 생성합니다:

| 상품명 | 가격 | Billing |
|---|---|---|
| Pro Plan | $29.00 | Monthly recurring |
| Enterprise Plan | $99.00 | Monthly recurring |

2. 각 가격의 Price ID를 `.env`에 설정합니다:

```env
STRIPE_PRICE_PRO=price_xxxxxxxxxxxxxxxx
STRIPE_PRICE_ENTERPRISE=price_xxxxxxxxxxxxxxxx
```

### 3. Webhook 설정

1. **Developers > Webhooks**에서 엔드포인트를 추가합니다.
2. URL: `https://tenant.gnuboard.net/webhook/stripe`
3. 이벤트 선택:
   - `checkout.session.completed`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
4. Signing Secret을 `.env`에 설정합니다:

```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxx
```

### 4. 테스트

Stripe 테스트 카드: `4242 4242 4242 4242` (만료일: 아무 미래 날짜, CVC: 아무 3자리)

---

## 테스트

```bash
# 전체 테스트 실행
php artisan test

# 특정 테스트만 실행
php artisan test --filter=TenantModelTest
php artisan test --filter=UsageTrackerTest
php artisan test --filter=SuperAdminTest
```

### 테스트 목록 (40개 테스트, 86 assertions)

| 테스트 파일 | 테스트 수 | 범위 |
|---|---:|---|
| `TenantModelTest` | 10 | 플랜 체크, 기능 확인, 제한값, trial, grace period |
| `UsageTrackerTest` | 8 | track, canUse, remaining, percent, unlimited, chart |
| `TenantRegistrationTest` | 5 | 로그인 페이지, 회원가입, 비활성 403, 미존재 404 |
| `MemberManagementTest` | 4 | 멤버 목록, 역할 변경, owner 보호, 자기삭제 방지 |
| `UsageLimitTest` | 4 | Rate limit 헤더, Free 차단, 429 응답, 대시보드 |
| `SuperAdminTest` | 8 | 대시보드, 비인가 403, 테넌트 CRUD, 수익 분석 |
| `ExampleTest` | 1 | 기본 테스트 |

---

## 큐 워커

초대 이메일, 사용량 경고 알림 등은 큐를 통해 비동기 처리됩니다.

### 개발 환경

```bash
php artisan queue:work --sleep=3 --tries=3
```

### 프로덕션 환경 (Supervisor)

```bash
sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/saas-platform.conf
# 설정 파일 내 경로를 실제 프로젝트 경로로 수정
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start saas-queue-worker:*
```

---

## 환경 변수

| 변수 | 설명 | 예시 |
|---|---|---|
| `APP_BASE_DOMAIN` | 플랫폼 기본 도메인 | `tenant.gnuboard.net` |
| `SESSION_DOMAIN` | 세션 쿠키 도메인 (서브도메인 공유) | `.tenant.gnuboard.net` |
| `STRIPE_KEY` | Stripe Publishable Key | `pk_test_...` |
| `STRIPE_SECRET` | Stripe Secret Key | `sk_test_...` |
| `STRIPE_WEBHOOK_SECRET` | Stripe Webhook Signing Secret | `whsec_...` |
| `STRIPE_PRICE_PRO` | Pro 플랜 Stripe Price ID | `price_...` |
| `STRIPE_PRICE_ENTERPRISE` | Enterprise 플랜 Stripe Price ID | `price_...` |
| `SENTRY_LARAVEL_DSN` | Sentry 에러 리포팅 DSN | `https://...@sentry.io/...` |
| `TELESCOPE_ENABLED` | Telescope 활성화 여부 | `true` / `false` |

전체 변수 목록은 `.env.example` 파일을 참조하세요.

---

## 프로젝트 구조

```
app/
  Console/Commands/          # ResetDailyUsage (사용량 정리 스케줄러)
  Http/
    Controllers/
      Admin/                 # DashboardController, TenantController, RevenueController, UserController
      Auth/                  # RegisteredUserController, AuthenticatedSessionController
      BrandingController     # 로고, 색상, 이메일, 커스텀 도메인
      FileController         # 파일 업로드/삭제 + 저장소 추적
      InvitationController   # 멤버 초대 생성/수락
      MemberController       # 멤버 목록, 역할 변경, 제거
      SubscriptionController # Stripe 결제, 구독 관리
      TenantController       # 조직 설정
      UsageController        # 사용량 대시보드
      WebhookController      # Stripe Webhook 이벤트 처리
    Middleware/
      CheckSubscription      # 플랜 기반 접근 제어
      IdentifyTenant         # 서브도메인/커스텀도메인 → 테넌트 식별
      SuperAdminMiddleware    # 슈퍼 관리자 체크
      TenantRateLimiter      # 컨텍스트별 Rate Limiting
      TrackApiUsage          # API 호출 추적 + 429 응답
  Mail/
    TenantMail               # 테넌트 브랜딩 적용 베이스 Mailable
    TenantInvitation         # 초대 이메일
  Models/
    AuditLog                 # 감사 로그
    Invitation               # 초대
    Subscription             # Stripe 구독 (stripe_id 암호화)
    Tenant                   # 테넌트 (플랜, 브랜딩, 구독 메서드)
    UsageRecord              # 사용량 기록
    User                     # 사용자 (TenantScope, 역할, 슈퍼관리자)
  Notifications/
    UsageLimitWarning        # 80%/100% 사용량 경고 (메일 + DB)
  Observers/
    TenantObserver           # 테넌트 변경 감사 로그 + 캐시 무효화
    UserObserver             # 사용자 변경 감사 로그
  Policies/
    TenantPolicy             # update (owner), manageMembers (owner/admin)
  Scopes/
    TenantScope              # 쿼리에 tenant_id 자동 필터링
  Services/
    TenantCache              # 테넌트 캐싱 (subdomain, custom_domain)
    UsageTracker             # 사용량 추적/조회/알림 통합 서비스
  View/Components/
    TenantStyle              # CSS 변수 동적 주입 컴포넌트

config/
  plans.php                  # 플랜 정의 (이름, 가격, 기능, 제한)

routes/
  web.php                    # 메인 도메인 + 커스텀 도메인 폴백
  tenant.php                 # 테넌트 라우트 (서브도메인/커스텀 도메인 공유)
  admin.php                  # 슈퍼 관리자 라우트
  console.php                # 스케줄러 (usage:reset-daily)

resources/views/
  admin/                     # 슈퍼 관리자: 대시보드, 테넌트/사용자/수익 관리
  tenant/                    # 테넌트: 대시보드, 멤버, 구독, 사용량, 브랜딩, 파일
  components/                # TenantStyle CSS 변수
  emails/                    # 초대 이메일 (테넌트 브랜딩 적용)
  errors/                    # 할당량 초과 안내 페이지
  layouts/                   # Breeze 레이아웃 + 네비게이션 (로고, 브랜드 색상)

tests/
  Unit/                      # TenantModelTest, UsageTrackerTest
  Feature/                   # TenantRegistration, MemberManagement, UsageLimit, SuperAdmin

deploy/
  supervisor.conf            # 큐 워커 + 스케줄러 Supervisor 설정
```

---

## 라이선스

MIT License
