@php
    $maxDate = today()->format('Y-m-d');
@endphp
<div>
    <div class=" flex justify-between items-center mb-4">
        <x-filament::input.wrapper class="inline-block w-auto">
            <x-filament::input
                id="date"
                type="date"
                class="w-auto"
                value="{{ now('Asia/Ho_Chi_Minh')->toDateString() }}"
                max="{{ now('Asia/Ho_Chi_Minh')->toDateString() }}"
                x-on:change="$dispatch('dateChanged', { date: $event.target.value })"
            />
        </x-filament::input.wrapper>
    </div>

    {{ $this->table }}
</div>
