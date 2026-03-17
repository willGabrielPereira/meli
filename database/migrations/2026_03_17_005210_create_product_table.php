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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('meli_id');
            $table->integer('seller');
            $table->string('title');
            $table->string('status');
            $table->dateTime('last_sync');
            $table->timestamps();

            // Índice composto único: garante que não existam duplicatas por seller+anúncio
            $table->unique(['seller', 'meli_id']);

            // Índice em status para facilitar filtros futuros (ex: WHERE status = 'active')
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
