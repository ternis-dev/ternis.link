<?php

namespace App\Livewire\Concerns;

/**
 * Cloudflare-style per-user table customization: toggle column
 * visibility and reorder columns. Preferences persist per table key
 * in `users.table_columns` (JSON), so they survive reloads.
 *
 * A component may host more than one table. Every accessor takes an
 * optional table key and falls back to `tableKey()`; components with
 * extra tables pass the key explicitly and override
 * `availableColumnsFor()` to describe them.
 *
 * Usage: `use WithTableColumns;` + implement `tableKey()` and
 * `availableColumns()` (ordered key => label map). Call
 * `$this->visibleColumns()` in `render()` and pass the result to the
 * view as `$visibleColumns`; render `<th>`/`<td>` in that order and
 * include the `livewire.partials.column-customizer` panel.
 */
trait WithTableColumns
{
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

    /**
     * Columns of one specific table. Components that host more than a
     * single table override this and switch on the key.
     *
     * @return array<string, string>
     */
    public function availableColumnsFor(?string $table = null): array
    {
        return $this->availableColumns();
    }

    /**
     * Resolve a table key argument to the key that is actually used.
     */
    public function tableKeyFor(?string $table = null): string
    {
        return ($table === null || $table === '') ? $this->tableKey() : $table;
    }

    /**
     * Effective column state of a table: order (including hidden
     * columns), hidden keys and visible keys. Hydrates and repairs the
     * stored preferences — unknown keys are dropped, newly added
     * columns are appended, and "hide everything" falls back to
     * showing all.
     *
     * @return array{order: list<string>, hidden: list<string>, visible: list<string>}
     */
    public function columnStateFor(?string $table = null): array
    {
        $key = $this->tableKeyFor($table);
        $available = array_keys($this->availableColumnsFor($key));

        $stored = $this->storedState($key);
        $storedOrder = is_array($stored['order'] ?? null) ? $stored['order'] : [];
        $storedHidden = is_array($stored['hidden'] ?? null) ? $stored['hidden'] : [];

        $order = array_values(array_intersect($storedOrder, $available));
        foreach ($available as $column) {
            if (! in_array($column, $order, true)) {
                $order[] = $column;
            }
        }

        $hidden = array_values(array_intersect($storedHidden, $available));
        $visible = array_values(array_diff($order, $hidden));

        // Hiding every column is not a valid state — show all again.
        if ($visible === []) {
            $hidden = [];
            $visible = $order;
        }

        return [
            'order' => $order,
            'hidden' => $hidden,
            'visible' => $visible,
        ];
    }

    /**
     * Ordered visible column keys of a table.
     *
     * @return list<string>
     */
    public function visibleColumns(?string $table = null): array
    {
        return $this->columnStateFor($table)['visible'];
    }

    /**
     * Hidden column keys of a table.
     *
     * @return list<string>
     */
    public function hiddenColumns(?string $table = null): array
    {
        return $this->columnStateFor($table)['hidden'];
    }

    /**
     * Ordered column keys of a table, hidden ones included.
     *
     * @return list<string>
     */
    public function columnOrder(?string $table = null): array
    {
        return $this->columnStateFor($table)['order'];
    }

    public function toggleColumn(string $column, ?string $table = null): void
    {
        $key = $this->tableKeyFor($table);
        $available = $this->availableColumnsFor($key);

        if (! array_key_exists($column, $available)) {
            return;
        }

        $state = $this->columnStateFor($key);
        $hidden = $state['hidden'];

        if (in_array($column, $hidden, true)) {
            $hidden = array_values(array_diff($hidden, [$column]));
        } else {
            // Never hide the last visible column.
            if (count($state['visible']) <= 1) {
                return;
            }
            $hidden[] = $column;
        }

        $this->saveColumnPreferences($key, $hidden, $state['order']);
    }

    public function moveColumn(string $column, string $direction, ?string $table = null): void
    {
        $key = $this->tableKeyFor($table);
        $state = $this->columnStateFor($key);
        $order = $state['order'];

        $index = array_search($column, $order, true);
        if ($index === false) {
            return;
        }

        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swap < 0 || $swap >= count($order)) {
            return;
        }

        [$order[$index], $order[$swap]] = [$order[$swap], $order[$index]];

        $this->saveColumnPreferences($key, $state['hidden'], array_values($order));
    }

    public function resetColumns(?string $table = null): void
    {
        $key = $this->tableKeyFor($table);
        $available = array_keys($this->availableColumnsFor($key));

        $this->saveColumnPreferences($key, [], $available);
    }

    /**
     * Stored preferences of one table as saved in `users.table_columns`.
     *
     * @return array<string, mixed>
     */
    private function storedState(string $table): array
    {
        $stored = auth()->user()?->table_columns[$table] ?? [];

        return is_array($stored) ? $stored : [];
    }

    /**
     * @param  list<string>  $hidden
     * @param  list<string>  $order
     */
    protected function saveColumnPreferences(string $table, array $hidden, array $order): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $prefs = $user->table_columns ?? [];
        $prefs[$table] = [
            'hidden' => array_values($hidden),
            'order' => array_values($order),
        ];

        $user->update(['table_columns' => $prefs]);
    }
}
