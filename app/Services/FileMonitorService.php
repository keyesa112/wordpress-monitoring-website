<?php

namespace App\Services;

use App\Models\FileSnapshot;
use App\Models\FileChange;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileMonitorService
{
    /**
     * Suspicious patterns untuk deteksi malware
     */
    protected $suspiciousPatterns = [
        // PHP backdoor patterns
        'eval\s*\(',
        'base64_decode\s*\(',
        'gzinflate\s*\(',
        'str_rot13\s*\(',
        'assert\s*\(',
        'preg_replace\s*\(.*\/e',
        'system\s*\(',
        'exec\s*\(',
        'shell_exec\s*\(',
        'passthru\s*\(',
        'proc_open\s*\(',
        'popen\s*\(',
        'escapeshellcmd\s*\(',
        '\$_GET\[',
        '\$_POST\[',
        '\$_REQUEST\[',
        '\$_COOKIE\[',
        '\$_SERVER\[',
        'file_get_contents\s*\(\s*["\']http',
        'fopen\s*\(\s*["\']http',
        'curl_exec\s*\(',
        'fsockopen\s*\(',
        'socket_create\s*\(',
        'move_uploaded_file\s*\(',
        'chmod\s*\(\s*.*\s*,\s*0777',
        'FilesMan',
        'c99shell',
        'r57shell',
        'WSO\s+shell',
        
        // Obfuscation patterns
        '\\\x[0-9a-fA-F]{2}',
        'chr\s*\(\s*\d+\s*\)',
        '\\\\[0-7]{3}',
        
        // Gambling keywords (WARNING - MEDIUM PRIORITY)
        'slot\s+gacor',
        'slot\s+online',
        'situs\s+slot',
        'link\s+slot',
        'judi\s+online',
        'togel\s+online',
        'casino\s+online',
        'bandar\s+togel',
        'taruhan\s+bola',
        'bonus\s+new\s+member',
        'deposit\s+pulsa',
        'game\s+penghasil\s+uang',
        'info\s+link\s+gacor',
        'gampang\s+menang',
        'maxwin',
        'slot88',
        'slot77',
        'toto88',
        'markasbola365',
        'bet777',
        'zeus365',
        'fairslot',
        'sbobet',
    ];

    /**
     * Directories to monitor (relative to website root)
     */
    protected $monitoredDirs = [
        'wp-content/themes',
        'wp-content/plugins',
        'wp-content/uploads',
    ];

    /**
     * File extensions to scan
     */
    protected $scannedExtensions = ['php', 'js', 'html', 'htm', 'txt', 'htaccess'];

    /**
     * Create baseline snapshot (first scan)
     */
    public function createBaseline($websiteId, $rootPath)
    {
        set_time_limit(600); // 10 minutes

        Log::info("Creating file baseline for website ID: {$websiteId}");

        $files = $this->scanDirectory($rootPath);
        $snapshotCount = 0;

        foreach ($files as $filePath => $fileInfo) {
            FileSnapshot::updateOrCreate(
                [
                    'website_id' => $websiteId,
                    'file_path' => $filePath,
                ],
                [
                    'file_hash' => $fileInfo['hash'],
                    'file_size' => $fileInfo['size'],
                    'last_modified' => $fileInfo['modified'],
                    'status' => 'active',
                ]
            );
            $snapshotCount++;
        }

        Log::info("Baseline created: {$snapshotCount} files tracked");

        return [
            'success' => true,
            'files_tracked' => $snapshotCount,
        ];
    }

    /**
     * Compare current state with baseline
     */
    public function compareWithBaseline($websiteId, $rootPath)
    {
        set_time_limit(600);

        Log::info("Comparing files for website ID: {$websiteId}");

        // Get current files
        $currentFiles = $this->scanDirectory($rootPath);
        
        // Get baseline from database
        $baseline = FileSnapshot::where('website_id', $websiteId)
            ->where('status', 'active')
            ->get()
            ->keyBy('file_path');

        $changes = [
            'new' => [],
            'modified' => [],
            'deleted' => [],
        ];

        // Check for NEW and MODIFIED files
        foreach ($currentFiles as $filePath => $fileInfo) {
            if (!isset($baseline[$filePath])) {
                // NEW FILE
                $changes['new'][] = $filePath;
                $this->processNewFile($websiteId, $filePath, $fileInfo, $rootPath);
            } else {
                // CHECK IF MODIFIED
                if ($baseline[$filePath]->file_hash !== $fileInfo['hash']) {
                    $changes['modified'][] = $filePath;
                    $this->processModifiedFile($websiteId, $filePath, $fileInfo, $baseline[$filePath], $rootPath);
                }
                
                // Update snapshot
                $baseline[$filePath]->update([
                    'file_hash' => $fileInfo['hash'],
                    'file_size' => $fileInfo['size'],
                    'last_modified' => $fileInfo['modified'],
                ]);
            }
        }

        // Check for DELETED files
        foreach ($baseline as $filePath => $snapshot) {
            if (!isset($currentFiles[$filePath])) {
                $changes['deleted'][] = $filePath;
                $this->processDeletedFile($websiteId, $filePath, $snapshot);
            }
        }

        Log::info("File comparison completed", [
            'new' => count($changes['new']),
            'modified' => count($changes['modified']),
            'deleted' => count($changes['deleted']),
        ]);

        return [
            'success' => true,
            'changes' => $changes,
            'total_changes' => count($changes['new']) + count($changes['modified']) + count($changes['deleted']),
        ];
    }

    /**
     * Scan directory recursively
     */
    protected function scanDirectory($rootPath)
    {
        $files = [];

        foreach ($this->monitoredDirs as $dir) {
            $fullPath = rtrim($rootPath, '/') . '/' . $dir;
            
            if (!is_dir($fullPath)) {
                Log::warning("Directory not found: {$fullPath}");
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $this->shouldScanFile($file->getFilename())) {
                    $relativePath = str_replace($rootPath . '/', '', $file->getPathname());
                    
                    $files[$relativePath] = [
                        'hash' => hash_file('sha256', $file->getPathname()),
                        'size' => $file->getSize(),
                        'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                        'full_path' => $file->getPathname(),
                    ];
                }
            }
        }

        return $files;
    }

    /**
     * Check if file should be scanned
     */
    protected function shouldScanFile($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $this->scannedExtensions);
    }

    /**
     * Process new file
     */
    protected function processNewFile($websiteId, $filePath, $fileInfo, $rootPath)
    {
        // Create snapshot
        FileSnapshot::create([
            'website_id' => $websiteId,
            'file_path' => $filePath,
            'file_hash' => $fileInfo['hash'],
            'file_size' => $fileInfo['size'],
            'last_modified' => $fileInfo['modified'],
            'status' => 'active',
        ]);

        // Scan for suspicious content
        $scanResult = $this->scanFileContent($fileInfo['full_path']);

        FileChange::create([
            'website_id' => $websiteId,
            'file_path' => $filePath,
            'change_type' => 'new',
            'new_hash' => $fileInfo['hash'],
            'is_suspicious' => $scanResult['is_suspicious'],
            'suspicious_patterns' => $scanResult['patterns'],
            'file_preview' => $scanResult['preview'],
            'severity' => $scanResult['severity'],
            'recommendation' => $this->getRecommendation('new', $scanResult),
        ]);
    }

    /**
     * Process modified file
     */
    protected function processModifiedFile($websiteId, $filePath, $fileInfo, $oldSnapshot, $rootPath)
    {
        // Scan for suspicious content
        $scanResult = $this->scanFileContent($fileInfo['full_path']);

        FileChange::create([
            'website_id' => $websiteId,
            'file_path' => $filePath,
            'change_type' => 'modified',
            'old_hash' => $oldSnapshot->file_hash,
            'new_hash' => $fileInfo['hash'],
            'is_suspicious' => $scanResult['is_suspicious'],
            'suspicious_patterns' => $scanResult['patterns'],
            'file_preview' => $scanResult['preview'],
            'severity' => $scanResult['severity'],
            'recommendation' => $this->getRecommendation('modified', $scanResult),
        ]);
    }

    /**
     * Process deleted file
     */
    protected function processDeletedFile($websiteId, $filePath, $snapshot)
    {
        // Mark snapshot as deleted
        $snapshot->update(['status' => 'deleted']);

        // Record change
        FileChange::create([
            'website_id' => $websiteId,
            'file_path' => $filePath,
            'change_type' => 'deleted',
            'old_hash' => $snapshot->file_hash,
            'is_suspicious' => false,
            'severity' => 'info',
            'recommendation' => 'File deleted. Verify if this was intentional.',
        ]);
    }

    /**
     * Scan file content for suspicious patterns
     */
    protected function scanFileContent($filePath)
    {
        try {
            // Read first 100KB only to avoid memory issues
            $content = file_get_contents($filePath, false, null, 0, 102400);
            
            $foundPatterns = [];
            $isSuspicious = false;

            foreach ($this->suspiciousPatterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $content)) {
                    $foundPatterns[] = $pattern;
                    $isSuspicious = true;
                }
            }

            // Get file preview (first 500 chars)
            $preview = mb_substr($content, 0, 500);

            // Determine severity
            $severity = 'info';
            if ($isSuspicious) {
                if (preg_match('/(eval|base64_decode|gzinflate|system|exec)/i', $content)) {
                    $severity = 'critical';
                } else {
                    $severity = 'warning';
                }
            }

            return [
                'is_suspicious' => $isSuspicious,
                'patterns' => $foundPatterns,
                'preview' => $preview,
                'severity' => $severity,
            ];

        } catch (\Exception $e) {
            Log::error("Error scanning file: {$filePath} - " . $e->getMessage());
            
            return [
                'is_suspicious' => false,
                'patterns' => [],
                'preview' => 'Error reading file',
                'severity' => 'info',
            ];
        }
    }

    /**
     * Get recommendation based on change type and scan result
     */
    protected function getRecommendation($changeType, $scanResult)
    {
        if (!$scanResult['is_suspicious']) {
            if ($changeType === 'new') {
                return 'New file detected. Verify if this is expected.';
            } elseif ($changeType === 'modified') {
                return 'File modified. Review changes if necessary.';
            }
        }

        if ($scanResult['severity'] === 'critical') {
            return 'CRITICAL: Suspicious code detected! This file may contain malware. Quarantine immediately and restore from backup.';
        } elseif ($scanResult['severity'] === 'warning') {
            return 'WARNING: Suspicious patterns found. Review file content and verify legitimacy.';
        }

        return 'Review this change and verify if it was authorized.';
    }
}
