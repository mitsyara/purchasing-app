@props([
    'livewire' => null,
    'dark' => false,
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
        }
    </style>

    @stack('styles')

    @if (!filament()->hasDarkMode())
        <script>
            localStorage.setItem('theme', 'light')
        </script>
    @elseif (filament()->hasDarkModeForced())
        <script>
            localStorage.setItem('theme', 'dark')
        </script>
    @else
        <script>
            const loadDarkMode = () => {
                window.theme = localStorage.getItem('theme') ?? @js(filament()->getDefaultThemeMode()->value)

                if (
                    window.theme === 'dark' ||
                    (window.theme === 'system' &&
                        window.matchMedia('(prefers-color-scheme: dark)')
                        .matches)
                ) {
                    document.documentElement.classList.add('dark')
                }
            }

            loadDarkMode()

            document.addEventListener('livewire:navigated', loadDarkMode)
        </script>
    @endif

</head>

<body>
    <div x-data="{ isSticky: false }">

        {{-- Page Header --}}
        <div class="flex justify-between items-center px-8 py-4">
            <h1>
                <span class="text-lg font-semibold block leading-none">
                    DỮ LIỆU HẢI ANH cho người mới chém gió!
                </span>
                <span class="text-xs">version: 4.0</span>
            </h1>

            <x-filament::input.wrapper class="w-auto">
                <x-filament::input.select id="theme-switcher"
                    x-on:change="
                    localStorage.setItem('theme', $event.target.value);
                    theme = $event.target.value;
                    $dispatch('theme-changed', theme);
                    loadDarkMode();
                "
                    class="w-auto text-sm" aria-label="Chuyển đổi giao diện sáng/tối/hệ thống">
                    <option value="light" @if (filament()->getDefaultThemeMode() === \Filament\Enums\ThemeMode::Light) selected @endif>Giao diện sáng</option>
                    <option value="dark" @if (filament()->getDefaultThemeMode() === \Filament\Enums\ThemeMode::Dark) selected @endif>Giao diện tối</option>
                    <option value="system" @if (filament()->getDefaultThemeMode() === \Filament\Enums\ThemeMode::System) selected @endif>Giao diện Hệ thống</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        {{ $slot }}

        @livewire(Filament\Livewire\Notifications::class)

        @filamentScripts(withCore: true)

        @if (filament()->hasDarkMode() && !filament()->hasDarkModeForced())
            <script>
                loadDarkMode()
            </script>
        @endif

    </div>

    @stack('scripts')
</body>

</html>
