# GAR (Government Auto Rent) - 기획서

> 이 문서는 제품/시장/로드맵을 빠르게 훑는 개요 문서입니다. 구현 계약은 [README.md](README.md), [DOMAIN_MODEL.md](DOMAIN_MODEL.md), [G2B_API_SPEC.md](G2B_API_SPEC.md), [ANALYSIS_SCHEMA.md](ANALYSIS_SCHEMA.md)를 기준으로 봅니다.

## 🎯 서비스 개요

**GAR**은 나라장터(G2B) 차량 렌트/임차 입찰공고를 자동 수집·분석하여, 자동차대여(렌트) 사업자에게 맞춤형 입찰 정보를 제공하는 SaaS 플랫폼입니다.

### 핵심 가치
- 렌트사가 매일 나라장터를 뒤질 필요 없음
- "이 공고가 뭘 원하는지" AI가 정리해줌 (차량/자격/서류/마감)
- 내 소재지·프로필 기준 자격 체크 + 적격심사 시뮬레이션
- 새 공고 알림

### 타겟 고객
- 전국 자동차대여사업자 (업종코드 1457)
- **1차**: 5~50대 규모 중소 렌트사 사장님 — 나라장터 입찰 시장 자체를 모르는 경우 많음
- 2차: 대형 렌트사 영업팀 → 효율화 도구

### 온보딩 전략
- 가입 시 **소재지만** 필수 입력 → 즉시 지역 매칭 공고 노출
- 점진적 온보딩: 프로필 완성할수록 자격 체크/시뮬레이션 정확도 향상
- 업종코드 → 차종/대수 → 기업규모 → 신용등급 순으로 유도

---

## 📊 시장 분석

### 공고 규모 (2026년 3월 기준, 월간)
| 카테고리 | 월간 건수 | 비고 |
|----------|-----------|------|
| 차량임차 (용역) | ~423건 | 통학/체험 포함 |
| 차량렌트 (용역) | ~5건 | 순수 렌트 키워드 |
| 장기렌트 (용역) | ~1건 | |
| 기타공고 | ~3건 | |
| 물품공고 | ~3건 | |
| **순수 렌트 타겟** | **~95건** | 분류코드 78111808 기준 |
| **확장 타겟** | **~161건** | 운동부 차량 등 포함 |

### 금액 규모
- 소형: 2,000만~5,000만원 (업무용 1대, 36개월)
- 중형: 5,000만~2억원 (공단/공사 다수 차량)
- 대형: 2억~20억원 (국세청 13억, 한국자산관리공사 20억)

### 경쟁 분석
- **직접 경쟁**: 없음 (차량 렌트 특화 입찰 서비스 부재)
- **간접 경쟁**: 비드프로, 나라장터 직접 검색, 조달청 알리미
- **차별점**: 차량 렌트 특화 + 첨부파일 AI 분석 + 견적서 자동 생성

---

## 🏗️ 시스템 아키텍처

### 기술 스택
- **Backend**: Laravel 12 + PHP 8.4
- **Frontend**: Vue 3 + Inertia.js + Tailwind CSS 4
- **Database**: PostgreSQL (+ pgvector for 문서 유사도)
- **Queue**: Redis + Laravel Queue (공고 수집/분석 비동기)
- **문서 파싱**: hwpx-ts (HWPX), olefile (HWP), pdf-parse (PDF)
- **AI**: Laravel AI SDK (공고 요약, 견적서 생성)
- **알림**: 카카오 알림톡, 텔레그램, 이메일
- **배포**: Laravel Cloud

