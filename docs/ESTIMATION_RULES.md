# GAR Estimation Rules

## 목적

이 문서는 견적 생성에 필요한 입력 차원, 산식 순서, 수동 검토 기준을 정의한다.

## 1. 범위

첫 버전의 견적은 "투찰 참고용 초안"이다. 제출용 최종 견적 확정은 사용자 검토를 전제로 한다.

## 2. 필수 입력

- 공고 분석 결과
- 고객사 단가표
- 계약기간
- 차량 대수
- 보험 조건
- 추가 옵션
- 탁송/인도 조건
- 낙찰하한율 또는 추천 투찰률 정책

## 3. 단가표 차원

`price_tables`는 최소 아래 차원을 지원해야 한다.

| 차원 | 필요 여부 | 설명 |
|------|-----------|------|
| 제조사 | 필수 | 현대/기아 등 |
| 모델 | 필수 | 그랜저/스타리아 등 |
| 트림 | 권장 | 프레스티지/익스클루시브 등 |
| 연료 | 권장 | 가솔린/전기 등 |
| 계약개월 구간 | 필수 | 12/24/36/48/60개월 |
| 연간주행거리 구간 | 필수 | 1만/2만/3만 km 등 |
| 기본 월 렌트료 | 필수 | VAT 별도 또는 포함 명시 |
| 보험 패키지 | 필수 | 대인/대물/자차별 증감 |
| 옵션 단가 | 필수 | 블랙박스/썬팅/스노우타이어 등 |
| 탁송/지역 가산 | 권장 | 인도 지역별 비용 |

## 4. 계산 순서

1. 차량별 기본 월 렌트료 선택
2. 계약개월/주행거리 보정 적용
3. 보험 조건 가산/감산
4. 옵션 단가 합산
5. 탁송/인도비 반영
6. 선납금/보증금/잔존가치 조건 반영
7. 차량별 월 금액과 총액 계산
8. VAT 처리
9. 낙찰하한율 기준 추천 투찰가 계산

## 5. 기준 산식

```text
vehicle_monthly = base_monthly_rate
                + mileage_adjustment
                + insurance_adjustment
                + option_adjustment

vehicle_total = (vehicle_monthly * contract_months * quantity)
              + delivery_fee
              + opening_fee

recommended_bid_price = max(
  floor_price_by_threshold,
  company_min_margin_price
)
```

## 6. 출력물

- 견적 요약 헤더
- 차량별 line item
- 옵션/보험 breakdown
- 총액, VAT, 월 환산 금액
- 추천 투찰가
- 수동 검토 경고

## 7. 수동 검토가 필요한 경우

- 단가표에 해당 모델/트림이 없는 경우
- 공고 조건에 잔존가치/보증금/선납금이 명시됐지만 산식 입력이 없는 경우
- 보험 조건이 단가표 패키지와 정확히 매칭되지 않는 경우
- 옵션이 자유서술로만 존재하는 경우

## 8. 데이터 모델 보강 권장

아래 구조가 있어야 견적 재현성이 생긴다.

- `price_table_versions`
- `price_table_items`
- `estimate_inputs_snapshot`
- `estimate_line_items`
- `estimate_exceptions`

## 9. 비범위

- 자동 최적 투찰 알고리즘
- 경쟁사 가격 추정
- 실제 나라장터 제출 파일 자동 생성
