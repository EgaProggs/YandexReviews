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
        Schema::create('parsing_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();
    
            // pending / running / completed / failed
            $table->string('status')->default('pending');
    
            // Прогресс
            $table->unsignedInteger('current_page')->default(0);
            $table->unsignedInteger('total_pages')->default(0);
            $table->unsignedInteger('reviews_found')->default(0);
    
            // Ошибка, если парсинг завершился неудачно
            $table->text('error_message')->nullable();
    
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parsing_runs');
    }
};
