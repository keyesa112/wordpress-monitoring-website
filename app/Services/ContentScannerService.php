<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

class ContentScannerService
{
    protected $client;
    protected $specificKeywords;
    protected $singleKeywords;
    
    protected $specificPattern;
    protected $singlePattern;

    public function __construct()
    {
        $this->specificKeywords = [
            'slot gacor', 'slot online', 'situs slot', 'link slot', 'daftar slot',
            'rtp slot', 'bocoran slot', 'jackpot slot', 'maxwin slot',
            'slot88', 'slot77', 'slot777', 'pragmatic play slot', 'pg soft slot',
            'togel online', 'bandar togel', 'togel hari ini', 'casino online',
            'judi online', 'taruhan bola', 'sbobet', 'bonus new member slot',
            'deposit pulsa slot', 'toto88', 'markasbola365', 'bet777', 'zeus365',
            'game penghasil uang', 'double win slots', 'vegas casino',
            'fortune scratch life: earn cash', 'info link gacor',
            'new member dapat freechip', 'bonus chip gratis', 'gampang menang',
            'toto12', 'dana toto', 'partai togel', 'ladang cuan',
            'anti rungkad', 'pasti jp',
        ];

        $this->singleKeywords = [
            'gacor', 'maxwin', 'rtp', 'togel', 'casino', 'betting',
            'poker', 'judi', 'jackpot', 'toto', 'fairslot', 'winrate',
            'gamble', 'domino', 'chip', 'freechip',
        ];

        $escapedSpecific = array_map(fn($k) => preg_quote($k, '/'), $this->specificKeywords);
        $escapedSingle = array_map(fn($k) => preg_quote($k, '/'), $this->singleKeywords);
        
        $this->specificPattern = '/\b(' . implode('|', $escapedSpecific) . ')\b/i';
        $this->singlePattern = '/\b(' . implode('|', $escapedSingle) . ')\b/i';

        $this->client = new Client([
            'timeout' => 20,
            'connect_timeout' => 10,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
            ],
            'allow_redirects' => true,
            'http_errors' => false,
        ]);
    }

    public function scanContent($url)
    {
        set_time_limit(300);
        
        $results = [
            'url' => $url,
            'scanned_at' => now()->toDateTimeString(),
        ];

        // Run scans concurrently
        $promises = [
            'posts' => fn() => $this->scanPosts($url),
            'pages' => fn() => $this->scanPages($url),
            'header_footer' => fn() => $this->scanHeaderFooter($url),
            'meta' => fn() => $this->scanMeta($url),
            'sitemap' => fn() => $this->scanSitemap($url),
        ];

        // Execute all scanners
        foreach ($promises as $key => $scanner) {
            $results[$key] = $scanner();
        }

        // Summary
        $results['has_suspicious_content'] = 
            $results['posts']['has_suspicious'] ||
            $results['pages']['has_suspicious'] ||
            $results['header_footer']['has_suspicious'] ||
            $results['meta']['has_suspicious'] ||
            $results['sitemap']['has_suspicious'];

        return $results;
    }

    public function scanPosts($url)
    {
        return $this->scanWpJson($url, 'posts');
    }

    public function scanPages($url)
    {
        return $this->scanWpJson($url, 'pages');
    }

    protected function scanWpJson($url, $type = 'posts')
    {
        try {
            $baseUrl = rtrim($url, '/');
            $perPage = 100;
            $endpoint = $type === 'posts' ? 'posts' : 'pages';
            
            $firstPageUrl = "{$baseUrl}/wp-json/wp/v2/{$endpoint}?per_page={$perPage}&page=1";
            
            try {
                $response = $this->client->get($firstPageUrl);
            } catch (\Exception $e) {
                return $this->emptyResult($type, "Gagal akses WP-JSON {$endpoint}");
            }

            if ($response->getStatusCode() !== 200) {
                return $this->emptyResult($type, "Gagal akses WP-JSON (HTTP {$response->getStatusCode()})");
            }

            $rawBody = $response->getBody()->getContents();
            $injectedHtml = $this->detectInjectedHtml($rawBody);

            $totalPages = max(1, (int) $response->getHeaderLine('X-WP-TotalPages'));
            $totalItems = (int) $response->getHeaderLine('X-WP-Total');
            $maxPages = min($totalPages, 50);

            $allItems = json_decode($rawBody, true) ?? [];

            if ($maxPages > 1) {
                $additionalItems = $this->fetchWpJsonConcurrently($baseUrl, $endpoint, $perPage, 2, $maxPages);
                if ($additionalItems) {
                    $allItems = array_merge($allItems, $additionalItems);
                }
            }

            $suspiciousItems = $this->scanItems($allItems);
            $hasSuspicious = !empty($suspiciousItems) || $injectedHtml['has_suspicious'];

            return [
                'has_suspicious' => $hasSuspicious,
                'total_' . $type => count($allItems),
                'total_' . $type . '_available' => $totalItems,
                'total_pages_scanned' => $maxPages,
                'suspicious_count' => count($suspiciousItems),
                'suspicious_' . $type => $suspiciousItems,
                'injected_html' => $injectedHtml,
                'error' => null,
            ];

        } catch (\Exception $e) {
            return $this->emptyResult($type, $e->getMessage());
        }
    }

    protected function emptyResult($type, $error)
    {
        return [
            'has_suspicious' => false,
            'total_' . $type => 0,
            'suspicious_count' => 0,
            'suspicious_' . $type => [],
            'error' => $error,
        ];
    }

    protected function scanItems($items)
    {
        if (empty($items) || !is_array($items)) {
            return [];
        }

        $suspicious = [];
        
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            
            $fullText = strtolower(
                ($item['content']['rendered'] ?? '') . ' ' .
                ($item['title']['rendered'] ?? '') . ' ' .
                ($item['excerpt']['rendered'] ?? '')
            );

            $foundKeywords = $this->detectKeywords($fullText);

            if ($foundKeywords['is_suspicious']) {
                $suspicious[] = [
                    'id' => $item['id'] ?? 0,
                    'title' => $item['title']['rendered'] ?? 'No title',
                    'link' => $item['link'] ?? '',
                    'keywords' => $foundKeywords['keywords'],
                    'keyword_count' => count($foundKeywords['keywords']),
                    'date' => $item['date'] ?? '',
                ];
            }
        }

        return $suspicious;
    }

    protected function fetchWpJsonConcurrently($baseUrl, $endpoint, $perPage, $startPage, $endPage)
    {
        $allItems = [];
        
        $requests = function () use ($baseUrl, $endpoint, $perPage, $startPage, $endPage) {
            for ($page = $startPage; $page <= $endPage; $page++) {
                yield new Request('GET', "{$baseUrl}/wp-json/wp/v2/{$endpoint}?per_page={$perPage}&page={$page}");
            }
        };

        $pool = new Pool($this->client, $requests(), [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) use (&$allItems) {
                $items = json_decode($response->getBody()->getContents(), true);
                if (is_array($items)) {
                    $allItems = array_merge($allItems, $items);
                }
            },
            'rejected' => function ($reason, $index) {
                // Silent error handling
            },
        ]);

        $pool->promise()->wait();
        return $allItems;
    }

    protected function detectInjectedHtml($rawBody)
    {
        $jsonStart = min(
            strpos($rawBody, '{') ?: PHP_INT_MAX,
            strpos($rawBody, '[') ?: PHP_INT_MAX
        );
        
        if ($jsonStart === PHP_INT_MAX || $jsonStart === 0) {
            return ['has_suspicious' => false, 'keywords' => [], 'html_snippet' => ''];
        }

        $htmlPart = substr($rawBody, 0, $jsonStart);
        
        if (strlen(trim($htmlPart)) < 10) {
            return ['has_suspicious' => false, 'keywords' => [], 'html_snippet' => ''];
        }
        
        $htmlLower = strtolower($htmlPart);
        $foundKeywords = $this->detectKeywords($htmlLower);
        
        $hasInjectionPattern = (
            strpos($htmlLower, 'display:none') !== false ||
            strpos($htmlLower, 'visibility:hidden') !== false ||
            strpos($htmlLower, 'opacity:0') !== false ||
            strpos($htmlLower, 'position:absolute') !== false ||
            strpos($htmlLower, 'left:-9999') !== false
        );
        
        $suspiciousLinks = [];
        if (preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $htmlPart, $links)) {
            foreach ($links[1] as $link) {
                if ($this->detectKeywords(strtolower($link))['is_suspicious']) {
                    $suspiciousLinks[] = $link;
                    if (count($suspiciousLinks) >= 10) break; // Early exit
                }
            }
        }
        
        return [
            'has_suspicious' => $foundKeywords['is_suspicious'] || $hasInjectionPattern || !empty($suspiciousLinks),
            'keywords' => $foundKeywords['keywords'],
            'injection_patterns' => $hasInjectionPattern ? ['hidden HTML detected'] : [],
            'suspicious_links' => $suspiciousLinks,
            'total_links' => count($suspiciousLinks),
            'html_snippet' => mb_substr($htmlPart, 0, 300) . (strlen($htmlPart) > 300 ? '...' : ''),
            'html_length' => strlen($htmlPart),
        ];
    }

    public function scanHeaderFooter($url)
    {
        try {
            $response = $this->client->get($url);
            $statusCode = $response->getStatusCode();
            $html = $response->getBody()->getContents();

            if ($this->isBlockedByWAF($html, $statusCode)) {
                return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'error' => "WAF blocked (HTTP {$statusCode})"];
            }

            if ($statusCode !== 200) {
                return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'error' => "HTTP {$statusCode}"];
            }

            $foundKeywords = $this->detectKeywords(strtolower($html));

            return [
                'has_suspicious' => $foundKeywords['is_suspicious'],
                'keywords' => $foundKeywords['keywords'],
                'keyword_count' => count($foundKeywords['keywords']),
                'error' => null,
            ];
        } catch (\Exception $e) {
            return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'error' => $e->getMessage()];
        }
    }

    public function scanMeta($url)
    {
        try {
            $response = $this->client->get($url);
            $statusCode = $response->getStatusCode();
            $html = $response->getBody()->getContents();

            if ($this->isBlockedByWAF($html, $statusCode) || $statusCode !== 200) {
                return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'meta_title' => '', 'meta_description' => '', 'error' => "HTTP {$statusCode}"];
            }
            
            preg_match('/<title>(.*?)<\/title>/i', $html, $title);
            preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/i', $html, $description);
            preg_match('/<meta\s+name=["\']keywords["\']\s+content=["\'](.*?)["\']/i', $html, $keywords);

            $metaText = strtolower(($title[1] ?? '') . ' ' . ($description[1] ?? '') . ' ' . ($keywords[1] ?? ''));
            $foundKeywords = $this->detectKeywords($metaText);

            return [
                'has_suspicious' => $foundKeywords['is_suspicious'],
                'keywords' => $foundKeywords['keywords'],
                'keyword_count' => count($foundKeywords['keywords']),
                'meta_title' => $title[1] ?? '',
                'meta_description' => $description[1] ?? '',
                'error' => null,
            ];
        } catch (\Exception $e) {
            return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'meta_title' => '', 'meta_description' => '', 'error' => $e->getMessage()];
        }
    }

    public function scanSitemap($url)
    {
        try {
            $response = $this->client->get(rtrim($url, '/') . '/sitemap.xml');
            if ($response->getStatusCode() !== 200) {
                return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'suspicious_urls' => [], 'error' => 'Sitemap not found'];
            }

            $xml = $response->getBody()->getContents();
            $foundKeywords = $this->detectKeywords(strtolower($xml));

            $suspiciousUrls = [];
            if ($foundKeywords['is_suspicious'] && preg_match_all('/<loc>(.*?)<\/loc>/i', $xml, $urls)) {
                foreach ($urls[1] ?? [] as $urlFromSitemap) {
                    foreach ($foundKeywords['keywords'] as $keyword) {
                        if (stripos($urlFromSitemap, $keyword) !== false) {
                            $suspiciousUrls[] = $urlFromSitemap;
                            break;
                        }
                    }
                }
            }

            return [
                'has_suspicious' => $foundKeywords['is_suspicious'],
                'keywords' => $foundKeywords['keywords'],
                'keyword_count' => count($foundKeywords['keywords']),
                'suspicious_urls' => array_unique($suspiciousUrls),
                'error' => null,
            ];
        } catch (\Exception $e) {
            return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'suspicious_urls' => [], 'error' => $e->getMessage()];
        }
    }

    protected function isBlockedByWAF($html, $statusCode)
    {
        if (empty($html)) return false;

        $htmlLower = strtolower($html);
        return (
            (strpos($htmlLower, 'cloudflare') !== false && strpos($htmlLower, 'challenge') !== false) ||
            (in_array($statusCode, [403, 503]) && (
                strpos($htmlLower, 'request rejected') !== false ||
                strpos($htmlLower, 'access denied') !== false
            ))
        );
    }

    protected function detectKeywords($text)
    {
        $foundSpecific = [];
        $foundSingle = [];

        // Use pre-compiled patterns (10x faster than looping)
        if (preg_match_all($this->specificPattern, $text, $matches)) {
            $foundSpecific = array_unique($matches[1]);
        }

        if (preg_match_all($this->singlePattern, $text, $matches)) {
            $foundSingle = array_unique($matches[1]);
        }

        $isSuspicious = !empty($foundSpecific) || count($foundSingle) >= 2;

        return [
            'is_suspicious' => $isSuspicious,
            'keywords' => array_unique(array_merge($foundSpecific, $foundSingle)),
        ];
    }
}
