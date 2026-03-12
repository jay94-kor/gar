# GAR AI 프롬프트 설계

> 이 문서는 프롬프트 원문입니다. 애플리케이션이 받아들이는 저장 계약과 검증 규칙은 [ANALYSIS_SCHEMA.md](ANALYSIS_SCHEMA.md)를 기준으로 합니다.

## 1. 공고 첨부파일 분석 프롬프트

### 1-1. 마스터 분석 프롬프트 (차량 렌트 공고 전용)

```
당신은 나라장터(G2B) 차량 렌트/임차 입찰공고 분석 전문가입니다.
첨부된 공고 문서를 분석하여 아래 JSON 형식으로 정확히 추출해주세요.
문서에 명시되지 않은 항목은 null로 표시하세요.
추정하지 말고 문서에 있는 내용만 추출하세요.

{
  "vehicles": [
    {
      "manufacturer": "제조사 (현대/기아/제네시스/BMW 등)",
      "model": "모델명 (그랜저/EV9/아이오닉5 등)",
      "trim": "트림/등급 (익스클루시브/롱레인지 등)",
      "fuel_type": "gasoline|diesel|hybrid|electric|lpg",
      "seats": "인승 (5/7/9/11/15)",
      "quantity": "대수",
      "year_condition": "연식 조건 (신차/2024년식 이후 등)",
      "color_exterior": "외장색",
      "color_interior": "내장색",
      "options": ["추가옵션 목록 (HUD, 썬팅, 블랙박스, 스노우타이어 등)"]
    }
  ],

  "contract": {
    "period_months": "계약기간 (개월)",
    "prepayment_rate": "선납금 비율 (%)",
    "prepayment_amount": "선납금 금액 (원)",
    "deposit": "보증금 (원, 0=면제)",
    "annual_mileage_km": "연간주행거리 (km, null=무제한)",
    "residual_value_rate": "잔존가치 (%)",
    "opening_fee": "개시대여료 (원)",
    "payment_method": "지급방식 (월후불/월선불 등)",
    "delivery_deadline": "차량인도 기한",
    "delivery_location": "인도 장소"
  },

  "insurance": {
    "liability_1": "대인1 (자배법 정한 금액 등)",
    "liability_2": "대인2 (무한/1인당 X억 등)",
    "property_damage": "대물 (X억원)",
    "own_vehicle": true|false,
    "own_vehicle_deductible": "자차 면책금 (원)",
    "personal_injury": "자기신체/자동차상해 (사망/부상 한도)",
    "uninsured_motorist": "무보험차상해 (한도)",
    "driver_age_min": "운전자 최소 연령",
    "driver_scope": "운전자 범위 (임직원한정/누구나/만26세이상 등)",
    "emergency_service": "긴급출동 (가입/횟수)",
    "special_coverage": "특약 (신차보상 등)"
  },

  "qualification": {
    "biz_type_code": "업종코드 (1457 등)",
    "biz_type_name": "업종명 (자동차대여사업 등)",
    "region_limit": "지역제한 (null=없음, 충남/경남 등)",
    "company_size_limit": "기업규모 제한 (null/소기업/소상공인/중소기업)",
    "joint_contract_allowed": true|false,
    "subcontract_allowed": true|false,
    "branch_requirement": "지점 요건 (전국/수도권 등, null=없음)",
    "other_requirements": ["기타 자격 요건"]
  },

  "evaluation": {
    "method": "competitive|negotiation|estimate (일반경쟁/협상/수의)",
    "standard": "적용기준 (별표8/경기도별표1-6 등)",
    "success_threshold_rate": "낙찰하한율 (%)",
    "passing_score": "종합평점 합격점",
    "price_basis": "가격기준 (총액/단가)",
    "preliminary_prices_count": "복수예비가격 수",
    "preliminary_prices_range": "예비가격 범위 (±2%/±3%)"
  },

  "performance_requirement": {
    "type": "same|similar|any|none (동등이상/유사/무관/없음)",
    "scope": "실적 범위 설명",
    "years": "실적 인정 기간 (년)",
    "min_amount": "최소 실적 금액 (원, null=없음)",
    "min_count": "최소 건수 (null=없음)"
  },

  "required_documents": {
    "bid_stage": ["입찰 참가 시 필요 서류"],
    "screening_stage": ["적격심사 시 필요 서류"],
    "contract_stage": ["계약 체결 시 필요 서류"]
  },

  "special_conditions": {
    "replacement_vehicle": "대차 조건 (동급무상/유상 등)",
    "maintenance_cycle": "정비 주기 (3개월1회 등)",
    "snow_tire": true|false,
    "snow_chain": true|false,
    "blackbox": true|false,
    "tinting": true|false,
    "safety_equipment": "안전장구 (소화기/삼각대 등)",
    "defect_replacement": "3회 고장 시 교체 의무 등",
    "early_termination_penalty": "중도해약 위약금",
    "return_condition": "반납 시 추가비용 여부",
    "other": ["기타 특수조건"]
  }
}
```

