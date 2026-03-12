# GAR G2B API Spec

## 목적

이 문서는 나라장터 수집기의 요청 규칙, 응답 해석, 내부 매핑, 실패 처리 정책을 정의한다.

## 1. 수집 범위

- 대상 API군: `BidPublicInfoService`
- 대상 엔드포인트
  - `getBidPblancListInfoServcPPSSrch`
  - `getBidPblancListInfoThngPPSSrch`
  - `getBidPblancListInfoEtcPPSSrch`
- 수집 시간대: 평일 08:00~18:00 KST 매시간
- 기준 시각: 등록일시 기반 증분 수집

## 2. 요청 규칙

| 파라미터 | 값 | 설명 |
|----------|----|------|
| `serviceKey` | 환경변수 | 공공데이터포털 인증키 |
| `type` | `json` 우선 | XML 응답이 오면 파서에서 변환 |
| `inqryDiv` | `1` | 등록일시 기준 증분 조회 |
| `inqryBgnDt` | 마지막 성공 수집 시각 | 시작 시각 |
| `inqryEndDt` | 현재 시각 | 종료 시각 |
| `numOfRows` | `100` | 페이지 크기 |
| `pageNo` | `1..N` | 페이지 반복 |

## 3. 페이지네이션 종료 조건

- 응답 항목 수가 `0`이면 종료
- 응답 항목 수가 `numOfRows` 미만이면 종료
- `max_pages`를 초과하면 종료하고 경고 로그 남김
- 동일 `bid_ntce_no + bid_ntce_ord`가 이미 모두 수집된 경우에도 해당 페이지는 끝까지 확인한다

## 4. 포함/제외 필터

### 1차 분류코드

- 포함: `78111808`, `73169001`
- 2차 필터 대상: `78111899`

### 2차 키워드

- 제외: `통학`, `수학여행`, `현장체험`, `수련활동`, `견학`, `소풍`, `야영`, `캠프`
- 보조 포함: `렌트`, `임차`, `임대`, `리스`
- `78111899` 추가 제외: `수송`, `수련`, `체험활동`

## 5. 내부 필드 매핑

아래는 첫 구현 시 반드시 검증해야 하는 핵심 필드 매핑이다. 실제 응답 fixture를 확보한 뒤 필드명 오타나 변형이 있으면 이 문서를 갱신한다.

| 내부 필드 | 예상 G2B 필드 | 설명 |
|-----------|---------------|------|
| `bid_ntce_no` | `bidNtceNo` | 공고번호 |
| `bid_ntce_ord` | `bidNtceOrd` | 공고차수 |
| `title` | `bidNtceNm` | 공고명 |
| `institution` | `dminsttNm` | 수요기관 |
| `category` | 엔드포인트 타입 | `service/goods/etc` |
| `classification_code` | `prdctClsfcNo` 또는 유사 필드 | 분류코드 |
| `budget` | `presmptPrce` 또는 유사 필드 | 추정가격/예산 |
| `bid_open_dt` | `opengDt` | 개찰일시 |
| `bid_close_dt` | `bidClseDt` | 입찰마감일시 |
| `raw_data` | 전체 응답 row | 디버깅/재처리용 원본 |

## 6. 첨부파일 탐지

- 우선순위
  1. `ntceSpecDocUrl1..10`
  2. `stdNtceDocUrl`
- 각 URL은 파일명, 확장자, 순번을 함께 저장한다.
- 다운로드 요청은 반드시 `Referer: https://www.g2b.go.kr/` 헤더를 포함한다.
- 확장자만으로 판단하지 않고 `Content-Type`을 함께 기록한다.

## 7. 중복 방지

- 업서트 키: `bid_ntce_no + bid_ntce_ord`
- 동일 첨부 URL 재수집 시 기존 `BidDocument`를 재사용한다.
- 분석 재실행 시 기존 `BidAnalysis`를 덮어쓰지 않고 새 버전을 만든다.

## 8. 실패 처리

| 실패 유형 | 처리 |
|-----------|------|
| 4xx 인증 오류 | 수집 중단, 운영 알림 발송 |
| 429 또는 rate limit 추정 | 지수 백오프 후 재시도 |
| 5xx | 최대 3회 재시도 후 실패 로그 |
| 빈 응답 | 성공으로 간주하되 수집 건수 0 기록 |
| 단일 row 파싱 오류 | 해당 row 스킵, raw payload 로그 |
| 첨부 다운로드 실패 | `BidDocument.failed`, 공고 본문 처리는 계속 |

## 9. 운영 로그

- 수집 시작/종료 시각
- 엔드포인트별 요청 횟수
- 신규 공고 수
- 중복 공고 수
- 필터 탈락 수
- 첨부 다운로드 성공/실패 수

## 10. 구현 전 체크리스트

- 실제 API 응답 fixture 3종 확보
- 첨부 다운로드 허용 헤더 확인
- JSON과 XML 응답 차이 확인
- 예산/분류코드/기관명 필드의 실제 이름 검증
