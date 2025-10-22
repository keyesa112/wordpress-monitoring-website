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
    ];

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 20,
            'connect_timeout' => 10,
            'verify' => false, // Disable SSL verification jika ada masalah sertifikat
        ]);
    }

    /**
     * Scan konten website untuk deteksi keyword judi online
     * Meliputi: header, footer, meta, post (via WP-JSON), dan sitemap
     */
    public function scanContent($url)
    {
        $results = [
            'url' => $url,
            'scanned_at' => now()->toDateTimeString(),
            'posts' => $this->scanPosts($url),
            'header_footer' => $this->scanHeaderFooter($url),
            'meta' => $this->scanMeta($url),
            'sitemap' => $this->scanSitemap($url),
        ];

        // Summary
        $results['has_suspicious_content'] = 
            $results['posts']['has_suspicious'] ||
            $results['header_footer']['has_suspicious'] ||
            $results['meta']['has_suspicious'] ||
            $results['sitemap']['has_suspicious'];

        return $results;
    }

    /**
     * Scan posts via WP-JSON dengan pagination concurrent menggunakan Guzzle Pool
     */
    public function scanPosts($url)
    {
        try {
            $baseUrl = rtrim($url, '/');
            $perPage = 100;
            
            // First request untuk mendapatkan total pages dari header
            $firstPageUrl = $baseUrl . "/wp-json/wp/v2/posts?per_page={$perPage}&page=1";
            
            try {
                $response = $this->client->get($firstPageUrl);
            } catch (\Exception $e) {
                return [
                    'has_suspicious' => false,
                    'total_posts' => 0,
                    'suspicious_posts' => [],
                    'error' => 'Gagal akses WP-JSON (bukan WordPress atau WP-JSON disabled)',
                ];
            }

            if ($response->getStatusCode() !== 200) {
                return [
                    'has_suspicious' => false,
                    'total_posts' => 0,
                    'suspicious_posts' => [],
                    'error' => 'Gagal akses WP-JSON',
                ];
            }

            // Get total pages dari header X-WP-TotalPages
            $totalPages = (int) $response->getHeaderLine('X-WP-TotalPages');
            $totalPosts = (int) $response->getHeaderLine('X-WP-Total');
            
            if ($totalPages === 0) {
                $totalPages = 1;
            }

            // Limit maximum pages untuk safety
            $maxPages = min($totalPages, 50); // Maksimal 50 pages = 5000 posts

            $allPosts = json_decode($response->getBody()->getContents(), true);

            // Jika ada lebih dari 1 page, fetch concurrent
            if ($maxPages > 1) {
                $additionalPosts = $this->fetchPostsConcurrently($baseUrl, $perPage, 2, $maxPages);
                $allPosts = array_merge($allPosts, $additionalPosts);
            }

            if (empty($allPosts) || !is_array($allPosts)) {
                return [
                    'has_suspicious' => false,
                    'total_posts' => 0,
                    'suspicious_posts' => [],
                    'error' => 'Tidak ada posts ditemukan',
                ];
            }

            $suspiciousPosts = [];

            // Scan semua posts
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
                'suspicious_posts' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch multiple pages concurrently menggunakan Guzzle Pool
     */
    protected function fetchPostsConcurrently($baseUrl, $perPage, $startPage, $endPage)
    {
        $allPosts = [];
        
        // Generator function untuk create requests
        $requests = function () use ($baseUrl, $perPage, $startPage, $endPage) {
            for ($page = $startPage; $page <= $endPage; $page++) {
                $url = $baseUrl . "/wp-json/wp/v2/posts?per_page={$perPage}&page={$page}";
                yield new Request('GET', $url);
            }
        };

        // Create pool dengan concurrency
        $pool = new Pool($this->client, $requests(), [
            'concurrency' => 5, // 5 concurrent requests
            'fulfilled' => function ($response, $index) use (&$allPosts) {
                $posts = json_decode($response->getBody()->getContents(), true);
                if (is_array($posts)) {
                    $allPosts = array_merge($allPosts, $posts);
                }
            },
            'rejected' => function ($reason, $index) {
                // Handle error silently atau log jika perlu
            },
        ]);

        // Execute pool
        $promise = $pool->promise();
        $promise->wait();

        return $allPosts;
    }

    /**
     * Scan header dan footer HTML
     */
    public function scanHeaderFooter($url)
    {
        try {
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== 200) {
                return [
                    'has_suspicious' => false,
                    'keywords' => [],
                    'error' => 'Gagal mengakses halaman',
                ];
            }

            $html = strtolower($response->getBody()->getContents());
            
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
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Scan meta tags (title, description, keywords)
     */
    public function scanMeta($url)
    {
        try {
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== 200) {
                return [
                    'has_suspicious' => false,
                    'keywords' => [],
                    'error' => 'Gagal mengakses halaman',
                ];
            }

            $html = $response->getBody()->getContents();
            
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

            if ($response->getStatusCode() !== 200) {
                return [
                    'has_suspicious' => false,
                    'suspicious_urls' => [],
                    'error' => 'Sitemap tidak ditemukan',
                ];
            }

            $xml = strtolower($response->getBody()->getContents());
            
            $foundKeywords = $this->detectKeywords($xml);

            // Extract URLs yang mengandung keyword
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
                'suspicious_urls' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Deteksi keyword dalam text
     * Rule: Suspicious jika ada minimal 1 specific keyword ATAU minimal 2 single keyword
     */
    protected function detectKeywords($text)
    {
        $foundKeywords = [];

        // Check specific keywords (kombinasi 2-3 kata)
        foreach ($this->specificKeywords as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $text)) {
                $foundKeywords[] = $keyword;
            }
        }

        // Check single keywords (whole word only)
        $singleMatches = [];
        foreach ($this->singleKeywords as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $text)) {
                $singleMatches[] = $keyword;
            }
        }

        // Gabungkan semua keyword yang ditemukan
        $allFoundKeywords = array_merge($foundKeywords, $singleMatches);

        // Rule: Suspicious jika ada minimal 1 specific keyword ATAU minimal 2 single keyword
        $isSuspicious = !empty($foundKeywords) || count($singleMatches) >= 2;

        return [
            'is_suspicious' => $isSuspicious,
            'keywords' => array_unique($allFoundKeywords),
        ];
    }
}
