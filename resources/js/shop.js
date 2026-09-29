// Live stock on the shop page: no polling, no refresh. Stock levels come from
// InventoryService broadcasting on the public "inventory" channel every time
// stock changes anywhere — a staff stock adjustment, a delivery being cancelled
// and returned to stock, or another customer's order using up the last units.
document.addEventListener('DOMContentLoaded', () => {
    const grid = document.querySelector('[data-product-row]');
    if (!grid || !window.Echo) return;

    window.Echo.channel('inventory').listen('.stock.updated', (payload) => {
        const row = document.querySelector(`[data-product-row="${payload.product_id}"]`);
        if (!row) return;

        const label = row.querySelector('[data-stock-label]');
        const qtyInput = row.querySelector('[data-qty-input]');
        const addBtn = row.querySelector('[data-add-btn]');
        const addLabel = row.querySelector('[data-add-label]');

        if (label) {
            label.textContent = payload.in_stock ? `${payload.quantity_on_hand} in stock` : 'Out of stock';
            label.classList.toggle('text-red-500', !payload.in_stock);
            label.classList.toggle('text-gray-400', payload.in_stock);
        }
        if (qtyInput) {
            qtyInput.max = Math.max(1, payload.quantity_on_hand);
            qtyInput.disabled = !payload.in_stock;
            if (parseInt(qtyInput.value || '1', 10) > payload.quantity_on_hand) {
                qtyInput.value = Math.max(1, payload.quantity_on_hand);
            }
        }
        if (addBtn) addBtn.disabled = !payload.in_stock;
        if (addLabel) addLabel.textContent = payload.in_stock ? 'Add to cart' : 'Out of stock';
    });
});
