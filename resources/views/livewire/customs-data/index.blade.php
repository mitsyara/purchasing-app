<div>
    <div class=" px-4 xl:px-6">
        {{ $this->table }}
    </div>

    <style>
        .fi-ta-text-item.fi-size-sm {
            font-size: var(--text-sm);
            line-height: var(--tw-leading, var(--text-sm--line-height));
            --tw-leading: calc(var(--spacing) * 4);
            line-height: calc(var(--spacing) * 4);
        }
    </style>
</div>

<script>
    let polling = true;
    const MAX_POLL_TIME = 5 * 60 * 1000; // 5 phút
    const startTime = Date.now();

    async function pollExportStatus() {
        const route = "{{ route('exports.status') }}";

        if (!polling) return;

        // Kiểm tra đã quá 5 phút chưa
        if (Date.now() - startTime > MAX_POLL_TIME) {
            console.log('Polling stopped after 5 minutes.');
            polling = false;
            return;
        }

        try {
            const res = await fetch(route, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) throw new Error('Network response was not ok');

            const data = await res.json();

            if (data.status === 'ready') {
                console.log('Export ready! URL:', data.url);
                polling = false;
                @this.dispatch('fileReady');

            } else if (data.status === 'failed') {
                console.log('Export failed. ', data.message);
                failedNotify();
                polling = false;
            } else {
                console.log('Polling...');
            }

        } catch (err) {
            console.error('Error fetching export status', err);
        } finally {
            if (polling) {
                setTimeout(pollExportStatus, 10000); // 10 giây
            }
        }
    }

    // Start polling ngay khi load trang
    pollExportStatus();

    function failedNotify() {
        new FilamentNotification()
            .title('Export Failed')
            .danger()
            .body('There was an error processing your export. Please try again later.')
            .send();
    }

    window.addEventListener('resetPolling', event => {
        console.log('Reset polling triggered');
        polling = true;
        pollExportStatus();
    });
</script>
