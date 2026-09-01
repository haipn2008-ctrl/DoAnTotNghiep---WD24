<nav aria-label="Điều hướng quản lý hợp đồng" class="inline-flex w-full rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm sm:w-auto">
    <a href="{{ route('admin.contracts.index') }}"
       @class([
           'inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition sm:flex-none',
           'bg-indigo-600 text-white shadow-sm' => request()->routeIs('admin.contracts.index'),
           'text-slate-600 hover:bg-slate-100 hover:text-slate-950' => ! request()->routeIs('admin.contracts.index'),
       ])>
        <i class="bx bx-file text-lg" aria-hidden="true"></i>
        <span>Quản lý hợp đồng</span>
    </a>
    <a href="{{ route('admin.contracts.template') }}"
       @class([
           'inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition sm:flex-none',
           'bg-indigo-600 text-white shadow-sm' => request()->routeIs('admin.contracts.template*'),
           'text-slate-600 hover:bg-slate-100 hover:text-slate-950' => ! request()->routeIs('admin.contracts.template*'),
       ])>
        <i class="bx bx-history text-lg" aria-hidden="true"></i>
        <span>Lịch sử phiên bản</span>
    </a>
</nav>
