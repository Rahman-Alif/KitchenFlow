<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Category;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditController extends Controller
{
    /**
     * Get audit history for a menu item
     * Demonstrates created_by, updated_by, deleted_by fields
     */
    public function menuItemAudit(MenuItem $menuItem): JsonResource
    {
        $auditData = [
            'menu_item' => [
                'id' => $menuItem->id,
                'name' => $menuItem->name,
                'price' => $menuItem->price,
            ],
            'audit_trail' => [
                'created_at' => $menuItem->created_at,
                'created_by' => $menuItem->createdBy?->only(['id', 'name', 'email']),
                'updated_at' => $menuItem->updated_at,
                'updated_by' => $menuItem->updatedBy?->only(['id', 'name', 'email']),
                'deleted_at' => $menuItem->deleted_at,
                'deleted_by' => $menuItem->deletedBy?->only(['id', 'name', 'email']),
                'is_deleted' => (bool) $menuItem->deleted_at,
            ],
            'summary' => $this->buildAuditSummary($menuItem),
        ];

        return JsonResource::make([
            'success' => true,
            'data' => $auditData,
            'message' => 'Audit trail retrieved - demonstrates audit columns in action',
        ]);
    }

    /**
     * Get audit history for a category
     * Another example of audit tracking
     */
    public function categoryAudit(Category $category): JsonResource
    {
        $auditData = [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'audit_trail' => [
                'created_at' => $category->created_at,
                'created_by' => $category->createdBy?->only(['id', 'name', 'email']),
                'updated_at' => $category->updated_at,
                'updated_by' => $category->updatedBy?->only(['id', 'name', 'email']),
                'deleted_at' => $category->deleted_at,
                'deleted_by' => $category->deletedBy?->only(['id', 'name', 'email']),
                'is_deleted' => (bool) $category->deleted_at,
            ],
            'summary' => $this->buildAuditSummary($category),
        ];

        return JsonResource::make([
            'success' => true,
            'data' => $auditData,
            'message' => 'Category audit trail retrieved',
        ]);
    }

    /**
     * Get all recent changes across the system
     * Demonstrates that all tables now track who made changes
     */
    public function recentChanges(): JsonResource
    {
        // Get recently updated menu items
        $updatedItems = MenuItem::whereNotNull('updated_by')
            ->where('updated_at', '>=', now()->subDays(7))
            ->orderBy('updated_at', 'desc')
            ->with(['updatedBy:id,name,email'])
            ->limit(10)
            ->get(['id', 'name', 'updated_at', 'updated_by'])
            ->map(fn($item) => [
                'type' => 'Menu Item Update',
                'resource_id' => $item->id,
                'resource_name' => $item->name,
                'changed_at' => $item->updated_at,
                'changed_by' => $item->updatedBy?->only(['id', 'name', 'email']),
            ]);

        // Get recently created categories
        $createdCategories = Category::whereNotNull('created_by')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->with(['createdBy:id,name,email'])
            ->limit(10)
            ->get(['id', 'name', 'created_at', 'created_by'])
            ->map(fn($cat) => [
                'type' => 'Category Created',
                'resource_id' => $cat->id,
                'resource_name' => $cat->name,
                'changed_at' => $cat->created_at,
                'changed_by' => $cat->createdBy?->only(['id', 'name', 'email']),
            ]);

        // Get recently deleted items
        $deletedItems = MenuItem::onlyTrashed()
            ->whereNotNull('deleted_by')
            ->where('deleted_at', '>=', now()->subDays(7))
            ->orderBy('deleted_at', 'desc')
            ->with(['deletedBy:id,name,email'])
            ->limit(10)
            ->get(['id', 'name', 'deleted_at', 'deleted_by'])
            ->map(fn($item) => [
                'type' => 'Menu Item Deleted',
                'resource_id' => $item->id,
                'resource_name' => $item->name,
                'changed_at' => $item->deleted_at,
                'changed_by' => $item->deletedBy?->only(['id', 'name', 'email']),
            ]);

        // Merge and sort by timestamp
        $allChanges = $updatedItems->concat($createdCategories)->concat($deletedItems)
            ->sortByDesc('changed_at')
            ->values();

        return JsonResource::make([
            'success' => true,
            'data' => [
                'recent_changes' => $allChanges,
                'total_changes' => $allChanges->count(),
                'period' => 'Last 7 days',
            ],
            'message' => 'Recent system changes - demonstrates audit columns across multiple tables',
        ]);
    }

    /**
     * Helper method to build an audit summary
     */
    private function buildAuditSummary($model): string
    {
        $parts = [];

        if ($model->created_by) {
            $creator = $model->createdBy;
            $parts[] = "Created by {$creator->name} ({$creator->email}) on {$model->created_at->format('Y-m-d H:i:s')}";
        } else {
            $parts[] = "Created (creator unknown) on {$model->created_at->format('Y-m-d H:i:s')}";
        }

        if ($model->updated_by && $model->updated_at != $model->created_at) {
            $updater = $model->updatedBy;
            $parts[] = "Last updated by {$updater->name} ({$updater->email}) on {$model->updated_at->format('Y-m-d H:i:s')}";
        }

        if ($model->deleted_at) {
            if ($model->deleted_by) {
                $deleter = $model->deletedBy;
                $parts[] = "Soft deleted by {$deleter->name} ({$deleter->email}) on {$model->deleted_at->format('Y-m-d H:i:s')}";
            } else {
                $parts[] = "Soft deleted (deleter unknown) on {$model->deleted_at->format('Y-m-d H:i:s')}";
            }
        }

        return implode(". ", $parts) . ".";
    }
}
