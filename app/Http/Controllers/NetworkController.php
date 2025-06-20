<?php

namespace App\Http\Controllers;

use App\Http\Services\NetworkCheck;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NetworkController extends Controller
{
    protected $networkCheck;

    public function __construct(NetworkCheck $networkCheck)
    {
        $this->networkCheck = $networkCheck;
    }

    /**
     * Get complete network status
     */
    public function getNetworkStatus(): JsonResponse
    {
        $results = $this->networkCheck->checkNetwork();
        
        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Simple boolean check for network health
     */
    public function isNetworkHealthy(): JsonResponse
    {
        $isHealthy = $this->networkCheck->isNetworkHealthy();
        $results = $this->networkCheck->checkNetwork();
        
        return response()->json([
            'success' => true,
            'is_healthy' => $isHealthy,
            'message' => $isHealthy ? 'All network connections are healthy' : 'Some network connections have issues',
            'summary' => $results['summary']
        ]);
    }

    /**
     * Check a specific server by key
     */
    public function checkSpecificServer($serverKey): JsonResponse
    {
        $result = $this->networkCheck->checkSpecificServer($serverKey);
        
        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'available_servers' => $result['available_servers'] ?? []
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Check a custom URL
     */
    public function checkCustomUrl(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
            'name' => 'nullable|string|max:255',
            'timeout' => 'nullable|integer|min:1|max:30'
        ]);

        $url = $request->input('url');
        $name = $request->input('name', $url);
        $timeout = $request->input('timeout', 5);

        $result = $this->networkCheck->pingServer($url, $name, $timeout);
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Get list of configured servers
     */
    public function getServers(): JsonResponse
    {
        $servers = $this->networkCheck->getServers();
        
        return response()->json([
            'success' => true,
            'servers' => $servers
        ]);
    }

    /**
     * Add a new server to monitor
     */
    public function addServer(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'timeout' => 'nullable|integer|min:1|max:30',
            'retry' => 'nullable|integer|min:0|max:5'
        ]);

        $this->networkCheck->addServer(
            $request->input('key'),
            $request->input('name'),
            $request->input('url'),
            $request->input('timeout', 5),
            $request->input('retry', 2)
        );

        return response()->json([
            'success' => true,
            'message' => 'Server added successfully'
        ]);
    }

    /**
     * Remove a server from monitoring
     */
    public function removeServer($serverKey): JsonResponse
    {
        $servers = $this->networkCheck->getServers();
        
        if (!isset($servers[$serverKey])) {
            return response()->json([
                'success' => false,
                'message' => 'Server not found'
            ], 404);
        }

        $this->networkCheck->removeServer($serverKey);
        
        return response()->json([
            'success' => true,
            'message' => 'Server removed successfully'
        ]);
    }

    /**
     * System ping check (alternative method)
     */
    public function systemPing(Request $request): JsonResponse
    {
        $request->validate([
            'host' => 'required|string',
            'timeout' => 'nullable|integer|min:1|max:30'
        ]);

        $host = $request->input('host');
        $timeout = $request->input('timeout', 5);

        $result = $this->networkCheck->systemPing($host, $timeout);
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Dashboard view (if you want a web interface)
     */
    public function dashboard()
    {
        $networkStatus = $this->networkCheck->logResults(); // This will also log the results
        
        return view('network.dashboard', compact('networkStatus'));
    }

    /**
     * API endpoint for real-time monitoring
     */
    public function monitor(): JsonResponse
    {
        $results = $this->networkCheck->logResults();
        
        return response()->json([
            'success' => true,
            'timestamp' => now()->toISOString(),
            'data' => $results
        ]);
    }
}
