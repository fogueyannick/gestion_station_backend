<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            // Index carburants
            $table->decimal('super1_index')->default(0);
            $table->decimal('super2_index')->default(0);
            $table->decimal('super3_index')->default(0);
            $table->decimal('gazoil1_index')->default(0);
            $table->decimal('gazoil2_index')->default(0);
            $table->decimal('gazoil3_index')->default(0);

            // Ventes calculées
            $table->decimal('super_sales', 12, 2)->default(0);
            $table->decimal('gazoil_sales', 12, 2)->default(0);
            $table->decimal('total_sales', 12, 2)->default(0);

            // Stocks
            $table->integer('stock_sup_9000')->default(0);
            $table->integer('stock_sup_10000')->default(0);
            $table->integer('stock_sup_14000')->default(0);
            $table->integer('stock_gaz_10000')->default(0);
            $table->integer('stock_gaz_6000')->default(0);

            // Versement et dépenses
            $table->decimal('versement', 12, 2)->default(0);
            $table->json('depenses')->nullable()->default(json_encode([]));
            $table->json('autres_ventes')->nullable()->default(json_encode([]));
            $table->json('commandes')->nullable()->default(json_encode([]));

            // Photos
            $table->json('photos')->nullable()->default(json_encode([]));

            $table->timestamps();

            // Unique par station + user + date
            $table->unique(['station_id', 'user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
