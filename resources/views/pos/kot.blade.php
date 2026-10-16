<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kitchen Ticket {{ $kot->kot_number }}</title>
    <style>
        @media print { body { margin: 0; padding: 0; } @page { margin: 0; size: 80mm auto; } }
        body { font-family: 'Courier New', Courier, monospace; width: 80mm; margin: auto; padding: 8px; color: #000; font-size: 13px; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .border-top { border-top: 2px dashed #000; margin-top: 6px; padding-top: 6px; }
        .border-bottom { border-bottom: 2px dashed #000; margin-bottom: 6px; padding-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 4px 0; }
    </style>
</head>
<body onload="window.print()">
    <div class="text-center border-bottom">
        <h2 style="margin: 0; font-size: 18px;">KITCHEN ORDER TICKET</h2>
        <div style="font-size: 16px; font-weight: bold; margin-top: 4px;">{{ $kot->kot_number }}</div>
    </div>
    <div style="margin: 6px 0;">
        <div><strong>Type:</strong> {{ strtoupper($kot->order_type) }}</div>
        @if($kot->table_name)
            <div><strong>Table:</strong> {{ $kot->table_name }}</div>
        @endif
        <div><strong>Time:</strong> {{ $kot->created_at->format('d/m/Y h:i:s A') }}</div>
    </div>
    <table class="border-top border-bottom">
        <thead>
            <tr style="border-bottom: 1px solid #000;">
                <th style="text-align: left;">Item</th>
                <th style="text-align: right; width: 50px;">Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach($kot->items as $item)
            <tr>
                <td class="fw-bold" style="font-size: 14px;">
                    {{ $item->item_name }}
                    @if($item->variant_name)
                        [{{ $item->variant_name }}]
                    @endif
                    @if(!empty($item->addons))
                        <div style="font-size: 11px; font-weight: normal;">+ {{ implode(', ', json_decode($item->addons, true) ?? []) }}</div>
                    @endif
                </td>
                <td style="text-align: right; font-size: 18px; font-weight: bold;">{{ $item->quantity }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($kot->special_instructions)
    <div class="border-top" style="margin-top: 8px;">
        <strong>Special Kitchen Notes:</strong>
        <div>{{ $kot->special_instructions }}</div>
    </div>
    @endif
</body>
</html>
