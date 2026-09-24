<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ErrorEncounter extends Model
{
    use HasUlids;

    protected $fillable = [
        'http_code',
        'error_message',
        'exception_class',
        'method',
        'host',
        'path',
        'user_id',
        'ip_hash',
        'user_agent',
    ];

    protected $casts = [
        'http_code' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an error encounter. Never throws — logging must not break
     * error rendering itself (e.g. when the database is down).
     */
    public static function record(Throwable $e): void
    {
        try {
            $request = request();

            static::create([
                'http_code' => $e instanceof HttpExceptionInterface
                    ? $e->getStatusCode()
                    : (is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500),
                'error_message' => mb_substr($e->getMessage() ?: class_basename($e), 0, 65535),
                'exception_class' => get_class($e),
                'method' => mb_substr($request->method(), 0, 10),
                'host' => mb_substr($request->getHost(), 0, 255),
                'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 2048),
                'user_id' => $request->user()?->id,
                'ip_hash' => $request->ip() ? hash('sha256', (string) $request->ip()) : null,
                'user_agent' => ($ua = $request->userAgent()) ? mb_substr($ua, 0, 512) : null,
            ]);
        } catch (Throwable) {
            // Logging an error must never raise a new one.
        }
    }
}
