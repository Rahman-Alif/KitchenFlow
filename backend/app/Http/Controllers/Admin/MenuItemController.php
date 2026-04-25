<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateMenuItemRequest;
use App\Http\Requests\Admin\UpdateMenuItemRequest;
use App\Http\Resources\Admin\MenuItemResource;
use App\Models\MenuItem;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MenuItemController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $request->user()->tenant_id;

        $items = MenuItem::with('category')
            ->whereHas('category', fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('created_at', 'desc')
            ->get();

        return MenuItemResource::collection($items);
    }

    public function store(CreateMenuItemRequest $request): MenuItemResource
    {
        $this->authorizeCategoryTenant($request->category_id, $request->user()->tenant_id);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu', 'public');
        }

        $item = MenuItem::create([
            'category_id'         => $request->category_id,
            'name'                => $request->name,
            'description'         => $request->description,
            'image_path'          => $imagePath,
            'price'               => $request->price,
            'stock_quantity'      => $request->stock_quantity,
            'low_stock_threshold' => $request->low_stock_threshold,
            'is_available'        => $request->is_available,
        ]);

        return new MenuItemResource($item->load('category'));
    }

    public function show(Request $request, MenuItem $menuItem): MenuItemResource
    {
        $this->authorizeItemTenant($menuItem, $request->user()->tenant_id);
        return new MenuItemResource($menuItem->load('category'));
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): MenuItemResource
    {
        $this->authorizeItemTenant($menuItem, $request->user()->tenant_id);

        if ($request->hasFile('image')) {
            if ($menuItem->image_path) {
                Storage::disk('public')->delete($menuItem->image_path);
            }
            $data['image_path'] = $request->file('image')->store('menu', 'public');
        }

        $data = array_merge(
            $request->only(['category_id', 'name', 'description', 'price', 'stock_quantity', 'low_stock_threshold', 'is_available']),
            $data ?? []
        );

        $menuItem->update($data);

        return new MenuItemResource($menuItem->load('category'));
    }

    public function destroy(Request $request, MenuItem $menuItem): JsonResponse
    {
        $this->authorizeItemTenant($menuItem, $request->user()->tenant_id);

        if ($menuItem->image_path) {
            Storage::disk('public')->delete($menuItem->image_path);
        }

        $menuItem->delete();

        return response()->json(['message' => 'Menu item deleted successfully.']);
    }

    public function restock(Request $request, MenuItem $menuItem): MenuItemResource
    {
        $this->authorizeItemTenant($menuItem, $request->user()->tenant_id);

        $request->validate(['quantity' => ['required', 'integer', 'min:1']]);

        $menuItem->stock_quantity += (int) $request->quantity;

        // Restock can re-enable only when stock is above threshold.
        if ($menuItem->stock_quantity > $menuItem->low_stock_threshold) {
            $menuItem->is_available = true;
        }

        $menuItem->save();

        return new MenuItemResource($menuItem->load('category'));
    }

    public function lowStock(Request $request): AnonymousResourceCollection
    {
        $tenantId = $request->user()->tenant_id;
    
        $items = MenuItem::whereHas('category', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0)
            ->with('category')
            ->orderBy('stock_quantity')
            ->get();
    
        return MenuItemResource::collection($items);
    }    

    private function authorizeCategoryTenant(int $categoryId, int $tenantId): void
    {
        $category = Category::findOrFail($categoryId);
        if ($category->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized.');
        }
    }

    private function authorizeItemTenant(MenuItem $menuItem, int $tenantId): void
    {
        if ($menuItem->category->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized.');
        }
    }
    
    public function updateAvailability(Request $request, MenuItem $menuItem): JsonResponse
    {
        $this->authorizeItemTenant($menuItem, $request->user()->tenant_id);
    
        $request->validate([
            'is_available' => ['required', 'boolean'],
        ]);
    
        $enabling = $request->boolean('is_available');
    
        // Prevent enabling if stock is at or below threshold
        if ($enabling && $menuItem->stock_quantity <= $menuItem->low_stock_threshold) {
            return response()->json([
                'message' => "Cannot enable — stock ({$menuItem->stock_quantity}) is at or below the low stock threshold ({$menuItem->low_stock_threshold}).",
            ], 422);
        }
    
        $menuItem->update(['is_available' => $enabling]);
    
        return response()->json(['message' => 'Availability updated.']);
    }
}