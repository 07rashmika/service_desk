import Alpine from 'alpinejs';
import {
    CategoryScale,
    Chart,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip);

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

/**
 * Draws a vertical hairline at the hovered date so readers can aim at a day, not a 2px line.
 */
const crosshair = {
    id: 'crosshair',
    afterDatasetsDraw(chart) {
        const active = chart.tooltip?.getActiveElements() ?? [];

        if (!active.length) {
            return;
        }

        const { ctx, chartArea } = chart;
        const x = active[0].element.x;

        ctx.save();
        ctx.beginPath();
        ctx.moveTo(x, chartArea.top);
        ctx.lineTo(x, chartArea.bottom);
        ctx.lineWidth = 1;
        ctx.strokeStyle = '#cbd5e1';
        ctx.stroke();
        ctx.restore();
    },
};

/**
 * A multi-series line chart for <x-chart.line>. Series arrive from the server as
 * [{ name, color, data }]; the legend and table view are plain HTML around it.
 */
Alpine.data('lineChart', ({ labels = [], series = [] } = {}) => ({
    chart: null,

    init() {
        const font = { family: getComputedStyle(document.body).fontFamily, size: 12 };

        this.chart = new Chart(this.$refs.canvas, {
            type: 'line',
            data: {
                labels,
                datasets: series.map((line) => ({
                    label: line.name,
                    data: line.data,
                    borderColor: line.color,
                    backgroundColor: line.color,
                    borderWidth: 2,
                    borderCapStyle: 'round',
                    borderJoinStyle: 'round',
                    tension: 0.3,
                    pointRadius: 0,
                    pointHitRadius: 12,
                    pointHoverRadius: 5,
                    pointHoverBorderWidth: 2,
                    pointHoverBorderColor: '#ffffff',
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 400 },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        titleFont: { ...font, weight: '500' },
                        bodyFont: font,
                        boxWidth: 12,
                        boxHeight: 2,
                        boxPadding: 6,
                        callbacks: {
                            label: (item) => ` ${item.formattedValue}  ${item.dataset.label}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { color: '#cbd5e1' },
                        ticks: { color: '#64748b', font, maxTicksLimit: 8, maxRotation: 0 },
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: '#e2e8f0' },
                        ticks: { color: '#64748b', font, precision: 0, maxTicksLimit: 6 },
                    },
                },
            },
            plugins: [crosshair],
        });
    },

    destroy() {
        this.chart?.destroy();
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
