<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('metric'); // api_calls, storage_mb
            $table->decimal('value', 16, 4)->default(0);
            $table->timestamp('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
