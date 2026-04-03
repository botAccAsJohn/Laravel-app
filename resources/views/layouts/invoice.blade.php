<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
            /* Removes default dompdf margins which often cause extra pages */
        }

        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.4;
            margin: 0 auto;
            max-width: 800px;
            padding: 30px;
            /* Internal padding instead of page margin */
            font-size: 14px;

            /* Constraints to force exactly one page */
            height: 1122px;
            /* A4 height at 96PPI */
            max-height: 100%;
            overflow: hidden;
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
        }

        /* Helpers */
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .font-bold {
            font-weight: bold;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .w-half {
            width: 50%;
        }

        .w-third {
            width: 33.333%;
        }

        .mb-20 {
            margin-bottom: 20px;
        }

        .mb-40 {
            margin-bottom: 40px;
        }

        .p-10 {
            padding: 10px;
        }

        /* Typography */
        h1 {
            font-size: 42px;
            letter-spacing: 2px;
            color: #444;
            margin: 0;
        }

        /* Items Table */
        .items-table {
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .items-table th {
            text-align: left;
            background-color: #f8fafc;
            color: #64748b;
            font-weight: normal;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            padding: 12px;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            height: 25px;
            /* For empty rows */
        }

        .items-table th:last-child,
        .items-table td:last-child {
            border-right: none;
        }

        /* Bottom Section */
        .tax-note {
            font-size: 11px;
            color: #64748b;
            line-height: 1.3;
        }

        .summary-label {
            padding-right: 15px;
            padding-bottom: 10px;
            text-align: right;
            width: 60%;
        }

        .summary-value {
            border-bottom: 1px dotted #ccc;
            width: 40%;
        }

        .footer-logo-text {
            margin-top: 40px;
        }

        .footer-logo-text span.powered {
            font-size: 10px;
            color: #64748b;
            display: block;
            margin-bottom: 2px;
        }

        .footer-logo-text span.general {
            color: #334155;
            font-size: 18px;
            letter-spacing: -0.5px;
        }

        .footer-logo-text span.blue {
            color: #3b82f6;
            font-size: 18px;
            letter-spacing: -0.5px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <table class="mb-40">
        <tr>
            <td class="w-half">
                <h1>INVOICE</h1>
            </td>
            <td class="w-half text-right" style="padding-top: 15px;">
                <table style="width: auto; float: right;">
                    <tr>
                        <td style="padding-right: 15px; padding-bottom: 8px;">Date:</td>
                        <td style="border-bottom: 1px solid #eee; width: 120px;">{{ $invoice['date'] ?? date('Y-m-d') }}</td>
                    </tr>
                    <tr>
                        <td style="padding-right: 15px;">Invoice #:</td>
                        <td style="border-bottom: 1px solid #eee; width: 120px;">{{ $invoice['invoice_number'] ?? 'N/A' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Addresses -->
    <table class="mb-40">
        <tr>
            <td class="w-half">
                <div class="font-bold" style="margin-bottom: 5px;">Bill To:</div>
                <div style="line-height: 1.5;">
                    {{ $invoice['bill_to']['name'] ?? 'Client Name' }}<br>
                    {{ $invoice['bill_to']['address_1'] ?? 'Address Line 1' }}<br>
                    @if(!empty($invoice['bill_to']['address_2']))
                    {{ $invoice['bill_to']['address_2'] }}<br>
                    @endif
                    {{ $invoice['bill_to']['city_state_zip'] ?? 'City, State, Zip' }}<br>
                    {{ $invoice['bill_to']['phone'] ?? 'Phone' }}
                </div>
            </td>
            <td class="w-half">
                <div class="font-bold" style="margin-bottom: 5px;">Ship To:</div>
                <div style="line-height: 1.5;">
                    {{ $invoice['ship_to']['name'] ?? 'Client Name' }}<br>
                    {{ $invoice['ship_to']['address_1'] ?? 'Address Line 1' }}<br>
                    @if(!empty($invoice['ship_to']['address_2']))
                    {{ $invoice['ship_to']['address_2'] }}<br>
                    @endif
                    {{ $invoice['ship_to']['city_state_zip'] ?? 'City, State, Zip' }}<br>
                    {{ $invoice['ship_to']['phone'] ?? 'Phone' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <tr>
            <th>Description</th>
            <th class="text-center" style="width: 100px;">Quantity</th>
            <th class="text-center" style="width: 120px;">Unit Price</th>
            <th class="text-center" style="width: 120px;">Amount</th>
        </tr>

        <!-- Render Actual Items -->
        @foreach($invoice['items'] ?? [] as $item)
        <tr>
            <td>{{ $item['description'] ?? 'N/A' }}</td>
            <td class="text-center">{{ $item['quantity'] ?? 0 }}</td>
            <td class="text-center">${{ number_format($item['unit_price'] ?? 0, 2) }}</td>
            <td class="text-center">${{ number_format(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), 2) }}</td>
        </tr>
        @endforeach

        <!-- Generate padded blank rows to match the image structure up to 10 rows -->
        @php
        $itemCount = count($invoice['items'] ?? []);
        $padRows = max(0, 10 - $itemCount);
        @endphp
        @for($i = 0; $i < $padRows; $i++)
            <tr>
            <td></td>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="text-center"></td>
            </tr>
            @endfor
    </table>

    <!-- Bottom Section -->
    <table>
        <tr>
            <!-- Left Info -->
            <td class="w-third" style="padding-right: 20px;">
                <div class="tax-note">
                    Please enter tax rate in a decimal format.<br>
                    Example: 0.10 for 10% or 0.05 for 5%.
                </div>
                <!-- Logo mimic -->
                <div class="footer-logo-text">
                    <span class="powered">powered by</span>
                    <span class="general font-bold">General</span><span class="blue font-bold">Blue</span>
                </div>
            </td>

            <!-- Middle Info -->
            <td class="w-third">
                <table>
                    <tr>
                        <td class="summary-label">Tax Rate:</td>
                        <td class="summary-value">{{ isset($invoice['tax_rate']) ? ($invoice['tax_rate'] * 100) . '%' : '0%' }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label">Sales Tax:</td>
                        <td class="summary-value">${{ number_format($invoice['sales_tax'] ?? 0, 2) }}</td>
                    </tr>
                </table>
            </td>

            <!-- Right Info -->
            <td class="w-third text-right">
                <table>
                    <tr>
                        <td class="summary-label">Subtotal:</td>
                        <td class="summary-value">${{ number_format($invoice['subtotal'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label">Shipping Charges:</td>
                        <td class="summary-value">${{ number_format($invoice['shipping_charges'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label font-bold uppercase">TOTAL:</td>
                        <td class="summary-value font-bold" style="border-bottom: 2px solid #ccc;">
                            ${{ number_format($invoice['total'] ?? 0, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>