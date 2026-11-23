<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Label stok {{ $stock->product->sku }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 12px;
            max-width: 900px;
            margin: 0 auto 20px;
            padding: 16px;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #ffffff;
        }
        .toolbar h1 { margin: 0; font-size: 16px; }
        .toolbar p { margin: 4px 0 0; font-size: 12px; color: #64748b; }
        .toolbar form { display: flex; align-items: end; gap: 8px; margin-left: auto; }
        .toolbar label { display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 4px; }
        .toolbar input {
            width: 80px;
            padding: 8px 10px;
            font-size: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 16px;
            font-size: 13px;
            font-weight: 600;
            border: 0;
            border-radius: 8px;
            background: #1d4ed8;
            color: #ffffff;
            cursor: pointer;
            text-decoration: none;
        }
        .button.secondary { background: #ffffff; color: #334155; box-shadow: inset 0 0 0 1px #cbd5e1; }
        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            max-width: 900px;
            margin: 0 auto;
            justify-content: center;
        }
        .label {
            width: 72mm;
            min-height: 44mm;
            padding: 3mm;
            border: 1px dashed #94a3b8;
            border-radius: 6px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 1.5mm;
            break-inside: avoid;
        }
        .label .name { margin: 0; font-size: 10px; font-weight: 700; line-height: 1.25; }
        .label .meta { margin: 0; font-size: 8px; color: #475569; }
        .label .bin { margin: 0; font-size: 12px; font-weight: 700; letter-spacing: 0.04em; }
        .label .barcode { text-align: center; }
        .label .barcode svg { width: 100%; height: auto; max-height: 12mm; }
        .label .foot { display: flex; align-items: baseline; justify-content: space-between; gap: 4mm; }
        .label .code { margin: 0; font-family: ui-monospace, Menlo, monospace; font-size: 9px; font-weight: 700; letter-spacing: 0.04em; }
        .label .qty { margin: 0; font-size: 10px; font-weight: 700; white-space: nowrap; }
        @media print {
            body { padding: 0; background: #ffffff; }
            .no-print { display: none !important; }
            .grid { gap: 2mm; justify-content: flex-start; }
            .label { border: 1px solid #e2e8f0; }
            @page { size: A4; margin: 8mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <div>
            <h1>Label lot stok</h1>
            <p>{{ $stock->product->sku }} | {{ $stock->product->name }} | {{ $stock->location->full_code }}</p>
        </div>

        <form method="GET" action="{{ wms_route('labels.stocks', $stock) }}">
            <div>
                <label for="copies">Jumlah salinan</label>
                <input id="copies" name="copies" type="number" min="1" max="50" value="{{ $copies }}">
            </div>
            <button type="submit" class="button secondary">Terapkan</button>
            <button type="button" class="button" onclick="window.print()">Cetak</button>
        </form>
    </div>

    <div class="grid">
        @for ($copy = 1; $copy <= $copies; $copy++)
            <div class="label">
                <div>
                    <p class="name">{{ $stock->product->name }}</p>
                    <p class="meta">SKU {{ $stock->product->sku }}{{ $stock->product->uom ? ' | '.$stock->product->uom : '' }}</p>
                </div>

                <div>
                    <p class="bin">{{ $stock->location->full_code }}</p>
                    <p class="meta">{{ $stock->location->warehouse?->name }}</p>
                    <p class="meta">Batch {{ $stock->batch_number ?: '-' }} | ED {{ $stock->expired_date ? $stock->expired_date->format('d/m/Y') : '-' }}</p>
                </div>

                <div class="barcode">{!! $barcode !!}</div>

                <div class="foot">
                    <p class="code">{{ $code }}</p>
                    <p class="qty">Qty {{ number_format($stock->qty_on_hand, 2, ',', '.') }}</p>
                </div>
            </div>
        @endfor
    </div>
</body>
</html>
