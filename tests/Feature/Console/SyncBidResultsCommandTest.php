<?php

namespace Tests\Feature\Console;

use App\Enums\BidStatus;
use App\Models\Bid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncBidResultsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_awarded_bid_results_into_the_database(): void
    {
        config()->set('g2b.bid_result.service_key', 'test-service-key');

        Http::fake([
            'https://apis.data.go.kr/1230000/as/ScsbidInfoService/*' => Http::response([
                'response' => [
                    'header' => [
                        'resultCode' => '00',
                        'resultMsg' => 'NORMAL SERVICE.',
                    ],
                    'body' => [
                        'totalCount' => 1,
                        'items' => [
                            'item' => [
                                'bidNtceNo' => 'R25BK01181722',
                                'bidNtceOrd' => '000',
                                'bidNtceNm' => '업무용 차량 임차',
                                'sucsfbidAmt' => '1255000000',
                                'sucsfbidRate' => '87.045',
                                'prtcptCnum' => '5',
                                'bidwinnrNm' => '롯데렌탈',
                                'bidwinnrBizno' => '123-45-67890',
                                'fnlSucsfDate' => '2026-03-10',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $bid = Bid::query()->create([
            'bid_ntce_no' => 'R25BK01181722',
            'bid_ntce_ord' => '000',
            'title' => '업무용 차량 임차',
            'category' => 'service',
            'classification_code' => '78111808',
            'budget' => 1306000000,
            'bid_close_dt' => now()->subDays(10),
            'status' => BidStatus::Closed,
            'pipeline_status' => 'persisted',
        ]);

        $this->artisan('g2b:sync-bid-results', [
            '--bid' => [$bid->bid_ntce_no],
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('bid_results', [
            'bid_id' => $bid->id,
            'result_status' => 'awarded',
            'awarded_company' => '롯데렌탈',
            'awarded_amount' => 1255000000,
            'award_dt' => '2026-03-10 00:00:00',
        ]);

        $this->assertDatabaseHas('bid_result_rankings', [
            'company_name' => '롯데렌탈',
            'rank' => 1,
            'is_winner' => 1,
        ]);

        $result = $bid->fresh()->result;

        $this->assertArrayNotHasKey('serviceKey', $result->raw_data['request']);
        $this->assertSame(BidStatus::Awarded, $bid->fresh()->status);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://apis.data.go.kr/1230000/as/ScsbidInfoService/getScsbidListSttusServc')
                && $request['serviceKey'] === 'test-service-key'
                && $request['bidNtceNo'] === 'R25BK01181722'
                && $request['bidNtceOrd'] === '000'
                && $request['inqryDiv'] === '4';
        });
    }
}
