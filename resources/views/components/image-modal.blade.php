<div id="imagePreviewModal"
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/85 p-3 backdrop-blur-sm sm:p-6"
     role="dialog"
     aria-modal="true"
     aria-labelledby="imagePreviewModalTitle"
     aria-hidden="true">
    <button type="button" data-image-modal-close class="absolute inset-0 cursor-default" aria-label="Đóng cửa sổ xem ảnh"></button>

    <div class="relative z-10 flex max-h-full w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-200 px-4 py-3 sm:px-5">
            <h2 id="imagePreviewModalTitle" class="truncate text-base font-bold text-slate-900">Xem ảnh</h2>
            <button type="button" data-image-modal-close class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-600 transition hover:bg-slate-200 hover:text-slate-900" aria-label="Đóng">&times;</button>
        </div>

        <div class="relative flex min-h-64 flex-1 items-center justify-center overflow-auto bg-slate-100 p-3 sm:p-5">
            <p data-image-modal-loading class="absolute text-sm font-semibold text-slate-500">Đang tải ảnh...</p>
            <img data-image-modal-image src="" alt="" class="relative hidden max-h-[calc(100vh-10rem)] max-w-full rounded-lg object-contain shadow-sm">
            <div data-image-modal-error class="relative hidden rounded-xl border border-rose-200 bg-white px-5 py-4 text-center text-sm font-semibold text-rose-700">
                Không thể tải ảnh. Vui lòng thử lại.
            </div>
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('imagePreviewModal');
            if (!modal) return;

            const image = modal.querySelector('[data-image-modal-image]');
            const title = modal.querySelector('#imagePreviewModalTitle');
            const loading = modal.querySelector('[data-image-modal-loading]');
            const error = modal.querySelector('[data-image-modal-error]');
            let trigger = null;

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
                image.removeAttribute('src');
                image.classList.add('hidden');
                trigger?.focus();
                trigger = null;
            };

            const openModal = (link) => {
                trigger = link;
                title.textContent = link.dataset.imageTitle || link.getAttribute('title') || link.textContent.trim() || 'Xem ảnh';
                image.alt = title.textContent;
                image.classList.add('hidden');
                error.classList.add('hidden');
                loading.classList.remove('hidden');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                image.src = link.href;
            };

            image.addEventListener('load', () => {
                loading.classList.add('hidden');
                image.classList.remove('hidden');
            });
            image.addEventListener('error', () => {
                loading.classList.add('hidden');
                image.classList.add('hidden');
                error.classList.remove('hidden');
            });
            document.addEventListener('click', (event) => {
                const link = event.target.closest('a[data-image-modal]');
                if (!link) return;
                event.preventDefault();
                openModal(link);
            });
            modal.querySelectorAll('[data-image-modal-close]').forEach(button => button.addEventListener('click', closeModal));
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });
        });
    </script>
@endonce
