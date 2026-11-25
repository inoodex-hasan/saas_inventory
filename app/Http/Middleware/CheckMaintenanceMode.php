<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if maintenance mode is enabled
        $isMaintenance = (bool) SiteSetting::get('maintenance_mode', false);
        if (!$isMaintenance) {
            return $next($request);
        }

        // 2. Allow request if query has valid bypass key
        $bypassSecret = SiteSetting::get('maintenance_bypass_secret');
        if ($request->has('bypass') && !empty($bypassSecret) && hash_equals((string) $bypassSecret, (string) $request->query('bypass'))) {
            $request->session()->put('maintenance_bypassed', true);
            return $next($request);
        }

        // 3. Allow request if session already has valid bypass
        if ($request->session()->get('maintenance_bypassed')) {
            return $next($request);
        }

        // 4. Always allow authenticated Super Admins full access
        $user = $request->user();
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        // 5. Allow Super Admin routes & authentication endpoints so admin can sign in
        if ($request->is(
            'admin',
            'admin/*',
            'login',
            'logout',
            'two-factor-challenge',
            'maintenance-bypass/*',
            'maintenance-exit-bypass',
            'up',
            'build/*',
            'storage/*',
            'landing/*',
            'favicon.ico'
        )) {
            return $next($request);
        }

        // 6. Block all other requests with 503 Maintenance Response
        $brandName = SiteSetting::get('brand_name', config('app.name', 'InoManage'));
        $title = SiteSetting::get('maintenance_title', 'System Upgrade in Progress');
        $message = SiteSetting::get('maintenance_message', 'We are performing scheduled maintenance and database optimizations. The platform will be back online shortly.');
        $duration = SiteSetting::get('maintenance_duration', 'A few minutes');
        $supportEmail = SiteSetting::get('support_email', '');

        if ($request->expectsJson() || $request->header('X-Inertia')) {
            return response()->json([
                'message' => $message,
                'status' => 'maintenance',
                'title' => $title,
            ], 503);
        }

        return response()->view('errors.503', [
            'brandName' => $brandName,
            'title' => $title,
            'message' => $message,
            'duration' => $duration,
            'supportEmail' => $supportEmail,
        ], 503);
    }
}
