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

            case 'has_suspicious_header':
                return $scanResults['header_footer']['has_suspicious'] ?? false;

            case 'has_suspicious_meta':
                return $scanResults['meta']['has_suspicious'] ?? false;

            case 'has_suspicious_sitemap':
                return $scanResults['sitemap']['has_suspicious'] ?? false;

            case 'website_offline':
                // Check if status is offline or response time > 5000ms
                return ($scanResults['status']['status'] ?? '') === 'offline' ||
                       ($scanResults['status']['response_time'] ?? 0) > 5000;

            case 'no_suspicious_content':
                return !($scanResults['has_suspicious_content'] ?? false);

            case 'multiple_areas_infected':
                $infectedAreas = 0;
                if ($scanResults['posts']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['header_footer']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['meta']['has_suspicious'] ?? false) $infectedAreas++;
                if ($scanResults['sitemap']['has_suspicious'] ?? false) $infectedAreas++;
                
                return $infectedAreas >= ($recommendation['min_areas'] ?? 3);

            case 'wp_json_disabled':
                $error = $scanResults['posts']['error'] ?? '';
                return stripos($error, 'WP-JSON') !== false || 
                       stripos($error, 'WordPress') !== false;

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
            case 'header_footer':
                return $scanResults['header_footer']['keyword_count'] ?? 0;
            case 'meta':
                return $scanResults['meta']['keyword_count'] ?? 0;
            case 'sitemap':
                return count($scanResults['sitemap']['suspicious_urls'] ?? []);
            case 'multiple':
                $count = 0;
                if ($scanResults['posts']['has_suspicious'] ?? false) $count++;
                if ($scanResults['header_footer']['has_suspicious'] ?? false) $count++;
                if ($scanResults['meta']['has_suspicious'] ?? false) $count++;
                if ($scanResults['sitemap']['has_suspicious'] ?? false) $count++;
                return $count;
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
}
