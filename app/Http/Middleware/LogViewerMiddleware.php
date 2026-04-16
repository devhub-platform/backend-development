<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LogViewerMiddleware
{
    protected array $allowedIps = [
        // '192.168.1.1',
        // '10.0.0.1',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!empty($this->allowedIps)) {
            if (!in_array($request->ip(), $this->allowedIps)) {
                \Illuminate\Support\Facades\Log::warning('Log Viewer access denied for IP: ' . $request->ip(), [
                    'user_id' => auth()->id(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response('Unauthorized: Access denied from your IP address', 403);
            }
        }

        // Log access to Log Viewer
        \Illuminate\Support\Facades\Log::info('Log Viewer accessed', [
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $next($request);
    }
}

