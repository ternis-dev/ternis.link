<?php

namespace App\Http\Middleware;

use App\Models\ApiVersion;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Stable JSON envelopes for the focused v2 API, including framework errors. */
class ApiV2Response
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
        $meta = ['api_version' => 2, 'request_id' => $requestId];

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
                    'quota' => $body['quota'] ?? null,
                ], 'meta' => $meta];
            } else {
                if (is_array($body) && isset($body['current_page'], $body['last_page'])) {
                    $meta['pagination'] = [
                        'current_page' => $body['current_page'],
                        'last_page' => $body['last_page'],
                        'per_page' => $body['per_page'],
                        'total' => $body['total'],
                        'next_page' => $body['current_page'] < $body['last_page'] ? $body['current_page'] + 1 : null,
                    ];
                    $body = $body['data'];
                }
                $body = ['data' => $this->present($body), 'error' => null, 'meta' => $meta];
            }

            // v2 deletion acknowledges the result with the same JSON shape.
            $response = response()->json($body, $status === 204 ? 200 : $status, $response->headers->all());
        }

        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('API-Version', '2');
        $response->headers->set('API-Latest-Version', (string) ApiVersion::latestVersion());

        return $response;
    }

    private function present(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }
        if (array_is_list($data)) {
            return array_map($this->present(...), $data);
        }
        if (isset($data['slug'], $data['destination_url'], $data['domain']['hostname'])) {
            $data['short_url'] = 'https://'.$data['domain']['hostname'].'/'.$data['slug'];
        }

        return $data;
    }
}
