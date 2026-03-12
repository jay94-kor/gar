# GAR 개발 구조

> 이 문서는 목표 아키텍처 개요입니다. 상태 전이와 구현 계약은 [DOMAIN_MODEL.md](DOMAIN_MODEL.md), 외부 수집 계약은 [G2B_API_SPEC.md](G2B_API_SPEC.md), AI 저장 계약은 [ANALYSIS_SCHEMA.md](ANALYSIS_SCHEMA.md)를 우선합니다.

## 디렉토리 구조

```
gar/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── CollectBidsCommand.php        # 공고 수집 (schedule:run)
│   │       ├── AnalyzeBidDocumentsCommand.php # 문서 분석 트리거
│   │       └── SendNotificationsCommand.php   # 알림 발송
│   │
│   ├── Models/
│   │   ├── Bid.php                 # 공고
│   │   ├── BidDocument.php         # 첨부파일
│   │   ├── BidVehicle.php          # 차량 상세
│   │   ├── BidContract.php         # 계약 조건
│   │   ├── BidInsurance.php        # 보험 조건
│   │   ├── BidQualification.php    # 자격요건 + 적격심사
│   │   ├── BidPerformance.php      # 이행실적 요구
│   │   ├── BidFinancial.php        # 경영상태 요구
│   │   ├── BidCredibility.php      # 신인도 가감점
│   │   ├── BidChecklist.php        # 서류 체크리스트
│   │   ├── Company.php             # 렌트사 (고객)
│   │   ├── CompanyPreference.php   # 알림 설정
│   │   ├── CompanyFleet.php        # 보유 차종
│   │   ├── CompanyCredential.php   # 시뮬레이션용 프로필
│   │   └── User.php
│   │
│   ├── Services/
│   │   ├── G2B/
│   │   │   ├── G2BClient.php            # 나라장터 API 클라이언트
│   │   │   ├── G2BCollector.php          # 공고 수집 + 필터링
│   │   │   └── G2BDocumentDownloader.php # 첨부파일 다운로드
│   │   │
│   │   ├── Parser/
│   │   │   ├── DocumentParserFactory.php  # 팩토리 (확장자별 분기)
│   │   │   ├── HwpxParser.php            # hwpx-ts 연동 (Node subprocess)
│   │   │   ├── HwpParser.php             # olefile 연동 (Python subprocess)
│   │   │   └── PdfParser.php             # pdf-parse
│   │   │
│   │   ├── Analyzer/
│   │   │   ├── BidAnalyzer.php           # AI 분석 오케스트레이터
│   │   │   ├── VehicleExtractor.php      # 차량 정보 추출
│   │   │   ├── ContractExtractor.php     # 계약 조건 추출
│   │   │   ├── QualificationExtractor.php # 자격/심사 추출
│   │   │   └── ChecklistGenerator.php    # 서류 체크리스트 생성
│   │   │
│   │   ├── Matcher/
│   │   │   ├── BidMatcher.php            # 공고-렌트사 매칭
│   │   │   ├── ScoreCalculator.php       # 매칭 점수 계산
│   │   │   └── SimulationEngine.php      # 적격심사 시뮬레이션
│   │   │
│   │   ├── Simulator/
│   │   │   └── SimulationEngine.php      # 적격심사 시뮬레이션
│   │   │
│   │   └── Notification/
│   │       ├── NotificationService.php    # 알림 서비스
│   │       ├── Channels/
│   │       │   ├── KakaoAlimtalkChannel.php
│   │       │   ├── TelegramChannel.php
│   │       │   └── EmailChannel.php
│   │       └── Templates/
│   │           ├── NewBidTemplate.php
│   │           ├── DeadlineTemplate.php
│   │           └── MatchTemplate.php
│   │
│   ├── Jobs/
│   │   ├── CollectBidsJob.php            # 공고 수집 Job
│   │   ├── DownloadDocumentJob.php       # 첨부파일 다운로드
│   │   ├── AnalyzeDocumentJob.php        # 문서 AI 분석
│   │   ├── GenerateChecklistJob.php      # 체크리스트 생성
│   │   ├── MatchBidsJob.php              # 매칭 실행
│   │   └── SendNotificationJob.php       # 알림 발송
│   │
│   ├── Http/
│   │   └── Controllers/
│   │       ├── DashboardController.php
│   │       ├── BidController.php          # 공고 목록/상세
│   │       ├── BidFilterController.php    # 필터 API
│   │       ├── CompanyController.php      # 회사 프로필
│   │       ├── FleetController.php        # 보유 차종 관리
│   │       ├── PreferenceController.php   # 알림 설정
│   │       ├── CredentialController.php   # 시뮬레이션 프로필
│   │       ├── SimulationController.php   # 적격심사 시뮬레이션
│   │       └── ChecklistController.php    # 서류 체크리스트
│   │
│   └── Enums/
│       ├── FuelType.php          # gasoline/diesel/hybrid/electric
│       ├── EvaluationMethod.php  # competitive/negotiation/estimate
│       ├── CompanySize.php       # micro/small/medium/large
│       ├── BidStatus.php         # open/closed/awarded
│       ├── DocumentType.php      # hwp/hwpx/pdf
│       └── ClassificationCode.php # 78111808 등
│
├── resources/
│   └── js/
│       ├── Pages/
│       │   ├── Dashboard.vue
│       │   ├── Bids/
│       │   │   ├── Index.vue          # 공고 목록 (필터/정렬)
│       │   │   ├── Show.vue           # 공고 상세 + 분석 카드
│       │   │   ├── Checklist.vue      # 서류 체크리스트
│       │   │   └── Simulation.vue     # 적격심사 시뮬레이션
│       │   ├── Company/
│       │   │   ├── Profile.vue        # 회사 정보
│       │   │   ├── Fleet.vue          # 보유 차종
│       │   │   └── Preferences.vue    # 알림 설정
│       │   └── Simulation/
│       │       └── Show.vue           # 적격심사 시뮬레이션
│       │
│       └── Components/
│           ├── BidCard.vue            # 공고 요약 카드
│           ├── BidFilter.vue          # 필터 사이드바
│           ├── VehicleInfo.vue        # 차량 정보 표시
│           ├── ContractInfo.vue       # 계약 조건 표시
│           ├── InsuranceInfo.vue      # 보험 조건 표시
│           ├── QualificationBadge.vue # 자격 충족 여부 뱃지
│           ├── MatchScore.vue         # 매칭 점수 게이지
│           ├── ScoreSimulator.vue     # 점수 시뮬레이터
│           └── ChecklistItem.vue      # 체크리스트 아이템
│
├── scripts/
│   ├── hwpx-parser.ts         # hwpx-ts 기반 파서 (Node.js)
│   └── hwp-parser.py          # olefile 기반 파서 (Python)
│
├── database/
│   └── migrations/
│       ├── create_bids_table.php
│       ├── create_bid_documents_table.php
│       ├── create_bid_vehicles_table.php
│       ├── create_bid_contracts_table.php
│       ├── create_bid_insurance_table.php
│       ├── create_bid_qualifications_table.php
│       ├── create_bid_performances_table.php
│       ├── create_bid_financials_table.php
│       ├── create_bid_credibilities_table.php
│       ├── create_bid_checklists_table.php
│       ├── create_companies_table.php
│       ├── create_company_preferences_table.php
│       ├── create_company_fleets_table.php
│       └── create_company_credentials_table.php
│
├── config/
│   ├── gar.php                # GAR 설정 (API키, 분류코드, 제외키워드)
│   └── ai.php                 # AI 프롬프트 설정
│
├── routes/
│   └── web.php
│
├── tests/
│   ├── Unit/
│   │   ├── G2BClientTest.php
│   │   ├── G2BCollectorTest.php
│   │   ├── DocumentParserTest.php
│   │   ├── BidAnalyzerTest.php
│   │   ├── BidMatcherTest.php
│   │   └── ScoreCalculatorTest.php
│   └── Feature/
│       ├── BidCollectionTest.php
│       ├── DocumentAnalysisTest.php
│       ├── BidFilterTest.php
│       ├── MatchingTest.php
│       ├── SimulationTest.php
│       └── NotificationTest.php
│
└── docs/
    ├── PLANNING.md
    ├── PROMPTS.md
    └── ARCHITECTURE.md
```

