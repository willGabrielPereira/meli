<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\ProcessProduct;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SearchProductsTest extends TestCase
{
    public function test_command_dispatches_jobs_for_fetched_products(): void
    {
        Queue::fake();

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/sellers/')) {
                return Http::response([
                    'inactive_token' => false,
                    'access_token' => 'valid-token-from-tray'
                ], 200);
            }

            if (str_contains($request->url(), '/sites/MLB/search')) {
                // Return first page
                if (str_contains($request->url(), 'offset=0')) {
                    return Http::response([
                        'results' => [
                            'MLB1',
                            'MLB2',
                        ],
                        'paging' => [
                            'total' => 3,
                            'limit' => 2,
                            'offset' => 0
                        ]
                    ], 200);
                }

                // Return second page
                if (str_contains($request->url(), 'offset=2')) {
                    return Http::response([
                        'results' => [
                            'MLB3',
                        ],
                        'paging' => [
                            'total' => 3,
                            'limit' => 2,
                            'offset' => 2
                        ]
                    ], 200);
                }
            }

            return Http::response([], 404);
        });

        $this->artisan('app:search-products', ['seller' => 12345])
            ->expectsOutput("Foram enviados 2 produtos para a fila (Offset atual: 0).")
            ->expectsOutput("Foram enviados 1 produtos para a fila (Offset atual: 2).")
            ->assertExitCode(0);

        Queue::assertPushed(ProcessProduct::class, 3);

        Queue::assertPushed(ProcessProduct::class, function ($job) {
            return in_array($job->productId, ['MLB1', 'MLB2', 'MLB3']) &&
                $job->seller == 12345 &&
                $job->token === 'valid-token-from-tray';
        });
    }
}
