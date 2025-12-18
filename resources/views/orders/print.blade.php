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
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            padding: 15px;
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
                size: A4;
            }
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
        }

        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }

        .no-print button {
            background: #333;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            margin: 0 5px;
        }

        .no-print button:hover {
            background: #555;
        }

        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .logo {
            max-height: 50px;
            max-width: 150px;
        }

        .company-info h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-info p {
            font-size: 11px;
            color: #666;
        }

        .order-info {
            text-align: right;
        }

        .order-info h2 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .info-row {
            margin-bottom: 5px;
            font-size: 11px;
        }

        .info-row strong {
            font-weight: bold;
        }

        .content-section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-box {
            border: 1px solid #000;
            padding: 10px;
        }

        .info-box h3 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        .info-item {
            margin-bottom: 6px;
            font-size: 11px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 80px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }

        .table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .table td {
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .product-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border: 1px solid #ccc;
        }

        .totals {
            margin-top: 15px;
            width: 100%;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #000;
            font-size: 11px;
        }

        .total-row.final {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            margin-top: 10px;
            padding: 8px 0;
            font-size: 14px;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <button onclick="window.print()">Yazdır</button>
            <button onclick="window.close()">Kapat</button>
        </div>

        <div class="header">
            <div class="header-top">
                <div class="company-info">
                    <img src="{{ asset('logo.png') }}" alt="Logo" class="logo">
                    <h1>{{ env('APP_NAME', 'Firma Adı') }}</h1>
                    <p>Sipariş Belgesi</p>
                </div>
                <div class="order-info">
                    <h2>SİPARİŞ</h2>
                    <div class="info-row">
                        <strong>Sipariş No:</strong> {{ $order->order_no }}
                    </div>
                    <div class="info-row">
                        <strong>Tarih:</strong> {{ $order->created_at->format('d.m.Y H:i') }}
                    </div>
                    <div class="info-row">
                        <strong>Durum:</strong> {{ $order->status->label() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="content-section">
            <div class="info-grid">
                <div class="info-box">
                    <h3>Müşteri Bilgileri</h3>
                    <div class="info-item">
                        <span class="info-label">Ad Soyad:</span>
                        <span>{{ $order->customer->full_name ?? '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Telefon:</span>
                        <span>{{ $order->customer->phone ?? '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Şehir:</span>
                        <span>{{ $order->customer->city ?? '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">İlçe:</span>
                        <span>{{ $order->customer->district ?? '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Adres:</span>
                        <span>{{ $order->customer->address ?? '-' }}</span>
                    </div>
                    @if($order->customer->note)
                    <div class="info-item">
                        <span class="info-label">Not:</span>
                        <span>{{ $order->customer->note }}</span>
                    </div>
                    @endif
                </div>

                <div class="info-box">
                    <h3>Sipariş Bilgileri</h3>
                    <div class="info-item">
                        <span class="info-label">Toplam Kalem:</span>
                        <span>{{ $order->orderItems->count() }} ürün</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Toplam Adet:</span>
                        <span>{{ $order->orderItems->sum('quantity') }} adet</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Para Birimi:</span>
                        <span>{{ $order->currency }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-section">
            <h3 class="section-title">Sipariş Kalemleri</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Görsel</th>
                        <th>Ürün Adı</th>
                        <th style="width: 100px;">SKU</th>
                        <th style="width: 60px;" class="text-center">Adet</th>
                        <th style="width: 100px;" class="text-right">Birim Fiyat</th>
                        <th style="width: 100px;" class="text-right">Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->orderItems as $item)
                    <tr>
                        <td class="text-center">
                            @if($item->product && $item->product->default_image_url)
                                <img src="{{ $item->product->default_image_url }}" 
                                     alt="{{ $item->product_name_snapshot ?? 'Ürün' }}" 
                                     class="product-image">
                            @else
                                <span>-</span>
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

        <div class="totals">
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
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 250);
        };
    </script>
</body>
</html>
