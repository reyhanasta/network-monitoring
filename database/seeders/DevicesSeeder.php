<?php

namespace Database\Seeders;

use App\Models\Devices;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DevicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $devices = [
            [
                'name' => 'SIMRS Server',
                'ip_address' => '192.168.18.29',
                'type' => 'server',
                'is_online' => false,
                'last_seen_at' => null,
            ],
            [
                'name' => 'Admin PC',
                'ip_address' => '192.168.18.5',
                'type' => 'pc',
                'is_online' => false,
                'last_seen_at' => null,
            ],
            [
                'name' => 'PC Kasir',
                'ip_address' => '192.168.19.42',
                'type' => 'pc',
                'is_online' => false,
                'last_seen_at' => null,
            ],
            [
                'name' => 'PC Pendaftaran',
                'ip_address' => '192.168.19.248',
                'type' => 'pc',
                'is_online' => false,
                'last_seen_at' => null,
            ],
        ];

        foreach ($devices as $device) {
            Devices::create($device);
        }
    }
}
