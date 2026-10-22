<x-layouts.hnz-dashboard title="Edit {{ $group->name }} — {{ \App\Support\PublicDashboard::label() }}">
    @php($groupRoutePrefix = \App\Support\PublicDashboard::routePrefix())
    @include('partials.groups._edit')
</x-layouts.hnz-dashboard>
