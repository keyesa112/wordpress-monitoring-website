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

    public function generateRecommendations($scanResults)
    {
        $triggered = [];
        $severityOrder = ['critical' => 0, 'high' => 1, 'warning' => 2, 'info' => 3, 'success' => 4];

        foreach ($this->recommendations as $rec) {
            if ($this->checkTrigger($rec, $scanResults)) {
                $triggered[] = $this->formatRecommendation($rec, $scanResults);
            }
        }

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

    protected function checkTrigger($recommendation, $scanResults)
    {
        $trigger = $recommendation['trigger'];
        $category = $recommendation['category'];

        switch ($trigger) {
            case 'has_suspicious_posts':
                return ($scanResults['posts']['has_suspicious'] ?? false) && 
                       ($scanResults['posts']['suspicious_count'] ?? 0) >= ($recommendation['min_count'] ?? 1);

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
                return ($scanResults['status']['status'] ?? '') === 'offline';

            case 'website_slow':
                $status = $scanResults['status']['status'] ?? '';
                $responseTime = $scanResults['status']['response_time'] ?? 0;
                return $status === 'online' && $responseTime > 3000 && $responseTime <= 10000;

            case 'website_very_slow':
                $status = $scanResults['status']['status'] ?? '';
                $responseTime = $scanResults['status']['response_time'] ?? 0;
                return $status === 'online' && $responseTime > 10000;

            case 'has_injected_html':
                $postsInjection = $scanResults['posts']['injected_html']['has_suspicious'] ?? false;
                $pagesInjection = $scanResults['pages']['injected_html']['has_suspicious'] ?? false;
                return $postsInjection || $pagesInjection;

            case 'has_hidden_backlinks':
                $postsLinks = $scanResults['posts']['injected_html']['total_links'] ?? 0;
                $pagesLinks = $scanResults['pages']['injected_html']['total_links'] ?? 0;
                return ($postsLinks + $pagesLinks) > 0;

            case 'no_suspicious_content':
                $postsInjection = $scanResults['posts']['injected_html']['has_suspicious'] ?? false;
                $pagesInjection = $scanResults['pages']['injected_html']['has_suspicious'] ?? false;
                
                return !($scanResults['has_suspicious_content'] ?? false) && 
                       !$postsInjection && 
                       !$pagesInjection;

            case 'multiple_areas_infected':
                $infectedAreas = 0;
                if ($scanResults['posts']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['pages']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['header_footer']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['meta']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['sitemap']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['posts']['injected_html']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['pages']['injected_html']['has_suspicious'] ?? false) $infectedAreas++;
                
                return $infectedAreas >= ($recommendation['min_areas'] ?? 3);

            case 'wp_json_disabled':
                return $this->isWpJsonDisabled($scanResults);

            // 🔥 NEW: Trigger R12 - Single Page Scan Only
            case 'single_page_scan_only':
                return isset($scanResults['single_page_scan']) && 
                       $scanResults['single_page_scan'] !== null &&
                       !($scanResults['rest_api_available'] ?? true);

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

    protected function isWpJsonDisabled($scanResults)
    {
        $postsError = $scanResults['posts']['error'] ?? '';
        $pagesError = $scanResults['pages']['error'] ?? '';
        
        $postsHasData = ($scanResults['posts']['total_posts'] ?? 0) > 0;
        $pagesHasData = ($scanResults['pages']['total_pages'] ?? 0) > 0;
        
        if ($postsHasData || $pagesHasData) {
            return false;
        }
        
        $disabledPatterns = [
            'WP-JSON disabled',
            'REST API',
            'bukan WordPress',
            'not WordPress',
        ];
        
        $postsDisabled = false;
        $pagesDisabled = false;
        
        foreach ($disabledPatterns as $pattern) {
            if (stripos($postsError, $pattern) !== false) {
                $postsDisabled = true;
            }
            if (stripos($pagesError, $pattern) !== false) {
                $pagesDisabled = true;
            }
        }
        
        return $postsDisabled && $pagesDisabled;
    }

    protected function formatRecommendation($recommendation, $scanResults)
    {
        $description = $recommendation['description'];

        if (strpos($description, '{count}') !== false) {
            $count = $this->getRelevantCount($recommendation['category'], $scanResults);
            $description = str_replace('{count}', $count, $description);
        }

        if (strpos($description, '{response_time}') !== false) {
            $responseTime = number_format($scanResults['status']['response_time'] ?? 0, 0);
            $description = str_replace('{response_time}', $responseTime, $description);
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

    protected function getRelevantCount($category, $scanResults)
    {
        switch ($category) {
            case 'posts':
                return $scanResults['posts']['suspicious_count'] ?? 0;
            
            case 'pages':
                return $scanResults['pages']['suspicious_count'] ?? 0;
            
            case 'header_footer':
                return $scanResults['header_footer']['keyword_count'] ?? 0;
            
            case 'meta':
                return $scanResults['meta']['keyword_count'] ?? 0;
            
            case 'sitemap':
                return count($scanResults['sitemap']['suspicious_urls'] ?? []);
            
            case 'wp_json_injection':
                $postsLinks = $scanResults['posts']['injected_html']['total_links'] ?? 0;
                $pagesLinks = $scanResults['pages']['injected_html']['total_links'] ?? 0;
                return $postsLinks + $pagesLinks;
            
            case 'multiple':
                $count = 0;
                if ($scanResults['posts']['has_suspicious'] ?? false) $count++;
                if ($scanResults['pages']['has_suspicious'] ?? false) $count++;
                if ($scanResults['header_footer']['has_suspicious'] ?? false) $count++;
                if ($scanResults['meta']['has_suspicious'] ?? false) $count++;
                if ($scanResults['sitemap']['has_suspicious'] ?? false) $count++;
                if ($scanResults['posts']['injected_html']['has_suspicious'] ?? false) $count++;
                if ($scanResults['pages']['injected_html']['has_suspicious'] ?? false) $count++;
                return $count;
            
            case 'file_integrity':
                return $scanResults['file_changes']['suspicious_count'] ?? 0;
            
            case 'performance':
                return (int)($scanResults['status']['response_time'] ?? 0);
            
            default:
                return 0;
        }
    }

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
            'performance' => 'fa-tachometer-alt',
        ];

        return $icons[$category] ?? 'fa-info-circle';
    }
}
