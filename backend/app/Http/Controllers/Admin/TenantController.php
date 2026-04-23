<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\TenantResource;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function show(Request $request): TenantResource
    {
        return new TenantResource($request->user()->tenant);
    }
}