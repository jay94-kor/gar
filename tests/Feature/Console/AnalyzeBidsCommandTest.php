<?php

namespace Tests\Feature\Console;

use App\Enums\BidStatus;
use App\Enums\DocumentType;
use App\Models\Bid;
use App\Models\BidDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalyzeBidsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_parsed_documents_to_openclaw_and_persists_analysis(): void
    {
        config()->set('analysis.openclaw.base_url', 'http://openclaw.test');
        config()->set('analysis.openclaw.endpoint', '/api/analyze-bid');
        config()->set('analysis.openclaw.api_key', 'secret-key');

        Http::fake([
            'http://openclaw.test/api/analyze-bid' => Http::response([
                'meta' => [
                    'schema_version' => 'v1',
                    'prompt_version' => 'analysis-master-v1',
                    'model_name' => 'openclaw-local',
                    'analysis_confidence' => 0.91,
                ],
                'summary' => [
                    'title' => '핵심 요약',
                    'key_points' => ['신차 3대 필요', '사업용 등록 필수'],
                ],
                'vehicles' => [[
                    'manufacturer' => '현대',
                    'model' => '그랜저',
                    'trim' => '익스클루시브',
                    'fuel_type' => 'gasoline',
                    'seats' => 5,
                    'quantity' => 3,
                    'year_condition' => '신차',
                    'color_exterior' => null,
                    'color_interior' => null,
                    'options' => ['블랙박스'],
                ]],
                'procurement' => [
                    'vehicle_condition' => 'new_only',
                    'year_threshold' => 2026,
                    'registration_requirement' => true,
                    'funding_implication' => 'purchase_required',
                ],
                'contract' => [
                    'period_months' => 36,
                    'prepayment_rate' => 10,
                    'prepayment_amount' => 5000000,
                    'deposit' => 0,
                    'annual_mileage_km' => 30000,
                    'residual_value_rate' => 45,
                    'opening_fee' => 0,
                    'payment_method' => '월후불',
                    'delivery_deadline' => '계약 후 30일',
                    'delivery_location' => '서울',
                ],
                'insurance' => [
                    'liability_1' => '자배법',
                    'liability_2' => '무한',
                    'property_damage' => 300000000,
                    'own_vehicle' => true,
                    'own_vehicle_deductible' => 300000,
                    'personal_injury' => '1억원',
                    'uninsured_motorist' => '2억원',
                    'driver_age_min' => 26,
                    'driver_scope' => '임직원한정',
                    'emergency_service' => '기본',
                    'special_coverage' => null,
                ],
                'qualification' => [
                    'biz_type_code' => '1457',
                    'biz_type_name' => '자동차대여사업',
                    'region_limit' => ['서울', '경기'],
                    'company_size_limit' => 'small',
                    'joint_contract_allowed' => false,
                    'subcontract_allowed' => false,
                    'branch_requirement' => '수도권',
                    'other_requirements' => ['사업용 등록 차량'],
                ],
                'evaluation' => [
                    'method' => 'competitive',
                    'standard' => '별표8',
                    'success_threshold_rate' => 84.245,
                    'passing_score' => 85,
                    'price_basis' => '총액',
                    'preliminary_prices_count' => 15,
                    'preliminary_prices_range' => '±2%',
                ],
                'performance_requirement' => [
                    'type' => 'similar',
                    'scope' => '차량 장기임차',
                    'years' => 3,
                    'min_amount' => 100000000,
                    'min_count' => 1,
                ],
                'required_documents' => [
                    'bid_stage' => ['사업자등록증'],
                    'screening_stage' => ['이행실적증명서'],
                    'contract_stage' => ['계약보증서'],
                ],
                'special_conditions' => [
                    'replacement_vehicle' => '동급 대차',
                    'maintenance_cycle' => '분기 1회',
                    'snow_tire' => false,
                    'snow_chain' => false,
                    'blackbox' => true,
                    'tinting' => true,
                    'safety_equipment' => '소화기',
                    'defect_replacement' => '3회 고장시 교체',
                    'early_termination_penalty' => null,
                    'return_condition' => null,
                    'other' => ['세차 포함'],
                ],
            ]),
        ]);

        $bid = Bid::query()->create([
            'bid_ntce_no' => 'R26BK01111111',
            'bid_ntce_ord' => '000',
            'title' => '업무용 차량 임차 용역',
            'category' => 'service',
            'classification_code' => '78111808',
            'budget' => 120000000,
            'status' => BidStatus::Closed,
            'pipeline_status' => 'documents_ready',
        ]);

        BidDocument::query()->create([
            'bid_id' => $bid->id,
            'seq' => 1,
            'filename' => 'notice.pdf',
            'url' => 'https://example.test/notice.pdf',
            'file_type' => DocumentType::Pdf,
            'file_path' => 'bid-documents/1/notice.pdf',
            'extracted_text' => '신차 3대, 사업용 등록 필수, 계약기간 36개월',
            'status' => 'parsed',
        ]);

        $this->artisan('bids:analyze', [
            '--bid' => [$bid->bid_ntce_no],
        ])->assertExitCode(0);

        $this->assertDatabaseHas('bid_analyses', [
            'bid_id' => $bid->id,
            'status' => 'validated',
            'schema_version' => 'v1',
            'prompt_version' => 'analysis-master-v1',
            'model_name' => 'openclaw-local',
            'analysis_version' => 1,
        ]);

        $this->assertDatabaseHas('bid_vehicles', [
            'bid_id' => $bid->id,
            'seq' => 1,
            'model' => '그랜저',
            'quantity' => 3,
        ]);

        $this->assertDatabaseHas('bid_contracts', [
            'bid_id' => $bid->id,
            'vehicle_condition' => 'new_only',
            'funding_implication' => 'purchase_required',
            'contract_months' => 36,
        ]);

        $this->assertDatabaseHas('bid_checklists', [
            'bid_id' => $bid->id,
            'stage' => 'screening_stage',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://openclaw.test/api/analyze-bid'
                && $request->hasHeader('Authorization', 'Bearer secret-key')
                && str_contains((string) $request->data()['combined_text'], '신차 3대');
        });
    }
}
