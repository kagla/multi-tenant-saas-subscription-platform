<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('is_active');
            $table->string('primary_color', 7)->default('#3B82F6')->after('logo_path');
            $table->string('secondary_color', 7)->default('#10B981')->after('primary_color');
            $table->string('custom_domain')->nullable()->after('secondary_color');
            $table->string('email_from_name')->nullable()->after('custom_domain');
            $table->string('email_from_address')->nullable()->after('email_from_name');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path', 'primary_color', 'secondary_color',
                'custom_domain', 'email_from_name', 'email_from_address',
            ]);
        });
    }
};
