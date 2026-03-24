<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('stats_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('metric', 50);
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['date', 'metric']);
            $table->index('date');
            $table->index('metric');
        });

        Schema::create('stats_heatmap', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->tinyInteger('day_of_week')->unsigned();
            $table->tinyInteger('hour')->unsigned();
            $table->string('metric', 50);
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['date', 'day_of_week', 'hour', 'metric']);
            $table->index('date');
        });

        Schema::create('stats_pageviews', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('page', 50);
            $table->boolean('is_bot')->default(false);
            $table->tinyInteger('hour')->unsigned();
            $table->string('country', 2)->default('XX');
            $table->string('locale', 5)->default('');
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->unique(['date', 'page', 'is_bot', 'hour', 'country', 'locale']);
            $table->index('date');
            $table->index(['page', 'date']);
        });

        Schema::create('stats_local_hours', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->tinyInteger('local_hour')->unsigned();
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->unique(['date', 'local_hour']);
            $table->index('date');
        });

        Schema::create('stats_referrers', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('referrer_domain', 100);
            $table->boolean('is_bot')->default(false);
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->unique(['date', 'referrer_domain', 'is_bot']);
            $table->index('date');
        });
    }
};
