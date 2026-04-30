<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Alert</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f7f9;
            color: #334155;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .header {
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .content {
            padding: 40px;
        }
        .alert-box {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        .alert-box p {
            margin: 0;
            font-weight: 600;
            color: #991b1b;
        }
        .product-card {
            display: flex;
            align-items: center;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .product-image {
            width: 80px;
            height: 80px;
            background: #fff;
            border-radius: 8px;
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-contain: cover;
        }
        .product-info h2 {
            margin: 0;
            font-size: 18px;
            color: #1e293b;
        }
        .product-info p {
            margin: 4px 0 0;
            font-size: 14px;
            color: #64748b;
        }
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }
        .stat-item {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
        }
        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            color: #94a3b8;
            display: block;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
        }
        .stat-value.danger {
            color: #dc2626;
        }
        .footer {
            padding: 30px;
            text-align: center;
            background: #f8fafc;
            font-size: 12px;
            color: #94a3b8;
        }
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background-color: #0f172a;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            margin-top: 10px;
            transition: transform 0.2s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Stock Depletion Alert</h1>
        </div>
        <div class="content">
            <div class="alert-box">
                <p>Attention Admin: A product is running critically low on stock.</p>
            </div>

            <div class="product-card">
                <div class="product-image">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                </div>
                <div class="product-info">
                    <h2>{{ $product->name }}</h2>
                    <p>SKU: {{ $product->slug }}</p>
                </div>
            </div>

            <div class="stats">
                <div class="stat-item">
                    <span class="stat-label">Current Stock</span>
                    <span class="stat-value danger">{{ $product->quantity }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Price</span>
                    <span class="stat-value">${{ number_format($product->price ?? $product->discount_price, 2) }}</span>
                </div>
            </div>

            <p style="text-align: center;">
                Please restock this item immediately to avoid loss of sales.
            </p>

            <div style="text-align: center; margin-top: 20px;">
                <a href="{{ config('app.url') . '/products/' . $product->slug . '/edit' }}" class="btn">Update Inventory</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }} Admin Dashboard. This is an automated notification.
        </div>
    </div>
</body>
</html>
