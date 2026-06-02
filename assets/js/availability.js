/**
 * Workshop Availability Auto-Refresh Engine
 * ------------------------------------------
 * Polls the availability API every 5 seconds and updates
 * seat counters, progress bars, status badges, and registration
 * buttons across all pages without page refresh.
 *
 * Usage: Add data-workshop-id="X" to containers, and the script
 * will update child elements with matching data-avail-* attributes.
 */
(function () {
    'use strict';

    const POLL_INTERVAL = 5000; // 5 seconds
    const API_BASE = (function() {
        // Detect base path from the page
        const scripts = document.querySelectorAll('script[src*="availability.js"]');
        if (scripts.length > 0) {
            const src = scripts[0].getAttribute('src');
            // src is like "../../assets/js/availability.js" or "../assets/js/availability.js"
            const parts = src.split('assets/js/availability.js');
            return parts[0] + 'api/api_availability.php';
        }
        // Fallback: try to find a meta tag or use relative path
        const base = document.querySelector('meta[name="base-path"]');
        if (base) {
            return base.content + 'api/api_availability.php';
        }
        return '/seminar_portal/api/api_availability.php';
    })();

    /**
     * Fetch availability for all workshops and update DOM elements.
     */
    function refreshAvailability() {
        const containers = document.querySelectorAll('[data-workshop-id]');
        if (containers.length === 0) return;

        // Collect unique workshop IDs
        const ids = new Set();
        containers.forEach(function(el) {
            ids.add(el.getAttribute('data-workshop-id'));
        });

        // If there's only one unique ID, fetch single; otherwise fetch all
        if (ids.size === 1) {
            const singleId = ids.values().next().value;
            fetch(API_BASE + '?id=' + singleId)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success && data.workshop) {
                        updateContainers(containers, [data.workshop]);
                    }
                })
                .catch(function() { /* silently retry on next interval */ });
        } else {
            fetch(API_BASE)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success && data.workshops) {
                        updateContainers(containers, data.workshops);
                    }
                })
                .catch(function() { /* silently retry on next interval */ });
        }
    }

    /**
     * Update DOM containers with fresh availability data.
     */
    function updateContainers(containers, workshops) {
        // Build lookup map
        var wsMap = {};
        workshops.forEach(function(ws) {
            wsMap[ws.id] = ws;
        });

        containers.forEach(function(container) {
            var wsId = container.getAttribute('data-workshop-id');
            var ws = wsMap[wsId];
            if (!ws) return;

            // Update registered count
            var regEl = container.querySelector('[data-avail-registered]');
            if (regEl) regEl.textContent = ws.registered;

            // Update available count
            var availEl = container.querySelector('[data-avail-available]');
            if (availEl) availEl.textContent = ws.available;

            // Update capacity
            var capEl = container.querySelector('[data-avail-capacity]');
            if (capEl) capEl.textContent = ws.capacity;

            // Update "booked / capacity" combined display
            var bookedEl = container.querySelector('[data-avail-booked]');
            if (bookedEl) bookedEl.textContent = ws.registered + ' / ' + ws.capacity;

            // Update status badge
            var statusEl = container.querySelector('[data-avail-status]');
            if (statusEl) {
                statusEl.textContent = ws.status;
                statusEl.classList.remove('bg-success', 'bg-danger', 'text-dark');
                if (ws.status === 'Open') {
                    statusEl.classList.add('bg-success');
                } else {
                    statusEl.classList.add('bg-danger');
                }
            }

            // Update progress bar
            var progressEl = container.querySelector('[data-avail-progress]');
            if (progressEl) {
                var pct = ws.capacity > 0 ? Math.round((ws.registered / ws.capacity) * 100) : 0;
                progressEl.style.width = pct + '%';
                progressEl.setAttribute('aria-valuenow', pct);
                progressEl.textContent = pct + '%';

                // Color coding
                progressEl.classList.remove('bg-primary', 'bg-warning', 'bg-danger', 'bg-success');
                if (pct >= 100) {
                    progressEl.classList.add('bg-danger');
                } else if (pct >= 75) {
                    progressEl.classList.add('bg-warning');
                } else {
                    progressEl.classList.add('bg-primary');
                }
            }

            // Update registration button
            var regBtn = container.querySelector('[data-avail-register-btn]');
            if (regBtn) {
                if (ws.status === 'Full') {
                    regBtn.disabled = true;
                    regBtn.classList.remove('btn-warning');
                    regBtn.classList.add('btn-secondary');
                    regBtn.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i>Full';
                } else {
                    regBtn.disabled = false;
                    regBtn.classList.remove('btn-secondary');
                    regBtn.classList.add('btn-warning');
                    regBtn.innerHTML = 'Register';
                }
            }
        });
    }

    // Start polling on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setInterval(refreshAvailability, POLL_INTERVAL);
        });
    } else {
        setInterval(refreshAvailability, POLL_INTERVAL);
    }
})();
