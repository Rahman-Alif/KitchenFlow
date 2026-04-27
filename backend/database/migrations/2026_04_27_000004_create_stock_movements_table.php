<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('stock_id')->constrained('stock')->onDelete('cascade');
            $table->enum('movement_type', ['initial', 'restock', 'sale', 'adjustment', 'damage', 'return'])->default('adjustment');
            $table->integer('quantity_changed');
            $table->integer('previous_quantity');
            $table->integer('new_quantity');
            $table->string('reason', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
