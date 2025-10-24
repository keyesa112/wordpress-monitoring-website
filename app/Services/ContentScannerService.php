<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class ContentScannerService
{
    protected $client;

    /**
     * Keyword kombinasi yang lebih spesifik (2-3 kata)
     */
    protected $specificKeywords = [
        'slot gacor',
        'slot online',
        'situs slot',
        'link slot',
        'daftar slot',
        'rtp slot',
        'bocoran slot',
        'jackpot slot',
        'maxwin slot',
        'slot88',
        'slot77',
        'pragmatic play slot',
        'pg soft slot',
        'togel online',
        'bandar togel',
        'togel hari ini',
        'casino online',
        'judi online',
        'taruhan bola',
        'sbobet',
        'bonus new member slot',
        'deposit pulsa slot',
        'toto88',
        'markasbola365',
        'bet777',
        'zeus365',
        'game penghasil uang',
        'double win slots',
        'vegas casino',
        'fortune scratch life: earn cash',
        'info link gacor',
        'new member dapat freechip',
        'bonus chip gratis',
        'gampang menang',
        'toto12',
        'dana toto',
        'partai togel',
    ];

    /**
     * Keyword tunggal (harus ada minimal 2 untuk dianggap suspicious)
     */
    protected $singleKeywords = [
        'gacor',
        'maxwin',
        'rtp',
        'togel',
        'casino',
        'betting',
        'poker',
        'judi',
        'jackpot',
        'toto',
        'fairslot',
        'winrate',
        'gamble',
        'domino',
        'chip',
        'freechip',
    ];

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 20,
            'connect_timeout' => 10,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Cache-Control' => 'max-age=0',
            ],
            'allow_redirects' => true,
            'http_errors' => false,
        ]);
    }

    /**
     * Scan konten website untuk deteksi keyword judi online
     */
    public function scanContent($url)
    {
        set_time_limit(300);
        
        $results = [
            'url' => $url,
            'scanned_at' => now()->toDateTimeString(),
            'posts' => $this->scanPosts($url),
            'pages' => $this->scanPages($url), // ✅ ADDED
            'header_footer' => $this->scanHeaderFooter($url),
            'meta' => $this->scanMeta($url),
            'sitemap' => $this->scanSitemap($url),
        ];

        // Summary
        $results['has_suspicious_content'] = 
            $results['posts']['has_suspicious'] ||
            $results['pages']['has_suspicious'] || // ✅ ADDED
            $results['header_footer']['has_suspicious'] ||
            $results['meta']['has_suspicious'] ||
            $results['sitemap']['has_suspicious'];

        return $results;
    }

    /**
     * Scan posts via WP-JSON dengan pagination concurrent
     */
    public function scanPosts($url)
    {
        try {
            $baseUrl = rtrim($url, '/');
            $perPage = 100;
            
            $firstPageUrl = $baseUrl . "/wp-json/wp/v2/posts?per_page={$perPage}&page=1";
            
            try {
                $response = $this->client->get($firstPageUrl);
            } catch (\Exception $e) {
                return [
                    'has_suspicious' => false,
                    'total_posts' => 0,
                    'suspicious_count' => 0,
                    'suspicious_posts' => [],
                    'error' => 'Gagal akses WP-JSON (bukan WordPress atau WP-JSON disabled)',
                ];
            }

            if ($response->getStatusCode() !== 200) {
                return [
                    'has_suspicious' => false,
                    'total_posts' => 0,
                    'suspicious_count' => 0,
                    'suspicious_posts' => [],
                    'error' => 'Gagal akses WP-JSON (HTTP ' . $response->getStatusCode() . ')',
                ];
            }

            $totalPages = (int) $response->getHeaderLine('X-WP-TotalPages');
            $totalPosts = (int) $response->getHeaderLine('X-WP-Total');
            
            if ($totalPages === 0) {
                $totalPages = 1;
            }

            $maxPages = min($totalPages, 50);

            $allPosts = json_decode($response->getBody()->getContents(), true);

            if ($maxPages > 1) {
                $additionalPosts = $this->fetchPostsConcurrently($baseUrl, $perPage, 2, $maxPages);
                $allPosts = array_merge($allPosts, $additionalPosts);
            }

            if (empty($allPosts) || !is_array($allPosts)) {
                return [
                    'has_suspicious' => false,
                    'total_posts' => 0,
                    'suspicious_count' => 0,
                    'suspicious_posts' => [],
                    'error' => 'Tidak ada posts ditemukan',
                ];
            }

            $suspiciousPosts = [];

            foreach ($allPosts as $post) {
                $content = strtolower($post['content']['rendered'] ?? '');
                $title = strtolower($post['title']['rendered'] ?? '');
                $excerpt = strtolower($post['excerpt']['rendered'] ?? '');

                $fullText = $content . ' ' . $title . ' ' . $excerpt;

                $foundKeywords = $this->detectKeywords($fullText);

                if ($foundKeywords['is_suspicious']) {
                    $suspiciousPosts[] = [
                        'id' => $post['id'],
                        'title' => $post['title']['rendered'] ?? 'No title',
                        'link' => $post['link'] ?? '',
                        'keywords' => $foundKeywords['keywords'],
                        'keyword_count' => count($foundKeywords['keywords']),
                        'date' => $post['date'] ?? '',
                    ];
                }
            }

            return [
                'has_suspicious' => !empty($suspiciousPosts),
                'total_posts' => count($allPosts),
                'total_posts_available' => $totalPosts,
                'total_pages_scanned' => $maxPages,
                'suspicious_count' => count($suspiciousPosts),
                'suspicious_posts' => $suspiciousPosts,
                'error' => null,
            ];

        } catch (\Exception $e) {
            return [
                'has_suspicious' => false,
                'total_posts' => 0,
                'suspicious_count' => 0,
                'suspicious_posts' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ✅ NEW: Scan pages via WP-JSON dengan pagination concurrent
     */
    public function scanPages($url)
    {
        try {
            $baseUrl = rtrim($url, '/');
            $perPage = 100;
            
            $firstPageUrl = $baseUrl . "/wp-json/wp/v2/pages?per_page={$perPage}&page=1";
            
            try {
                $response = $this->client->get($firstPageUrl);
            } catch (\Exception $e) {
                return [
                    'has_suspicious' => false,
                    'total_pages' => 0,
                    'suspicious_count' => 0,
                    'suspicious_pages' => [],
                    'error' => 'Gagal akses WP-JSON Pages',
                ];
            }

            if ($response->getStatusCode() !== 200) {
                return [
                    'has_suspicious' => false,
                    'total_pages' => 0,
                    'suspicious_count' => 0,
                    'suspicious_pages' => [],
                    'error' => 'Gagal akses WP-JSON Pages (HTTP ' . $response->getStatusCode() . ')',
                ];
            }

            $totalPaginationPages = (int) $response->getHeaderLine('X-WP-TotalPages');
            $totalItems = (int) $response->getHeaderLine('X-WP-Total');
            
            if ($totalPaginationPages === 0) {
                $totalPaginationPages = 1;
            }

            $maxPages = min($totalPaginationPages, 50);

            $allPages = json_decode($response->getBody()->getContents(), true);

            if ($maxPages > 1) {
                $additionalPages = $this->fetchPagesConcurrently($baseUrl, $perPage, 2, $maxPages);
                $allPages = array_merge($allPages, $additionalPages);
            }

            if (empty($allPages) || !is_array($allPages)) {
                return [
                    'has_suspicious' => false,
                    'total_pages' => 0,
                    'suspicious_count' => 0,
                    'suspicious_pages' => [],
                    'error' => 'Tidak ada pages ditemukan',
                ];
            }

            $suspiciousPages = [];

            foreach ($allPages as $page) {
                $content = strtolower($page['content']['rendered'] ?? '');
                $title = strtolower($page['title']['rendered'] ?? '');
                $excerpt = strtolower($page['excerpt']['rendered'] ?? '');

                $fullText = $content . ' ' . $title . ' ' . $excerpt;

                $foundKeywords = $this->detectKeywords($fullText);

                if ($foundKeywords['is_suspicious']) {
                    $suspiciousPages[] = [
                        'id' => $page['id'],
                        'title' => $page['title']['rendered'] ?? 'No title',
                        'link' => $page['link'] ?? '',
                        'keywords' => $foundKeywords['keywords'],
                        'keyword_count' => count($foundKeywords['keywords']),
                        'date' => $page['date'] ?? '',
                    ];
                }
            }

            return [
                'has_suspicious' => !empty($suspiciousPages),
                'total_pages' => count($allPages),
                'total_pages_available' => $totalItems,
                'total_pagination_scanned' => $maxPages,
                'suspicious_count' => count($suspiciousPages),
                'suspicious_pages' => $suspiciousPages,
                'error' => null,
            ];

        } catch (\Exception $e) {
            return [
                'has_suspicious' => false,
                'total_pages' => 0,
                'suspicious_count' => 0,
                'suspicious_pages' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch multiple posts pages concurrently
     */
    protected function fetchPostsConcurrently($baseUrl, $perPage, $startPage, $endPage)
    {
        $allPosts = [];
        
        $requests = function () use ($baseUrl, $perPage, $startPage, $endPage) {
            for ($page = $startPage; $page <= $endPage; $page++) {
                $url = $baseUrl . "/wp-json/wp/v2/posts?per_page={$perPage}&page={$page}";
                yield new Request('GET', $url);
            }
        };

        $pool = new Pool($this->client, $requests(), [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) use (&$allPosts) {
                $posts = json_decode($response->getBody()->getContents(), true);
                if (is_array($posts)) {
                    $allPosts = array_merge($allPosts, $posts);
                }
            },
            'rejected' => function ($reason, $index) {
                // Silent error handling
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $allPosts;
    }

    /**
     * ✅ NEW: Fetch multiple pages concurrently
     */
    protected function fetchPagesConcurrently($baseUrl, $perPage, $startPage, $endPage)
    {
        $allPages = [];
        
        $requests = function () use ($baseUrl, $perPage, $startPage, $endPage) {
            for ($page = $startPage; $page <= $endPage; $page++) {
                $url = $baseUrl . "/wp-json/wp/v2/pages?per_page={$perPage}&page={$page}";
                yield new Request('GET', $url);
            }
        };

        $pool = new Pool($this->client, $requests(), [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) use (&$allPages) {
                $pages = json_decode($response->getBody()->getContents(), true);
                if (is_array($pages)) {
                    $allPages = array_merge($allPages, $pages);
                }
            },
            'rejected' => function ($reason, $index) {
                // Silent error handling
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $allPages;
    }

    /**
     * Scan header dan footer HTML
     */
    public function scanHeaderFooter($url)
    {
        try {
            $response = $this->client->get($url);

            $statusCode = $response->getStatusCode();
            $html = $response->getBody()->getContents();

            if ($this->isBlockedByWAF($html, $statusCode)) {
                return [
                    'has_suspicious' => false,
                    'keywords' => [],
                    'keyword_count' => 0,
                    'error' => 'Request diblokir oleh WAF/Cloudflare (HTTP ' . $statusCode . ')',
                ];
            }

            if ($statusCode !== 200) {
                return [
                    'has_suspicious' => false,
                    'keywords' => [],
                    'keyword_count' => 0,
                    'error' => 'Gagal mengakses halaman (HTTP ' . $statusCode . ')',
                ];
            }

            $html = strtolower($html);
            
            $foundKeywords = $this->detectKeywords($html);

            return [
                'has_suspicious' => $foundKeywords['is_suspicious'],
                'keywords' => $foundKeywords['keywords'],
                'keyword_count' => count($foundKeywords['keywords']),
                'error' => null,
            ];

        } catch (\Exception $e) {
            return [
                'has_suspicious' => false,
                'keywords' => [],
                'keyword_count' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Scan meta tags
     */
    public function scanMeta($url)
    {
        try {
            $response = $this->client->get($url);

            $statusCode = $response->getStatusCode();
            $html = $response->getBody()->getContents();

            if ($this->isBlockedByWAF($html, $statusCode)) {
                return [
                    'has_suspicious' => false,
                    'keywords' => [],
                    'keyword_count' => 0,
                    'meta_title' => '',
                    'meta_description' => '',
                    'error' => 'Request diblokir oleh WAF/Cloudflare (HTTP ' . $statusCode . ')',
                ];
            }

            if ($statusCode !== 200) {
                return [
                    'has_suspicious' => false,
                    'keywords' => [],
                    'keyword_count' => 0,
                    'meta_title' => '',
                    'meta_description' => '',
                    'error' => 'Gagal mengakses halaman (HTTP ' . $statusCode . ')',
                ];
            }
            
            // Extract meta tags
            preg_match('/<title>(.*?)<\/title>/i', $html, $title);
            preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/i', $html, $description);
            preg_match('/<meta\s+name=["\']keywords["\']\s+content=["\'](.*?)["\']/i', $html, $keywords);

            $metaText = strtolower(
                ($title[1] ?? '') . ' ' . 
                ($description[1] ?? '') . ' ' . 
                ($keywords[1] ?? '')
            );

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
            return [
                'has_suspicious' => false,
                'keywords' => [],
                'keyword_count' => 0,
                'meta_title' => '',
                'meta_description' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Scan sitemap.xml
     */
    public function scanSitemap($url)
    {
        try {
            $baseUrl = rtrim($url, '/');
            $sitemapUrl = $baseUrl . '/sitemap.xml';

            $response = $this->client->get($sitemapUrl);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                return [
                    'has_suspicious' => false,
                    'keywords' => [],
                    'keyword_count' => 0,
                    'suspicious_urls' => [],
                    'error' => 'Sitemap tidak ditemukan (HTTP ' . $statusCode . ')',
                ];
            }

            $xml = $response->getBody()->getContents();
            
            $foundKeywords = $this->detectKeywords(strtolower($xml));

            $suspiciousUrls = [];
            if ($foundKeywords['is_suspicious']) {
                preg_match_all('/<loc>(.*?)<\/loc>/i', $xml, $urls);
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
            return [
                'has_suspicious' => false,
                'keywords' => [],
                'keyword_count' => 0,
                'suspicious_urls' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Deteksi WAF block
     */
    protected function isBlockedByWAF($html, $statusCode)
    {
        if (empty($html)) {
            return false;
        }

        $htmlLower = strtolower($html);
        
        $blockPatterns = [
            'request rejected',
            'access denied',
            'forbidden',
            'cloudflare',
            'checking your browser',
            'please wait while we',
            'security check',
            'ray id',
            'attention required',
            'sucuri website firewall',
            'wordfence',
        ];

        foreach ($blockPatterns as $pattern) {
            if (stripos($htmlLower, $pattern) !== false) {
                if (stripos($htmlLower, 'cloudflare') !== false && 
                    stripos($htmlLower, 'challenge') !== false) {
                    return true;
                }
                
                if (in_array($statusCode, [403, 503]) || 
                    stripos($htmlLower, 'request rejected') !== false ||
                    stripos($htmlLower, 'access denied') !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Deteksi keyword
     */
    protected function detectKeywords($text)
    {
        $foundKeywords = [];

        foreach ($this->specificKeywords as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $text)) {
                $foundKeywords[] = $keyword;
            }
        }

        $singleMatches = [];
        foreach ($this->singleKeywords as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $text)) {
                $singleMatches[] = $keyword;
            }
        }

        $allFoundKeywords = array_merge($foundKeywords, $singleMatches);

        $isSuspicious = !empty($foundKeywords) || count($singleMatches) >= 2;

        return [
            'is_suspicious' => $isSuspicious,
            'keywords' => array_unique($allFoundKeywords),
        ];
    }
}
