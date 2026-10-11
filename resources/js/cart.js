/**
 * Client cart: add to order without reloading, header mini-cart, and toast messages.
 * Forms keep a normal action/method, so everything still works without JavaScript.
 */
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.headers ?? {}),
        },
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
        throw new Error(firstError ?? data.message ?? window.portalLang?.genericError ?? 'Something went wrong.');
    }

    return data;
}

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.store('toast', {
        items: [],
        show(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, type });
            setTimeout(() => this.dismiss(id), 3500);
        },
        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    });

    Alpine.store('cart', {
        count: 0,
        total: '',
        items: [],
        loaded: false,
        loading: false,
        bump: false,
        summaryUrl: null,

        set(data) {
            this.count = data.count ?? 0;
            this.total = data.total ?? '';
            this.items = data.items ?? [];
            this.loaded = true;
        },

        async refresh() {
            if (!this.summaryUrl || this.loading) {
                return;
            }

            this.loading = true;

            try {
                this.set(await requestJson(this.summaryUrl));
            } finally {
                this.loading = false;
            }
        },

        animate() {
            this.bump = true;
            setTimeout(() => (this.bump = false), 600);
        },
    });

    Alpine.data('addToOrder', (max) => ({
        qty: 1,
        max,
        adding: false,
        added: false,

        async submit(form) {
            if (this.adding) {
                return;
            }

            this.adding = true;

            try {
                const data = await requestJson(form.action, { method: 'POST', body: new FormData(form) });

                Alpine.store('cart').set(data);
                Alpine.store('cart').animate();
                Alpine.store('toast').show(data.message, data.limited ? 'warning' : 'success');

                this.qty = 1;
                this.added = true;
                setTimeout(() => (this.added = false), 1500);
            } catch (error) {
                Alpine.store('toast').show(error.message, 'error');
            } finally {
                this.adding = false;
            }
        },
    }));
});
