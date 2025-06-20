<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class NetworkCheck
{
    protected $servers = [
        'simrs' => [
            'name' => 'SIMRS Server',
            'url' => 'http://192.168.18.29',
            'timeout' => 5,
            'retry' => 2
        ],
        'admin_pc' => [
            'name' => 'Admin PC',
            'url' => 'http://192.168.18.5',
            'timeout' => 5,
            'retry' => 2
        ]
    ];

    /**
     * Check all configured network connections
     */
    public function checkNetwork()
    {
        $results = [];
        $overallStatus = 'healthy';

        foreach ($this->servers as $key => $server) {
            $result = $this->pingServer($server['url'], $server['name'], $server['timeout'], $server['retry']);
            $results[$key] = $result;

            if (!$result['is_reachable']) {
                $overallStatus = 'unhealthy';
            }
        }

        return [
            'overall_status' => $overallStatus,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'servers' => $results,
            'summary' => $this->generateSummary($results)
        ];
    }

    /**
     * Ping a specific server
     */
    public function pingServer($url, $name = null, $timeout = 5, $maxRetries = 2)
    {
        $name = $name ?? $url;
        $startTime = microtime(true);
        $attempts = 0;
        $lastError = null;

        for ($i = 0; $i <= $maxRetries; $i++) {
            $attempts++;
            
            try {
                $response = Http::timeout($timeout)->get($url);
                $endTime = microtime(true);
                $responseTime = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

                if ($response->successful()) {
                    return [
                        'name' => $name,
                        'url' => $url,
                        'is_reachable' => true,
                        'status_code' => $response->status(),
                        'response_time_ms' => $responseTime,
                        'attempts' => $attempts,
                        'checked_at' => now()->format('Y-m-d H:i:s'),
                        'message' => 'Server is reachable'
                    ];
                } else {
                    $lastError = "HTTP {$response->status()}";
                }
            } catch (Exception $e) {
                $lastError = $e->getMessage();
            }

            // Wait a bit before retrying (except on last attempt)
            if ($i < $maxRetries) {
                usleep(500000); // Wait 0.5 seconds
            }
        }

        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2);

        return [
            'name' => $name,
            'url' => $url,
            'is_reachable' => false,
            'status_code' => null,
            'response_time_ms' => $responseTime,
            'attempts' => $attempts,
            'checked_at' => now()->format('Y-m-d H:i:s'),
            'message' => "Server is not reachable: {$lastError}",
            'error' => $lastError
        ];
    }

    /**
     * Check if network is healthy (all servers reachable)
     */
    public function isNetworkHealthy()
    {
        $result = $this->checkNetwork();
        return $result['overall_status'] === 'healthy';
    }

    /**
     * Check a specific server by key
     */
    public function checkSpecificServer($serverKey)
    {
        if (!isset($this->servers[$serverKey])) {
            return [
                'error' => 'Server not found',
                'available_servers' => array_keys($this->servers)
            ];
        }

        $server = $this->servers[$serverKey];
        return $this->pingServer($server['url'], $server['name'], $server['timeout'], $server['retry']);
    }

    /**
     * Add a new server to check dynamically
     */
    public function addServer($key, $name, $url, $timeout = 5, $retry = 2)
    {
        $this->servers[$key] = [
            'name' => $name,
            'url' => $url,
            'timeout' => $timeout,
            'retry' => $retry
        ];

        return $this;
    }

    /**
     * Remove a server from checks
     */
    public function removeServer($key)
    {
        unset($this->servers[$key]);
        return $this;
    }

    /**
     * Get list of configured servers
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * Generate summary of results
     */
    private function generateSummary($results)
    {
        $total = count($results);
        $reachable = 0;
        $unreachable = 0;
        $totalResponseTime = 0;

        foreach ($results as $result) {
            if ($result['is_reachable']) {
                $reachable++;
                $totalResponseTime += $result['response_time_ms'];
            } else {
                $unreachable++;
            }
        }

        $averageResponseTime = $reachable > 0 ? round($totalResponseTime / $reachable, 2) : 0;

        return [
            'total_servers' => $total,
            'reachable' => $reachable,
            'unreachable' => $unreachable,
            'success_rate' => $total > 0 ? round(($reachable / $total) * 100, 2) : 0,
            'average_response_time_ms' => $averageResponseTime
        ];
    }

    /**
     * Perform a simple ping using system ping command (alternative method)
     */
    public function systemPing($host, $timeout = 5)
    {
        // Extract hostname/IP from URL if needed
        $parsedUrl = parse_url($host);
        $hostname = $parsedUrl['host'] ?? $host;

        // Use system ping command
        $output = [];
        $returnVar = 0;
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows ping command
            exec("ping -n 1 -w " . ($timeout * 1000) . " {$hostname}", $output, $returnVar);
        } else {
            // Linux/Unix ping command
            exec("ping -c 1 -W {$timeout} {$hostname}", $output, $returnVar);
        }

        return [
            'host' => $hostname,
            'is_reachable' => $returnVar === 0,
            'output' => implode("\n", $output),
            'checked_at' => now()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Log network check results
     */
    public function logResults($results = null)
    {
        if ($results === null) {
            $results = $this->checkNetwork();
        }

        $logMessage = "Network Check - Overall Status: {$results['overall_status']} | ";
        $logMessage .= "Success Rate: {$results['summary']['success_rate']}% | ";
        $logMessage .= "Reachable: {$results['summary']['reachable']}/{$results['summary']['total_servers']}";

        if ($results['overall_status'] === 'healthy') {
            Log::info($logMessage);
        } else {
            Log::warning($logMessage);
            
            // Log details of failed servers
            foreach ($results['servers'] as $key => $server) {
                if (!$server['is_reachable']) {
                    Log::error("Server '{$server['name']}' ({$server['url']}) is unreachable: {$server['error']}");
                }
            }
        }

        return $results;
    }
}
