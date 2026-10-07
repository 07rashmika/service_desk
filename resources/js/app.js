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

window.Alpine = Alpine;

Alpine.start();
