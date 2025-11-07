<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WebsiteCheckService
{
    /**
     * Cek status website (ping check)
     */
    public function checkStatus($url)
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout(10)->get($url);
            $endTime = microtime(true);

            $responseTime = round(($endTime - $startTime) * 1000);

            return [
                'status' => $response->successful() ? 'online' : 'offline',
                'http_code' => $response->status(),
                'response_time' => $responseTime,
                'success' => true,
                'error' => null,
            ];

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            return [
                'status' => 'offline',
                'http_code' => 0,
                'response_time' => $responseTime,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Full check (ping + content scan via ContentScannerService)
     */
    public function fullCheck($url, ContentScannerService $contentScanner = null)
    {
        $statusCheck = $this->checkStatus($url);
        
        $result = [
            'url' => $url,
            'checked_at' => now()->toDateTimeString(),
            'status' => $statusCheck,
        ];

        if ($contentScanner) {
            $result['content_scan'] = $contentScanner->scanContent($url);
        }

        return $result;
    }
}