### 시스템 흐름도
```
┌─────────────────────────────────────────────────────┐
│                    GAR 시스템                         │
├─────────────────────────────────────────────────────┤
│                                                      │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐       │
│  │ Collector │───▶│ Analyzer │───▶│ Notifier │       │
│  │ (수집기)  │    │ (분석기) │    │ (알림기) │       │
│  └──────────┘    └──────────┘    └──────────┘       │
│       │               │               │              │
│       ▼               ▼               ▼              │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐       │
│  │ G2B API  │    │ Doc      │    │ 카카오    │       │
│  │ 나라장터  │    │ Parser   │    │ 텔레그램  │       │
│  │          │    │ (HWP/PDF)│    │ 이메일    │       │
│  └──────────┘    └──────────┘    └──────────┘       │
│                                                      │
│  ┌──────────────────────────────────────────┐       │
│  │              Dashboard (SPA)              │       │
│  │  공고목록 | 필터 | 분석카드 | 견적서생성  │       │
│  └──────────────────────────────────────────┘       │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

## 🔄 핵심 로직

### 1. 공고 수집 (Collector)

**스케줄**: 매 1시간 (평일 08:00~18:00)

```
수집 파이프라인:
1. 나라장터 API 호출 (3개 카테고리)
   ├── 용역: getBidPblancListInfoServcPPSSrch
   ├── 물품: getBidPblancListInfoThngPPSSrch
   └── 기타: getBidPblancListInfoEtcPPSSrch

2. 파라미터
   ├── inqryDiv=1 (등록일시 기준)
   ├── inqryBgnDt: 마지막 수집 시점
   ├── inqryEndDt: 현재
   └── numOfRows=100, 페이지네이션

3. 1차 필터 (분류코드)
   ├── ✅ 78111808 (자동차렌트서비스) → 무조건 포함
   ├── ✅ 73169001 (운송장비임대서비스) → 무조건 포함
   ├── ⚠️ 78111899 (도로여객운송서비스) → 2차 필터 적용
   └── ⚠️ 기타 → 키워드 매칭 시 포함

4. 2차 필터 (키워드)
   ├── 제외: 통학, 수학여행, 현장체험, 수련활동, 견학, 소풍, 야영, 캠프
   ├── 포함 보조: 렌트, 임차, 임대, 리스
   └── 78111899 추가 제외: 수송, 수련, 체험활동

5. 중복 체크 (bidNtceNo 기준)
6. DB 저장
```

### 2. 문서 분석 (Analyzer)

**트리거**: 새 공고 저장 시 Queue Job 발행

```
분석 파이프라인:
1. 첨부파일 다운로드
   ├── ntceSpecDocUrl1~10
   ├── stdNtceDocUrl
   └── Referer: https://www.g2b.go.kr/ 헤더 필수

2. 파일 타입별 텍스트 추출
   ├── .hwpx → hwpx-ts (TypeScript, 완벽 파싱)
   ├── .hwp  → olefile + zlib (Python, 텍스트만)
   └── .pdf  → pdf-parse (텍스트 추출)

3. AI 분석 (Laravel AI SDK)
   프롬프트: 추출된 텍스트 → 구조화된 JSON

   ★ 견적 핵심 변수 (반드시 파싱):
   ├── 차량 상세
   │   ├── 제조사 (현대/기아/제네시스/BMW/벤츠 등)
   │   ├── 모델명 (그랜저/쏘나타/EV9/아이오닉5 등)
   │   ├── 트림/등급 (익스클루시브/프레스티지/롱레인지 등)
   │   ├── 연료 (가솔린/디젤/하이브리드/전기)
   │   ├── 대수
   │   ├── 연식 조건 (신차/2024년식 이후 등)
   │   ├── 인승 (5인승/7인승/11인승/15인승)
   │   ├── 색상 (외장/내장)
   │   └── 추가옵션 (HUD/썬팅/블랙박스/스노우타이어/네비 등)
   │
   ├── 계약 핵심 조건
   │   ├── 계약기간 (개월 수)
   │   ├── 선납금 (% 또는 금액, 0원 여부)
   │   ├── 보증금 (면제 여부)
   │   ├── 연간주행거리 (제한 없음 / km 단위)
   │   ├── 잔존가치 (% 또는 반납 조건)
   │   ├── 개시대여료
   │   └── 임차료 지급방식 (월 후불/선불)
   │
   ├── 자격 요건: 업종, 지역제한, 기업규모
   ├── 보험 조건
   │   ├── 대인1/대인2 (무한 여부)
   │   ├── 대물 (한도액)
   │   ├── 자차 (면책금)
   │   ├── 자기신체/자동차상해 (한도)
   │   ├── 무보험차상해
   │   ├── 운전자 조건 (만26세 이상 등)
   │   ├── 운전자 범위 (임직원 한정/누구나)
   │   └── 긴급출동 (횟수)
   │
   ├── 평가 방식: 적격심사/최저가/협상, 낙찰하한율
   ├── 입찰 일정: 마감일, 개찰일
   └── 특수 조건: 대차 의무, 정비 주기, 스노우타이어/체인, 3회 고장 시 교체 등

