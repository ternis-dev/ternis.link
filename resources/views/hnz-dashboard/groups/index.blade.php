<x-layouts.hnz-dashboard title="Link Groups — {{ \App\Support\PublicDashboard::label() }}">
    @php($groupRoutePrefix = \App\Support\PublicDashboard::routePrefix())
    @include('partials.groups._index')
</x-layouts.hnz-dashboard>