## 핵심 플로우

### Flow 1: 공고 수집 (매시간)

```
[Scheduler] schedule:run (매 1시간, 평일 08~18시)
    │
    ▼
[CollectBidsCommand]
    │
    ├─ G2BClient::fetchServiceBids()     # 용역 공고
    ├─ G2BClient::fetchGoodsBids()       # 물품 공고
    └─ G2BClient::fetchEtcBids()         # 기타 공고
    │
    ▼
[G2BCollector::filter()]
    │
    ├─ 1차: 분류코드 필터 (78111808 ✅, 73169001 ✅)
    ├─ 2차: 78111899 → 제외키워드 체크
    ├─ 3차: 중복 체크 (bidNtceNo)
    └─ 저장: Bid::updateOrCreate()
    │
    ▼
[DownloadDocumentJob] (Queue, 공고당 최대 10개)
    │
    ├─ curl + Referer: https://www.g2b.go.kr/
    ├─ 파일 저장 (storage/app/bid-documents/{bidId}/)
    └─ BidDocument 레코드 생성
    │
    ▼
[AnalyzeDocumentJob] (Queue)
    │
    ├─ DocumentParserFactory::parse()
    │   ├─ .hwpx → HwpxParser (Node subprocess)
    │   ├─ .hwp  → HwpParser (Python subprocess)
    │   └─ .pdf  → PdfParser
    │
    ├─ BidAnalyzer::analyze(extractedText)
    │   ├─ AI 프롬프트 호출 (마스터 분석)
    │   ├─ JSON 파싱 + 검증
    │   └─ 각 테이블에 저장
    │       ├─ BidVehicle::create()
    │       ├─ BidContract::create()
    │       ├─ BidInsurance::create()
    │       ├─ BidQualification::create()
    │       └─ BidChecklist::create()
    │
    └─ MatchBidsJob::dispatch()
```

