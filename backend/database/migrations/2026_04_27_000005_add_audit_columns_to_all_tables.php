<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'tenants',
            'categories',
            'menu_items',
            'orders',
            'order_items',
            'transactions',
            'messages',
            'stock',
            'stock_movements',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    if (!Schema::hasColumn($table->getTable(), 'created_by')) {
                        $table->foreignId('created_by')
                            ->nullable()
                            ->after('created_at')
                            ->constrained('users')
                            ->nullOnDelete();
                    }
                    if (!Schema::hasColumn($table->getTable(), 'updated_by')) {
                        $table->foreignId('updated_by')
                            ->nullable()
                            ->after('updated_at')
                            ->constrained('users')
                            ->nullOnDelete();
                    }
                    if (!Schema::hasColumn($table->getTable(), 'deleted_by')) {
                        $table->foreignId('deleted_by')
                            ->nullable()
                            ->after('deleted_at')
                            ->constrained('users')
                            ->nullOnDelete();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'tenants',
            'categories',
            'menu_items',
            'orders',
            'order_items',
            'transactions',
            'messages',
            'stock',
            'stock_movements',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'created_by')) {
                        $table->dropForeignIdFor('users', 'created_by');
                        $table->dropColumn('created_by');
                    }
                    if (Schema::hasColumn($table->getTable(), 'updated_by')) {
                        $table->dropForeignIdFor('users', 'updated_by');
                        $table->dropColumn('updated_by');
                    }
                    if (Schema::hasColumn($table->getTable(), 'deleted_by')) {
                        $table->dropForeignIdFor('users', 'deleted_by');
                        $table->dropColumn('deleted_by');
                    }
                });
            }
        }
    }
};
