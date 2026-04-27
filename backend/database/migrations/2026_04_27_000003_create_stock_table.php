<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('menu_item_id')->unique()->constrained('menu_items')->onDelete('cascade');
            $table->integer('current_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(10);
            $table->integer('restock_level')->default(50);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
