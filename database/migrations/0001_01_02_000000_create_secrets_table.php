<?php

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
