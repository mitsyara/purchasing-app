@props([
    'livewire' => null,
])
<!DOCTYPE html>
<html lang="vi">
<html lang="vi" @class(['fi', 'dark' => filament()->hasDarkModeForced()])>

<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow">
    @if ($favicon = filament()->getFavicon())
        <link rel="icon" href="{{ $favicon }}" />
    @endif

    <title>Nhập môn DLHQ v4.0</title>

    <style>
        [x-cloak=''],
        [x-cloak='x-cloak'],
        [x-cloak='1'] {
            display: none !important;
        }

        [x-cloak='inline-flex'] {
            display: inline-flex !important;
        }

        @media (max-width: 1023px) {
            [x-cloak='-lg'] {
                display: none !important;
            }
        }

        @media (min-width: 1024px) {
            [x-cloak='lg'] {
                display: none !important;
            }
        }
    </style>

    @vite(['resources/css/filament/purchasing/theme.css'])

    @filamentStyles

    {{ filament()->getTheme()->getHtml() }}
    {{ filament()->getFontHtml() }}
    {{ filament()->getMonoFontHtml() }}
    {{ filament()->getSerifFontHtml() }}

    <style>
        :root {
            --font-family: '{!! filament()->getFontFamily() !!}';
            --mono-font-family: '{!! filament()->getMonoFontFamily() !!}';
            --serif-font-family: '{!! filament()->getSerifFontFamily() !!}';
            --sidebar-width: {{ filament()->getSidebarWidth() }};
            --collapsed-sidebar-width: {{ filament()->getCollapsedSidebarWidth() }};
            --default-theme-mode: {{ filament()->getDefaultThemeMode()->value }};
            --text-sm: 0.75rem;
            --text-sm--line-height: calc(1 / 0.75);
        }
    </style>

    @stack('styles')

    {{-- THEME INITIALIZER --}}
    <script>
        /**
         * Smart theme loader
         * - Priority: localStorage.theme > Filament defaultThemeMode
         * - Supports system theme sync
         */
        const loadDarkMode = () => {
            let theme = localStorage.getItem('theme');

            if (!theme) {
                theme = @js(filament()->getDefaultThemeMode()->value);
                localStorage.setItem('theme', theme);
            }

            if (theme === 'system') {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            document.documentElement.classList.toggle('dark', theme === 'dark');
        };

        // Run before rendering
        loadDarkMode();

        // Listen for Livewire navigation or system theme change
        document.addEventListener('livewire:navigated', loadDarkMode);
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (localStorage.getItem('theme') === 'system') loadDarkMode();
        });
    </script>

</head>

<body>
    <div x-data="{ isSticky: false }">

        {{-- Page Header --}}
        <div class="flex flex-col md:flex-row justify-between items-center px-8 py-4">
            <div>
                <h1 class="text-lg block leading-none text-start">
                    <span
                        class="inline-block text-2xl font-bold bg-[linear-gradient(to_right,#06b6d4,#3b82f6,#8b5cf6,#ec4899,#f97316)] bg-clip-text text-transparent">
                        DỮ LIỆU HẢI NINH
                    </span>
                    <span class="text-sm font-normal">cho người mới chém gió!</span>
                </h1>
                <span class="text-xs">version: 4.0</span>
            </div>

            {{-- Left side --}}
            <div class="flex items-center space-x-4">
                {{-- Ultiliti Button --}}
                <x-filament::button x-on:click="$dispatch('open-modal', { id: 'ultility' })" color="info"
                    icon="heroicon-s-wrench-screwdriver" outlined>
                    Ultility Tools
                </x-filament::button>

                {{-- Theme switcher --}}
                <x-filament::input.wrapper class="w-auto">
                    <x-filament::input.select id="theme-switcher" x-data="{ theme: localStorage.getItem('theme') ?? '@js(filament()->getDefaultThemeMode()->value)' }" x-init="theme = localStorage.getItem('theme') ?? '@js(filament()->getDefaultThemeMode()->value)';
                    $el.value = theme;
                    loadDarkMode();"
                        x-on:change="
                            theme = $event.target.value;
                            localStorage.setItem('theme', theme);
                            $dispatch('theme-changed', theme);
                            loadDarkMode();
                        "
                        class="w-auto text-sm" aria-label="Chuyển đổi giao diện sáng/tối/hệ thống">
                        <option value="light">Giao diện sáng</option>
                        <option value="dark">Giao diện tối</option>
                        <option value="system">Giao diện hệ thống</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>

        {{ $slot }}

        @livewire(Filament\Livewire\Notifications::class)

        @filamentScripts(withCore: true)

    </div>

    {{-- Ultility Modal --}}
    <x-filament::modal id="ultility" icon="heroicon-o-information-circle" slide-over width="5xl" sticky-footer
        :close-by-clicking-away="false" :close-by-escaping="false">
        {{-- TODO: Ultility tools here. --}}
        <div class="text-xs">
            <x-slot>
                <div x-data="{ activeTab: 'tab1' }">
                    <x-filament::tabs>
                        <x-filament::tabs.item alpine-active="activeTab === 'tab1'" x-on:click="activeTab = 'tab1'">
                            Tính Giá hoà vốn
                        </x-filament::tabs.item>

                        <x-filament::tabs.item alpine-active="activeTab === 'tab2'" x-on:click="activeTab = 'tab2'">
                            Xem tỷ giá VCB
                        </x-filament::tabs.item>

                        <x-filament::tabs.item alpine-active="activeTab === 'tab3'" x-on:click="activeTab = 'tab3'">
                            Tạo Báo giá
                        </x-filament::tabs.item>

                        {{-- Other tabs --}}
                    </x-filament::tabs>


                    {{-- List content of each tab --}}
                    <div x-show="activeTab === 'tab1'" class="p-4">
                        Đang phát triển thêm, bình tĩnh, tự tin, chờ đợi...
                    </div>
                    <div x-show="activeTab === 'tab2'" class="p-4">
                        <livewire:customs-data.exchange-rate lazy />
                    </div>
                    <div x-show="activeTab === 'tab3'" class="p-4">
                        <livewire:customs-data.price-quote lazy />
                    </div>

                </div>
            </x-slot>
        </div>

        <x-slot name="footer">
            {{-- Modal footer actions --}}
            <x-slot name="footerActions">
                <x-filament::button x-on:click="$dispatch('close-modal', { id: 'ultility' })" outlined color="gray">
                    Xin cảm ơn Nam Vương
                </x-filament::button>
            </x-slot>
        </x-slot>
    </x-filament::modal>

    {{-- Modal script --}}
    <script>
        // Runs immediately after Livewire has finished initializing
        document.addEventListener('livewire:initialized', () => {
            console.log('Livewire initialized');
        });
    </script>


    @stack('scripts')
</body>

</html>
