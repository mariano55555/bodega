// Register Alpine.js components globally before Alpine starts
document.addEventListener('alpine:init', () => {
    // Initialize stores for dispatch rows
    Alpine.store('dispatchProducts', {});
    Alpine.store('dispatchAvailableStock', {});
    Alpine.store('rowTotals', {});
    Alpine.store('grandTotal', 0);

    // Initialize stores for purchase rows
    Alpine.store('purchaseProducts', {});
    Alpine.store('purchaseRowTotals', {});
    Alpine.store('purchaseGrandTotal', 0);

    // Initialize stores for transfer rows
    Alpine.store('transferProducts', {});
    Alpine.store('transferAvailableStock', {});

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

        get availableStock() {
            const stockData = Alpine.store('dispatchAvailableStock') || {};
            return this.productId ? (parseFloat(stockData[this.productId]) || 0) : 0;
        },

        get exceedsStock() {
            if (!this.productId || this.availableStock <= 0) return false;
            return (parseFloat(this.quantity) || 0) > this.availableStock;
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
            this.capQuantityToStock();
            this.emitTotal();
            this.syncToLivewire();
        },

        updateQuantity() {
            this.capQuantityToStock();
            this.emitTotal();
            this.syncToLivewire();
        },

        capQuantityToStock() {
            if (this.productId && this.availableStock > 0) {
                const qty = parseFloat(this.quantity) || 0;
                if (qty > this.availableStock) {
                    this.quantity = parseFloat(this.availableStock.toFixed(5));
                }
            }
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

    // Purchase row component for purchase creation/edit form
    Alpine.data('purchaseRow', (config) => ({
        expanded: false,
        index: config.index,
        productId: config.productId,
        quantity: config.quantity,
        unitCost: config.unitCost,
        discountPercentage: config.discountPercentage,
        taxPercentage: config.taxPercentage,
        lotNumber: config.lotNumber,
        expirationDate: config.expirationDate,
        notes: config.notes,

        // Autocomplete properties
        searchText: '',
        showDropdown: false,
        highlightIndex: -1,

        init() {
            // Set initial search text from selected product
            if (this.productId) {
                const data = Alpine.store('purchaseProducts') || {};
                const p = data[this.productId];
                if (p) {
                    this.searchText = p.name + (p.sku ? ' - ' + p.sku : '');
                }
            }
            // Emit initial total on mount
            this.$nextTick(() => {
                this.emitTotal();
            });
        },

        get filteredProducts() {
            if (!this.searchText || this.searchText.length < 1) return [];
            const s = this.searchText.toLowerCase();
            const store = Alpine.store('purchaseProducts') || {};
            return Object.entries(store)
                .filter(([id, p]) =>
                    p.name.toLowerCase().includes(s) ||
                    (p.sku && p.sku.toLowerCase().includes(s))
                )
                .slice(0, 15)
                .map(([id, p]) => ({
                    id,
                    label: p.name + (p.sku ? ' - ' + p.sku : '')
                }));
        },

        onArrowDown() {
            if (!this.showDropdown) this.showDropdown = true;
            if (this.highlightIndex < this.filteredProducts.length - 1) this.highlightIndex++;
        },

        onArrowUp() {
            if (this.highlightIndex > 0) this.highlightIndex--;
        },

        onEnter() {
            if (this.highlightIndex >= 0 && this.filteredProducts[this.highlightIndex]) {
                this.pickProduct(this.filteredProducts[this.highlightIndex].id);
            }
        },

        pickProduct(id) {
            const store = Alpine.store('purchaseProducts') || {};
            const p = store[id];
            this.searchText = p ? p.name + (p.sku ? ' - ' + p.sku : '') : '';
            this.showDropdown = false;
            this.highlightIndex = -1;
            this.selectProduct(id);
        },

        clearSearch() {
            this.searchText = '';
            this.showDropdown = false;
            this.highlightIndex = -1;
            this.selectProduct('');
        },

        get productInfo() {
            const data = Alpine.store('purchaseProducts') || {};
            return this.productId ? data[this.productId] : null;
        },

        get subtotal() {
            return (parseFloat(this.quantity) || 0) * (parseFloat(this.unitCost) || 0);
        },

        get discountAmount() {
            return this.subtotal * ((parseFloat(this.discountPercentage) || 0) / 100);
        },

        get taxableAmount() {
            return this.subtotal - this.discountAmount;
        },

        get taxAmount() {
            return this.taxableAmount * ((parseFloat(this.taxPercentage) || 0) / 100);
        },

        get total() {
            return this.subtotal;
        },

        emitTotal() {
            window.dispatchEvent(new CustomEvent('purchase-row-total-updated', {
                detail: { index: this.index, total: this.total }
            }));
        },

        selectProduct(id) {
            this.productId = id;
            const productsData = Alpine.store('purchaseProducts') || {};
            if (id && productsData[id]) {
                // Set default unit cost from product's cost if available
                if (productsData[id].cost) {
                    this.unitCost = productsData[id].cost;
                }
            }
            this.emitTotal();
            this.syncToLivewire();
        },

        updateQuantity() {
            this.emitTotal();
            this.syncToLivewire();
        },

        updateUnitCost() {
            this.emitTotal();
            this.syncToLivewire();
        },

        updateDiscount() {
            this.emitTotal();
            this.syncToLivewire();
        },

        updateTax() {
            this.emitTotal();
            this.syncToLivewire();
        },

        clearRow() {
            // Instant client-side clear - no server request
            this.productId = '';
            this.quantity = 1;
            this.unitCost = 0;
            this.discountPercentage = 0;
            this.taxPercentage = 13;
            this.lotNumber = '';
            this.expirationDate = '';
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
            this.$wire.set(`details.${this.index}.unit_cost`, this.unitCost, false);
            this.$wire.set(`details.${this.index}.discount_percentage`, this.discountPercentage, false);
            this.$wire.set(`details.${this.index}.tax_percentage`, this.taxPercentage, false);
            this.$wire.set(`details.${this.index}.lot_number`, this.lotNumber, false);
            this.$wire.set(`details.${this.index}.expiration_date`, this.expirationDate, false);
            this.$wire.set(`details.${this.index}.notes`, this.notes, false);
        }
    }));

    // Transfer row component for inventory transfer creation/edit form
    Alpine.data('transferRow', (config) => ({
        expanded: false,
        index: config.index,
        productId: config.productId,
        quantity: config.quantity,
        notes: config.notes,

        init() {
            this.emitTotal();
            this._recalcHandler = () => this.emitTotal();
            window.addEventListener('transfer-recalculate-totals', this._recalcHandler);
        },

        destroy() {
            if (this._recalcHandler) {
                window.removeEventListener('transfer-recalculate-totals', this._recalcHandler);
            }
        },

        get productInfo() {
            const data = Alpine.store('transferProducts') || {};
            return this.productId ? data[this.productId] : null;
        },

        get availableStock() {
            const stockData = Alpine.store('transferAvailableStock') || {};
            return this.productId ? (parseFloat(stockData[this.productId]) || 0) : 0;
        },

        get exceedsStock() {
            if (!this.productId || this.availableStock <= 0) return false;
            return (parseFloat(this.quantity) || 0) > this.availableStock;
        },

        get total() {
            const unitCost = this.productInfo?.unit_cost || 0;
            return (parseFloat(this.quantity) || 0) * unitCost;
        },

        emitTotal() {
            window.dispatchEvent(new CustomEvent('transfer-row-total-updated', {
                detail: { index: this.index, total: this.total }
            }));
        },

        selectProduct(id) {
            this.productId = id;
            // Cap quantity if it exceeds new product's available stock
            this.capQuantityToStock();
            this.emitTotal();
            this.syncToLivewire();
        },

        updateQuantity() {
            this.capQuantityToStock();
            this.emitTotal();
            this.syncToLivewire();
        },

        capQuantityToStock() {
            if (this.productId && this.availableStock > 0) {
                const qty = parseFloat(this.quantity) || 0;
                if (qty > this.availableStock) {
                    this.quantity = parseFloat(this.availableStock.toFixed(5));
                }
            }
        },

        clearRow() {
            // Instant client-side clear - no server request
            this.productId = '';
            this.quantity = 1;
            this.notes = '';
            this.expanded = false;
            this.emitTotal();
            // Sync cleared state to Livewire in background (deferred)
            this.syncToLivewire();
        },

        syncToLivewire() {
            // Batch update to Livewire (single request with defer)
            this.$wire.set(`products.${this.index}.product_id`, this.productId, false);
            this.$wire.set(`products.${this.index}.quantity`, this.quantity, false);
            this.$wire.set(`products.${this.index}.notes`, this.notes, false);
        }
    }));
});
