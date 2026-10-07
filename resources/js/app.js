import Alpine from 'alpinejs';

/**
 * Keeps a list of chosen files for <x-ui.file-dropzone>, enforcing the count and size
 * limits, and mirrors that list back into the real file input so it submits normally.
 */
Alpine.data('fileDropzone', ({ maxFiles = 5, maxSizeMb = 5 } = {}) => ({
    files: [],
    dragging: false,
    error: '',
    previewUrls: new WeakMap(),

    isImage(file) {
        return file.type.startsWith('image/');
    },

    /**
     * A temporary browser URL so a chosen image can be shown before it's uploaded.
     */
    previewUrl(file) {
        if (!this.previewUrls.has(file)) {
            this.previewUrls.set(file, URL.createObjectURL(file));
        }

        return this.previewUrls.get(file);
    },

    add(fileList) {
        this.error = '';

        for (const file of Array.from(fileList)) {
            const isDuplicate = this.files.some(
                (existing) =>
                    existing.name === file.name &&
                    existing.size === file.size &&
                    existing.lastModified === file.lastModified,
            );

            if (isDuplicate) {
                continue;
            }

            if (file.size > maxSizeMb * 1024 * 1024) {
                this.error = `${file.name} is larger than ${maxSizeMb} MB.`;
                continue;
            }

            if (this.files.length >= maxFiles) {
                this.error = `You can attach up to ${maxFiles} files.`;
                break;
            }

            this.files.push(file);
        }

        this.sync();
    },

    remove(index) {
        const [file] = this.files.splice(index, 1);

        if (this.previewUrls.has(file)) {
            URL.revokeObjectURL(this.previewUrls.get(file));
            this.previewUrls.delete(file);
        }

        this.sync();
    },

    sync() {
        const transfer = new DataTransfer();
        this.files.forEach((file) => transfer.items.add(file));
        this.$refs.input.files = transfer.files;
    },

    formatSize(bytes) {
        if (bytes < 1024) {
            return `${bytes} B`;
        }

        if (bytes < 1024 * 1024) {
            return `${Math.round(bytes / 1024)} KB`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    },
}));

/**
 * Full-screen viewer for <x-ui.image-viewer>. It is opened with the element that was
 * clicked, and lets the user browse every image that shares its data-image-preview group.
 */
Alpine.data('imageViewer', () => ({
    open: false,
    items: [],
    index: 0,

    get current() {
        return this.items[this.index] ?? null;
    },

    show(trigger) {
        const group = trigger.dataset.imagePreview;
        const elements = [...document.querySelectorAll('[data-image-preview]')].filter(
            (element) => element.dataset.imagePreview === group,
        );

        this.items = elements.map((element) => ({
            src: element.dataset.src,
            name: element.dataset.name,
            meta: element.dataset.meta ?? '',
            download: element.dataset.download ?? null,
        }));
        this.index = Math.max(0, elements.indexOf(trigger));
        this.open = true;
        document.body.classList.add('overflow-hidden');
    },

    close() {
        this.open = false;
        document.body.classList.remove('overflow-hidden');
    },

    next() {
        this.index = (this.index + 1) % this.items.length;
    },

    previous() {
        this.index = (this.index - 1 + this.items.length) % this.items.length;
    },
}));

/**
 * Icon chip colours for notifications, keyed by the palette name the server sends.
 */
const notificationColors = {
    slate: 'bg-slate-100 text-slate-600',
    blue: 'bg-blue-50 text-blue-600',
    violet: 'bg-violet-50 text-violet-600',
    amber: 'bg-amber-50 text-amber-600',
    orange: 'bg-orange-50 text-orange-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    red: 'bg-red-50 text-red-600',
    primary: 'bg-primary-50 text-primary-600',
};

/**
 * Short-lived pop-ups in the corner, used when a notification arrives live.
 */
Alpine.store('toasts', {
    items: [],

    push(toast) {
        const id = `${Date.now()}-${Math.random()}`;
        this.items.push({ ...toast, id });
        setTimeout(() => this.remove(id), 7000);
    },

    remove(id) {
        this.items = this.items.filter((toast) => toast.id !== id);
    },
});

/**
 * The bell in the top bar. It starts with the server-rendered count and list, then
 * listens on the user's private Reverb channel so new notifications appear instantly.
 */
Alpine.data('notificationBell', ({ count = 0, items = [], userId = null, limit = 6 } = {}) => ({
    open: false,
    count,
    items,

    init() {
        if (!window.Echo || !userId) {
            return;
        }

        window.Echo.private(`App.Models.User.${userId}`).notification((notification) => {
            this.count++;
            this.items = [
                {
                    id: notification.id,
                    message: notification.message,
                    icon: notification.icon,
                    color: notification.color,
                    time: 'Just now',
                    read: false,
                    url: notification.open_url,
                },
                ...this.items,
            ].slice(0, limit);

            Alpine.store('toasts').push({
                message: notification.message,
                icon: notification.icon,
                color: notification.color,
                url: notification.open_url,
            });
        });
    },

    colorClasses(color) {
        return notificationColors[color] ?? notificationColors.slate;
    },
}));

window.notificationColors = notificationColors;
window.Alpine = Alpine;

Alpine.start();

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
