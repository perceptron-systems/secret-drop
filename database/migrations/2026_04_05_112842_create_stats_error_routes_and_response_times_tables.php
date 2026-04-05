<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('stats_error_routes', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedSmallInteger('status');
            $table->string('route', 100);
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->unique(['date', 'status', 'route']);
        });

        Schema::create('stats_response_times', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('route_group', 30);
            $table->unsignedSmallInteger('bucket');
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->unique(['date', 'route_group', 'bucket']);
        });
    }
};
