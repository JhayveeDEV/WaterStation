// Live stat counters for the staff/admin dashboards. Reuses the same "staff"
// channel events the order board already listens to — no extra broadcast needed.
document.addEventListener('DOMContentLoaded', () => {
    const pendingEl = document.querySelector('[data-stat="pending"]');
    const inProgressEl = document.querySelector('[data-stat="in_progress"]');
    const completedEl = document.querySelector('[data-stat="completed_today"]');
    const ordersMonthEl = document.querySelector('[data-stat="orders_this_month"]');

    if (!pendingEl && !inProgressEl && !completedEl && !ordersMonthEl) return;

    const IN_PROGRESS = ['confirmed', 'preparing', 'ready_for_pickup', 'assigned', 'out_for_delivery'];

    function bump(el, delta) {
        if (!el) return;
        el.textContent = String(Math.max(0, parseInt(el.textContent || '0', 10) + delta));
    }

    document.addEventListener('order-created', () => {
        bump(pendingEl, 1);
        bump(ordersMonthEl, 1);
    });

    document.addEventListener('order-status-updated', (e) => {
        const { status, from_status } = e.detail;

        if (from_status === 'pending') bump(pendingEl, -1);
        else if (IN_PROGRESS.includes(from_status)) bump(inProgressEl, -1);

        if (status === 'pending') bump(pendingEl, 1);
        else if (IN_PROGRESS.includes(status)) bump(inProgressEl, 1);
        else if (status === 'completed') bump(completedEl, 1);
    });
});
