(function () {
    function formatClp(amount) {
        return '$' + Math.round(amount).toLocaleString('es-CL');
    }

    function clampQuantity(value) {
        const parsed = parseInt(value, 10);

        if (Number.isNaN(parsed) || parsed < 1) {
            return 1;
        }

        return Math.min(parsed, 99);
    }

    function recalculateCart() {
        let subtotal = 0;
        let itemCount = 0;

        document.querySelectorAll('[data-cart-row]').forEach(function (row) {
            const unitPrice = parseFloat(row.dataset.unitPrice) || 0;
            const input = row.querySelector('.js-cart-quantity');
            const lineTotalCell = row.querySelector('.js-cart-line-total');

            if (!input || !lineTotalCell) {
                return;
            }

            const quantity = clampQuantity(input.value);
            const lineTotal = Math.round(unitPrice * quantity);

            lineTotalCell.textContent = formatClp(lineTotal);
            subtotal += lineTotal;
            itemCount += quantity;
        });

        document.querySelectorAll('.js-cart-subtotal').forEach(function (el) {
            el.textContent = formatClp(subtotal);
        });

        const countLabel = document.querySelector('.js-cart-item-count');

        if (countLabel) {
            countLabel.textContent = itemCount + ' ítems';
        }
    }

    document.querySelectorAll('.js-cart-quantity-form').forEach(function (form) {
        const input = form.querySelector('.js-cart-quantity');

        if (!input) {
            return;
        }

        const originalQuantity = input.value;

        input.addEventListener('input', recalculateCart);

        input.addEventListener('change', function () {
            const quantity = clampQuantity(input.value);
            input.value = quantity;
            recalculateCart();

            if (String(quantity) !== String(originalQuantity)) {
                form.submit();
            }
        });
    });
})();
