// Live order board: new orders and status changes arrive over the "staff" private channel
// (see notifications.js, which re-dispatches them as DOM CustomEvents) and are reflected
// here with no page reload and no polling.
document.addEventListener('DOMContentLoaded', () => {
    const board = document.getElementById('order-board');
    if (!board) return;

    function columnFor(status) {
        return document.getElementById(`col-${status}`);
    }

    function cardTemplate({ order_id, order_number, customer_name, total_amount, status }) {
        const el = document.createElement('a');
        el.href = `/manage/orders/${order_id}`;
        el.dataset.orderRow = order_id;
        el.className = 'group block rounded-xl border border-slate-200 border-l-4 border-l-sky-400 bg-white p-3 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md';
        el.innerHTML = `
            <div class="flex items-start justify-between gap-2">
                <p class="truncate text-sm font-bold text-slate-900 group-hover:text-brand-700">${order_number}</p>
                <p class="shrink-0 text-sm font-bold text-slate-900">₱${Number(total_amount).toFixed(2)}</p>
            </div>
            <p class="mt-1 truncate text-xs font-medium text-slate-600">${customer_name || 'New customer order'}</p>
            <div class="mt-3 flex flex-wrap gap-1.5 text-[11px] font-medium text-slate-500">
                <span class="rounded-md bg-slate-100 px-2 py-1">New order</span>
            </div>
        `;
        return el;
    }

    document.addEventListener('order-created', (e) => {
        const col = columnFor(e.detail.status);
        if (col) {
            col.querySelector('[data-empty]')?.remove();
            col.prepend(cardTemplate(e.detail));
            const counter = col.closest('[data-column]')?.querySelector('[data-count]');
            if (counter) counter.textContent = String(Number(counter.textContent || '0') + 1);
        }
    });

    document.addEventListener('order-status-updated', (e) => {
        const row = document.querySelector(`[data-order-row="${e.detail.order_id}"]`);
        const targetCol = columnFor(e.detail.status);

        if (!targetCol) {
            // Final status (completed/cancelled/rejected) — it leaves the active board entirely.
            row?.remove();
            return;
        }

        if (row) {
            targetCol.prepend(row);
        } else {
            targetCol.prepend(cardTemplate({
                order_id: e.detail.order_id,
                order_number: e.detail.order_number,
                customer_name: '',
                total_amount: 0,
                status: e.detail.status,
            }));
        }
    });
});
