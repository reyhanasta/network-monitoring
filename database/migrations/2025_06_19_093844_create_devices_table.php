<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "PC-Admin", "SIMRS Server"
            $table->string('ip_address'); // e.g. "192.168.1.10"
            $table->enum('type', ['pc', 'server', 'router', 'printer', 'other'])->default('pc');
            $table->timestamp('last_seen_at')->nullable(); // Last successful ping
            $table->boolean('is_online')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
