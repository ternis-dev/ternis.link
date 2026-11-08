<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Stable JSON envelopes for the v3 API, including structured meta and preferred domain resolution. */
class ApiV3Response
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $handler = app(ExceptionHandler::class);
            $handler->report($e);
            $response = $handler->render($request, $e);
        }

        $requestId = (string) Str::uuid();
        $status = $response->getStatusCode();
        $meta = ['api_version' => 3, 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()];

        if ($response instanceof JsonResponse || $status === 204) {
            $body = $status === 204 ? null : $response->getData(true);

            if ($status >= 400) {
                $code = $body['code'] ?? match ($status) {
                    401 => 'authentication_required',
                    403 => 'forbidden',
                    404 => 'not_found',
                    405 => 'method_not_allowed',
                    410 => 'api_version_retired',
                    422 => 'validation_failed',
                    429 => 'rate_limited',
                    default => $status >= 500 ? 'internal_error' : 'request_failed',
                };
                $retryAfter = $response->headers->get('Retry-After');
                $body = ['data' => null, 'error' => [
                    'code' => $code,
                    'message' => $body['message'] ?? Response::$statusTexts[$status] ?? 'Request failed.',
                    'fields' => (object) ($body['errors'] ?? []),
                    'retry_after_seconds' => is_numeric($retryAfter) ? (int) $retryAfter : null,
                ]];
            } elseif (! is_array($body) || (! array_key_exists('data', $body) && ! array_key_exists('error', $body))) {
                $body = ['data' => $body, 'error' => null];
            }

            $body['meta'] = array_merge($meta, $body['meta'] ?? []);

            $newResponse = response()->json($body, $status);
            foreach ($response->headers->all() as $name => $values) {
                if (! str_starts_with(strtolower($name), 'content-')) {
                    $newResponse->headers->set($name, $values);
                }
            }

            return $newResponse;
        }

        return $response;
    }
}
