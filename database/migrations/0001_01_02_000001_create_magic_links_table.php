<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('magic_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email_hash', 64);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expire_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index('email_hash');
        });
    }
};
