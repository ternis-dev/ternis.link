<?php

namespace App\Jobs;

use App\Models\Click;
use App\Models\Link;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordClick implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $linkId,
        public ?string $referrer,
        public ?string $userAgent,
        public ?string $ipHash,
        public bool $isDirectUrl = false,
    ) {}

    public function handle(): void
    {
        Click::create([
            'link_id' => $this->linkId,
            'referrer' => $this->referrer,
            'user_agent' => $this->userAgent,
            'ip_hash' => $this->ipHash,
            'country_code' => null,
            'city' => null,
            'is_direct_url' => $this->isDirectUrl,
        ]);

        // Increment denormalized counter
        Link::where('id', $this->linkId)->increment('click_count');
    }
}
