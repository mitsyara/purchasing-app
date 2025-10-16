<div>

    <div style="font-size: 0.65rem!important;">
        {{ $this->table }}
    </div>

    <x-filament::modal id="ultility">
        {{-- TODO: Ultility tools here. --}}
    </x-filament::modal>
</div>

<script>
    let polling = true;

    async function pollExportStatus() {
        const route = "{{ route('exports.status') }}";

        if (!polling) return;

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
            setTimeout(pollExportStatus, 10000); // 10 giây
        }
    }

    // Start polling ngay khi load trang
    pollExportStatus();

    window.addEventListener('resetPolling', event => {
        console.log('Reset polling triggered');
        resetPolling();
    });

    function resetPolling() {
        polling = true;
        pollExportStatus();
    }

    function skipExport() {
        @this.dispatch('skipExport');
    }

    function failedNotify() {
        new FilamentNotification()
            .title('Export Failed')
            .danger()
            .body('There was an error processing your export. Please try again later.')
            .send();
    }
</script>
