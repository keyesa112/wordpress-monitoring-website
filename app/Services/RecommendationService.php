<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class RecommendationService
{
    protected $recommendations;

    public function __construct()
    {
        // Load recommendations from JSON
        $jsonPath = storage_path('app/recommendations.json');
        
        if (file_exists($jsonPath)) {
            $this->recommendations = json_decode(file_get_contents($jsonPath), true);
        } else {
            $this->recommendations = [];
        }
    }

    /**
     * Generate recommendations based on scan results
     */
    public function generateRecommendations($scanResults)
    {
        $triggered = [];
        $severityOrder = ['critical' => 0, 'high' => 1, 'warning' => 2, 'info' => 3, 'success' => 4];

        // Check each recommendation condition
        foreach ($this->recommendations as $rec) {
            if ($this->checkTrigger($rec, $scanResults)) {
                $triggered[] = $this->formatRecommendation($rec, $scanResults);
            }
        }

        // Sort by severity (critical first)
        usort($triggered, function($a, $b) use ($severityOrder) {
            return $severityOrder[$a['severity']] <=> $severityOrder[$b['severity']];
        });

        return [
            'total' => count($triggered),
            'critical_count' => count(array_filter($triggered, fn($r) => $r['severity'] === 'critical')),
            'high_count' => count(array_filter($triggered, fn($r) => $r['severity'] === 'high')),
            'recommendations' => $triggered,
        ];
    }

    /**
     * Check if recommendation should be triggered
     */
    protected function checkTrigger($recommendation, $scanResults)
    {
        $trigger = $recommendation['trigger'];
        $category = $recommendation['category'];

        switch ($trigger) {
            case 'has_suspicious_posts':
                return ($scanResults['posts']['has_suspicious'] ?? false) && 
                       ($scanResults['posts']['suspicious_count'] ?? 0) >= ($recommendation['min_count'] ?? 1);

            // ✅ NEW: Pages detection
            case 'has_suspicious_pages':
                return ($scanResults['pages']['has_suspicious'] ?? false) && 
                       ($scanResults['pages']['suspicious_count'] ?? 0) >= ($recommendation['min_count'] ?? 1);

            case 'has_suspicious_header':
                return $scanResults['header_footer']['has_suspicious'] ?? false;

            case 'has_suspicious_meta':
                return $scanResults['meta']['has_suspicious'] ?? false;

            case 'has_suspicious_sitemap':
                return $scanResults['sitemap']['has_suspicious'] ?? false;

            case 'website_offline':
                return ($scanResults['status']['status'] ?? '') === 'offline' ||
                       ($scanResults['status']['response_time'] ?? 0) > 5000;

            // ✅ NEW: Injected HTML detection
            case 'has_injected_html':
                $postsInjection = $scanResults['posts']['injected_html']['has_suspicious'] ?? false;
                $pagesInjection = $scanResults['pages']['injected_html']['has_suspicious'] ?? false;
                return $postsInjection || $pagesInjection;

            // ✅ NEW: Hidden backlinks detection
            case 'has_hidden_backlinks':
                $postsLinks = $scanResults['posts']['injected_html']['total_links'] ?? 0;
                $pagesLinks = $scanResults['pages']['injected_html']['total_links'] ?? 0;
                return ($postsLinks + $pagesLinks) > 0;

            case 'no_suspicious_content':
                // Check injected HTML too
                $postsInjection = $scanResults['posts']['injected_html']['has_suspicious'] ?? false;
                $pagesInjection = $scanResults['pages']['injected_html']['has_suspicious'] ?? false;
                
                return !($scanResults['has_suspicious_content'] ?? false) && 
                       !$postsInjection && 
                       !$pagesInjection;

            case 'multiple_areas_infected':
                $infectedAreas = 0;
                if ($scanResults['posts']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['pages']['has_suspicious'] ?? false) $infectedAreas++; // ✅ ADDED
                if ($scanResults['header_footer']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['meta']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['sitemap']['has_suspicious'] ?? false) $infectedAreas++;
                
                // ✅ ADDED: Check injected HTML
                if ($scanResults['posts']['injected_html']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['pages']['injected_html']['has_suspicious'] ?? false) $infectedAreas++;
                
                return $infectedAreas >= ($recommendation['min_areas'] ?? 3);

            case 'wp_json_disabled':
                $postsError = $scanResults['posts']['error'] ?? '';
                $pagesError = $scanResults['pages']['error'] ?? '';
                return stripos($postsError, 'WP-JSON') !== false || 
                       stripos($postsError, 'WordPress') !== false ||
                       stripos($pagesError, 'WP-JSON') !== false;

            // ✅ NEW: File integrity triggers
            case 'suspicious_file_detected':
                return isset($scanResults['file_changes']['suspicious_count']) &&
                       $scanResults['file_changes']['suspicious_count'] > 0;

            case 'theme_plugin_modified':
                return isset($scanResults['file_changes']['modified_count']) &&
                       $scanResults['file_changes']['modified_count'] > 0;

            case 'uploads_folder_compromised':
                return isset($scanResults['file_changes']['uploads_php_count']) &&
                       $scanResults['file_changes']['uploads_php_count'] > 0;

            case 'core_files_modified':
                return isset($scanResults['file_changes']['core_modified']) &&
                       $scanResults['file_changes']['core_modified'] === true;

            default:
                return false;
        }
    }

    /**
     * Format recommendation with dynamic data
     */
    protected function formatRecommendation($recommendation, $scanResults)
    {
        $description = $recommendation['description'];

        // Replace placeholders
        if (strpos($description, '{count}') !== false) {
            $count = $this->getRelevantCount($recommendation['category'], $scanResults);
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
     * Get relevant count for category
     */
    protected function getRelevantCount($category, $scanResults)
    {
        switch ($category) {
            case 'posts':
                return $scanResults['posts']['suspicious_count'] ?? 0;
            
            // ✅ NEW: Pages count
            case 'pages':
                return $scanResults['pages']['suspicious_count'] ?? 0;
            
            case 'header_footer':
                return $scanResults['header_footer']['keyword_count'] ?? 0;
            
            case 'meta':
                return $scanResults['meta']['keyword_count'] ?? 0;
            
            case 'sitemap':
                return count($scanResults['sitemap']['suspicious_urls'] ?? []);
            
            // ✅ NEW: Injected HTML links count
            case 'wp_json_injection':
                $postsLinks = $scanResults['posts']['injected_html']['total_links'] ?? 0;
                $pagesLinks = $scanResults['pages']['injected_html']['total_links'] ?? 0;
                return $postsLinks + $pagesLinks;
            
            case 'multiple':
                $count = 0;
                if ($scanResults['posts']['has_suspicious'] ?? false) $count++;
                if ($scanResults['pages']['has_suspicious'] ?? false) $count++; // ✅ ADDED
                if ($scanResults['header_footer']['has_suspicious'] ?? false) $count++;
                if ($scanResults['meta']['has_suspicious'] ?? false) $count++;
                if ($scanResults['sitemap']['has_suspicious'] ?? false) $count++;
                
                // ✅ ADDED: Count injected HTML
                if ($scanResults['posts']['injected_html']['has_suspicious'] ?? false) $count++;
                if ($scanResults['pages']['injected_html']['has_suspicious'] ?? false) $count++;
                
                return $count;
            
            // ✅ NEW: File integrity counts
            case 'file_integrity':
                return $scanResults['file_changes']['suspicious_count'] ?? 0;
            
            default:
                return 0;
        }
    }

    /**
     * Get severity badge HTML
     */
    public function getSeverityBadge($severity)
    {
        $badges = [
            'critical' => '<span class="badge badge-danger"><i class="fas fa-exclamation-circle"></i> Critical</span>',
            'high' => '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> High</span>',
            'warning' => '<span class="badge badge-warning"><i class="fas fa-exclamation"></i> Warning</span>',
            'info' => '<span class="badge badge-info"><i class="fas fa-info-circle"></i> Info</span>',
            'success' => '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Success</span>',
        ];

        return $badges[$severity] ?? '<span class="badge badge-secondary">Unknown</span>';
    }

    /**
     * Get icon for recommendation category
     */
    public function getCategoryIcon($category)
    {
        $icons = [
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

        return $icons[$category] ?? 'fa-info-circle';
    }
}
