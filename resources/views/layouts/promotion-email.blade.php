<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Promotion Offer</title>
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
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .header {
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .header p {
            margin: 8px 0 0;
            font-size: 16px;
            font-weight: 500;
            opacity: 0.95;
        }

        .content {
            padding: 40px;
        }

        .greeting {
            font-size: 16px;
            color: #1e293b;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .discount-section {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #fcd34d;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }

        .discount-label {
            font-size: 13px;
            text-transform: uppercase;
            font-weight: 700;
            color: #92400e;
            display: block;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .discount-percentage {
            font-size: 48px;
            font-weight: 800;
            color: #d97706;
            margin: 0;
            line-height: 1;
        }

        .discount-text {
            font-size: 14px;
            color: #b45309;
            margin-top: 5px;
        }

        .code-section {
            background: #f8fafc;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .code-label {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            display: block;
            margin-bottom: 10px;
        }

        .discount-code {
            background: #ffffff;
            border: 2px dashed #d97706;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 20px;
            font-weight: 700;
            text-align: center;
            color: #d97706;
            letter-spacing: 2px;
        }

        .code-hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 8px;
            text-align: center;
        }

        .audience-section {
            background: #f0fdf4;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #22c55e;
            margin-bottom: 30px;
        }

        .audience-label {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            color: #166534;
            display: block;
            margin-bottom: 5px;
        }

        .audience-value {
            font-size: 16px;
            font-weight: 600;
            color: #16a34a;
        }

        .details-box {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }

        .details-text {
            font-size: 14px;
            color: #475569;
            margin: 0;
            line-height: 1.6;
        }

        .details-text strong {
            color: #1e293b;
            font-weight: 600;
        }

        .cta-button {
            display: inline-block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s ease;
            border: none;
            box-sizing: border-box;
        }

        .cta-button:hover {
            transform: translateY(-2px);
        }

        .footer {
            padding: 30px;
            text-align: center;
            background: #f8fafc;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            margin: 8px 0;
        }

        .footer-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 15px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🎉 Special Offer</h1>
            <p>Exclusive promotion just for you!</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Greeting -->
            <div class="greeting">
                <p>Hello,</p>
                <p>We're excited to offer you an exclusive discount! Take advantage of this limited-time promotion and
                    save on your next purchase.</p>
            </div>

            <!-- Discount Highlight -->
            <div class="discount-section">
                <span class="discount-label">Your Savings</span>
                <p class="discount-percentage">{{ $percentage }}%</p>
                <p class="discount-text">Off Your Purchase</p>
            </div>

            <!-- Discount Code -->
            <div class="code-section">
                <span class="code-label">Use This Code</span>
                <div class="discount-code">{{ $code }}</div>
                <p class="code-hint">Copy and paste this code at checkout</p>
            </div>

            <!-- Audience Info -->
            <div class="audience-section">
                <span class="audience-label">This Offer is For</span>
                <div class="audience-value">{{ $audience }}</div>
            </div>

            <!-- Details -->
            <div class="details-box">
                <p class="details-text">
                    <strong>Don't miss out!</strong> This exclusive promotion is available for a limited time only.
                    Shop now and enjoy {{ $percentage }}% off with code <strong>{{ $code }}</strong>.
                </p>
            </div>

            <!-- CTA Button -->
            <a href="{{ config('app.url') }}/shop" class="cta-button">Shop Now</a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>We appreciate your business!</p>
            <div class="footer-divider"></div>
            <p>If you have any questions, please contact our support team.</p>
            <p style="margin-top: 10px; font-size: 11px; color: #94a3b8;">
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>

</html>