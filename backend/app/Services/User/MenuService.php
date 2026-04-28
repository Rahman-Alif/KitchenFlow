<?php

namespace App\Services\User;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class MenuService
{
    public function getAvailableMenu(int $tenantId): Collection
    {
        return Category::where('tenant_id', $tenantId)
            ->whereHas('menuItems', function ($query) {
                // Show items to users only if stock > 0
                $query->where('stock_quantity', '>', 0);
            })
            ->with(['menuItems' => function ($query) {
                // Show items to users only if stock > 0
                $query->where('stock_quantity', '>', 0)
                      ->oldest('name');
            }])
            ->oldest('name')
            ->get();
    }
}