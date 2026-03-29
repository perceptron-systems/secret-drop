<?php

/*
|--------------------------------------------------------------------------
| Database Schema — Secret-Drop
|--------------------------------------------------------------------------
|
|  ┌──────────────────────────────────────────────────────────────────┐
|  │                           secrets                                │
|  ├──────────────────────────────────────────────────────────────────┤
|  │ PK  id                 uuid                                      │
|  │ UQ  token              varchar(32)                               │
|  │ UQ  admin_token_hash   varchar(64)                               │
|  │     type               enum(text,file)                           │
|  │     cipher_meta        json                                      │
|  │     ciphertext         longtext          NULL                    │
|  │     file_path          varchar           NULL                    │
|  │     max_views          uint              NULL                    │
|  │     read_count         uint              = 0                     │
|  │     first_read_at      timestamp         NULL                    │
|  │     last_read_at       timestamp         NULL                    │
|  │ IX  expire_at          timestamp         NULL                    │
|  │     revoked_at         timestamp         NULL                    │
|  │ IX  creator_email_hash varchar           NULL                    │
|  │ IX  created_at / updated_at                                      │
|  └──────────────┬───────────────────────────────────────────────────┘
|                 │ creator_email_hash
|                 │ = email_hash
|                 ▼ (logical, no FK)
|  ┌──────────────────────────────────────────────────────────────────┐
|  │                         magic_links                              │
|  ├──────────────────────────────────────────────────────────────────┤
|  │ PK  id                 uuid                                      │
|  │ IX  email_hash         varchar(64)                               │
|  │ UQ  token_hash         varchar(64)                               │
|  │     expire_at          timestamp                                 │
|  │     used_at            timestamp         NULL                    │
|  │     created_at / updated_at                                      │
|  └──────────────────────────────────────────────────────────────────┘
|
|  ┌──────────────────────────────────────────────────────────────────┐
|  │                    personal_access_tokens                        │
|  ├──────────────────────────────────────────────────────────────────┤
|  │ PK  id                 bigint auto                               │
|  │     tokenable_type     varchar      ─┐ morphs                    │
|  │ IX  tokenable_id       bigint       ─┘                           │
|  │     name               text                                      │
|  │ UQ  token              varchar(64)                               │
|  │     abilities          text              NULL                    │
|  │     last_used_at       timestamp         NULL                    │
|  │ IX  expires_at         timestamp         NULL                    │
|  │     created_at / updated_at                                      │
|  └──────────────────────────────────────────────────────────────────┘
|
|  ┌──────────────────────────────────────────────────────────────────┐
|  │                      stats_*  (analytics)                        │
|  ├──────────────────────────────────────────────────────────────────┤
|  │                                                                  │
|  │  stats_daily        UQ(date, metric)                             │
|  │  stats_heatmap      UQ(date, day_of_week, hour, metric)          │
|  │  stats_pageviews    UQ(date, page, is_bot, hour, country, loc.)  │
|  │  stats_local_hours  UQ(date, local_hour)                         │
|  │  stats_referrers    UQ(date, referrer_domain, is_bot)            │
|  │  stats_devices      UQ(date, device_type)                        │
|  │  stats_bots         UQ(date, bot_name)                           │
|  │                                                                  │
|  │  Toutes avec: PK id (bigint auto), count, timestamps             │
|  └──────────────────────────────────────────────────────────────────┘
|
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('secrets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('token', 32)->unique();
            $table->string('admin_token_hash', 64)->unique();

            $table->enum('type', ['text', 'file']);
            $table->json('cipher_meta');
            $table->longText('ciphertext')->nullable();
            $table->string('file_path')->nullable();

            $table->unsignedInteger('max_views')->nullable();
            $table->unsignedInteger('read_count')->default(0);

            $table->timestamp('first_read_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('expire_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();

            $table->string('creator_email_hash')->nullable()->index();

            $table->timestamps();

            $table->index('created_at');
        });
    }
};
