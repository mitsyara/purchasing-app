<div style="font-size: 0.65rem!important;" x-on:export-started.window="startPolling()">
    
    {{ $this->table }}

    <x-filament::modal id="lock-screen">
        {{-- TODO: User must input PIN before proceeding. --}}
    </x-filament::modal>
</div>

<script>
    async function pollExportStatus() {
        try {
            const res = await fetch('/export/status', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) throw new Error('Network response was not ok');

            const data = await res.json();

            if (data.status === 'ready') {
                console.log('Export ready! URL:', data.url);
                notify(data.url);
            } else {
                console.log('Export pending...');
            }
        } catch (err) {
            console.error('Error fetching export status', err);
        } finally {
            // luôn schedule lần poll tiếp theo sau 10 giây
            setTimeout(pollExportStatus, 10000);
        }
    }

    // Start polling ngay khi load trang
    pollExportStatus();

    function notify(url) {
        new FilamentNotification()
            .title('Your export is ready!')
            .success()
            .body('Your export file is ready for download.')
            .duration(500000) // 5 phút
            .actions([
                new FilamentNotificationAction('download')
                .link()
                .color('info')
                .url(url)
                .openUrlInNewTab(),
            ])
            .send();
    }

    function failedNotify() {
        new FilamentNotification()
            .title('Export Failed')
            .danger()
            .body('There was an error processing your export. Please try again later.')
            .send();
    }
</script>