### Flow 2: 매칭 + 알림

```
[MatchBidsJob]
    │
    ▼
[BidMatcher::matchAll(bid)]
    │
    ├─ Company::with('preferences', 'fleet')->get()
    │
    ├─ 각 렌트사별 매칭 스코어 계산
    │   ├─ 지역 매칭 (30점)
    │   │   └─ company.region ∩ bid.region_limit
    │   ├─ 차종 매칭 (25점)
    │   │   └─ company.fleet ∩ bid.vehicles
    │   ├─ 규모 매칭 (20점)
    │   │   └─ bid.budget ∈ [pref.budget_min, pref.budget_max]
    │   ├─ 자격 매칭 (15점)
    │   │   └─ company.size ∈ bid.company_size_limit
    │   └─ 기간 매칭 (10점)
    │       └─ bid.period_months ∈ [pref.months_min, pref.months_max]
    │
    └─ score ≥ 70 → SendNotificationJob::dispatch()
```

### Flow 3: 적격심사 시뮬레이션

```
[SimulationEngine::simulate(company, bid)]
    │
    ├─ 이행실적 점수 (0~10)
    │   ├─ company.totalPerformanceAmount / bid.estimatedPrice
    │   └─ 100% 이상 → 10점, 70%→8점, 50%→6점 ...
    │
    ├─ 경영상태 점수 (0~20)
    │   └─ company.creditGrade → 별표10 매핑
    │
    ├─ 사후관리 점수 (0~15)
    │   ├─ 전국 정비네트워크 보유?
    │   ├─ 대차 가능 차량 보유?
    │   └─ A/S 조치기한 충족?
    │
    ├─ 입찰가격 점수 (0~55)
    │   └─ 45 = 60 - 2 × |88/100 - 투찰률| × 100
    │       (투찰률 95.5% 이상이면 45점 고정)
    │
    ├─ 신인도 가감점 (+4.25 ~ -5.0)
    │   ├─ ISO 인증? → +1.0
    │   ├─ 여성/장애인/사회적기업? → +0.75
    │   └─ 부정당업자 이력? → -2.0
    │
    └─ 합계 → 85점 이상? → "합격 가능"
```

## 설정 파일

### config/gar.php

