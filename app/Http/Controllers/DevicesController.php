<?php

namespace App\Http\Controllers;

use App\Models\Devices;
use Illuminate\Http\Request;
use App\Models\DevicesStatusLogs;

class DevicesController extends Controller
{
    public function checkAllDevices()
    {
        $devices = Devices::all();

        foreach ($devices as $device) {
            $result = $this->ping($device->ip_address);

            // Update device status
            $device->update([
                'is_online' => $result['is_online'],
                'last_seen_at' => $result['is_online'] ? now() : $device->last_seen_at, // only update if online
            ]);

            // Log to status log table
            DevicesStatusLogs::create([
                'device_id' => $device->id,
                'is_online' => $result['is_online'],
                'latency' => $result['latency'],
                'checked_at' => now(),
            ]);
        }

        return response()->json(['message' => 'All devices checked.']);
    }

    private function ping($ip)
    {
        // Windows: ping -n 1, Linux/macOS: ping -c 1
        $command = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "ping -n 1 {$ip}" : "ping -c 1 {$ip}";
        exec($command, $output, $status);

        $latency = null;

        // Try to extract latency (ms)
        foreach ($output as $line) {
            if (preg_match('/time[=<]([\d.]+) ?ms/', $line, $matches)) {
                $latency = (int) $matches[1];
                break;
            }
        }

        return [
            'is_online' => $status === 0,
            'latency' => $latency,
        ];
    }
}
