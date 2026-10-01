<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BioButton;
use App\Models\BioPage;
use App\Models\Domain;
use App\Services\BioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BioController extends Controller
{
    public function __construct(
        private BioService $bio,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pages = $request->user()->bioPages()->with(['domain:id,hostname', 'children'])
            ->orderByDesc('created_at')->paginate(25);

        return response()->json($pages);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'domain_id' => ['required', 'exists:domains,id'],
            'parent_id' => ['nullable', 'exists:bio_pages,id'],
            'slug' => ['nullable', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:280'],
            'avatar_url' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'theme' => ['nullable', 'in:minimal,dark,paper'],
            'accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'og_title' => ['nullable', 'string', 'max:120'],
            'og_description' => ['nullable', 'string', 'max:300'],
            'og_image_url' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $domain = Domain::findOrFail($data['domain_id']);
        $parent = isset($data['parent_id']) ? BioPage::findOrFail($data['parent_id']) : null;

        $page = $this->bio->createPage($request->user(), $domain, $data, $parent);

        return response()->json($page->load(['domain:id,hostname', 'buttons']), 201);
    }

    public function show(Request $request, BioPage $page): JsonResponse
    {
        $this->authorizePage($request, $page);

        return response()->json($page->load(['domain:id,hostname', 'buttons', 'children']));
    }

    public function update(Request $request, BioPage $page): JsonResponse
    {
        $this->authorizePage($request, $page);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:280'],
            'avatar_url' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'theme' => ['nullable', 'in:minimal,dark,paper'],
            'accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'og_title' => ['nullable', 'string', 'max:120'],
            'og_description' => ['nullable', 'string', 'max:300'],
            'og_image_url' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $page->update($data);
        $this->bio->forgetCaches($page->fresh());

        return response()->json($page->fresh()->load(['domain:id,hostname', 'buttons']));
    }

    public function destroy(Request $request, BioPage $page): JsonResponse
    {
        $this->authorizePage($request, $page);
        $page->update(['is_active' => false]);
        $this->bio->forgetCaches($page);

        return response()->json(null, 204);
    }

    public function syncButtons(Request $request, BioPage $page): JsonResponse
    {
        $this->authorizePage($request, $page);

        $data = $request->validate([
            'buttons' => ['required', 'array', 'max:25'],
            'buttons.*.label' => ['required_unless:buttons.*.kind,divider', 'string', 'max:60'],
            'buttons.*.sublabel' => ['nullable', 'string', 'max:120'],
            'buttons.*.kind' => ['required', 'in:link,header,divider,social'],
            'buttons.*.destination_url' => ['nullable', 'url', 'max:2048'],
            'buttons.*.icon' => ['nullable', 'in:instagram,tiktok,x,youtube,github,globe,mail,link'],
            'buttons.*.thumbnail_url' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'buttons.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
            'buttons.*.is_active' => ['nullable', 'boolean'],
            'buttons.*.starts_at' => ['nullable', 'date'],
            'buttons.*.ends_at' => ['nullable', 'date'],
        ]);

        $this->bio->syncButtons($page, $data['buttons'], $request->user());

        return response()->json($page->fresh()->load('buttons'));
    }

    public function stats(Request $request, BioPage $page): JsonResponse
    {
        $this->authorizePage($request, $page);

        $days = (int) $request->query('days', 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $since = now()->subDays($days)->startOfDay();

        $views = $page->events()->where('kind', 'view')->where('created_at', '>=', $since)->count();
        $taps = $page->events()->where('kind', 'tap')->where('created_at', '>=', $since)->count();

        $byButton = $page->buttons()->orderBy('sort_order')->get()->map(function (BioButton $b) use ($page, $since, $taps) {
            $count = $page->events()->where('kind', 'tap')->where('bio_button_id', $b->id)->where('created_at', '>=', $since)->count();

            return ['id' => $b->id, 'label' => $b->label, 'taps' => $count, 'share' => $taps > 0 ? round($count / $taps * 100, 1) : null];
        })->values()->all();

        $byDay = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $byDay[] = [
                'date' => $date,
                'views' => (clone $page->events())->where('kind', 'view')->whereDate('created_at', $date)->count(),
                'taps' => (clone $page->events())->where('kind', 'tap')->whereDate('created_at', $date)->count(),
            ];
        }

        return response()->json([
            'views' => $views,
            'taps' => $taps,
            'ctr' => $views > 0 ? round($taps / $views * 100, 1) : null,
            'by_button' => $byButton,
            'by_day' => $byDay,
        ]);
    }

    public function exportEvents(Request $request, BioPage $page)
    {
        $this->authorizePage($request, $page);

        $events = $page->events()->with('button:id,label')->orderBy('created_at')->cursor();

        return response()->streamDownload(function () use ($events) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['timestamp', 'kind', 'button', 'referrer', 'country_code', 'city', 'ip_hash']);
            foreach ($events as $e) {
                fputcsv($out, [$e->created_at?->toIso8601String(), $e->kind, $e->button?->label ?? '', $e->referrer, $e->country_code, $e->city, $e->ip_hash]);
            }
            fclose($out);
        }, 'bio-'.$page->id.'-events.csv', ['Content-Type' => 'text/csv']);
    }

    private function authorizePage(Request $request, BioPage $page): void
    {
        if ($page->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this page.');
        }
    }
}
