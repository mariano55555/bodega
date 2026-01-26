// Register Alpine.js components globally before Alpine starts
document.addEventListener('alpine:init', () => {
    // Initialize stores for dispatch rows
    Alpine.store('dispatchProducts', {});
    Alpine.store('rowTotals', {});
    Alpine.store('grandTotal', 0);

    // Dispatch row component for dispatch creation form
    Alpine.data('dispatchRow', (config) => ({
        expanded: false,
        index: config.index,
        productId: config.productId,
        quantity: config.quantity,
        unitPrice: config.unitPrice,
        unitId: config.unitId,
        notes: config.notes,

        init() {
            // Emit initial total on mount
            this.$nextTick(() => {
                this.emitTotal();
            });
        },

        get productInfo() {
            const data = Alpine.store('dispatchProducts') || {};
            return this.productId ? data[this.productId] : null;
        },

        get total() {
            return (parseFloat(this.quantity) || 0) * (parseFloat(this.unitPrice) || 0);
        },

        emitTotal() {
            window.dispatchEvent(new CustomEvent('row-total-updated', {
                detail: { index: this.index, total: this.total }
            }));
        },

        selectProduct(id) {
            this.productId = id;
            const productsData = Alpine.store('dispatchProducts') || {};
            if (id && productsData[id]) {
                this.unitPrice = productsData[id].cost || 0;
                this.unitId = productsData[id].unit_id || '';
            }
            this.emitTotal();
            this.syncToLivewire();
        },

        updateQuantity() {
            this.emitTotal();
            this.syncToLivewire();
        },

        updateUnitPrice() {
            this.emitTotal();
            this.syncToLivewire();
        },

        clearRow() {
            // Instant client-side clear - no server request
            this.productId = '';
            this.quantity = 1;
            this.unitPrice = 0;
            this.unitId = '';
            this.notes = '';
            this.expanded = false;
            this.emitTotal();
            // Sync cleared state to Livewire in background (deferred)
            this.syncToLivewire();
        },

        syncToLivewire() {
            // Batch update to Livewire (single request with defer)
            this.$wire.set(`details.${this.index}.product_id`, this.productId, false);
            this.$wire.set(`details.${this.index}.quantity`, this.quantity, false);
            this.$wire.set(`details.${this.index}.unit_price`, this.unitPrice, false);
            this.$wire.set(`details.${this.index}.unit_of_measure_id`, this.unitId, false);
            this.$wire.set(`details.${this.index}.notes`, this.notes, false);
        }
    }));
});
