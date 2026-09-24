<div>
    @if ($modal)
        @include('livewire.dashboard.partials.link-form-body')
    @else
        <x-ui.card title="New Short Link">
            @include('livewire.dashboard.partials.link-form-body')
        </x-ui.card>
    @endif
</div>
