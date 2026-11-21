<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class RecordAuditLog
{
    /**
     * Record every successful write action performed by an authenticated user.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user() || $request->isMethod('GET') || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
            return $response;
        }

        if ($response->getStatusCode() >= 400 || $this->isSensitiveRoute($request)) {
            return $response;
        }

        $payload = $this->sanitize($request->all());

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $request->route()?->getName() ?? $request->path(),
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'input' => $payload,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return $response;
    }

    /**
     * Never store credentials or tokens.
     */
    private function isSensitiveRoute(Request $request): bool
    {
        return $request->routeIs(
            'login',
            'logout',
            'register',
            'password.*',
            'verification.*',
            'profile.*',
        );
    }

    /**
     * Remove sensitive values before persisting the payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitize(array $payload): array
    {
        foreach (['password', 'password_confirmation', 'current_password', '_token', 'token'] as $key) {
            unset($payload[$key]);
        }

        foreach ($payload as $key => $value) {
            if ($value instanceof UploadedFile) {
                $payload[$key] = [
                    'name' => $value->getClientOriginalName(),
                    'mime_type' => $value->getClientMimeType(),
                    'size' => $value->getSize(),
                ];

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->sanitize($value);
            }
        }

        return $payload;
    }
}
