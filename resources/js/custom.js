console.log("🧠 Filament SPA Canceller Initialized");

// Track active non-navigation requests
window.__pendingControllers = [];

function registerAbortableFetch() {
    const originalFetch = window.fetch;
    window.fetch = async (input, init = {}) => {
        // Detect if it's Livewire/SPA navigation
        const url = typeof input === "string" ? input : input.url ?? "";
        const isNavigation =
            url.includes("/livewire/message") ||
            url.includes("/livewire/update") ||
            url.includes("/livewire/") ||
            url.includes("/_livewire");

        // Only track non-navigation fetch
        if (!isNavigation) {
            const controller = new AbortController();
            init.signal = controller.signal;
            window.__pendingControllers.push(controller);

            try {
                const response = await originalFetch(input, init);
                window.__pendingControllers = window.__pendingControllers.filter(c => c !== controller);
                return response;
            } catch (e) {
                if (e.name === "AbortError") {
                    console.warn("🛑 Aborted request:", url);
                    return;
                }
                throw e;
            }
        } else {
            return await originalFetch(input, init);
        }
    };
}

// Cancel pending requests
function cancelPendingRequests() {
    if (window.__pendingControllers.length === 0) return;

    console.log(`🧨 Cancelling ${window.__pendingControllers.length} non-SPA requests`);
    window.__pendingControllers.forEach(ctrl => ctrl.abort());
    window.__pendingControllers = [];
}

// Register fetch patch
registerAbortableFetch();

// Listen BEFORE navigation starts
// document.addEventListener("livewire:navigate", () => {
//     // abort right before SPA routing
//     cancelPendingRequests();
// }, { capture: true });
