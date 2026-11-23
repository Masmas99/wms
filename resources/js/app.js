import Alpine from 'alpinejs';

const themeStorageKey = 'theme';
const themeModes = ['light', 'dark', 'system'];

const prefersDarkScheme = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

// Only the signed-in app shell opts into theming. Guest pages (login, register,
// password reset) stay light even when a dark theme is stored.
const themeIsScopedToApp = () => document.documentElement.dataset.themeScope === 'app';

Alpine.store('theme', {
    mode: 'system',
    isDark: false,
    initialized: false,

    init() {
        if (this.initialized) {
            return;
        }

        this.initialized = true;

        if (!themeIsScopedToApp()) {
            return;
        }

        let stored = null;

        try {
            stored = localStorage.getItem(themeStorageKey);
        } catch (error) {
            stored = null;
        }

        this.mode = themeModes.includes(stored) ? stored : 'system';
        this.apply();

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (this.mode === 'system') {
                this.apply();
            }
        });

        window.addEventListener('storage', (event) => {
            if (event.key !== themeStorageKey) {
                return;
            }

            this.mode = themeModes.includes(event.newValue) ? event.newValue : 'system';
            this.apply();
        });
    },

    set(mode) {
        this.mode = themeModes.includes(mode) ? mode : 'system';

        try {
            localStorage.setItem(themeStorageKey, this.mode);
        } catch (error) {
            // Storage can be unavailable (private mode); keep the in-memory theme.
        }

        this.apply();
    },

    apply() {
        if (!themeIsScopedToApp()) {
            return;
        }

        this.isDark = this.mode === 'dark' || (this.mode === 'system' && prefersDarkScheme());

        document.documentElement.classList.toggle('dark', this.isDark);
        document.documentElement.style.colorScheme = this.isDark ? 'dark' : 'light';
    },
});

Alpine.store('theme').init();

Alpine.store('notifications', {
    unread: 0,
    readIds: [],
    savingAll: false,

    setUnread(count) {
        this.unread = Math.max(0, Number(count) || 0);
    },

    markRead(id) {
        if (this.readIds.includes(id)) {
            return;
        }

        this.readIds.push(id);
        this.setUnread(this.unread - 1);
    },

    markUnread(id) {
        this.readIds = this.readIds.filter((readId) => readId !== id);
        this.setUnread(this.unread + 1);
    },

    async markAllRead(form) {
        if (this.savingAll) {
            return;
        }

        this.savingAll = true;
        this.setUnread(0);
        window.dispatchEvent(new CustomEvent('notifications-read-all'));

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to mark all notifications as read.');
            }

            const data = await response.json();
            this.setUnread(data.unread_count);
        } catch (error) {
            console.error(error);
            window.location.reload();
        } finally {
            this.savingAll = false;
        }
    },
});

Alpine.data('notificationItem', () => ({
    id: null,
    action: null,
    unread: false,
    saving: false,

    init() {
        this.id = this.$el.dataset.id;
        this.action = this.$el.dataset.action;
        this.unread = this.$el.dataset.unread === '1';

        this.handleReadAll = () => {
            this.unread = false;
        };

        this.handleRead = (event) => {
            if (event.detail.id === this.id) {
                this.unread = false;
            }
        };

        this.handleUnread = (event) => {
            if (event.detail.id === this.id) {
                this.unread = true;
            }
        };

        window.addEventListener('notifications-read-all', this.handleReadAll);
        window.addEventListener('notifications-read', this.handleRead);
        window.addEventListener('notifications-unread', this.handleUnread);
    },

    destroy() {
        window.removeEventListener('notifications-read-all', this.handleReadAll);
        window.removeEventListener('notifications-read', this.handleRead);
        window.removeEventListener('notifications-unread', this.handleUnread);
    },

    async markRead() {
        const store = Alpine.store('notifications');

        if (this.saving || !this.unread || store.savingAll) {
            return;
        }

        this.saving = true;
        this.unread = false;
        store.markRead(this.id);
        window.dispatchEvent(new CustomEvent('notifications-read', { detail: { id: this.id } }));

        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this.$refs.form ?? this.$el),
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to mark the notification as read.');
            }

            const data = await response.json();
            store.setUnread(data.unread_count);
        } catch (error) {
            console.error(error);
            this.unread = true;
            store.markUnread(this.id);
            window.dispatchEvent(new CustomEvent('notifications-unread', { detail: { id: this.id } }));
        } finally {
            this.saving = false;
        }
    },
}));

window.Alpine = Alpine;

Alpine.start();
