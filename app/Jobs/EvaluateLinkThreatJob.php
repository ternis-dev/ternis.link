<?php

namespace App\Jobs;

use App\Models\Link;
use App\Services\AiThreatDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateLinkThreatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 5;

    public function __construct(
        public string $linkId,
        public array $context = [],
    ) {}

    public function handle(AiThreatDetectionService $service): void
    {
        $link = Link::with('domain', 'user')->find($this->linkId);

        if (! $link || $link->is_removed) {
            return;
        }

        $service->evaluateLink($link, $this->context);
    }
}
