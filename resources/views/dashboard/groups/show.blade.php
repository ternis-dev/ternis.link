<x-layouts.dashboard title="{{ $group->name }} — Groups">
    @php($groupRoutePrefix = 'dashboard.')
    @include('partials.groups._show')
</x-layouts.dashboard>
