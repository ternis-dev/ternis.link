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

            // activity.csv (own actions + admin actions on own stuff; no IPs/user-agents)
            $activityHandle = fopen(Storage::disk('local')->path("{$dir}/activity.csv"), 'w');
            fputcsv($activityHandle, ['occurred_at', 'action', 'actor', 'subject_type', 'subject_label', 'metadata']);
            \App\Models\ActivityLog::visibleTo($user->id)->orderBy('created_at')->chunk(1000, function ($entries) use ($activityHandle, $user) {
                foreach ($entries as $e) {
                    fputcsv($activityHandle, [
                        $e->created_at?->toIso8601String(),
                        $e->action,
                        $e->actor_id === $user->id ? 'self' : 'other',
                        $e->subject_type,
                        $e->subject_label,
                        $e->metadata ? json_encode($e->metadata) : '',
                    ]);
                }
            });
            fclose($activityHandle);

            // errors.csv (own error encounters: what/where/when, no IPs/user-agents)
            $errorsHandle = fopen(Storage::disk('local')->path("{$dir}/errors.csv"), 'w');
            fputcsv($errorsHandle, ['occurred_at', 'http_code', 'exception', 'method', 'host', 'path', 'message']);
            \App\Models\ErrorEncounter::where('user_id', $user->id)->orderBy('created_at')->chunk(1000, function ($errors) use ($errorsHandle) {
                foreach ($errors as $e) {
                    fputcsv($errorsHandle, [
                        $e->created_at?->toIso8601String(),
                        $e->http_code,
                        $e->exception_class,
                        $e->method,
                        $e->host,
                        $e->path,
                        $e->error_message,
                    ]);
                }
            });
            fclose($errorsHandle);

            // bio-pages.csv (own pages and buttons; no password hashes)
            $bioHandle = fopen(Storage::disk('local')->path("{$dir}/bio-pages.csv"), 'w');
            fputcsv($bioHandle, ['id', 'title', 'slug', 'domain', 'is_active', 'bio', 'button_count', 'created_at']);
            foreach ($user->bioPages()->with(['domain:id,hostname'])->withCount('buttons')->get() as $p) {
                fputcsv($bioHandle, [
                    $p->id, $p->title, $p->slug, $p->domain?->hostname,
                    (int) $p->is_active, $p->bio, $p->buttons_count, $p->created_at?->toIso8601String(),
                ]);
            }
            fclose($bioHandle);

            // notifications.csv (own inbox)
            $notificationsHandle = fopen(Storage::disk('local')->path("{$dir}/notifications.csv"), 'w');
            fputcsv($notificationsHandle, ['created_at', 'title', 'lines', 'action_label', 'read_at']);
            foreach ($user->notifications()->orderBy('created_at')->get() as $n) {
                fputcsv($notificationsHandle, [
                    $n->created_at?->toIso8601String(),
                    $n->data['title'] ?? null,
                    isset($n->data['lines']) ? implode("\n", (array) $n->data['lines']) : '',
                    $n->data['action_label'] ?? '',
                    $n->read_at?->toIso8601String(),
                ]);
            }
            fclose($notificationsHandle);

            file_put_contents(
                Storage::disk('local')->path("{$dir}/README.txt"),
                "ternis.link privacy export. Aggregates only; no visitor IPs, no key digests, no SSO tokens.\n"
                ."activity.csv holds your actions plus admin actions on your stuff; errors.csv holds error pages shown to you.\n"
            );

            $zipPath = "privacy/{$export->id}.zip";
            $zip = new \ZipArchive;
            $zip->open(Storage::disk('local')->path($zipPath), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            foreach (['profile.json', 'links.csv', 'domains.csv', 'api-keys.csv', 'activity.csv', 'errors.csv', 'bio-pages.csv', 'notifications.csv', 'README.txt'] as $f) {
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
