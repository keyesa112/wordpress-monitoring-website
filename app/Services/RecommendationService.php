<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class RecommendationService
{
    protected $recommendations;
    protected const SEVERITY_ORDER = ['critical' => 0, 'high' => 1, 'warning' => 2, 'info' => 3, 'success' => 4];
    
    // ✅ OPTIMIZATION 1: Constant arrays (no repeated initialization)
    protected const SEVERITY_BADGES = [
        'critical' => '<span class="badge badge-danger"><i class="fas fa-exclamation-circle"></i> Critical</span>',
        'high' => '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> High</span>',
        'warning' => '<span class="badge badge-warning"><i class="fas fa-exclamation"></i> Warning</span>',
        'info' => '<span class="badge badge-info"><i class="fas fa-info-circle"></i> Info</span>',
        'success' => '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Success</span>',
    ];

    protected const CATEGORY_ICONS = [
        'posts' => 'fa-file-alt',
        'pages' => 'fa-file',
        'header_footer' => 'fa-code',
        'meta' => 'fa-tags',
        'sitemap' => 'fa-sitemap',
        'wp_json_injection' => 'fa-bug',
        'connection' => 'fa-link',
        'clean' => 'fa-check-circle',
        'multiple' => 'fa-exclamation-triangle',
        'wp_json' => 'fa-cog',
        'file_integrity' => 'fa-shield-alt',
    ];

    public function __construct()
    {
        // ✅ OPTIMIZATION 2: Cache recommendations JSON (1 hour cache)
        $this->recommendations = Cache::remember('recommendations_data', 3600, function () {
            $jsonPath = storage_path('app/recommendations.json');
            return file_exists($jsonPath) 
                ? json_decode(file_get_contents($jsonPath), true) 
                : [];
        });
    }

    public function generateRecommendations($scanResults)
    {
        // ✅ OPTIMIZATION 3: Pre-extract all values once
        $stats = $this->extractStats($scanResults);
        
        $triggered = [];

        foreach ($this->recommendations as $rec) {
            if ($this->checkTrigger($rec, $stats)) {
                $triggered[] = $this->formatRecommendation($rec, $stats);
            }
        }

        // Sort by severity
        usort($triggered, fn($a, $b) => self::SEVERITY_ORDER[$a['severity']] <=> self::SEVERITY_ORDER[$b['severity']]);

        // ✅ OPTIMIZATION 4: Single pass count by severity
        $severityCounts = array_count_values(array_column($triggered, 'severity'));

        return [
            'total' => count($triggered),
            'critical_count' => $severityCounts['critical'] ?? 0,
            'high_count' => $severityCounts['high'] ?? 0,
            'recommendations' => $triggered,
        ];
    }

    /**
     * ✅ OPTIMIZATION 5: Extract all stats once (avoid repeated array access)
     */
    protected function extractStats($scanResults): array
    {
        return [
            // Posts
            'posts_suspicious' => $scanResults['posts']['has_suspicious'] ?? false,
            'posts_count' => $scanResults['posts']['suspicious_count'] ?? 0,
            'posts_injection' => $scanResults['posts']['injected_html']['has_suspicious'] ?? false,
            'posts_links' => $scanResults['posts']['injected_html']['total_links'] ?? 0,
            'posts_error' => $scanResults['posts']['error'] ?? '',
            
            // Pages
            'pages_suspicious' => $scanResults['pages']['has_suspicious'] ?? false,
            'pages_count' => $scanResults['pages']['suspicious_count'] ?? 0,
            'pages_injection' => $scanResults['pages']['injected_html']['has_suspicious'] ?? false,
            'pages_links' => $scanResults['pages']['injected_html']['total_links'] ?? 0,
            'pages_error' => $scanResults['pages']['error'] ?? '',
            
            // Header/Footer
            'header_suspicious' => $scanResults['header_footer']['has_suspicious'] ?? false,
            'header_keywords' => $scanResults['header_footer']['keyword_count'] ?? 0,
            
            // Meta
            'meta_suspicious' => $scanResults['meta']['has_suspicious'] ?? false,
            'meta_keywords' => $scanResults['meta']['keyword_count'] ?? 0,
            
            // Sitemap
            'sitemap_suspicious' => $scanResults['sitemap']['has_suspicious'] ?? false,
            'sitemap_keywords' => $scanResults['sitemap']['keyword_count'] ?? 0,
            'sitemap_urls' => count($scanResults['sitemap']['suspicious_urls'] ?? []),
            
            // Status
            'status' => $scanResults['status']['status'] ?? '',
            'response_time' => $scanResults['status']['response_time'] ?? 0,
            
            // Overall
            'has_suspicious' => $scanResults['has_suspicious_content'] ?? false,
            
            // File integrity
            'file_suspicious' => $scanResults['file_changes']['suspicious_count'] ?? 0,
            'file_modified' => $scanResults['file_changes']['modified_count'] ?? 0,
            'file_uploads_php' => $scanResults['file_changes']['uploads_php_count'] ?? 0,
            'file_core_modified' => $scanResults['file_changes']['core_modified'] ?? false,
        ];
    }

    /**
     * ✅ OPTIMIZATION 6: Use array lookup instead of switch/match for better performance
     */
    protected function checkTrigger($recommendation, $stats): bool
    {
        $trigger = $recommendation['trigger'];
        $minCount = $recommendation['min_count'] ?? 1;
        $minAreas = $recommendation['min_areas'] ?? 3;

        // ✅ OPTIMIZATION 7: Static trigger map (faster than switch/match)
        static $triggerChecks = null;
        
        if ($triggerChecks === null) {
            $triggerChecks = $this->buildTriggerChecks();
        }

        if (isset($triggerChecks[$trigger])) {
            return $triggerChecks[$trigger]($stats, $minCount, $minAreas);
        }

        return false;
    }

    /**
     * ✅ OPTIMIZATION 8: Build trigger checks as closures once
     */
    protected function buildTriggerChecks(): array
    {
        return [
            'has_suspicious_posts' => fn($s, $mc) => $s['posts_suspicious'] && $s['posts_count'] >= $mc,
            'has_suspicious_pages' => fn($s, $mc) => $s['pages_suspicious'] && $s['pages_count'] >= $mc,
            'has_suspicious_header' => fn($s) => $s['header_suspicious'],
            'has_suspicious_meta' => fn($s) => $s['meta_suspicious'],
            'has_suspicious_sitemap' => fn($s) => $s['sitemap_suspicious'],
            
            'website_offline' => fn($s) => $s['status'] === 'offline' || $s['response_time'] > 5000,
            
            'has_injected_html' => fn($s) => $s['posts_injection'] || $s['pages_injection'],
            'has_hidden_backlinks' => fn($s) => ($s['posts_links'] + $s['pages_links']) > 0,
            
            'no_suspicious_content' => fn($s) => !$s['has_suspicious'] && !$s['posts_injection'] && !$s['pages_injection'],
            
            'multiple_areas_infected' => fn($s, $mc, $ma) => $this->countInfectedAreas($s) >= $ma,
            
            'wp_json_disabled' => fn($s) => 
                str_contains($s['posts_error'], 'WP-JSON') || 
                str_contains($s['posts_error'], 'WordPress') ||
                str_contains($s['pages_error'], 'WP-JSON'),
            
            'suspicious_file_detected' => fn($s) => $s['file_suspicious'] > 0,
            'theme_plugin_modified' => fn($s) => $s['file_modified'] > 0,
            'uploads_folder_compromised' => fn($s) => $s['file_uploads_php'] > 0,
            'core_files_modified' => fn($s) => $s['file_core_modified'],
        ];
    }

    /**
     * ✅ OPTIMIZATION 9: Optimized infected area counting
     */
    protected function countInfectedAreas($stats): int
    {
        return (int)(
            $stats['posts_suspicious'] +
            $stats['pages_suspicious'] +
            $stats['header_suspicious'] +
            $stats['meta_suspicious'] +
            $stats['sitemap_suspicious'] +
            $stats['posts_injection'] +
            $stats['pages_injection']
        );
    }

    protected function formatRecommendation($recommendation, $stats): array
    {
        $description = $recommendation['description'];

        // ✅ OPTIMIZATION 10: Use str_contains for faster check
        if (str_contains($description, '{count}')) {
            $count = $this->getRelevantCount($recommendation['category'], $stats);
            $description = str_replace('{count}', $count, $description);
        }

        return [
            'id' => $recommendation['id'],
            'category' => $recommendation['category'],
            'severity' => $recommendation['severity'],
            'title' => $recommendation['title'],
            'description' => $description,
            'recommendations' => $recommendation['recommendations'] ?? [],
            'actions' => $recommendation['actions'] ?? [],
            'prevention' => $recommendation['prevention'] ?? [],
        ];
    }

    /**
     * ✅ OPTIMIZATION 11: Array lookup instead of switch
     */
    protected function getRelevantCount($category, $stats): int
    {
        static $countMap = null;
        
        if ($countMap === null) {
            $countMap = [
                'posts' => fn($s) => $s['posts_count'],
                'pages' => fn($s) => $s['pages_count'],
                'header_footer' => fn($s) => $s['header_keywords'],
                'meta' => fn($s) => $s['meta_keywords'],
                'sitemap' => fn($s) => $s['sitemap_urls'],
                'wp_json_injection' => fn($s) => $s['posts_links'] + $s['pages_links'],
                'multiple' => fn($s) => $this->countInfectedAreas($s),
                'file_integrity' => fn($s) => $s['file_suspicious'],
            ];
        }

        return isset($countMap[$category]) ? $countMap[$category]($stats) : 0;
    }

    public function getSeverityBadge($severity): string
    {
        return self::SEVERITY_BADGES[$severity] ?? '<span class="badge badge-secondary">Unknown</span>';
    }

    public function getCategoryIcon($category): string
    {
        return self::CATEGORY_ICONS[$category] ?? 'fa-info-circle';
    }

    /**
     * ✅ OPTIMIZATION 12: Clear cache when recommendations updated
     */
    public static function clearCache(): void
    {
        Cache::forget('recommendations_data');
    }
}
