<x-filament-panels::page class="fi-width-4xl">
    <div class="grid grid-cols-4 gap-4">
        {{-- Sidebar --}}
        <div class="col-span-1 border-r border-gray-200 dark:border-gray-700 pr-4">
            <ul class="space-y-2">
                @foreach ($docs as $doc)
                    <li>
                        <x-filament::link
                            wire:click="$set('pin', '{{ $doc['slug'] }}')"
                            tag="button"
                            class="block text-left hover:underline transition-all
                                   {{ $pin === $doc['slug'] ? 'font-semibold text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' }}">
                            {{ $doc['name'] }}
                        </x-filament::link>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Content --}}
        <div class="col-span-3 prose prose-multicolor dark:prose-invert prose-sm max-w-none px-8">
            @if ($content)
                {!! $content !!}
            @else
                <p class="text-gray-500 dark:text-gray-400">Chọn một tài liệu để xem nội dung.</p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
