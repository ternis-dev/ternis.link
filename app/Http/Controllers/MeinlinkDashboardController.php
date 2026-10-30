<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Link;
use App\Models\QrGeneration;
use App\Services\LinkService;
use App\Support\LinkQrCode;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MeinlinkDashboardController extends Controller
{
    private function links(Request $request)
    {
        return $request->user()->links()->whereHas('domain', fn ($q) => $q->where('hostname', config('domains.meinlink_host')))->where('is_removed', false);
    }

    private function owned(Request $request, string $link): Link
    {
        return $this->links($request)->with('domain')->findOrFail($link);
    }

    public function index(Request $request)
    {
        $base = $this->links($request);
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 150);
        $status = $request->query('status', 'all');
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
            'links' => $filtered->with('domain')->latest()->paginate(15)->withQueryString(),
            'total' => (clone $base)->count(),
            'clicks' => (int) (clone $base)->sum('click_count'),
            'today' => (clone $base)->whereDate('created_at', today())->count(),
            'search' => $search, 'status' => $status,
        ]);
    }

    public function create()
    {
        return view('meinlink-dashboard.form', ['link' => null]);
    }

    private function data(Request $request, bool $create): array
    {
        return $request->validate([
            'destination_url' => ['required', 'url:http,https', 'max:'.LinkService::PUBLIC_MAX_URL_LENGTH],
            'description' => ['nullable', 'string', 'max:255'],
            'slug' => $create ? ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+$/'] : ['prohibited'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ], [
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
            );
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['destination_url' => 'Der Link konnte nicht erstellt werden. Prüfe die Zieladresse, dein Wunschkürzel und dein verfügbares Link-Kontingent.']);
        }

        return redirect()->route('meinlink-dashboard.show', $link->id)->with('success', 'Dein Link ist bereit zum Teilen.');
    }

    public function show(Request $request, string $link)
    {
        $link = $this->owned($request, $link);
        $daily = $link->clicks()->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupByRaw('DATE(created_at)')->pluck('total', 'day');

        return view('meinlink-dashboard.show', compact('link', 'daily'));
    }

    public function edit(Request $request, string $link)
    {
        return view('meinlink-dashboard.form', ['link' => $this->owned($request, $link)]);
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
        if (in_array('quarantine', $link->tags ?? [], true)
            || (! $link->is_active && $link->threatEvaluations()->where('action_taken', 'quarantined')->exists())) {
            throw ValidationException::withMessages(['link' => 'Dieser Link wurde aus Sicherheitsgründen gesperrt. Bitte kontaktiere abuse@ternis.dev für eine Überprüfung.']);
        }
    }

    public function toggle(Request $request, string $link, LinkService $service)
    {
        $link = $this->owned($request, $link);
        $this->ensureEditable($link);
        $service->update($link, ['is_active' => ! $link->is_active], $request->user());

        return back()->with('success', $link->is_active ? 'Link aktiviert.' : 'Link pausiert.');
    }

    public function qr(Request $request, string $link)
    {
        $link = $this->owned($request, $link);
        QrGeneration::create(['link_id' => $link->id, 'format' => 'png']);

        return response(LinkQrCode::png($link), 200, ['Content-Type' => 'image/png', 'Content-Disposition' => 'attachment; filename="meinlink-'.$link->slug.'.png"']);
    }
}
