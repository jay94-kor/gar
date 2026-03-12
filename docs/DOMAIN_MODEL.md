# GAR Domain Model

## 목적

이 문서는 구현 시 엔티티 경계, 상태 전이, 불변식, 쓰기 책임을 정의한다. 화면이나 프롬프트보다 우선하는 구현 계약 문서다.

## 1. 핵심 엔티티

| 엔티티 | 설명 | 주요 식별자 |
|--------|------|-------------|
| Bid | G2B에서 수집한 공고 본문과 메타데이터 | `bid_ntce_no + bid_ntce_ord` |
| BidDocument | 공고에 연결된 첨부파일 | `bid_id + seq` |
| BidAnalysis | AI 분석 결과의 버전 스냅샷 | `bid_id + analysis_version` |
| Company | 고객사(렌트사) | `company.id` |
| CompanyFleet | 고객사 보유 차종/대수 | `company_id + vehicle_key` |
| CompanyPreference | 관심 지역/예산/기간/채널 | `company_id` |
| BidVehicle | 공고 요구 차량 상세 (공고당 N대) | `bid_id + seq` |
| BidContract | 공고 계약 조건 | `bid_id` |
| BidInsurance | 공고 보험 조건 | `bid_id` |
| BidQualification | 자격요건 + 적격심사 배점 | `bid_id` |
| BidPerformance | 이행실적 요구사항 | `bid_id` |
| BidFinancial | 경영상태 요구사항 | `bid_id` |
| BidCredibility | 신인도 가감점 항목 | `bid_id + item_name` |
| BidChecklist | 서류 체크리스트 | `bid_id + stage` |
| MatchResult | 공고와 고객사의 적합도 평가 결과 | `bid_id + company_id + matching_version` |
| NotificationLog | 발송 이력과 중복 방지 키 | `dedupe_key` |
| CompanyCredential | 시뮬레이션용 프로필 (신용등급/실적/인증) | `company_id` |

## 2. 상태 모델

### 2.1 Bid

공고는 외부 조달 상태와 내부 처리 상태를 분리한다.

#### 외부 조달 상태

| 상태 | 의미 |
|------|------|
| `open` | 입찰 가능 상태 |
| `closed` | 입찰 마감 |
| `awarded` | 낙찰 결과 확인됨 |
| `cancelled` | 공고 취소/정정으로 유효하지 않음 |

#### 내부 처리 상태

| 상태 | 의미 | 주체 |
|------|------|------|
| `discovered` | API 응답에서 발견 | Collector |
| `persisted` | DB 저장 완료 | Collector |
| `documents_pending` | 첨부 다운로드 대기 | Collector |
| `documents_ready` | 최소 1개 첨부 다운로드 완료 | Downloader |
| `analysis_pending` | 분석 큐 적재 완료 | Downloader |
| `analyzed` | 최신 분석이 검증 통과 | Analyzer |
| `matching_complete` | 활성 고객사 대상 매칭 완료 | Matcher |
| `failed` | 처리 실패, 수동 확인 필요 | 각 파이프라인 |

### 2.2 BidDocument

| 상태 | 의미 |
|------|------|
| `queued` | 다운로드 대기 |
| `downloading` | 다운로드 중 |
| `downloaded` | 파일 저장 완료 |
| `parse_pending` | 파싱 대기 |
| `parsed` | 텍스트 추출 완료 |
| `unsupported` | 지원하지 않는 포맷 |
| `failed` | 다운로드 또는 파싱 실패 |

### 2.3 BidAnalysis

| 상태 | 의미 |
|------|------|
| `pending` | 분석 요청 생성됨 |
| `running` | 모델 호출 중 |
| `validated` | 스키마 검증 통과 |
| `needs_review` | 부분 추출 또는 신뢰도 부족 |
| `failed` | 분석 실패 |

### 2.4 MatchResult

| 상태 | 의미 |
|------|------|
| `eligible` | 하드 필터 통과 |
| `ineligible` | 자격/지역/차종 등 하드 필터 탈락 |
| `scored` | 점수 계산 완료 |
| `notified` | 즉시 알림 발송 완료 |
| `suppressed` | 중복/야간/사용자 설정으로 미발송 |

### 2.5 SimulationResult

| 상태 | 의미 |
|------|------|
| `calculated` | 점수 계산 완료 |
| `incomplete` | 프로필 정보 부족으로 부분 계산 |
| `stale` | 프로필 또는 공고 변경으로 재계산 필요 |

## 3. 불변식

- `bids`는 `bid_ntce_no + bid_ntce_ord`로 유일해야 한다.
- 동일 공고에 대해 최신 `validated` 분석은 하나만 `is_current=true`를 가진다.
- `NotificationLog.dedupe_key`는 `(company_id, bid_id, notification_type, channel)` 기준으로 유일해야 한다.
- `SimulationResult`는 `(company_id, bid_id)` 조합당 하나만 유효하며 프로필/공고 변경 시 재계산한다.
- 삭제 대신 소프트 삭제 또는 상태 전이로 처리한다.

## 4. 책임 경계

| 컴포넌트 | 쓰기 책임 |
|----------|-----------|
| Collector | `Bid` 생성/업데이트, 초기 내부 상태 지정 |
| Downloader | `BidDocument` 생성, 파일 경로와 상태 업데이트 |
| Parser | `BidDocument.extracted_text`, 파싱 메타데이터 |
| Analyzer | `BidAnalysis` 버전 생성, 파생 테이블 저장, `Bid` 내부 상태 업데이트 |
| Matcher | `MatchResult` 생성/업데이트 |
| Notification | `NotificationLog` 생성/전송 상태 업데이트 |
| Simulator | `SimulationResult` 생성/재계산 |

## 5. 추천 스키마 보강

현재 설계에 아래 테이블 또는 컬럼이 추가되어야 운영 안정성이 높아진다.

- `bids.pipeline_status`
- `bids.last_collected_at`
- `bid_documents.status`, `bid_documents.download_attempts`, `bid_documents.parse_attempts`
- `bid_analyses.status`, `bid_analyses.schema_version`, `bid_analyses.prompt_version`, `bid_analyses.confidence`
- `match_results` 테이블
- `notification_logs` 테이블
- `simulation_results` 테이블
- `company_credentials` 테이블

## 6. 수동 검토가 필요한 조건

- 첨부파일이 전부 파싱 실패한 경우
- 분석 결과가 `needs_review`인 경우
- 자격요건과 평가기준이 충돌하는 경우
- 시뮬레이션에 필요한 프로필 정보가 부족한 경우

## 7. 구현 결정

- 외부 조달 상태와 내부 파이프라인 상태는 분리 저장한다.
- 분석 결과는 덮어쓰기 대신 버전 스냅샷으로 저장한다.
- 알림은 반드시 발송 이력 기반으로 dedupe 한다.
- 시뮬레이션 결과는 계산 시 사용한 프로필 스냅샷 해시를 함께 저장한다.