```php
<?php

return [
    'g2b' => [
        'base_url' => 'https://apis.data.go.kr/1230000/ad/BidPublicInfoService/',
        'service_key' => env('G2B_SERVICE_KEY'),
        'endpoints' => [
            'service' => 'getBidPblancListInfoServcPPSSrch',
            'goods'   => 'getBidPblancListInfoThngPPSSrch',
            'etc'     => 'getBidPblancListInfoEtcPPSSrch',
        ],
    ],

    'filter' => [
        // 1차 필터: 분류코드
        'target_codes' => [
            '78111808', // 자동차렌트서비스 (핵심)
            '73169001', // 운송장비임대서비스
        ],
        'secondary_codes' => [
            '78111899', // 도로여객운송서비스 (2차 필터 필요)
        ],

        // 2차 필터: 제외 키워드
        'exclude_keywords' => [
            '통학', '수학여행', '현장체험', '수련활동',
            '견학', '소풍', '야영', '캠프',
        ],
        'secondary_exclude_keywords' => [
            '수송', '수련', '체험활동',
        ],

        // 포함 보조 키워드 (secondary_codes에서 추가 필터)
        'include_keywords' => [
            '렌트', '임차', '임대', '리스',
        ],
    ],

    'collect' => [
        'schedule' => '0 * * * 1-5',  // 평일 매시간
        'max_pages' => 10,
        'per_page' => 100,
    ],

    'document' => [
        'download_referer' => 'https://www.g2b.go.kr/',
        'storage_path' => 'bid-documents',
        'max_file_size_mb' => 50,
        'supported_types' => ['hwp', 'hwpx', 'pdf'],
    ],

    'analysis' => [
        'model' => env('AI_MODEL', 'anthropic/claude-sonnet-4-20250514'),
        'max_text_length' => 50000,
        'timeout_seconds' => 60,
    ],

    'matching' => [
        'min_score' => 70,  // 최소 매칭 점수
        'weights' => [
            'region'   => 30,
            'vehicle'  => 25,
            'budget'   => 20,
            'qualify'  => 15,
            'period'   => 10,
        ],
    ],

    'notification' => [
        'channels' => ['telegram', 'email'],
        'quiet_hours' => ['23:00', '08:00'],
        'deadline_alerts' => [3, 1],  // D-3, D-1 알림
    ],
];
```

## 스케줄러

### app/Console/Kernel.php

```php
protected function schedule(Schedule $schedule): void
{
    // 공고 수집: 평일 매시간
    $schedule->command('gar:collect-bids')
        ->hourly()
        ->weekdays()
        ->between('8:00', '18:00')
        ->withoutOverlapping();

    // 마감 임박 알림: 매일 09:00
    $schedule->command('gar:notify-deadlines')
        ->dailyAt('09:00');

    // 일일 다이제스트: 매일 18:00
    $schedule->command('gar:send-digest')
        ->dailyAt('18:00');

    // 낙찰 결과 추적: 매일 10:00
    $schedule->command('gar:track-results')
        ->dailyAt('10:00');
}
```

## 외부 파서 스크립트

### scripts/hwpx-parser.ts

```typescript
// Node.js 스크립트 — Laravel에서 subprocess로 호출
import { HwpxDocument } from "@ubermensch1218/hwpxcore";
import { readFileSync, writeFileSync } from "fs";

const inputPath = process.argv[2];
const outputPath = process.argv[3];

async function parse() {
  const buffer = readFileSync(inputPath);
  const doc = await HwpxDocument.open(new Uint8Array(buffer));

  const result = {
    text: doc.text,
    paragraphs: doc.paragraphs.map(p => p.text),
    tables: doc.tables.map(t => ({
      rows: t.rowCount,
      cols: t.colCount,
      cells: t.iterGrid().map(cell => ({
        row: cell.row,
        col: cell.col,
        text: cell.text,
      })),
    })),
  };

  writeFileSync(outputPath, JSON.stringify(result, null, 2));
}

parse().catch(console.error);
```

### scripts/hwp-parser.py

```python
# Python 스크립트 — Laravel에서 subprocess로 호출
import sys, json, olefile, zlib, struct

def extract_hwp_text(filepath):
    f = olefile.OleFileIO(filepath)
    texts = []
    for entry in f.listdir():
        entry_path = "/".join(entry)
        if entry_path.startswith("BodyText/Section"):
            data = f.openstream(entry_path).read()
            try:
                decompressed = zlib.decompress(data, -15)
            except:
                decompressed = data
            text = ""
            i = 0
            while i < len(decompressed):
                if i + 4 > len(decompressed): break
                header = struct.unpack_from("<I", decompressed, i)[0]
                rec_type = header & 0x3FF
                rec_size = (header >> 20) & 0xFFF
                i += 4
                if rec_type == 67:  # HWPTAG_PARA_TEXT
                    j = 0
                    while j < rec_size:
                        if j + 2 > rec_size: break
                        ch = struct.unpack_from("<H", decompressed, i + j)[0]
                        if ch == 0: break
                        elif ch < 32:
                            if ch in (10, 13): text += "\n"
                            j += 2
                        else:
                            text += chr(ch)
                            j += 2
                i += rec_size
            texts.append(text)
    f.close()
    return "\n".join(texts)

input_path = sys.argv[1]
output_path = sys.argv[2]

text = extract_hwp_text(input_path)
with open(output_path, 'w', encoding='utf-8') as f:
    json.dump({"text": text}, f, ensure_ascii=False, indent=2)
```
