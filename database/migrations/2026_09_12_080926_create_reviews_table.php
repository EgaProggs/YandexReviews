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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();
    
            // ID отзыва в Яндекс.Картах
            $table->string('external_id');
    
            // Автор
            $table->string('author_name')->nullable();
            $table->string('author_public_id')->nullable();
            $table->text('author_avatar_url')->nullable();
            $table->string('author_level')->nullable();
    
            // Отзыв
            $table->text('text')->nullable();
            $table->string('text_language', 10)->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
    
            // Дата изменения отзыва в Яндексе
            $table->timestamp('external_updated_at')->nullable();
    
            // Ответ организации
            $table->text('business_reply')->nullable();
            $table->timestamp('business_reply_updated_at')->nullable();
    
            $table->timestamps();
    
            // Один и тот же отзыв не должен дублироваться
            $table->unique(['organization_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
