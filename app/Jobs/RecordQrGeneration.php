<?php

namespace App\Jobs;

use App\Models\QrGeneration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordQrGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?string $linkId,
        public string $format = 'png',
    ) {}

    public function handle(): void
    {
        QrGeneration::create([
            'link_id' => $this->linkId,
            'format' => $this->format,
        ]);
    }
}
