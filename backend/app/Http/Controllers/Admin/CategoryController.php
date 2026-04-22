<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateCategoryRequest;
use App\Http\Resources\Admin\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = Category::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(CreateCategoryRequest $request): CategoryResource
    {
        $category = Category::create([
            'tenant_id' => $request->user()->tenant_id,
            'name'      => $request->name,
        ]);

        return new CategoryResource($category);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        if ($category->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Unauthorized.');
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }
}