import Alpine from 'alpinejs';

/**
 * Keeps a list of chosen files for <x-ui.file-dropzone>, enforcing the count and size
 * limits, and mirrors that list back into the real file input so it submits normally.
 */
Alpine.data('fileDropzone', ({ maxFiles = 5, maxSizeMb = 5 } = {}) => ({
    files: [],
    dragging: false,
    error: '',

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
        this.files.splice(index, 1);
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

window.Alpine = Alpine;

Alpine.start();
