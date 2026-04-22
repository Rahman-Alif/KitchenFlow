<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\MenuItemResource;
use App\Services\User\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct(
        private readonly MenuService $menuService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $categories = $this->menuService->getAvailableMenu($tenantId);

        $data = $categories->map(fn ($category) => [
            'category_id'   => $category->id,
            'category_name' => $category->name,
            'items'         => MenuItemResource::collection($category->menuItems)->resolve(),
        ]);

        return response()->json(['data' => $data]);
    }
}