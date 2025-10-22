<!DOCTYPE html>
<html lang={{ app()->getLocale() }}>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title')</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
        }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            line-height: 1.5;
        }

        a {
            text-decoration: none;
        }

        .antialiased {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .layout-wrapper {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
            min-height: 100vh;
            background-color: #f7fafc;
        }

        @media (prefers-color-scheme: dark) {
            .layout-wrapper {
                background-color: #1a202c;
            }
        }

        .layout-inner {
            width: 100%;
            padding: 0;
        }

        @media (min-width: 1024px) {
            .layout-inner {
                padding: 3rem;
            }
        }

        @media (min-width: 1280px) {
            .layout-inner {
                max-width: 72rem;
                margin-left: auto;
                margin-right: auto;
            }
        }

        .error-section {
            background-color: #ffffff;
            padding-top: 3rem;
            padding-bottom: 3rem;
            border-radius: 0.25rem;
        }

        .error_background_image {
            height: 400px;
            background-position: center;
            background-image: url({{ asset('images/error.gif') }} );
        }

        .error-code {
            font-size: 3.75rem;
            font-weight: 700;
            text-align: center;
            margin: 0 0;
            color: #ef4444;
        }

        .error-message {
            display: inline-block;
            margin-left: 0.75rem;
            font-size: 1.5rem;
            font-weight: 300;
            color: #718096;
        }

        .error-buttons {
            margin-top: 1rem;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 2rem;
        }

        .btn-back,
        .btn-home {
            display: inline-block;
            padding: 0.5rem 1rem;
            font-weight: 600;
            color: #ffffff;
            border-radius: 0.25rem;
            transition: background-color 0.1s ease-in-out;
            text-decoration: none;
        }

        .btn-back {
            background-color: #3b82f6;
        }

        .btn-back:hover {
            background-color: #2563eb;
        }

        .btn-home {
            background-color: #26bbd6;
        }

        .btn-home:hover {
            background-color: #1a9db7;
        }
    </style>

</head>

<body class="antialiased">
    <div class="layout-wrapper">
        <div class="layout-inner">
            <section class="error-section">
                <div class="error_background_image"></div>

                <h1 class="error-code">
                    @yield('code')!
                    <span class="error-message">
                        @yield('message')
                    </span>
                </h1>

                <div class="error-buttons">
                    <a href="{{ url()->previous() }}" class="btn-back">
                        {{ __('Go Back') }}
                    </a>
                </div>
            </section>
        </div>
    </div>
</body>

</html>
