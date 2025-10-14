@php
    use Illuminate\View\ComponentAttributeBag;
    use App\Filament\Clusters\CustomsData\Resources\CustomsData\Pages\ManageCustomsData;
@endphp

@props([
    'debounce' => '500ms',
    'onBlur' => false,
    'placeholder' => __('filament-tables::table.fields.search.placeholder'),
    'wireModel' => 'tableSearch',
])

@php
    $wireAttr  = $onBlur ? 'wire:model' : "wire:model.live.debounce.{$debounce}";

    // Common attributes
    $common = [
        'autocomplete' => 'off',
        'inlinePrefix' => true,
        'maxlength' => 1000,
        'placeholder' => $placeholder,
        'type' => 'search',
        'wire:key' => $this->getId() . ".table.{$wireModel}.field.input",
        $wireAttr => $wireModel,
        'x-bind:id' => '$id(\'input\')',
        'x-data' => '{ original: \'\' }',
        'x-init' => 'original = $el.value',
        'x-on:keyup' => 'if ($event.key === \'Enter\' && original.trim() !== $el.value.trim()) { original = $el.value.trim(); $wire.$refresh() }',
        'x-on:blur' => 'if (original.trim() !== $el.value.trim()) { original = $el.value.trim(); $wire.$refresh() }',
    ];
    
    $inputAttributes = (new ComponentAttributeBag())->merge($common, escape: false);

@endphp

<div x-id="['input']" {{ $attributes->class(['fi-ta-search-field']) }}>
    <label x-bind:for="$id('input')" class="fi-sr-only">
        {{ __('filament-tables::table.fields.search.label') }}
    </label>

    <x-filament::input.wrapper
        inline-prefix
        :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass"
        :prefix-icon-alias="\Filament\Tables\View\TablesIconAlias::SEARCH_FIELD"
        :wire:target="$wireModel"
    >
        <x-filament::input :attributes="$inputAttributes" />
    </x-filament::input.wrapper>
</div>