4. 분석 결과 DB 저장 → 입찰 카드 생성
```

### 3. 고객 매칭 (Matcher)

```
매칭 로직:
1. 고객 프로필
   ├── 보유 차종/대수
   ├── 영업 지역 (본사/지점 소재지)
   ├── 기업 규모 (소기업/중기업/대기업)
   ├── 선호 계약 규모 (min~max 금액)
   └── 선호 계약 기간

2. 공고별 매칭 스코어 (0~100)
   ├── 지역 매칭 (30점): 본사/지점이 입찰 지역제한 충족
   ├── 차종 매칭 (25점): 보유 차종이 요구 차종과 일치
   ├── 규모 매칭 (20점): 예산이 선호 범위 내
   ├── 자격 매칭 (15점): 기업규모 등 자격 충족
   └── 기간 매칭 (10점): 선호 계약기간과 유사

3. 매칭 스코어 70점 이상 → 알림 발송
```

### 4. 알림 (Notifier)

```
알림 채널:
├── 카카오 알림톡 (주력)
├── 텔레그램 봇
├── 이메일 (일일 다이제스트)
└── 웹 푸시

알림 내용:
┌─────────────────────────────┐
│ 🚗 새 입찰공고 알림         │
│                             │
│ 국세청 체납관리단 차량 임차  │
│ 💰 13.16억원 | 📅 3/25 마감 │
│ 🚙 업무용 차량 다수         │
│ 📊 매칭 스코어: 92점        │
│                             │
│ [상세보기] [견적서 생성]     │
└─────────────────────────────┘
```

### 5. 적격심사 시뮬레이션

```
시뮬레이션 (프로필 기반):
1. 프로필 정보
   ├── 이행실적 (과거 납품 금액)
   ├── 경영상태 (신용등급)
   ├── 사후관리 (정비네트워크, 대차 가능 여부)
   └── 신인도 (ISO, 여성기업, 부정당 이력 등)

2. 공고별 배점 기준 + 프로필 대입
   ├── 이행실적: 추정가격 대비 실적 → 등급 → 점수
   ├── 경영상태: 신용등급 → 별표10 매핑 → 점수
   ├── 사후관리: A/S 체계 → 점수
   └── 신인도: 가감점 합산

3. 결과 표시
   ├── 예상 종합평점 (입찰가격 제외)
   ├── "85점 넘으려면 투찰률 몇 % 이상 필요"
   └── 부족한 항목 안내
