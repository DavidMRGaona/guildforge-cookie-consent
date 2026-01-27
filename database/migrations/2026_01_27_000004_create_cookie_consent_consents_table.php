<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cookie_consent_consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visitor_id');
            $table->uuid('user_id')->nullable();
            $table->string('ip_hash', 64);
            $table->text('user_agent');
            $table->json('preferences');
            $table->unsignedInteger('config_version');
            $table->string('consent_method');
            $table->timestamp('consented_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['visitor_id', 'consented_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_consent_consents');
    }
};
