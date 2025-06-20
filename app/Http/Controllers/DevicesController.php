<?php

namespace App\Http\Controllers;

use App\Models\Devices;
use Illuminate\Http\Request;
use App\Models\DevicesStatusLogs;
use App\Http\Services\NetworkCheck;

class DevicesController extends Controller
{
    protected $networkCheck;

    public function __construct(NetworkCheck $networkCheck)
    {
        $this->networkCheck = $networkCheck;
    }

    /**
     * Display the devices monitoring dashboard
     */
    public function index()
    {
        $devices = Devices::with(['latestStatusLog'])->get();
        
        return view('devices.index', compact('devices'));
    }

    /**
     * Check all devices and return JSON response
     */
    public function checkAllDevices()
    {
        $devices = Devices::all();

        if($devices->count() == 0) {
            return response()->json([
                'success' => false,
                'message' => 'No devices found.'
            ]);
        }

        $results = [];
        $onlineCount = 0;
        $offlineCount = 0;

        foreach ($devices as $device) {
            $result = $this->ping($device->ip_address, $device->name);
            
            // Update device status
            $device->update([
                'is_online' => $result['is_online'],
                'last_seen_at' => $result['is_online'] ? now() : $device->last_seen_at,
            ]);

            // Log to status log table
            DevicesStatusLogs::create([
                'device_id' => $device->id,
                'is_online' => $result['is_online'],
                'latency' => $result['latency'],
                'checked_at' => now(),
            ]);

            $results[] = [
                'device' => $device,
                'status' => $result
            ];

            if ($result['is_online']) {
                $onlineCount++;
            } else {
                $offlineCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'All devices checked successfully.',
            'summary' => [
                'total' => $devices->count(),
                'online' => $onlineCount,
                'offline' => $offlineCount,
                'success_rate' => round(($onlineCount / $devices->count()) * 100, 2)
            ],
            'results' => $results,
            'checked_at' => now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check a specific device
     */
    public function checkDevice($id)
    {
        $device = Devices::findOrFail($id);
        $result = $this->ping($device->ip_address, $device->name);
        
        // Update device status
        $device->update([
            'is_online' => $result['is_online'],
            'last_seen_at' => $result['is_online'] ? now() : $device->last_seen_at,
        ]);

        // Log to status log table
        DevicesStatusLogs::create([
            'device_id' => $device->id,
            'is_online' => $result['is_online'],
            'latency' => $result['latency'],
            'checked_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'device' => $device->fresh(), // Get updated device data
            'status' => $result
        ]);
    }

    /**
     * Get devices status for dashboard (real-time updates)
     */
    public function getDevicesStatus()
    {
        $devices = Devices::with(['latestStatusLog'])->get();
        
        $summary = [
            'total' => $devices->count(),
            'online' => $devices->where('is_online', true)->count(),
            'offline' => $devices->where('is_online', false)->count(),
        ];
        
        $summary['success_rate'] = $summary['total'] > 0 
            ? round(($summary['online'] / $summary['total']) * 100, 2) 
            : 0;

        return response()->json([
            'success' => true,
            'devices' => $devices,
            'summary' => $summary,
            'last_updated' => now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get device status history
     */
    public function getDeviceHistory($id, Request $request)
    {
        $device = Devices::findOrFail($id);
        $hours = $request->get('hours', 24); // Default to last 24 hours
        
        $logs = DevicesStatusLogs::where('device_id', $id)
            ->where('checked_at', '>=', now()->subHours($hours))
            ->orderBy('checked_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'device' => $device,
            'logs' => $logs,
            'period' => "{$hours} hours"
        ]);
    }

    /**
     * Ping a specific IP address
     */
    private function ping($ip, $name = null)
    {
        // Create URL from IP (assuming HTTP, you can modify this)
        $url = "http://{$ip}";
        $deviceName = $name ?? $ip;
        
        try {
            $result = $this->networkCheck->pingServer($url, $deviceName, 5, 2);
            
            return [
                'is_online' => $result['is_reachable'],
                'latency' => $result['response_time_ms'] ?? null,
                'status_code' => $result['status_code'] ?? null,
                'message' => $result['message'],
                'attempts' => $result['attempts'] ?? 1
            ];
        } catch (\Exception $e) {
            return [
                'is_online' => false,
                'latency' => null,
                'status_code' => null,
                'message' => 'Error: ' . $e->getMessage(),
                'attempts' => 1
            ];
        }
    }

    /**
     * Alternative ping method using system ping
     */
    private function systemPing($ip, $name = null)
    {
        try {
            $result = $this->networkCheck->systemPing($ip, 5);
            
            return [
                'is_online' => $result['is_reachable'],
                'latency' => $this->extractLatencyFromPing($result['output']),
                'status_code' => null,
                'message' => $result['is_reachable'] ? 'Device is reachable' : 'Device is not reachable',
                'attempts' => 1
            ];
        } catch (\Exception $e) {
            return [
                'is_online' => false,
                'latency' => null,
                'status_code' => null,
                'message' => 'Error: ' . $e->getMessage(),
                'attempts' => 1
            ];
        }
    }

    /**
     * Extract latency from ping output
     */
    private function extractLatencyFromPing($output)
    {
        if (preg_match('/time[<=](\d+(?:\.\d+)?)ms/', $output, $matches)) {
            return (float) $matches[1];
        }
        return null;
    }

    /**
     * Manual refresh all devices (for button click)
     */
    public function refreshAll()
    {
        return $this->checkAllDevices();
    }
}
