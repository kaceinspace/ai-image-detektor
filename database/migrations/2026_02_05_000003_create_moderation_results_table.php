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
        Schema::create('moderation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_id')->constrained()->onDelete('cascade');
            
            // Google Vision API Safe Search Likelihoods
            $table->enum('adult_likelihood', ['UNKNOWN', 'VERY_UNLIKELY', 'UNLIKELY', 'POSSIBLE', 'LIKELY', 'VERY_LIKELY'])->default('UNKNOWN');
            $table->enum('violence_likelihood', ['UNKNOWN', 'VERY_UNLIKELY', 'UNLIKELY', 'POSSIBLE', 'LIKELY', 'VERY_LIKELY'])->default('UNKNOWN');
            $table->enum('racy_likelihood', ['UNKNOWN', 'VERY_UNLIKELY', 'UNLIKELY', 'POSSIBLE', 'LIKELY', 'VERY_LIKELY'])->default('UNKNOWN');
            $table->enum('medical_likelihood', ['UNKNOWN', 'VERY_UNLIKELY', 'UNLIKELY', 'POSSIBLE', 'LIKELY', 'VERY_LIKELY'])->default('UNKNOWN');
            $table->enum('spoof_likelihood', ['UNKNOWN', 'VERY_UNLIKELY', 'UNLIKELY', 'POSSIBLE', 'LIKELY', 'VERY_LIKELY'])->default('UNKNOWN');
            
            // Raw API Response
            $table->json('raw_response')->nullable()->comment('Full JSON response from Google Vision API');
            
            // Analysis Metadata
            $table->unsignedInteger('processing_time_ms')->nullable()->comment('Time taken to process in milliseconds');
            $table->text('api_error')->nullable()->comment('Error message if API call failed');
            
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
            
            $table->index('image_id');
            $table->index('adult_likelihood');
            $table->index('violence_likelihood');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_results');
    }
};
