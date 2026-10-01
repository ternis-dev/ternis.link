<?php

namespace App\Jobs;

use App\Models\BioButton;
use App\Models\BioEvent;
use App\Models\BioPage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordBioEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $bioPageId,
        public string $kind,
        public ?string $bioButtonId = null,
        public ?string $referrer = null,
        public ?string $userAgent = null,
        public ?string $ipHash = null,
        public ?string $countryCode = null,
        public ?string $city = null,
    ) {}

    public function handle(): void
    {
        BioEvent::create([
            'bio_page_id' => $this->bioPageId,
            'bio_button_id' => $this->bioButtonId,
            'kind' => $this->kind,
            'referrer' => $this->referrer,
            'user_agent' => $this->userAgent,
            'ip_hash' => $this->ipHash,
            'country_code' => $this->countryCode,
            'city' => $this->city,
        ]);

        if ($this->kind === 'view') {
            BioPage::where('id', $this->bioPageId)->increment('view_count');
        }

        if ($this->kind === 'tap' && $this->bioButtonId !== null) {
            BioButton::where('id', $this->bioButtonId)->increment('tap_count');
        }
    }
}
