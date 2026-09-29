// Live notification bell: badge count + toast, driven entirely by the private user channel.
// No polling — everything here reacts to a broadcast event.
document.addEventListener('DOMContentLoaded', () => {
    const badge = document.getElementById('notif-badge');
    const list = document.getElementById('notif-list');
    const toastHost = document.getElementById('toast-host');

    function bumpBadge() {
        if (!badge) return;
        const next = parseInt(badge.dataset.count || '0', 10) + 1;
        badge.dataset.count = String(next);
        badge.textContent = next > 9 ? '9+' : String(next);
        badge.classList.remove('hidden');
    }

    function decrementBadge() {
        if (!badge) return;
        const next = Math.max(0, parseInt(badge.dataset.count || '0', 10) - 1);
        badge.dataset.count = String(next);
        if (next === 0) {
            badge.classList.add('hidden');
        } else {
            badge.textContent = next > 9 ? '9+' : String(next);
        }
    }

    function markRead(id) {
        return fetch(`/notifications/${id}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
    }

    // Any notification link on the page — the bell dropdown or the full list —
    // gets marked read the moment it's clicked, and the badge drops immediately.
    // We don't block navigation on the network call finishing.
    document.addEventListener('click', (e) => {
        const el = e.target.closest('[data-notification-id]');
        if (!el || el.dataset.read === '1') return;

        el.dataset.read = '1';
        el.classList.remove('bg-brand-50/40');
        const dot = el.querySelector('.bg-brand-500');
        if (dot) dot.classList.replace('bg-brand-500', 'bg-gray-200');
        decrementBadge();
        markRead(el.dataset.notificationId).catch(() => {});
    });

    function showToast(title, message) {
        if (!toastHost) return;
        const el = document.createElement('div');
        el.className = 'card px-4 py-3 text-sm shadow-lg animate-[fadein_.15s_ease-out] max-w-sm';
        el.innerHTML = `<p class="font-medium text-gray-900">${title}</p><p class="text-gray-600">${message}</p>`;
        toastHost.appendChild(el);
        setTimeout(() => el.remove(), 5000);
    }

    function prependNotification(n) {
        if (!list) return;
        const item = document.createElement('a');
        item.href = n.url || '#';
        item.className = 'block px-4 py-3 border-b border-gray-100 hover:bg-gray-50';
        item.innerHTML = `<p class="text-sm font-medium text-gray-900">${n.title}</p>
                           <p class="text-xs text-gray-500 mt-0.5">${n.message}</p>
                           <p class="text-xs text-gray-400 mt-1">${n.created_at}</p>`;
        list.prepend(item);
    }

    if (window.Echo && window.AppUserId) {
        window.Echo.private(`users.${window.AppUserId}`)
            .listen('.notification.created', (n) => {
                bumpBadge();
                showToast(n.title, n.message);
                prependNotification(n);
            })
            .listen('.order.status.updated', (payload) => {
                const row = document.querySelector(`[data-order-row="${payload.order_id}"]`);
                if (row) {
                    const badgeEl = row.querySelector('[data-order-status-badge]');
                    if (badgeEl) badgeEl.textContent = payload.status_label;
                }
            })
            .listen('.payment.status.updated', (payload) => {
                const el = document.querySelector('[data-payment-status]');
                if (el && el.closest('[data-order-row], body')) {
                    el.textContent = el.textContent.replace(/·.*/, '· ' + payload.payment_status.charAt(0).toUpperCase() + payload.payment_status.slice(1));
                }
                if (payload.payment_status === 'paid') {
                    document.querySelector('a[href*="/pay"]')?.remove();
                }
            });

        // Staff/admin: live order board. Only present on those pages.
        if (window.AppIsStaff) {
            window.Echo.private('staff')
                .listen('.order.created', (payload) => {
                    document.dispatchEvent(new CustomEvent('order-created', { detail: payload }));
                    showToast('New order', `${payload.customer_name} placed an order (${payload.order_number}).`);
                })
                .listen('.order.status.updated', (payload) => {
                    document.dispatchEvent(new CustomEvent('order-status-updated', { detail: payload }));
                });
        }
    }
});
