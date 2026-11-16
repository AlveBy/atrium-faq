<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categories of questions and the questions themselves, per site, with the
 * texts in every content language (JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->json('name');
            $table->json('code');
            $table->boolean('active')->default(true);
            $table->integer('sort')->default(500);
            $table->timestamps();
        });

        Schema::create('faq_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->foreignId('category_id')->index();
            $table->json('question');
            $table->json('answer')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort')->default(500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_questions');
        Schema::dropIfExists('faq_categories');
    }
};
