<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In phụ lục {{ $appendix->code }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 18mm 16mm; }
        @media print { .no-print { display: none !important; } body { background: white !important; } .document { box-shadow: none !important; padding: 0 !important; } }
    </style>
</head>
<body class="bg-slate-100 text-slate-900">
    <div class="no-print sticky top-0 z-10 flex justify-end gap-2 border-b border-slate-200 bg-white p-4">
        <a href="{{ route('admin.contract-appendices.show', $appendix) }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold">Quay lại</a>
        <button type="button" onclick="window.print()" class="rounded-lg bg-indigo-700 px-5 py-2 text-sm font-semibold text-white">In phụ lục</button>
    </div>
    <main class="document mx-auto min-h-[297mm] max-w-[210mm] bg-white px-[16mm] py-[18mm] shadow-lg">
        @include('shared.contract-appendix-document', ['appendix' => $appendix])
    </main>
</body>
</html>
