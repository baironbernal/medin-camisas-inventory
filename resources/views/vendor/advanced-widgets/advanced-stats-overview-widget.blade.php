@php
    $columns = $this->getColumns();
    $filters = $this->getFilters();
@endphp

<x-filament-widgets::widget class="fi-wi-stats-overview">

    @if ($filters)
        <div class="flex justify-end mb-4 px-1">
            <select
                wire:model.live="filter"
                class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-3 pr-8 text-sm text-gray-700 shadow-sm
                       focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500
                       dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            >
                @foreach ($filters as $value => $label)
                    <option value="{{ $value }}" @selected(($this->filter ?? array_key_first($filters)) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div
        @if ($pollingInterval = $this->getPollingInterval())
            wire:poll.{{ $pollingInterval }}
        @endif
        @class([
            'fi-wi-stats-overview-stats-ctn grid gap-6',
            'md:grid-cols-1' => $columns === 1,
            'md:grid-cols-2' => $columns === 2,
            'md:grid-cols-3' => $columns === 3,
            'md:grid-cols-2 xl:grid-cols-4' => $columns === 4,
        ])
    >
        @foreach ($this->getCachedStats() as $stat)
            {{ $stat }}
        @endforeach
    </div>

</x-filament-widgets::widget>
