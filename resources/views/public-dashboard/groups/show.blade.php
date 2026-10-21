<x-layouts.public-dashboard title="{{ $group->name }} — Groups">
    @php($groupRoutePrefix = \App\Support\PublicDashboard::routePrefix())
    @include('partials.groups._show')
</x-layouts.public-dashboard>
