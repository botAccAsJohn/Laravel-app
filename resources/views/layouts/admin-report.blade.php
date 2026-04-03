<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Report - {{ ucfirst($type) }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #444;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #2c3e50;
        }
        .header p {
            margin: 5px 0 0;
            color: #7f8c8d;
        }
        .summary-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .summary-box h3 {
            margin-top: 0;
            color: #2980b9;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table th, table td {
            text-align: left;
            padding: 10px;
            border: 1px solid #eee;
        }
        table th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
        }
        table tr:nth-child(even) {
            background-color: #fcfcfc;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #95a5a6;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            background-color: #e67e22;
            color: white;
        }
        .price {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Admin Report: {{ ucfirst($type) }}</h1>
        <p>Generated on {{ $generatedAt }}</p>
    </div>

    @if($type === 'sales')
        <div class="summary-box">
            <h3>Executive Summary</h3>
            <p>Total Revenue: <span class="price">₹{{ number_format($data['total_revenue'], 2) }}</span></p>
            <p>Total Orders (Delivered): <strong>{{ $data['order_count'] }}</strong></p>
        </div>

        <h3>Top 5 Selling Products</h3>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity Sold</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['top_products'] as $product)
                    <tr>
                        <td>{{ $product['name'] }}</td>
                        <td>{{ $product['qty_sold'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($type === 'inventory')
        <div class="summary-box">
            <h3>Inventory Status</h3>
            <p>Total Inventory Value: <span class="price">₹{{ number_format($data['total_value'], 2) }}</span></p>
            <p>Out of Stock Items: <strong style="color: #c0392b;">{{ $data['out_of_stock'] }}</strong></p>
            <p>Low Stock Alerts: <strong>{{ count($data['low_stock_items']) }}</strong></p>
        </div>

        <h3>Low Stock Items (1-5 units)</h3>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Remaining Stock</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['low_stock_items'] as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['stock'] }} units</td>
                    </tr>
                @endforeach
                @if(count($data['low_stock_items']) === 0)
                    <tr>
                        <td colspan="2" style="text-align: center;">No low stock items found.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    @if($type === 'customers')
        <div class="summary-box">
            <h3>Customer Metrics</h3>
            <p>New Registrations (Last 30 Days): <strong>{{ $data['new_registrations'] }}</strong></p>
            <p>Inactive Users (No order in 90 Days): <strong>{{ $data['inactive_users'] }}</strong></p>
        </div>

        <h3>Top 5 High-Value Customers</h3>
        <table>
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Total Lifetime Spend</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['top_buyers'] as $buyer)
                    <tr>
                        <td>{{ $buyer['name'] }}</td>
                        <td><span class="price">₹{{ number_format($buyer['spent'], 2) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Confidential Admin Report - Autogenerated by {{ config('app.name') }}
    </div>

</body>
</html>
