# GAR Documentation

## 목적

GAR 문서는 제품 설명과 구현 계약을 분리해, 구현 시작 이후에도 무엇이 기준 문서인지 바로 판단할 수 있도록 구성한다.

## 문서 맵

| 문서 | 역할 | 독자 |
|------|------|------|
| [PRD.md](PRD.md) | 고객, 문제, 가치, 범위, KPI를 정의하는 제품 문서 | 대표, PM, 영업 |
| [PLANNING.md](PLANNING.md) | 전체 방향과 사업/시장/로드맵을 빠르게 훑는 개요 문서 | 전체 |
| [ARCHITECTURE.md](ARCHITECTURE.md) | 목표 시스템 구조와 구현 대상 컴포넌트 개요 | 엔지니어, PM |
| [DOMAIN_MODEL.md](DOMAIN_MODEL.md) | 핵심 엔티티, 상태 전이, 불변식, 책임 경계 | 엔지니어 |
| [G2B_API_SPEC.md](G2B_API_SPEC.md) | 나라장터 수집 규칙, 요청/응답 매핑, 실패 처리 | 엔지니어 |
| [ANALYSIS_SCHEMA.md](ANALYSIS_SCHEMA.md) | AI 분석 출력 계약, 검증 규칙, DB 매핑 | 엔지니어, AI |
| [PROMPTS.md](PROMPTS.md) | AI 프롬프트 원문 | 엔지니어, AI |
| [MATCHING_RULES.md](MATCHING_RULES.md) | 매칭 점수 계산과 알림 트리거 규칙 | 엔지니어, PM |

| [UI_SPECS.md](UI_SPECS.md) | 화면별 기능, 상태, acceptance criteria | 엔지니어, 디자이너 |
| [OPS.md](OPS.md) | 큐, 스케줄, 로그, 보안, 보존 정책 | 엔지니어, 운영 |
| [MVP_BACKLOG.md](MVP_BACKLOG.md) | 구현 우선순위와 작업 단위 | 엔지니어, PM |

## 소스 오브 트루스

- 제품 범위와 비즈니스 목표: [PRD.md](PRD.md)
- 도메인 상태, 불변식, 책임 경계: [DOMAIN_MODEL.md](DOMAIN_MODEL.md)
- 외부 수집 계약: [G2B_API_SPEC.md](G2B_API_SPEC.md)
- AI 분석 출력 계약: [ANALYSIS_SCHEMA.md](ANALYSIS_SCHEMA.md)
- 매칭과 알림 계산 규칙: [MATCHING_RULES.md](MATCHING_RULES.md)

- 화면 동작과 페이지 요구사항: [UI_SPECS.md](UI_SPECS.md)
- 운영 정책: [OPS.md](OPS.md)
- 프롬프트 문구 자체: [PROMPTS.md](PROMPTS.md)

## 읽는 순서

1. 제품을 이해하려면 [PRD.md](PRD.md)와 [PLANNING.md](PLANNING.md)를 먼저 읽는다.
2. 구현을 시작하려면 [DOMAIN_MODEL.md](DOMAIN_MODEL.md), [G2B_API_SPEC.md](G2B_API_SPEC.md), [ANALYSIS_SCHEMA.md](ANALYSIS_SCHEMA.md)를 먼저 읽는다.
3. 매칭/견적/UI 작업은 각 전용 문서를 기준으로 진행한다.
4. 운영 준비와 배포는 [OPS.md](OPS.md)와 [MVP_BACKLOG.md](MVP_BACKLOG.md)를 기준으로 한다.

## 현재 상태

- 제품/도메인 정의는 충분히 구체적이다.
- 구현 계약은 이제 문서상으로 출발 가능한 수준까지 세분화했다.
- 실제 코드베이스는 아직 시작 전 단계이므로, 문서와 구현을 동시에 맞춰가야 한다.
