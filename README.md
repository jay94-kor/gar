# 🚗 GAR (Government Auto Rent)

> 나라장터 차량 렌트 입찰 자동화 플랫폼

GAR은 공공기관 차량 렌트/임차 입찰공고를 자동 수집·분석하여, 자동차대여 사업자에게 맞춤형 입찰 정보를 제공하는 SaaS입니다.

## 핵심 기능

- **🔍 자동 수집** — 나라장터 API에서 차량 렌트 공고 실시간 수집
- **🎯 스마트 필터** — 분류코드 + 키워드 기반 정밀 필터링 (정확도 90%+)
- **📄 문서 분석** — HWP/HWPX/PDF 첨부파일 AI 자동 분석
- **🔔 맞춤 알림** — 지역·차종·예산 조건에 맞는 공고 즉시 알림
- **📝 견적서 생성** — 단가표 기반 견적서 자동 산출

## 기술 스택

- Laravel 12 + Vue 3 + Inertia.js + Tailwind CSS 4
- PostgreSQL + Redis
- Laravel AI SDK
- hwpx-ts + olefile (문서 파싱)

## 시장

- 월간 **~160건** 차량 렌트 입찰공고
- 연간 수천억 원 규모 공공 차량 임차 시장
- 직접 경쟁 서비스 **없음** (블루오션)

## 문서

- [문서 인덱스](docs/README.md)
- [PRD](docs/PRD.md)
- [기획 개요 (PLANNING.md)](docs/PLANNING.md)
- [아키텍처](docs/ARCHITECTURE.md)
- [도메인 모델](docs/DOMAIN_MODEL.md)
- [G2B API 규격](docs/G2B_API_SPEC.md)
- [AI 분석 스키마](docs/ANALYSIS_SCHEMA.md)
- [매칭 규칙](docs/MATCHING_RULES.md)
- [견적 규칙](docs/ESTIMATION_RULES.md)
- [UI 스펙](docs/UI_SPECS.md)
- [운영 문서](docs/OPS.md)
- [MVP 백로그](docs/MVP_BACKLOG.md)

## License

Proprietary - © 2026 ZZZARIT Co., Ltd.