```

---

## 💰 수익 모델

### 요금제
| 플랜 | 월 요금 | 기능 |
|------|---------|------|
| **Free** | 0원 | 공고 목록 열람 (3일 지연), 일일 5건 |
| **Basic** | 49,000원 | 실시간 알림, 무제한 열람, 기본 필터 |
| **Pro** | 99,000원 | AI 분석 카드, 적격심사 시뮬레이션, 매칭 알림 |

### 추가 수익
- 렌트사 광고 (공고 상세 페이지)
- 데이터 분석 리포트 (월간 시장 동향)

---

## 📱 화면 구성

### 1. 대시보드
- 오늘의 새 공고 수
- 마감 임박 공고 (D-3 이내)
- 매칭 스코어 상위 공고
- 월간 트렌드 차트

### 2. 공고 목록
- 필터: 지역, 차종, 예산, 마감일, 분류코드
- 정렬: 최신순, 마감순, 금액순, 매칭순
- 리스트/카드 뷰 전환

### 3. 공고 상세
- 입찰 요약 카드 (AI 분석)
- 원본 첨부파일 다운로드
- 자격 요건 체크리스트 (내 프로필 대비)
- 견적서 생성 버튼

### 4. 내 프로필
- 회사 정보 (소재지 필수, 나머지 점진적 온보딩)
- 보유 차종/대수
- 이행실적/신용등급/인증 (시뮬레이션용)
- 알림 설정 (채널, 주기, 필터)

### 5. 적격심사 시뮬레이션
- 공고별 예상 점수
- 부족 항목 안내
- 프로필 완성도 유도

---

## 🗓️ 로드맵

### Phase 1 — MVP (2주)
- [ ] Laravel 프로젝트 셋업
- [ ] 나라장터 API 연동 (수집기)
- [ ] 분류코드 + 키워드 필터링
- [ ] 공고 목록/상세 페이지
- [ ] 기본 필터 (지역, 차종, 예산)
- [ ] 텔레그램 알림

### Phase 2 — AI 분석 (1주)
- [ ] 첨부파일 다운로드 + 파싱 (HWP/HWPX/PDF)
- [ ] AI 분석 → 입찰 카드 생성
- [ ] 자격 요건 자동 체크

### Phase 3 — 매칭 + 알림 (1주)
- [ ] 고객 프로필 시스템
- [ ] 매칭 스코어 엔진
- [ ] 카카오 알림톡 연동
- [ ] 이메일 다이제스트

### Phase 4 — 시뮬레이션 (1주)
- [ ] 적격심사 시뮬레이션 엔진
- [ ] 프로필 기반 점수 계산
- [ ] 부족 항목 안내 UI

### Phase 5 — 고도화 (ongoing)
- [ ] 낙찰 결과 추적 (개찰 API)
- [ ] 시장 분석 리포트
- [ ] 모바일 앱 (NativePHP)

---

## 📐 DB 스키마 (핵심)

### bids (공고)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| bid_ntce_no | varchar | 공고번호 (unique) |
| bid_ntce_ord | varchar | 공고차수 |
| title | varchar | 공고명 |
| institution | varchar | 발주기관 |
| category | enum | service/goods/etc |
| classification_code | varchar | 분류코드 (78111808 등) |
| budget | bigint | 예산금액 |
| bid_open_dt | datetime | 개찰일시 |
| bid_close_dt | datetime | 입찰마감일시 |
| region | varchar | 지역제한 |
| success_method | varchar | 낙찰방식 |
| raw_data | json | API 원본 데이터 |
| status | enum | open/closed/awarded |
| created_at | timestamp | |

### bid_documents (첨부파일)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| bid_id | FK | bids.id |
| seq | int | 파일 순서 (1~10) |
| filename | varchar | 파일명 |
| url | text | 다운로드 URL |
| file_type | enum | hwp/hwpx/pdf/etc |
| file_path | varchar | 로컬 저장 경로 |
| extracted_text | text | 추출된 텍스트 |

### bid_analyses (AI 분석 요약)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| bid_id | FK | bids.id |
| special_conditions | json | 특수조건 (대차의무, 스노우타이어 등) |
| summary | text | AI 요약 |

### bid_qualifications (자격요건 + 적격심사 정량서류)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| bid_id | FK | bids.id |
| **입찰 자격** | | |
| biz_registration | varchar | 업종코드 (1457 자동차대여사업 등) |
| region_limit | varchar | 지역제한 (null=없음, 충남/경남 등) |
| company_size_limit | enum | null/micro/small/medium (소기업/소상공인 제한) |
| joint_contract | boolean | 공동도급 허용 여부 |
| subcontract | boolean | 하도급 허용 여부 |
| **낙찰 방식** | | |
| evaluation_method | enum | competitive/negotiation/estimate (일반경쟁/협상/수의) |
| evaluation_standard | varchar | 적용기준 (별표8 임대차/경기도 별표1-6 등) |
| success_threshold | decimal | 낙찰하한율 (%) |
| passing_score | int | 종합평점 합격점 (85점/88점/95점) |
| **적격심사 배점 (별표8 임대차 기준)** | | |
| score_performance | int | 이행실적 배점한도 (기본 10점) |
| score_financial | int | 경영상태 배점한도 (기본 20점) |
| score_afterservice | int | 사후관리(A/S) 배점한도 (기본 15점) |
| score_price | int | 입찰가격 배점한도 (기본 55점) |
| score_credibility_plus | decimal | 신인도 가점 한도 (+4.25) |
| score_credibility_minus | decimal | 신인도 감점 한도 (-5.0) |
| score_disqualify | int | 결격사유 감점 (-20) |
| score_adjusted | boolean | 배점 조정 여부 (±20% 가능) |
| **제출서류** | | |
| required_docs | json | 필수 제출서류 목록 |

### bid_performance_requirements (이행실적 요구사항)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| bid_id | FK | bids.id |
| **실적 기준** | | |
| performance_type | enum | same/similar/any (동등이상/유사/무관) |
| performance_scope | text | 실적 범위 설명 (차량 임대차 등) |
| performance_years | int | 실적 인정 기간 (5년, 소기업 7년) |
| min_amount | bigint | 최소 실적 금액 (원, null=없음) |
| min_count | int | 최소 실적 건수 (null=없음) |
| min_quantity | int | 최소 실적 규모-대수 (null=없음) |
| **이행실적 등급별 배점** | | |
| grade_criteria | json | 등급별 기준+배점 (아래 참조) |

> **이행실적 등급 (별표8 기준)**
> - A등급 (10점): 추정가격 100% 이상 실적
> - B등급 (8점): 추정가격 70~100% 실적
> - C등급 (6점): 추정가격 50~70% 실적
> - D등급 (4점): 추정가격 30~50% 실적
> - E등급 (2점): 추정가격 30% 미만 실적
> - F등급 (0점): 실적 없음
> ※ 소기업/소상공인: 실적 인정기간 7년, 최소 2점 보장

### bid_financial_requirements (경영상태 요구사항)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| bid_id | FK | bids.id |
| **신용평가 기준 (별표10)** | | |
| eval_method | enum | credit_rating/financial_statement (신용등급/재무제표) |
| min_credit_grade | varchar | 최소 신용등급 (BBB- 등, null=없음) |
| **재무제표 기준 (신용등급 없을 때)** | | |
| max_debt_ratio | decimal | 최대 부채비율 (%) |
| min_current_ratio | decimal | 최소 유동비율 (%) |
| min_equity | bigint | 최소 자기자본 (원) |

> **경영상태 등급 (별표10 신용평가등급 기준)**
> - 20점: AA- 이상
> - 18점: A+ ~ A-
> - 16점: BBB+ ~ BBB
> - 14점: BBB-
> - 12점: BB+ ~ BB
> - 10점: BB-
> - 8점: B+ 이하
> - 0점: 부도/워크아웃 등

### bid_credibility_items (신인도 가감점 항목)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| bid_id | FK | bids.id |
| item_type | enum | plus/minus (가점/감점) |
| item_name | varchar | 항목명 |
| score | decimal | 점수 |
| description | text | 설명 |

> **신인도 가점 (별표11, 최대 +4.25)**
> - +1.0: ISO 9001/14001 인증
> - +1.0: 직접생산확인서 보유
> - +0.75: 여성기업/장애인기업/사회적기업/자활기업
> - +0.5: 녹색기업/환경경영인증
> - +0.5: 고용우수기업 인증
> - +0.25: 기술혁신형(이노비즈)/경영혁신형(메인비즈)
> - +0.25: 산재예방 우수사업장
>
> **신인도 감점 (별표11, 최대 -5.0)**
> - -2.0: 부정당업자 제재 이력 (최근 2년)
> - -1.0: 계약불이행/부실이행 이력
> - -1.0: 산업재해 발생 (사망)
> - -0.5: 고용관련 법령 위반
> - -0.5: 환경법령 위반

### bid_vehicles (차량 상세 — 공고당 N대)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| bid_id | FK | bids.id |
| manufacturer | varchar | 제조사 (현대/기아 등) |
| model | varchar | 모델명 (그랜저/EV9 등) |
| trim | varchar | 트림/등급 (익스클루시브 등) |
| fuel_type | enum | gasoline/diesel/hybrid/electric |
| seats | int | 인승 |
| quantity | int | 대수 |
| year_condition | varchar | 연식 조건 (신차/2022년식 이후 등) |
| color_exterior | varchar | 외장색 |
| color_interior | varchar | 내장색 |
| options | json | 추가옵션 목록 |

### bid_contracts (계약 조건)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| bid_id | FK | bids.id |
| contract_months | int | 계약기간 (개월) |
| prepayment_rate | decimal | 선납금 비율 (%) |
| prepayment_amount | bigint | 선납금 금액 (원) |
| deposit | bigint | 보증금 (0=면제) |
| annual_mileage | int | 연간주행거리 (null=무제한) |
| residual_value_rate | decimal | 잔존가치 (%) |
| opening_fee | bigint | 개시대여료 |
| payment_method | varchar | 지급방식 (월후불 등) |

### bid_insurance (보험 조건)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| bid_id | FK | bids.id |
| liability_1 | varchar | 대인1 |
| liability_2 | varchar | 대인2 (무한 등) |
| property_damage | bigint | 대물 한도 (원) |
| own_vehicle | boolean | 자차 가입 여부 |
| own_vehicle_deductible | int | 자차 면책금 (원) |
| personal_injury | varchar | 자기신체/자동차상해 |
| uninsured_motorist | varchar | 무보험차상해 |
| driver_age_min | int | 운전자 최소 연령 |
| driver_scope | varchar | 운전자 범위 (임직원한정 등) |
| emergency_service | varchar | 긴급출동 (가입/횟수) |

### companies (고객사 - 렌트사)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| name | varchar | 회사명 |
| biz_no | varchar | 사업자등록번호 |
| region | varchar | 본사 소재지 |
| branches | json | 지점 소재지 목록 |
| company_size | enum | micro/small/medium/large |
| fleet | json | 보유 차종/대수 |
| plan | enum | free/basic/pro/enterprise |

### company_preferences (알림 설정)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| company_id | FK | companies.id |
| regions | json | 관심 지역 |
| vehicle_types | json | 관심 차종 |
| budget_min | bigint | 최소 예산 |
| budget_max | bigint | 최대 예산 |
| contract_months_min | int | 최소 계약기간 |
| notification_channels | json | 알림 채널 |

### company_credentials (시뮬레이션용 프로필)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | bigint | PK |
| company_id | FK | companies.id |
| credit_grade | varchar | 신용평가등급 (AA-, BBB+ 등) |
| total_performance_amount | bigint | 이행실적 누적 금액 |
| has_iso | boolean | ISO 인증 보유 |
| has_maintenance_network | boolean | 전국 정비네트워크 보유 |
| certifications | json | 기타 인증 목록 (여성기업/이노비즈 등) |
| penalty_history | json | 부정당/제재 이력 |

---

## 🔑 핵심 상수

### 분류코드
```php
const TARGET_CODES = [
    '78111808' => '자동차렌트서비스',      // 핵심 타겟
    '73169001' => '운송장비임대서비스',    // 보조 타겟
];

const SECONDARY_CODES = [
    '78111899' => '도로여객운송서비스',    // 2차 필터 필요
];
```

### 제외 키워드
```php
const EXCLUDE_KEYWORDS = [
    '통학', '수학여행', '현장체험', '수련활동',
    '견학', '소풍', '야영', '캠프',
];

const SECONDARY_EXCLUDE = [
    '수송', '수련', '체험활동',  // 78111899 추가 제외
];
```

### API 정보
```php
const G2B_BASE_URL = 'https://apis.data.go.kr/1230000/ad/BidPublicInfoService/';
const G2B_ENDPOINTS = [
    'service' => 'getBidPblancListInfoServcPPSSrch',
    'goods'   => 'getBidPblancListInfoThngPPSSrch',
    'etc'     => 'getBidPblancListInfoEtcPPSSrch',
];
const G2B_SERVICE_ID = '15129394';
```
