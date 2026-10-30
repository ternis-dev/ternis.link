<?php

namespace App\Http\Controllers;

use App\Exceptions\DailyLinkLimitException;
use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\QrGeneration;
use App\Services\LinkService;
use App\Support\DailyLinkQuota;
use App\Support\LinkQrCode;
use App\Support\MeinlinkLinkState;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;

class MeinlinkDashboardController extends Controller
{
    private function links(Request $request)
    {
        return $request->user()->links()->whereHas('domain', fn ($q) => $q->where('hostname', config('domains.meinlink_host')))->where('is_removed', false);
    }

    private function owned(Request $request, string $link): Link
    {
        return $this->links($request)->with('domain')->withExists(['threatEvaluations as has_quarantine_evaluation' => fn ($q) => $q->where('action_taken', 'quarantined')])->findOrFail($link);
    }

    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'clicks'])],
        ], ['string' => 'Bitte gib einen Suchbegriff als Text ein.', 'max' => 'Der Suchbegriff darf höchstens :max Zeichen enthalten.', 'in' => 'Bitte wähle einen gültigen Filter.']);
        $base = $this->links($request);
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 150);
        $status = $request->query('status') ?: 'all';
        $sort = $request->query('sort') ?: 'newest';
        $filtered = clone $base;
        if ($search !== '') {
            $filtered->where(fn ($q) => $q->where('slug', 'like', '%'.$search.'%')->orWhere('destination_url', 'like', '%'.$search.'%')->orWhere('description', 'like', '%'.$search.'%'));
        }
        if ($status === 'active') {
            $filtered->where('is_active', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
        } elseif ($status === 'inactive') {
            $filtered->where(fn ($q) => $q->where('is_active', false)->orWhere('expires_at', '<=', now()));
        }

        return view('meinlink-dashboard.index', [
            'links' => $filtered->with('domain')
                ->withExists(['threatEvaluations as has_quarantine_evaluation' => fn ($q) => $q->where('action_taken', 'quarantined')])
                ->when($sort === 'clicks', fn ($q) => $q->orderByDesc('click_count'))
                ->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc')->orderBy('id')
                ->paginate(15)->withQueryString(),
            'total' => (clone $base)->count(),
            'clicks' => (int) (clone $base)->sum('click_count'),
            'today' => (clone $base)->whereDate('created_at', today())->count(),
            'search' => $search, 'status' => $status, 'sort' => $sort,
        ]);
    }

    public function create(Request $request)
    {
        return view('meinlink-dashboard.form', ['link' => null, 'quota' => DailyLinkQuota::forUser($request->user())]);
    }

    private function data(Request $request, bool $create): array
    {
        return $request->validate([
            'destination_url' => ['required', 'url:http,https', 'max:'.LinkService::PUBLIC_MAX_URL_LENGTH],
            'description' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'max:128', 'prohibits:remove_password'],
            'remove_password' => ['sometimes', 'boolean'],
            'bot_protection' => ['sometimes', 'boolean'],
            'slug' => $create ? ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+$/'] : ['prohibited'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ], [
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen enthalten.',
            'password.prohibits' => 'Bitte wähle entweder ein neues Passwort oder entferne das bestehende.',
            'boolean' => 'Bitte wähle eine gültige Einstellung.',
            'required' => 'Bitte fülle dieses Feld aus.',
            'destination_url.url' => 'Bitte gib eine gültige Adresse mit https:// oder http:// ein.',
            'max' => 'Dieser Wert ist zu lang (maximal :max Zeichen).',
            'slug.regex' => 'Verwende nur Buchstaben, Zahlen, Bindestriche und Unterstriche.',
            'expires_at.after' => 'Das Ablaufdatum muss in der Zukunft liegen.',
            'expires_at.date' => 'Bitte gib ein gültiges Datum ein.',
        ]);
    }

    public function store(Request $request, LinkService $service)
    {
        $data = $this->data($request, true);
        $domain = Domain::where('hostname', config('domains.meinlink_host'))->where('is_active', true)->firstOrFail();
        try {
            $link = $service->create(
                destinationUrl: $data['destination_url'], domain: $domain, user: $request->user(),
                customSlug: $data['slug'] ?? null,
                expiresAt: empty($data['expires_at']) ? null : new \DateTimeImmutable($data['expires_at']),
                description: $data['description'] ?? null,
                password: $data['password'] ?? null,
                botProtection: (bool) ($data['bot_protection'] ?? false),
            );
        } catch (DailyLinkLimitException $e) {
            throw ValidationException::withMessages(['destination_url' => 'Dein tägliches Link-Kontingent ist aufgebraucht. Bitte versuche es nach dem nächsten Tageswechsel erneut.']);
        } catch (ThrottleRequestsException $e) {
            throw ValidationException::withMessages(['destination_url' => 'Du hast gerade viele Links erstellt. Bitte warte eine Minute und versuche es erneut.']);
        } catch (ValidationException $e) {
            $messages = [];
            foreach (array_keys($e->errors()) as $field) {
                $messages[$field] = $field === 'slug'
                    ? 'Dieses Wunschkürzel ist bereits vergeben, reserviert oder für deinen Tarif zu kurz. Bitte wähle ein anderes.'
                    : 'Diese Zieladresse kann nicht verwendet werden. Bitte prüfe deine Eingabe.';
            }
            throw ValidationException::withMessages($messages);
        }

        return redirect()->route('meinlink-dashboard.show', $link->id)->with('success', 'Dein Link ist bereit zum Teilen.');
    }

    public function show(Request $request, string $link)
    {
        $link = $this->owned($request, $link);
        $daily = $link->clicks()->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupByRaw('DATE(created_at)')->pluck('total', 'day');

        return view('meinlink-dashboard.show', ['link' => $link, 'daily' => $daily, 'blocked' => MeinlinkLinkState::blocked($link)]);
    }

    public function edit(Request $request, string $link)
    {
        $link = $this->owned($request, $link);
        if (MeinlinkLinkState::blocked($link)) {
            return redirect()->route('meinlink-dashboard.show', $link->id)->withErrors(['link' => 'Dieser Link ist gesperrt und kann nicht bearbeitet werden. Bitte frage eine Sicherheitsprüfung an.']);
        }

        return view('meinlink-dashboard.form', ['link' => $link]);
    }

    public function update(Request $request, string $link, LinkService $service)
    {
        $link = $this->owned($request, $link);
        $data = $this->data($request, false);
        $this->ensureEditable($link);
        try {
            $service->update($link, $data, $request->user());
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['destination_url' => 'Diese Zieladresse kann nicht verwendet werden. Bitte prüfe deine Eingabe.']);
        }

        return redirect()->route('meinlink-dashboard.show', $link->id)->with('success', 'Änderungen gespeichert.');
    }

    private function ensureEditable(Link $link): void
    {
        if (MeinlinkLinkState::blocked($link)) {
            throw ValidationException::withMessages(['link' => 'Dieser Link wurde aus Sicherheitsgründen gesperrt. Bitte kontaktiere abuse@ternis.dev für eine Überprüfung.']);
        }
    }

    public function toggle(Request $request, string $link, LinkService $service)
    {
        $link = $this->owned($request, $link);
        $this->ensureEditable($link);
        if (! $link->is_active && $link->isExpired()) {
            throw ValidationException::withMessages(['link' => 'Dieser Link ist abgelaufen. Ändere oder entferne zuerst das Ablaufdatum, bevor du ihn aktivierst.']);
        }
        $service->update($link, ['is_active' => ! $link->is_active], $request->user());

        return back()->with('success', $link->is_active ? 'Link aktiviert.' : 'Link pausiert.');
    }

    private function statisticsData(Request $request): array
    {
        $request->validate(['days' => ['nullable', Rule::in(['7', '30', '90'])]], [
            'days.in' => 'Bitte wähle einen Zeitraum von 7, 30 oder 90 Tagen.',
        ]);
        $days = (int) ($request->query('days') ?: 30);
        $end = now()->endOfDay();
        $start = $end->copy()->subDays($days - 1)->startOfDay();
        $links = $this->links($request);
        $clicks = Click::whereIn('link_id', (clone $links)->select('links.id'))
            ->where('is_direct_url', false)->whereBetween('created_at', [$start, $end]);
        $counts = (clone $clicks)->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')->pluck('total', 'day');
        $daily = collect();
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $daily->push(['date' => $date->format('Y-m-d'), 'label' => $date->format('d.m.Y'), 'count' => (int) ($counts[$date->format('Y-m-d')] ?? 0)]);
        }

        return [
            'days' => $days, 'daily' => $daily, 'total' => $daily->sum('count'),
            'activeDays' => $daily->where('count', '>', 0)->count(),
            'created' => (clone $links)->whereBetween('created_at', [$start, $end])->count(),
            'topLinks' => $this->links($request)->with('domain')->withCount([
                'clicks as period_clicks' => fn ($q) => $q->where('is_direct_url', false)->whereBetween('created_at', [$start, $end]),
            ])->orderByDesc('period_clicks')->orderBy('id')->limit(5)->get()->filter(fn ($link) => $link->period_clicks > 0),
        ];
    }

    public function statistics(Request $request)
    {
        return view('meinlink-dashboard.statistics', $this->statisticsData($request));
    }

    public function exportStatistics(Request $request)
    {
        $data = $this->statisticsData($request);

        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Datum', 'Klicks'], ';', '"', '');
            foreach ($data['daily'] as $day) {
                fputcsv($output, [$day['date'], $day['count']], ';', '"', '');
            }
            fclose($output);
        }, 'meinlink-statistik-'.$data['days'].'-tage.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function qr(Request $request, string $link)
    {
        $link = $this->owned($request, $link);
        QrGeneration::create(['link_id' => $link->id, 'format' => 'png']);

        return response(LinkQrCode::png($link), 200, ['Content-Type' => 'image/png', 'Content-Disposition' => 'attachment; filename="meinlink-'.$link->slug.'.png"']);
    }
}