### 1-2. 서류 체크리스트 생성 프롬프트

```
아래 입찰공고 분석 결과를 바탕으로, 렌트사가 준비해야 할 서류 체크리스트를 생성하세요.
각 서류에 대해 [필수/선택], [준비 난이도: 상/중/하], [예상 소요기간]을 표시하세요.

## 입찰 단계별 체크리스트

### 1단계: 입찰 참가 준비
- [ ] {서류명} | {필수/선택} | 난이도: {상/중/하} | {소요기간}

### 2단계: 적격심사 서류 (낙찰 대상자)
- [ ] {서류명} | {필수/선택} | 난이도: {상/중/하} | {소요기간}

### 3단계: 계약 체결
- [ ] {서류명} | {필수/선택} | 난이도: {상/중/하} | {소요기간}

### ⚠️ 주의사항
- {공고별 특이 요구사항}
```

### 1-3. 적격심사 시뮬레이션 프롬프트

```
렌트사 프로필과 공고 분석 결과를 비교하여 적격심사 예상 점수를 시뮬레이션하세요.
프로필에 없는 항목은 "정보 없음"으로 표시하고, 해당 점수는 계산하지 마세요.

## 렌트사 프로필
{company_profile}

## 공고 요구사항
{bid_analysis}

## 시뮬레이션 결과

| 심사분야 | 배점한도 | 예상점수 | 근거 |
|----------|----------|----------|------|
| 이행실적 | {점} | {점 또는 "정보 없음"} | {실적금액 대비 추정가격 비율} |
| 경영상태 | {점} | {점 또는 "정보 없음"} | {신용등급 기준} |
| 사후관리 | {점} | {점 또는 "정보 없음"} | {A/S 체계 기준} |
| 신인도 | ±{점} | {점 또는 "정보 없음"} | {가감점 항목} |

### 입찰가격 제외 소계: {점}/{배점합계}
### 85점 달성에 필요한 최소 입찰가격 점수: {점}
### 프로필 완성도: {%} — 부족 항목: {목록}
```

## 2. 공고 요약 카드 프롬프트

```
아래 입찰공고 정보를 렌트사 영업담당자가 30초 안에 파악할 수 있도록 요약 카드를 작성하세요.
불필요한 수식어 없이 핵심만 간결하게.

📋 {공고명}
🏢 {발주기관}
💰 예산 {금액} | 📅 마감 {날짜}

🚗 차량: {차종} {대수}대 ({연식조건})
⏱️ 기간: {개월}개월 | 거리: {제한/무제한}
💳 선납금: {%} | 보증금: {면제/금액}

✅ 자격: {업종} | {지역제한} | {기업규모}
📊 방식: {적격심사/수의} | 하한율 {%}

⚡ 특이사항:
- {핵심 특수조건 1~3개}
```

## 3. 알림 메시지 프롬프트

### 3-1. 카카오 알림톡 (1000자 제한)

```
아래 공고 정보를 카카오 알림톡 형식(1000자 이내)으로 작성하세요.

[GAR] 새 입찰공고 알림

📋 {공고명}
🏢 {기관} | 💰 {예산}
📅 마감: {날짜} (D-{일})

🚗 {차종} {대수}대 / {기간}개월
📊 매칭점수: {점}/100

▶ 상세보기: {URL}
```

### 3-2. 텔레그램 (마크다운)

```
아래 공고 정보를 텔레그램 마크다운 형식으로 작성하세요.

🚗 *새 입찰공고*

*{공고명}*
🏢 {기관}
💰 예산: {금액}
📅 마감: {날짜} (D-{일})

*차량정보*
• {차종} × {대수}대
• {연료} / {인승}
• 기간: {개월}개월

*입찰조건*
• 방식: {적격심사/수의}
• 하한율: {%}
• 지역: {제한/전국}

📊 매칭점수: {점}/100점
[상세보기]({URL})
```
