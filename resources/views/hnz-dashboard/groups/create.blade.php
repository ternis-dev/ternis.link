<x-layouts.hnz-dashboard title="New Group — {{ \App\Support\PublicDashboard::label() }}">
    @php($groupRoutePrefix = \App\Support\PublicDashboard::routePrefix())
    @include('partials.groups._create')
</x-layouts.hnz-dashboard>
