document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-inventory-selector]').forEach((container) => {
        const checkboxes = Array.from(container.querySelectorAll('[data-inventory-checkbox]'));
        const toggle = container.querySelector('[data-inventory-toggle-all]');
        const toggleLabel = toggle?.querySelector('span');
        const selectionCount = container.querySelector('[data-inventory-selection-count]');

        const updateInventoryToggle = () => {
            const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
            const allSelected = checkboxes.length > 0 && selected === checkboxes.length;

            if (toggleLabel) {
                toggleLabel.textContent = allSelected ? 'Bỏ chọn tất cả' : 'Chọn tất cả';
            }
            if (selectionCount) {
                selectionCount.textContent = `${selected}/${checkboxes.length} mục đã chọn`;
            }
            toggle?.setAttribute('aria-pressed', allSelected ? 'true' : 'false');
        };

        toggle?.addEventListener('click', () => {
            const shouldSelect = checkboxes.some((checkbox) => !checkbox.checked);
            checkboxes.forEach((checkbox) => {
                checkbox.checked = shouldSelect;
            });
            updateInventoryToggle();
        });

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateInventoryToggle));
        updateInventoryToggle();
    });

    document.querySelectorAll('.js-image-preview-input').forEach((input) => {
        const preview = document.getElementById(input.dataset.previewTarget);
        const grid = preview?.querySelector('[data-preview-grid]');
        const count = preview?.querySelector('[data-preview-count]');
        const error = preview?.querySelector('[data-preview-error]');
        const maximum = Number(input.dataset.maxFiles || 15);

        if (!preview || !grid || !window.DataTransfer) {
            return;
        }

        let files = [];
        let objectUrls = [];

        const fileKey = (file) => `${file.name}:${file.size}:${file.lastModified}`;

        const synchronizeInput = () => {
            const transfer = new DataTransfer();
            files.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
        };

        const render = () => {
            objectUrls.forEach((url) => URL.revokeObjectURL(url));
            objectUrls = [];
            grid.replaceChildren();
            preview.classList.toggle('hidden', files.length === 0);

            if (count) {
                count.textContent = `${files.length} ảnh đã chọn`;
            }

            files.forEach((file, index) => {
                const url = URL.createObjectURL(file);
                objectUrls.push(url);

                const card = document.createElement('div');
                card.className = 'group relative w-32 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm';

                const image = document.createElement('img');
                image.src = url;
                image.alt = file.name;
                image.className = 'h-24 w-full object-cover';

                const filename = document.createElement('p');
                filename.className = 'truncate px-2 py-1.5 text-xs text-slate-600';
                filename.title = file.name;
                filename.textContent = file.name;

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-rose-600 text-lg font-bold leading-none text-white opacity-100 shadow transition hover:bg-rose-700 focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-white sm:opacity-0 sm:group-hover:opacity-100';
                remove.setAttribute('aria-label', `Bỏ ảnh ${file.name}`);
                remove.title = 'Bỏ ảnh khỏi danh sách tải lên';
                remove.textContent = '×';
                remove.addEventListener('click', () => {
                    files.splice(index, 1);
                    if (error && files.length <= maximum) {
                        error.classList.add('hidden');
                    }
                    synchronizeInput();
                    render();
                });

                card.append(image, filename, remove);
                grid.append(card);
            });
        };

        input.addEventListener('change', () => {
            const knownKeys = new Set(files.map(fileKey));
            const incoming = Array.from(input.files).filter((file) => !knownKeys.has(fileKey(file)));
            files = [...files, ...incoming];

            if (files.length > maximum) {
                files = files.slice(0, maximum);
                if (error) {
                    error.textContent = `Chỉ được chọn tối đa ${maximum} ảnh.`;
                    error.classList.remove('hidden');
                }
            } else if (error) {
                error.classList.add('hidden');
            }

            synchronizeInput();
            render();
        });

        input.form?.addEventListener('reset', () => {
            files = [];
            synchronizeInput();
            render();
        });

        window.addEventListener('beforeunload', () => {
            objectUrls.forEach((url) => URL.revokeObjectURL(url));
        });
    });
});
