<?php

namespace Tests\Feature\Console;

use App\Enums\BidStatus;
use App\Models\Bid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class SyncBidDocumentsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_bid_documents_downloads_files_and_extracts_text(): void
    {
        Storage::fake('local');

        config()->set('g2b.bid_public.service_key', 'test-service-key');
        config()->set('g2b.documents.disk', 'local');

        $bid = Bid::query()->create([
            'bid_ntce_no' => 'R26BK09999999',
            'bid_ntce_ord' => '000',
            'title' => '업무용 차량 임차 용역',
            'category' => 'service',
            'classification_code' => '78111808',
            'budget' => 50000000,
            'bid_close_dt' => now()->subDays(5),
            'status' => BidStatus::Closed,
            'pipeline_status' => 'persisted',
        ]);

        Http::fake([
            'https://apis.data.go.kr/1230000/ad/BidPublicInfoService/*' => Http::response([
                'response' => [
                    'header' => [
                        'resultCode' => '00',
                        'resultMsg' => '정상',
                    ],
                    'body' => [
                        'totalCount' => 1,
                        'items' => [[
                            'bidNtceNo' => 'R26BK09999999',
                            'bidNtceOrd' => '000',
                            'bidNtceNm' => '업무용 차량 임차 용역',
                            'ntceSpecFileNm1' => 'spec.hwpx',
                            'ntceSpecDocUrl1' => 'https://www.g2b.go.kr/files/spec.hwpx',
                            'stdNtceDocUrl' => 'https://www.g2b.go.kr/files/spec.hwpx',
                        ]],
                    ],
                ],
            ]),
            'https://www.g2b.go.kr/*' => Http::response(
                $this->createHwpxBinary('<hp:p xmlns:hp="http://www.hancom.co.kr/hwpml/2011/paragraph">신차 3대 납품 조건</hp:p>'),
                200,
                ['Content-Type' => 'application/octet-stream']
            ),
        ]);

        $this->artisan('g2b:sync-bid-documents', [
            '--bid' => [$bid->bid_ntce_no],
            '--force-sync' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('bid_documents', [
            'bid_id' => $bid->id,
            'seq' => 1,
            'filename' => 'spec.hwpx',
            'status' => 'parsed',
        ]);

        $document = $bid->fresh()->documents()->firstOrFail();

        $this->assertSame('신차 3대 납품 조건', $document->extracted_text);
        Storage::disk('local')->assertExists($document->file_path);
    }

    protected function createHwpxBinary(string $xml): string
    {
        $path = tempnam(sys_get_temp_dir(), 'hwpx-test-');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('Contents/section0.xml', $xml);
        $zip->close();

        $contents = file_get_contents($path);
        @unlink($path);

        return $contents ?: '';
    }
}
