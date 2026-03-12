# GAR MVP Backlog

## 원칙

- 첫 목표는 "쓸 만한 공고 수집 + 읽을 만한 분석 카드"다.
- 견적은 MVP 후반에 붙인다.
- 각 에픽은 독립 시연 가능해야 한다.

## Epic 1. Foundation

- [ ] Laravel 프로젝트 초기화
- [ ] 인증/회사 기본 모델 생성
- [ ] 기본 레이아웃과 대시보드 뼈대 생성
- [ ] 환경변수/시크릿 구조 정의

### Done Criteria

- 로그인 후 빈 대시보드 진입 가능
- 회사 1개와 사용자 1개 생성 가능

## Epic 2. Collector

- [ ] G2B API 클라이언트 구현
- [ ] 증분 수집 커맨드 구현
- [ ] 분류코드/키워드 필터 구현
- [ ] 공고 upsert 구현
- [ ] 수집 로그 저장

### Done Criteria

- 샘플 기간 기준 수집 실행 가능
- 중복 저장 없이 재실행 가능

## Epic 3. Documents

- [ ] 첨부 URL 추출
- [ ] 첨부 다운로드 job 구현
- [ ] HWP/HWPX/PDF 파서 래퍼 구현
- [ ] 파싱 성공/실패 상태 저장

### Done Criteria

- 공고 1건당 첨부파일 다운로드 및 텍스트 추출 가능

## Epic 4. Analysis

- [ ] 분석 프롬프트 버전 관리
- [ ] AI 호출 서비스 구현
- [ ] 스키마 검증기 구현
- [ ] 결과 저장과 `needs_review` 플래그 구현
- [ ] 상세 화면 분석 카드 출력

### Done Criteria

- 샘플 공고 10건 기준 구조화 결과 저장 가능
- 검증 실패 케이스 분리 가능

## Epic 5. Matching & Notifications

- [ ] 회사 프로필/보유 차종 관리
- [ ] 선호도 설정 UI
- [ ] 매칭 점수 엔진 구현
- [ ] 즉시 알림과 다이제스트 구현
- [ ] 알림 dedupe 구현

### Done Criteria

- 공고 저장 후 매칭 점수 산출
- 70점 이상 즉시 알림 발송

## Epic 6. Search & UI Polish

- [ ] 공고 목록 필터
- [ ] 공고 상세 상태 배지
- [ ] 분석 미완료/실패 UI
- [ ] 빈 상태/오류 상태 정리

### Done Criteria

- 목록/상세 사용 흐름이 깨지지 않음

## Epic 7. Simulation

- [ ] 적격심사 시뮬레이션 엔진
- [ ] 프로필 기반 점수 계산 (이행실적/경영상태/사후관리/신인도)
- [ ] 부족 항목 안내 + 프로필 완성 유도 UI

### Done Criteria

- 프로필 입력 후 공고별 예상 점수 산출 가능
- 프로필 미완성 시 부분 계산 + 안내 표시

## Epic 8. Operational Readiness

- [ ] 스케줄러 설정
- [ ] 큐 모니터링
- [ ] 실패 알림 설정
- [ ] fixture/샘플 데이터 확보

### Done Criteria

- 평일 스케줄 기준 자동 동작 확인

## 선행 우선순위

1. Foundation
2. Collector
3. Documents
4. Analysis
5. Matching & Notifications
6. Search & UI Polish
7. Simulation
