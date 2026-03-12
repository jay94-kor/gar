# GAR Analysis Schema

## 목적

이 문서는 AI 분석 결과의 저장 계약을 정의한다. [PROMPTS.md](PROMPTS.md)는 모델에게 보내는 문장이고, 이 문서는 애플리케이션이 받아들일 수 있는 구조와 검증 규칙이다.

## 1. 분석 파이프라인

1. 첨부파일 다운로드
2. 파서로 텍스트 추출
3. 텍스트 정규화
4. AI 모델 호출
5. JSON 파싱
6. 스키마 검증
7. DB 저장 또는 `needs_review`

## 2. 최상위 출력 계약

```json
{
  "meta": {
    "schema_version": "v1",
    "prompt_version": "analysis-master-v1",
    "source_language": "ko",
    "text_quality": "high|medium|low",
    "analysis_confidence": 0.0
  },
  "summary": {
    "title": "30초 요약",
    "key_points": ["핵심 포인트"]
  },
  "vehicles": [],
  "procurement": {},
  "contract": {},
  "insurance": {},
  "qualification": {},
  "evaluation": {},
  "performance_requirement": {},
  "required_documents": {},
  "special_conditions": {}
}
```

## 3. 필드 규칙

### 공통

- 문서에 없으면 `null`
- 추정 금지
- 금액은 정수 원 단위
- 비율은 `0~100` 숫자
- `true/false`가 가능한 항목은 문자열 금지
- 알 수 없는 enum 값은 `null`
- 배열은 중복 제거 후 저장

### vehicles

- `quantity`는 정수
- `fuel_type` enum: `gasoline`, `diesel`, `hybrid`, `electric`, `lpg`
- `options`는 문자열 배열

### procurement

- `vehicle_condition` enum: `new_only`, `used_ok`, `unspecified`
  - `new_only`: "신차", "미등록", "출고 후 운행이력 없는 차량" 등 명시
  - `used_ok`: 연식 조건만 있고 신차 요구 없음 (예: "2023년식 이후")
  - `unspecified`: 조건 미명시
- `year_threshold`: 연식 하한 (예: `2023`, null이면 미명시)
- `registration_requirement`: "사업용 등록", "허 번호판" 등 명시 여부 (boolean)
- `funding_implication` enum: `purchase_required`, `stock_eligible`, `unknown`
  - 렌트사 입장 자금 부담 판단 보조 필드
  - `purchase_required`: 신차 구매 필수 (vehicle_condition=new_only)
  - `stock_eligible`: 보유 재고 투입 가능 (vehicle_condition=used_ok이고 연식 조건 충족 가능)
  - `unknown`: 판단 불가

### contract

- `period_months`는 정수 개월
- `annual_mileage_km`는 정수 km, 무제한이면 `null`
- `deposit`가 면제면 `0`

### insurance

- `property_damage`는 가능하면 숫자 원 단위로 정규화
- 정규화 불가 시 원문 문자열을 별도 메모에 남기고 메인 값은 `null`

### qualification

- `biz_type_code`는 문자열
- `region_limit`는 단일 문자열이 아니라 저장 시 배열로 정규화 가능해야 함
- `other_requirements`는 문자열 배열

### evaluation

- `method` enum: `competitive`, `negotiation`, `estimate`
- `success_threshold_rate`는 소수 허용

## 4. 검증 규칙

- top-level 키가 누락되면 실패
- `vehicles`는 배열이어야 한다
- `contract`, `insurance`, `qualification`, `evaluation`, `performance_requirement`, `required_documents`, `special_conditions`는 객체여야 한다
- 숫자 문자열은 파서 단계에서 숫자로 변환한다
- 허용되지 않은 키는 드롭하고 경고 로그를 남긴다
- `analysis_confidence < 0.6`이면 `needs_review`

## 5. 실패와 fallback

| 상황 | 처리 |
|------|------|
| JSON 파싱 실패 | 동일 모델 1회 재시도 |
| 스키마 검증 실패 | `failed`로 저장, 원문 응답 보존 |
| 일부 섹션만 누락 | `needs_review` 저장 |
| 텍스트 품질 `low` | 요약만 저장하고 상세 추출은 `null` 허용 |
| 지원되지 않는 첨부만 존재 | 분석 건너뛰고 수동 검토 큐로 이동 |

## 6. 버전 관리

- `schema_version`: 저장 계약의 버전
- `prompt_version`: 프롬프트 문안 버전
- `model_name`: 실제 호출한 모델명
- `input_hash`: 텍스트 입력의 SHA-256
- `analysis_version`: 동일 공고 분석의 증가 버전

## 7. DB 매핑

| 분석 섹션 | 저장 위치 |
|-----------|-----------|
| `summary` | `bid_analyses.summary` |
| `vehicles` | `bid_vehicles` |
| `contract` | `bid_contracts` |
| `insurance` | `bid_insurance` |
| `procurement` | `bid_contracts` (또는 별도 컬럼) |
| `qualification` | `bid_qualifications` |
| `evaluation` | `bid_qualifications` |
| `performance_requirement` | `bid_performances` |
| `required_documents` | `bid_checklists` |
| `special_conditions` | `bid_analyses.special_conditions` |

## 8. 수동 검토 플래그

아래 조건이면 UI에 경고 배지를 표시한다.

- `vehicles`가 비어 있는데 공고 제목에 차종 키워드가 존재
- `evaluation.method`가 `null`
- `qualification.biz_type_code`가 `null`
- 보험/계약 핵심 값이 대부분 `null`

## 9. 구현 메모

- 첫 버전은 evidence 추출보다 구조화 안정성에 집중한다.
- 추후 버전에서 필드별 근거 문장 배열을 추가할 수 있다.
- `PROMPTS.md` 변경 시 `prompt_version`도 함께 올린다.
