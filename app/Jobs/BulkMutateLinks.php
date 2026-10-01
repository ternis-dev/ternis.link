<?php

namespace App\Jobs;

use App\Models\BulkOperation;
use App\Models\Link;
use App\Services\LinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkMutateLinks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<string>  $linkIds
     */
    public function __construct(
        public string $operationId,
        public array $linkIds,
        public string $action,
        public array $patch = [],
    ) {}

    public function handle(LinkService $links): void
    {
        $op = BulkOperation::findOrFail($this->operationId);
        $op->update(['status' => 'processing']);

        $user = $op->user;
        $succeeded = 0;
        $errors = [];

        foreach (array_chunk($this->linkIds, 100) as $chunk) {
            foreach ($chunk as $id) {
                $link = Link::notRemoved()->whereKey($id)->first();

                if (! $link || ($link->user_id !== $user->id && ! $user->isAdmin())) {
                    $errors[] = ['id' => $id, 'error' => 'Not found or not owned.'];

                    continue;
                }

                try {
                    match ($this->action) {
                        'deactivate' => $links->deactivate($link),
                        'destroy' => $links->deactivate($link),
                        default => $links->update($link, $this->sanitizedPatch(), $user),
                    };
                    $succeeded++;
                } catch (\Throwable $e) {
                    $errors[] = ['id' => $id, 'error' => $e->getMessage()];
                }
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

    private function sanitizedPatch(): array
    {
        $allowed = [];
        foreach (['tags_add', 'tags_remove', 'expires_at', 'is_active', 'domain_id', 'description'] as $k) {
            if (array_key_exists($k, $this->patch)) {
                $allowed[$k] = $this->patch[$k];
            }
        }

        // Resolve tag add/remove into a final tags array at execution time per link.
        return $allowed;
    }
}
