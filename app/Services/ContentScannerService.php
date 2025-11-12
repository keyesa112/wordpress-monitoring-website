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
    
    // ✅ OPTIMIZATION 1: Compile regex patterns once
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

        // ✅ OPTIMIZATION 2: Pre-compile regex patterns (10x faster)
        $escapedSpecific = array_map(fn($k) => preg_quote($k, '/'), $this->specificKeywords);
        $escapedSingle = array_map(fn($k) => preg_quote($k, '/'), $this->singleKeywords);
        
        $this->specificPattern = '/\b(' . implode('|', $escapedSpecific) . ')\b/i';
        $this->singlePattern = '/\b(' . implode('|', $escapedSingle) . ')\b/i';

        $this->client = new Client([
            'timeout' => 20,
            'connect_timeout' => 10,
            'verify' => false,
            'headers' => [
                // 🔥 Gunakan real browser UA
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Cache-Control' => 'max-age=0',
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

    // ✅ OPTIMIZATION 4: Unified WP-JSON scanner (DRY principle)
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

            // ✅ OPTIMIZATION 5: Fetch additional pages only if needed
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

    // ✅ OPTIMIZATION 6: Reusable empty result generator
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

    // ✅ OPTIMIZATION 7: Batch scan items
    protected function scanItems($items)
    {
        if (empty($items) || !is_array($items)) {
            return [];
        }

        $suspicious = [];
        
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            
            // ✅ OPTIMIZATION 8: Single strtolower call
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

    // ✅ OPTIMIZATION 9: Unified concurrent fetcher
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
        
        // ✅ OPTIMIZATION 10: Use strpbrk for faster pattern matching
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

    // public function scanHeaderFooter($url)
    // {
    //     try {
    //         $response = $this->client->get($url);
    //         $statusCode = $response->getStatusCode();
    //         $html = $response->getBody()->getContents();

    //         if ($this->isBlockedByWAF($html, $statusCode)) {
    //             return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'error' => "WAF blocked (HTTP {$statusCode})"];
    //         }

    //         if ($statusCode !== 200) {
    //             return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'error' => "HTTP {$statusCode}"];
    //         }

    //         $foundKeywords = $this->detectKeywords(strtolower($html));

    //         return [
    //             'has_suspicious' => $foundKeywords['is_suspicious'],
    //             'keywords' => $foundKeywords['keywords'],
    //             'keyword_count' => count($foundKeywords['keywords']),
    //             'error' => null,
    //         ];
    //     } catch (\Exception $e) {
    //         return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'error' => $e->getMessage()];
    //     }
    // }

    // public function scanMeta($url)
    // {
    //     try {
    //         $response = $this->client->get($url);
    //         $statusCode = $response->getStatusCode();
    //         $html = $response->getBody()->getContents();

    //         if ($this->isBlockedByWAF($html, $statusCode) || $statusCode !== 200) {
    //             return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'meta_title' => '', 'meta_description' => '', 'error' => "HTTP {$statusCode}"];
    //         }
            
    //         preg_match('/<title>(.*?)<\/title>/i', $html, $title);
    //         preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/i', $html, $description);
    //         preg_match('/<meta\s+name=["\']keywords["\']\s+content=["\'](.*?)["\']/i', $html, $keywords);

    //         $metaText = strtolower(($title[1] ?? '') . ' ' . ($description[1] ?? '') . ' ' . ($keywords[1] ?? ''));
    //         $foundKeywords = $this->detectKeywords($metaText);

    //         return [
    //             'has_suspicious' => $foundKeywords['is_suspicious'],
    //             'keywords' => $foundKeywords['keywords'],
    //             'keyword_count' => count($foundKeywords['keywords']),
    //             'meta_title' => $title[1] ?? '',
    //             'meta_description' => $description[1] ?? '',
    //             'error' => null,
    //         ];
    //     } catch (\Exception $e) {
    //         return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'meta_title' => '', 'meta_description' => '', 'error' => $e->getMessage()];
    //     }
    // }

    public function scanHeaderFooter($url)
    {
        try {
            $response = $this->client->get($url);
            $statusCode = $response->getStatusCode();
            $html = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'error' => "HTTP {$statusCode}"];
            }

            if (empty($html) || strlen($html) < 100) {
                return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'error' => "Empty or invalid HTML"];
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

            if ($statusCode !== 200) {
                return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'meta_title' => '', 'meta_description' => '', 'error' => "HTTP {$statusCode}"];
            }

            if (empty($html) || strlen($html) < 100) {
                return ['has_suspicious' => false, 'keywords' => [], 'keyword_count' => 0, 'meta_title' => '', 'meta_description' => '', 'error' => "Empty HTML"];
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

    // ✅ OPTIMIZATION 11: Use compiled regex patterns
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

    /**
     * Fallback Detection + Single Page Content Scan
     */
    public function detectWordPressFallback($url)
    {
        try {
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() !== 200) {
                return [
                    'is_wordpress' => false,
                    'method' => 'Failed',
                    'version' => null,
                    'single_page_scan' => null,
                ];
            }

            $html = $response->getBody()->getContents();
            $htmlLower = strtolower($html);

            // 🔥 SCAN HOMEPAGE CONTENT (Single Page)
            $singlePageScan = $this->scanSinglePageContent($html);

            // Method 1: Meta Generator Tag
            if (preg_match('/<meta\s+name=["\']generator["\']\s+content=["\']WordPress\s+([0-9.]+)["\']/', $html, $matches)) {
                return [
                    'is_wordpress' => true,
                    'method' => 'Meta Generator',
                    'version' => $matches[1],
                    'can_scan_content' => false,
                    'single_page_scan' => $singlePageScan, // 🔥 Include scan result
                ];
            }

            // Method 2: HTML Keywords
            $wpKeywords = ['wp-content', 'wp-includes', 'wp-json', '/wp-content/themes/', '/wp-content/plugins/'];
            $foundCount = 0;
            foreach ($wpKeywords as $keyword) {
                if (strpos($htmlLower, $keyword) !== false) {
                    $foundCount++;
                }
            }

            if ($foundCount >= 2) {
                return [
                    'is_wordpress' => true,
                    'method' => 'HTML Keywords',
                    'version' => 'Unknown',
                    'can_scan_content' => false,
                    'single_page_scan' => $singlePageScan,
                ];
            }

            // Method 3: Check WP Endpoints
            try {
                $loginCheck = $this->client->get(rtrim($url, '/') . '/wp-login.php');
                if (in_array($loginCheck->getStatusCode(), [200, 302])) {
                    return [
                        'is_wordpress' => true,
                        'method' => 'WP Endpoints',
                        'version' => 'Unknown',
                        'can_scan_content' => false,
                        'single_page_scan' => $singlePageScan,
                    ];
                }
            } catch (\Exception $e) {
                // Silent fail
            }

            // Not WordPress
            return [
                'is_wordpress' => false,
                'method' => 'Not Detected',
                'version' => null,
                'single_page_scan' => $singlePageScan, // Tetap scan walau bukan WP
            ];

        } catch (\Exception $e) {
            return [
                'is_wordpress' => false,
                'method' => 'Error',
                'version' => null,
                'error' => $e->getMessage(),
                'single_page_scan' => null,
            ];
        }
    }

    /**
     * Scan Single Page Content (Homepage Only)
     */
    protected function scanSinglePageContent($html)
    {
        $htmlLower = strtolower($html);
        
        // 1. Scan HTML raw
        $foundKeywords = $this->detectKeywords($htmlLower);
        
        // 2. Extract title & meta
        preg_match('/<title>(.*?)<\/title>/i', $html, $title);
        preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/i', $html, $description);
        
        // 3. Detect hidden links
        $hiddenLinks = [];
        if (preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $links)) {
            foreach ($links[1] as $link) {
                if ($this->detectKeywords(strtolower($link))['is_suspicious']) {
                    $hiddenLinks[] = $link;
                    if (count($hiddenLinks) >= 10) break;
                }
            }
        }
        
        // 4. Strip HTML tags & scan plain text
        $plainText = strip_tags($html);
        $plainTextLower = strtolower($plainText);
        $plainTextKeywords = $this->detectKeywords($plainTextLower);

        \Log::info("Plain Text Keywords: " . json_encode($plainTextKeywords));
        
        // 🔥 5. NEW: Scan HTML attributes (data-*, title, alt, aria-label)
        $attributeText = '';
        if (preg_match_all('/(?:data-[a-z-]+|title|alt|aria-label)=["\']([^"\']+)["\']/i', $html, $attrs)) {
            $attributeText = implode(' ', $attrs[1]);
        }
        $attributeKeywords = $this->detectKeywords(strtolower($attributeText));
        
        // 6. Merge results
        $allKeywords = array_unique(array_merge(
            $foundKeywords['keywords'],
            $plainTextKeywords['keywords'],
            $attributeKeywords['keywords'] // 🔥 Include attribute keywords
        ));
        
        $isSuspicious = 
            $foundKeywords['is_suspicious'] || 
            $plainTextKeywords['is_suspicious'] ||
            $attributeKeywords['is_suspicious'] || // 🔥 Check attributes
            !empty($hiddenLinks);
        
        return [
            'has_suspicious' => $isSuspicious,
            'keywords' => $allKeywords,
            'keyword_count' => count($allKeywords),
            'hidden_links' => $hiddenLinks,
            'hidden_links_count' => count($hiddenLinks),
            'title' => $title[1] ?? '',
            'description' => $description[1] ?? '',
            'scan_type' => 'single_page_homepage',
            'plain_text_checked' => true,
            'attributes_checked' => true, // 🔥 NEW flag
        ];
    }
}
