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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_session_id')->constrained()->onDelete('cascade');
            
            // File Information
            $table->string('filename');
            $table->string('original_name');
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size')->comment('in bytes');
            $table->string('mime_type', 100);
            
            // Status & Review
            $table->enum('status', ['pending', 'approved', 'rejected', 'processing'])->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_note')->nullable();
            
            // Google Vision API flags
            $table->boolean('is_flagged')->default(false);
            $table->text('flagged_reason')->nullable()->comment('Which categories flagged this image');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('upload_session_id');
            $table->index('status');
            $table->index('is_flagged');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
