<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Yazdır - {{ $order->order_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 20px;
            background: #fff;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            @page {
                margin: 1cm;
            }
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }

        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo {
            max-height: 60px;
            max-width: 200px;
            object-fit: contain;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-section h2 {
            font-size: 16px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
        }

        .info-value {
            flex: 1;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .product-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #333;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .total-row.final {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .no-print {
            text-align: center;
            margin: 20px 0;
        }

        .no-print button {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 4px;
        }

        .no-print button:hover {
            background: #d97706;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <button onclick="window.print()">Yazdır</button>
            <button onclick="window.close()" style="background: #6b7280; margin-left: 10px;">Kapat</button>
        </div>

        <div class="header">
            <div class="header-top">
                <img src="{{ asset('logo.png') }}" alt="Logo" class="logo">
                <div>
                    <h1 style="margin: 0 0 5px 0;">{{ env('APP_NAME', 'Firma Adı') }}</h1>
                    <h2 style="font-size: 18px; margin: 0; color: #666;">SİPARİŞ DETAYI</h2>
                </div>
            </div>
            <div class="header-info">
                <div>
                    <div class="info-row">
                        <span class="info-label">Sipariş No:</span>
                        <span class="info-value"><strong>{{ $order->order_no }}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tarih:</span>
                        <span class="info-value">{{ $order->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Durum:</span>
                        <span class="info-value">{{ $order->status->label() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h2>Müşteri Bilgileri</h2>
            <div class="info-row">
                <span class="info-label">Ad Soyad:</span>
                <span class="info-value">{{ $order->customer->full_name ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Telefon:</span>
                <span class="info-value">{{ $order->customer->phone ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Şehir:</span>
                <span class="info-value">{{ $order->customer->city ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">İlçe:</span>
                <span class="info-value">{{ $order->customer->district ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Adres:</span>
                <span class="info-value">{{ $order->customer->address ?? '-' }}</span>
            </div>
            @if($order->customer->note)
            <div class="info-row">
                <span class="info-label">Not:</span>
                <span class="info-value">{{ $order->customer->note }}</span>
            </div>
            @endif
        </div>

        <div class="info-section">
            <h2>Sipariş Kalemleri</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Görsel</th>
                        <th>Ürün Adı</th>
                        <th>SKU</th>
                        <th class="text-center">Adet</th>
                        <th class="text-right">Birim Fiyat</th>
                        <th class="text-right">Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->orderItems as $item)
                    <tr>
                        <td class="text-center">
                            @if($item->product && $item->product->default_image_url)
                                <img src="{{ $item->product->default_image_url }}" alt="{{ $item->product_name_snapshot ?? 'Ürün' }}" class="product-thumbnail">
                            @else
                                <span style="color: #999;">-</span>
                            @endif
                        </td>
                        <td>{{ $item->product_name_snapshot ?? '-' }}</td>
                        <td>{{ $item->sku_snapshot ?? '-' }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unit_price_snapshot ?? 0, 2) }} {{ $order->currency }}</td>
                        <td class="text-right">{{ number_format($item->line_total ?? 0, 2) }} {{ $order->currency }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Sipariş kalemi bulunamadı.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <div class="total-row">
                <span>Ara Toplam:</span>
                <span>{{ number_format($order->subtotal ?? 0, 2) }} {{ $order->currency }}</span>
            </div>
            <div class="total-row final">
                <span>GENEL TOPLAM:</span>
                <span>{{ number_format($order->total ?? 0, 2) }} {{ $order->currency }}</span>
            </div>
        </div>

        <div class="footer">
            <p>Bu belge {{ now()->format('d.m.Y H:i') }} tarihinde oluşturulmuştur.</p>
            @if(env('APP_URL'))
            <p style="margin-top: 5px;"><strong>Web:</strong> {{ env('APP_URL') }}</p>
            @endif
        </div>
    </div>

    <script>
        // Sayfa yüklendiğinde otomatik print dialog aç
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 250);
        };
    </script>
</body>
</html>
