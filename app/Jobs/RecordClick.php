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
        public string $linkId,
        public ?string $referrer,
        public ?string $userAgent,
        public ?string $ipHash,
        public bool $isDirectUrl = false,
        public ?string $countryCode = null,
        public ?string $city = null,
        /** Raw visitor IP (or null when capture is off); the model's encrypted cast encrypts it on write. */
        public ?string $ip = null,
    ) {}

    public function handle(): void
    {
        Click::create([
            'link_id' => $this->linkId,
            'referrer' => $this->referrer,
            'user_agent' => $this->userAgent,
            'ip_hash' => $this->ipHash,
            'ip_encrypted' => $this->ip,
            'country_code' => $this->countryCode,
            'city' => $this->city,
            'is_direct_url' => $this->isDirectUrl,
        ]);

        // Increment denormalized counter
        Link::where('id', $this->linkId)->increment('click_count');
    }
}
