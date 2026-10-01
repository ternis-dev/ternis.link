<?php

namespace App\Jobs;

use App\Models\BulkOperation;
use App\Models\Domain;
use App\Services\LinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkImportLinks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<array{destination_url: string, domain_id?: ?string, slug?: ?string, expires_at?: ?string, description?: ?string, tags?: ?array}>  $rows
     */
    public function __construct(
        public string $operationId,
        public array $rows,
    ) {}

    public function handle(LinkService $links): void
    {
        $op = BulkOperation::findOrFail($this->operationId);
        $op->update(['status' => 'processing']);

        $user = $op->user;
        $succeeded = 0;
        $errors = [];

        foreach ($this->rows as $i => $row) {
            try {
                $domain = isset($row['domain_id'])
                    ? Domain::findOrFail($row['domain_id'])
                    : Domain::where('hostname', 'href.nz')->firstOrFail();

                $links->create(
                    destinationUrl: $row['destination_url'],
                    domain: $domain,
                    user: $user,
                    customSlug: $row['slug'] ?? null,
                    expiresAt: isset($row['expires_at']) && $row['expires_at'] ? new \DateTime($row['expires_at']) : null,
                    description: $row['description'] ?? null,
                    tags: $row['tags'] ?? null,
                );
                $succeeded++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $i + 1, 'error' => $e->getMessage()];
            }
        }

        $failed = count($errors);
        $op->update([
            'status' => $failed === 0 ? 'done' : ($succeeded === 0 ? 'failed' : 'partial'),
            'succeeded' => $succeeded,
            'failed' => $failed,
            'error_summary' => array_slice($errors, 0, 50),
        ]);
    }
}
