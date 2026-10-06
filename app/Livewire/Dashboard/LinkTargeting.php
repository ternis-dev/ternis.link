<?php

namespace App\Livewire\Dashboard;

use App\Models\Link;
use App\Services\LinkService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Per-link targeting rules (geo / device / weighted rotation).
 * Edits apply immediately via LinkService::syncTargets; the link's
 * own destination stays the fallback when nothing matches.
 */
class LinkTargeting extends Component
{
    public Link $link;

    public string $label = '';

    public string $destination_url = '';

    public string $country_codes = '';

    public string $device = '';

    public int $weight = 100;

    public ?string $editingTargetId = null;

    /**
     * Presentation theme: 'dashboard' (neutral) or 'public' (my.ternis.link
     * fresh-minimal theme + public-dashboard route names). Logic is
     * identical; only route prefixing and styling hooks change.
     */
    public string $theme = 'dashboard';

    public function mount(Link $link): void
    {
        $this->link = $this->editableLink($link->id);
    }

    protected function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:60'],
            'destination_url' => ['required', 'url', 'max:2048'],
            'country_codes' => ['nullable', 'string', 'max:200'],
            'device' => ['nullable', 'in:desktop,mobile,tablet'],
            'weight' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }

    public function addTarget(LinkService $links): void
    {
        $link = $this->freshLink();

        if (! $link) {
            return;
        }

        $this->validate();

        $rows = $this->currentRows($link);
        $rows[] = $this->newRow(count($rows));

        $this->persist($links, $link, $rows);
        $this->reset(['label', 'destination_url', 'country_codes', 'device']);
        $this->weight = 100;
    }

    public function startEdit(string $targetId): void
    {
        $link = $this->freshLink();

        if (! $link) {
            return;
        }

        $target = $link->targets()->find($targetId);

        if (! $target) {
            return;
        }

        $this->editingTargetId = $target->id;
        $this->label = (string) ($target->label ?? '');
        $this->destination_url = (string) $target->destination_url;
        $this->country_codes = $target->country_codes ? implode(', ', $target->country_codes) : '';
        $this->device = (string) ($target->device ?? '');
        $this->weight = (int) $target->weight;
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->editingTargetId = null;
        $this->reset(['label', 'destination_url', 'country_codes', 'device']);
        $this->weight = 100;
        $this->resetValidation();
    }

    public function updateTarget(LinkService $links): void
    {
        $link = $this->freshLink();

        if (! $link || $this->editingTargetId === null) {
            return;
        }

        $this->validate();

        $rows = [];
        foreach ($this->currentRows($link) as $row) {
            if ($row['id'] === $this->editingTargetId) {
                $row = [...$row, ...$this->newRow($row['sort_order'] ?? 0)];
            }
            $rows[] = $row;
        }

        $this->persist($links, $link, $rows);
        $this->cancelEdit();
    }

    public function toggleTarget(LinkService $links, string $targetId): void
    {
        $link = $this->freshLink();

        if (! $link) {
            return;
        }

        $rows = collect($this->currentRows($link))->map(
            fn (array $row) => $row['id'] === $targetId ? [...$row, 'is_active' => ! $row['is_active']] : $row
        )->all();

        $this->persist($links, $link, $rows);
    }

    public function removeTarget(LinkService $links, string $targetId): void
    {
        $link = $this->freshLink();

        if (! $link) {
            return;
        }

        $rows = collect($this->currentRows($link))
            ->reject(fn (array $row) => $row['id'] === $targetId)
            ->values()
            ->all();

        $this->persist($links, $link, $rows);

        if ($this->editingTargetId === $targetId) {
            $this->cancelEdit();
        }
    }

    public function render()
    {
        $link = $this->freshLink();

        $targets = $link ? $link->targets()->orderBy('sort_order')->get() : collect();
        $totalWeight = max(1, (int) $targets->where('is_active', true)->sum('weight'));

        return view('livewire.dashboard.link-targeting', compact('link', 'targets', 'totalWeight'));
    }

    private function freshLink(): ?Link
    {
        try {
            return $this->editableLink($this->link->id);
        } catch (\Throwable) {
            return null;
        }
    }

    private function editableLink(string $linkId): Link
    {
        return auth()->user()->links()->notRemoved()->findOrFail($linkId);
    }

    /**
     * @return list<array>
     */
    private function currentRows(Link $link): array
    {
        return $link->targets()->orderBy('sort_order')->get()->map(fn ($t) => [
            'id' => $t->id,
            'label' => $t->label,
            'destination_url' => $t->destination_url,
            'country_codes' => $t->country_codes,
            'device' => $t->device,
            'weight' => $t->weight,
            'sort_order' => $t->sort_order,
            'is_active' => $t->is_active,
        ])->all();
    }

    private function newRow(int $sortOrder): array
    {
        $codes = array_values(array_filter(array_map(
            fn ($c) => strtoupper(trim((string) $c)),
            explode(',', $this->country_codes)
        )));

        return [
            'label' => trim($this->label) !== '' ? mb_substr(trim($this->label), 0, 60) : null,
            'destination_url' => trim($this->destination_url),
            'country_codes' => $codes === [] ? null : $codes,
            'device' => trim($this->device) !== '' ? strtolower(trim($this->device)) : null,
            'weight' => $this->weight,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ];
    }

    private function persist(LinkService $links, Link $link, array $rows): void
    {
        try {
            $links->syncTargets($link, $rows, auth()->user());
            $this->link = $link->fresh();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->addError($field === 'targets' ? 'destination_url' : $field, $message);
                }
            }
        }
    }
}
