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
                // Show items to users only if they are marked as available
                $query->where('is_available', true);
            })
            ->with(['menuItems' => function ($query) {
                // Show items to users only if they are marked as available
                $query->where('is_available', true)
                      ->oldest('name');
            }])
            ->oldest('name')
            ->get();
    }
}