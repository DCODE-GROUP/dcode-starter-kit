<?php

namespace App\Http\Middleware;

use App\Models\Tracking\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuditLog
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->is('admin/*') && $request->route() !== null) {
            $action = $request->route()->getAction();
            $controller = $action['controller'] ?? $action['uses'] ?? null;

            if (! $controller instanceof Closure) {
                AuditLog::create([
                    'user_id' => $request->user()->getAuthIdentifier(),
                    'user_ip' => (string) $request->server('HTTP_X_REAL_IP', $request->ip()),
                    'requested_url' => $request->fullUrl(),
                    'referrer_url' => $request->server('HTTP_REFERER'),
                    'requested_method' => $request->method(),
                    'requested_action' => $request->route()->getActionMethod(),
                    'action_routing' => is_string($controller) ? $controller : $request->route()->getName(),
                ]);
            }
        }

        return $next($request);
    }
}
