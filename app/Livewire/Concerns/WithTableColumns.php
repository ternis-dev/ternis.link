<?php

namespace App\Livewire\Concerns;

/**
 * Cloudflare-style per-user table customization: toggle column
 * visibility and reorder columns. Preferences persist per table key
 * in `users.table_columns` (JSON), so they survive reloads.
 *
 * Usage: `use WithTableColumns;` + implement `tableKey()` and
 * `availableColumns()` (ordered key => label map). Call
 * `$this->visibleColumns()` in `render()` and pass the result to the
 * view as `$visibleColumns`; render `<th>`/`<td>` in that order and
 * include the `livewire.partials.column-customizer` panel.
 */
trait WithTableColumns
{
    /** @var list<string> */
    public array $hiddenColumns = [];

    /** @var list<string> */
    public array $columnOrder = [];

    /**
     * Stable per-table key, e.g. 'dashboard.links'.
     */
    abstract protected function tableKey(): string;

    /**
     * All toggleable columns in default order: key => label.
     * The actions column stays fixed and is never included here.
     *
     * @return array<string, string>
     */
    abstract protected function availableColumns(): array;

    public function toggleColumn(string $column): void
    {
        if (! array_key_exists($column, $this->availableColumns())) {
            return;
        }

        $this->visibleColumns();

        if (in_array($column, $this->hiddenColumns, true)) {
            $this->hiddenColumns = array_values(array_diff($this->hiddenColumns, [$column]));
        } else {
            // Never hide the last visible column.
            $visible = array_values(array_diff($this->columnOrder, $this->hiddenColumns));
            if (count($visible) <= 1) {
                return;
            }
            $this->hiddenColumns[] = $column;
        }

        $this->saveColumnPreferences();
    }

    public function moveColumn(string $column, string $direction): void
    {
        $this->visibleColumns();

        $index = array_search($column, $this->columnOrder, true);
        if ($index === false) {
            return;
        }

        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swap < 0 || $swap >= count($this->columnOrder)) {
            return;
        }

        $order = $this->columnOrder;
        [$order[$index], $order[$swap]] = [$order[$swap], $order[$index]];
        $this->columnOrder = array_values($order);

        $this->saveColumnPreferences();
    }

    public function resetColumns(): void
    {
        $this->hiddenColumns = [];
        $this->columnOrder = array_keys($this->availableColumns());

        $this->saveColumnPreferences();
    }

    /**
     * Ordered visible column keys. Hydrates (and repairs) state from
     * the stored preferences: unknown keys dropped, new columns
     * appended, "hide everything" repaired to show all.
     *
     * @return list<string>
     */
    protected function visibleColumns(): array
    {
        $available = array_keys($this->availableColumns());
        $stored = auth()->user()?->table_columns[$this->tableKey()] ?? [];
        $storedOrder = is_array($stored['order'] ?? null) ? $stored['order'] : [];
        $storedHidden = is_array($stored['hidden'] ?? null) ? $stored['hidden'] : [];

        $order = array_values(array_intersect($storedOrder, $available));
        foreach ($available as $key) {
            if (! in_array($key, $order, true)) {
                $order[] = $key;
            }
        }

        $hidden = array_values(array_intersect($storedHidden, $available));
        $visible = array_values(array_diff($order, $hidden));

        if ($visible === []) {
            $visible = $order;
            $hidden = [];
        }

        $this->columnOrder = $order;
        $this->hiddenColumns = $hidden;

        return $visible;
    }

    protected function saveColumnPreferences(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $prefs = $user->table_columns ?? [];
        $prefs[$this->tableKey()] = [
            'hidden' => array_values($this->hiddenColumns),
            'order' => array_values($this->columnOrder),
        ];

        $user->update(['table_columns' => $prefs]);
    }
}
