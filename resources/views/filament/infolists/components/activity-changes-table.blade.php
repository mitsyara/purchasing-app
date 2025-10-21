@php
    $state = $getState() ?? [];
@endphp
<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div class="changes-table-wrapper">
        <table class="changes-table">
            <thead>
                <tr>
                    <th>{{ __('Attribute') }}</th>
                    <th>{{ __('From') }}</th>
                    <th>{{ __('To') }}</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($state as $item)
                    @php
                        $old = $item['old'] ?? null;
                        $new = $item['new'] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $item['attribute'] }}</td>
                        <td class="text-muted">{{ $old === '' || $old === null ? '—' : $old }}</td>
                        <td @class([
                            'text-changed' => $old !== $new,
                            'text-muted' => $old === $new,
                        ])>
                            {{ $new === '' || $new === null ? '—' : $new }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-2">No changes found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-dynamic-component>

<style>
    .changes-table-wrapper {
        border-radius: var(--radius-lg);
        background-color: var(--color-white);
        --tw-shadow: 0 1px 2px 0 var(--tw-shadow-color, #0000000d);
        --tw-ring-color: color-mix(in oklab, var(--gray-950) 5%, transparent);
        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow);
        overflow-x: auto;
        max-width: 100%;
    }

    .changes-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
        /* ✅ để cột đầu auto theo nội dung */
        /* border-color: var(--gray-200); */
        font-size: var(--text-sm);
        line-height: var(--text-sm--line-height, 1.25rem);
    }

    /* --- Cấu trúc cột --- */
    .changes-table th:first-child,
    .changes-table td:first-child {
        white-space: nowrap;
        /* Attribute không wrap */
        width: 1%;
        /* fit nội dung nhưng vẫn cho table-layout tự tính */
    }

    .changes-table th:nth-child(2),
    .changes-table td:nth-child(2),
    .changes-table th:nth-child(3),
    .changes-table td:nth-child(3) {
        width: 50%;
        /* From / To chia đôi phần còn lại */
        white-space: normal;
        /* ✅ cho phép xuống dòng */
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    /* --- Padding / Border --- */
    .changes-table th,
    .changes-table td {
        padding-inline: calc(var(--spacing) * 3);
        padding-block: calc(var(--spacing) * 2);
        text-align: start;
        border-bottom: 1px solid var(--gray-200);
        vertical-align: top;
    }

    .changes-table thead th {
        font-weight: var(--font-weight-medium);
        color: var(--gray-700);
        background-color: var(--color-white);
    }

    /* --- Row style --- */
    .changes-table tbody tr:nth-child(even) td {
        background-color: color-mix(in oklab, var(--gray-100) 60%, transparent);
    }

    /* --- Text style --- */
    .text-muted {
        color: var(--gray-500);
    }

    .text-changed {
        color: var(--primary-600);
        font-weight: 500;
    }
</style>
