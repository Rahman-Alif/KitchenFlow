<?php

namespace App\Http\Controllers\KitchenStaff;

use App\Http\Controllers\Controller;
use App\Http\Requests\KitchenStaff\UpdateAvailabilityRequest;
use App\Http\Resources\KitchenStaff\MenuItemResource;
use App\Services\KitchenStaff\MenuAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MenuAvailabilityController extends Controller
{
    public function __construct(
        private readonly MenuAvailabilityService $menuAvailabilityService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $request->user()->tenant_id;

        $items = $this->menuAvailabilityService->getAllItems($tenantId);

        return MenuItemResource::collection($items);
    }

    public function updateAvailability(UpdateAvailabilityRequest $request, int $id): MenuItemResource
    {
        $tenantId = $request->user()->tenant_id;

        $item = $this->menuAvailabilityService->updateAvailability(
            $id,
            $request->validated('is_available'),
            $tenantId
        );

        return new MenuItemResource($item);
    }
}