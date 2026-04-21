<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTenantSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = $request->user()->tenant;

        if (!$tenant->subscription_active) {
            return response()->json(['message' => 'Organization subscription is inactive.'], 403);
        }

        if ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isPast()) {
            return response()->json(['message' => 'Organization subscription has expired.'], 403);
        }

        return $next($request);
    }
}