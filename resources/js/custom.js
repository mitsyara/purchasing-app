console.log("Filament SPA Canceller Initialized");

// Track active non-navigation requests
window.__pendingControllers = [];

function cancelPendingRequests() {
    const pending = window.__pendingControllers;

    if (pending.length <= 1) return;

    const toCancel = pending.slice(0, -1); // bỏ lại request cuối
    console.log(`🧨 Cancelling ${toCancel.length} non-SPA requests`);

    toCancel.forEach(ctrl => ctrl.abort());

    // chỉ giữ lại phần tử cuối
    window.__pendingControllers = pending.slice(-1);
}

// Listen BEFORE navigation starts
document.addEventListener("livewire:navigate", () => {
    cancelPendingRequests();
}, { capture: true });
