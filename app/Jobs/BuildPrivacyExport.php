<?php

namespace App\Jobs;

use App\Models\PrivacyExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class BuildPrivacyExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $exportId,
    ) {}

    public function handle(): void
    {
        $export = PrivacyExport::findOrFail($this->exportId);
        $export->update(['status' => 'processing']);

        $user = $export->user;
        $dir = "privacy/{$export->id}";
        Storage::disk('local')->makeDirectory($dir);

        try {
            // profile.json (no secrets)
            Storage::disk('local')->put("{$dir}/profile.json", json_encode([
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value ?? $user->role,
                'exported_at' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT));

            // links.csv (no IPs, no encrypted fields)
            $linksHandle = fopen(Storage::disk('local')->path("{$dir}/links.csv"), 'w');
            fputcsv($linksHandle, ['id', 'slug', 'destination_url', 'description', 'tags', 'click_count', 'is_active', 'expires_at', 'created_at']);
            $user->links()->orderBy('created_at')->chunk(1000, function ($links) use ($linksHandle) {
                foreach ($links as $l) {
                    fputcsv($linksHandle, [
                        $l->id, $l->slug, $l->destination_url, $l->description,
                        $l->tags ? implode(';', $l->tags) : '',
                        $l->click_count, (int) $l->is_active,
                        $l->expires_at?->toIso8601String(), $l->created_at?->toIso8601String(),
                    ]);
                }
            });
            fclose($linksHandle);

            // domains.csv / api-keys.csv (metadata only) / activity.csv
            $domainsHandle = fopen(Storage::disk('local')->path("{$dir}/domains.csv"), 'w');
            fputcsv($domainsHandle, ['id', 'hostname', 'type', 'is_active', 'verified_at']);
            foreach ($user->domains()->get() as $d) {
                fputcsv($domainsHandle, [$d->id, $d->hostname, is_object($d->type) ? $d->type->value : $d->type, (int) $d->is_active, $d->verified_at?->toIso8601String()]);
            }
            fclose($domainsHandle);

            $keysHandle = fopen(Storage::disk('local')->path("{$dir}/api-keys.csv"), 'w');
            fputcsv($keysHandle, ['id', 'name', 'key_prefix', 'last_used_at', 'created_at']);
            foreach ($user->apiKeys()->get() as $k) {
                fputcsv($keysHandle, [$k->id, $k->name, $k->key_prefix, $k->last_used_at?->toIso8601String(), $k->created_at?->toIso8601String()]);
            }
            fclose($keysHandle);

            file_put_contents(
                Storage::disk('local')->path("{$dir}/README.txt"),
                "ternis.link privacy export. Aggregates only; no visitor IPs, no key digests, no SSO tokens.\n"
            );

            $zipPath = "privacy/{$export->id}.zip";
            $zip = new \ZipArchive;
            $zip->open(Storage::disk('local')->path($zipPath), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            foreach (['profile.json', 'links.csv', 'domains.csv', 'api-keys.csv', 'README.txt'] as $f) {
                $zip->addFile(Storage::disk('local')->path("{$dir}/{$f}"), $f);
            }
            $zip->close();
            Storage::disk('local')->deleteDirectory($dir);

            $export->update([
                'status' => 'done',
                'path' => $zipPath,
                'expires_at' => now()->addDays((int) config('privacy.export_ttl_days', 7)),
            ]);
        } catch (\Throwable $e) {
            $export->update(['status' => 'failed']);
            throw $e;
        }
    }
}
