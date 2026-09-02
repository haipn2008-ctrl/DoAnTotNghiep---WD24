<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $contract->signed_at ? 'Hợp đồng' : ($contract->status === \App\Models\Contract::STATUS_PENDING_SIGNATURE ? 'Bản chờ ký' : 'Bản dự thảo') }} {{ $contract->contract_code }}</title>
    <style>
        @page{size:A4;margin:16mm}
        *{box-sizing:border-box}
        body{margin:0;background:#eef2f7}
        .print-toolbar{position:sticky;top:0;display:flex;justify-content:flex-end;gap:8px;padding:12px 20px;background:#fff;border-bottom:1px solid #ddd}
        .print-toolbar button{border:0;border-radius:7px;padding:9px 16px;font-weight:700;cursor:pointer}
        .print-toolbar .close{background:#e5e7eb;color:#111827}
        .print-toolbar .print{background:#166534;color:#fff}
        .print-page{width:210mm;min-height:297mm;margin:20px auto;padding:16mm;background:#fff;box-shadow:0 8px 28px rgba(15,23,42,.12)}
        @media print{body{background:#fff}.print-toolbar{display:none}.print-page{width:auto;min-height:auto;margin:0;padding:0;box-shadow:none}}
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" class="close" onclick="window.close()">Đóng</button>
        <button type="button" class="print" onclick="window.print()">In hợp đồng</button>
    </div>
    <main class="print-page">
        @if($contract->contract_content_snapshotted_at && filled($contract->contract_content))
            {!! $contract->contract_content !!}
        @else
            @include('admin.contracts.contract-template-content')
        @endif
    </main>
    <script>
        window.addEventListener('load', () => {
            if (window.self === window.top) {
                setTimeout(() => window.print(), 300);
            }
        });
    </script>
</body>
</html>
