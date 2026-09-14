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
        Schema::create('review_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('review_id')
                ->constrained()
                ->cascadeOnDelete();
            
            // ID медиа в Яндекс.Картах
            $table->string('external_id');
            
            // photo / video
            $table->string('type', 20);
            
            // URL-шаблон Яндекса с {size}
            $table->text('url_template')->nullable();
            
            // Время создания в Яндексе
            $table->timestamp('external_created_at')->nullable();
            
            $table->timestamps();
            
            // Одно медиа не должно дублироваться внутри отзыва
            $table->unique(['review_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_media');
    }
};
