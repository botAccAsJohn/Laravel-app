<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You left something behind!</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7fb; -webkit-font-smoothing: antialiased;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f7fb; padding: 40px 20px;">
    <tr>
      <td align="center">
        <!-- Main Email Container -->
        <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.06); margin: 0 auto; max-width: 600px; width: 100%;">
          
          <!-- Hero Section with Gradient -->
          <tr>
            <td align="center" style="background-color: #4f46e5; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 60px 30px;">
                <!-- Icon -->
                <img src="https://img.icons8.com/?size=160&id=12666&format=png&color=ffffff" alt="Cart" width="80" style="display: block; margin-bottom: 24px;">
                <!-- Heading -->
                <h1 style="color: #ffffff; font-size: 32px; margin: 0; font-weight: 800; letter-spacing: -0.5px;">Wait! Don't forget...</h1>
                <p style="color: #e0e7ff; font-size: 18px; margin: 12px 0 0 0; font-weight: 400;">You still have items waiting in your cart.</p>
            </td>
          </tr>
          
          <!-- Body Content -->
          <tr>
            <td style="padding: 40px 40px 10px 40px;">
              <h2 style="color: #1f2937; font-size: 22px; font-weight: 600; margin-top: 0; margin-bottom: 12px;">Hi {{ $userName }},</h2>
              <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0;">
                We noticed you left some great items in your shopping cart. They're still there waiting for you, but they might sell out soon!
              </p>
            </td>
          </tr>
          
          <!-- Items List -->
          <tr>
            <td style="padding: 24px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; width: 100%;">
                <tr>
                  <td style="padding: 24px;">
                    <span style="color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; display: block; margin-bottom: 20px;">Your Selection ({{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }})</span>
                    
                    @foreach($cartItems as $productId => $item)
                        @if($productId === '_last_activity_at') @continue @endif
                        @php
                            $model = $cartModels[$productId] ?? null;
                            $image = $model && $model->image_url ? asset('storage/' . $model->image_url) : 'https://via.placeholder.com/100';
                        @endphp
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 16px;">
                          <tr>
                            <td style="width: 80px; vertical-align: top;">
                                <img src="{{ $image }}" alt="{{ $item['name'] }}" width="64" height="64" style="border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0;">
                            </td>
                            <td style="padding-left: 16px; vertical-align: middle;">
                                <div style="color: #0f172a; font-size: 16px; font-weight: 600;">{{ $item['name'] }}</div>
                                <div style="color: #64748b; font-size: 14px;">Qty: {{ $item['quantity'] }} × ${{ number_format($item['price'], 2) }}</div>
                            </td>
                            <td align="right" style="vertical-align: middle;">
                                <span style="color: #0f172a; font-size: 16px; font-weight: 700;">${{ number_format($item['price'] * $item['quantity'], 2) }}</span>
                            </td>
                          </tr>
                        </table>
                        @if(!$loop->last)
                            <div style="height: 1px; background-color: #e2e8f0; margin: 12px 0;"></div>
                        @endif
                    @endforeach
                    
                    <!-- Total -->
                    <div style="height: 2px; background-color: #e2e8f0; margin: 16px 0;"></div>
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td align="left">
                            <span style="color: #0f172a; font-size: 18px; font-weight: 700;">Total</span>
                        </td>
                        <td align="right">
                            <span style="color: #4f46e5; font-size: 22px; font-weight: 800;">${{ number_format($cartTotal, 2) }}</span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          
          <!-- Call to Action -->
          <tr>
            <td align="center" style="padding: 24px 40px 40px 40px;">
              <p style="color: #64748b; font-size: 15px; margin-bottom: 24px;">Ready to complete your purchase?</p>
              <a href="{{ url('/cart') }}" style="background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 18px 40px; border-radius: 50px; font-size: 16px; font-weight: 600; display: inline-block; transition: all 0.2s; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);">
                Return to My Cart
              </a>
            </td>
          </tr>
          
          <!-- Footer section -->
          <tr>
            <td align="center" style="background-color: #f8fafc; padding: 32px 40px; border-top: 1px solid #f1f5f9;">
              <h3 style="color: #0f172a; font-size: 18px; margin-top: 0; margin-bottom: 12px;">Need help?</h3>
              <p style="color: #64748b; font-size: 15px; margin: 0 0 16px 0;">
                If you had trouble checking out or have questions, we're here to help!
              </p>
              <p style="color: #64748b; font-size: 15px; margin: 0;">
                Reply to this email or reach us at <a href="mailto:support@yourbrand.com" style="color: #4f46e5; text-decoration: none; font-weight: 500;">support@yourbrand.com</a>
              </p>
              <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
                <p style="color: #94a3b8; font-size: 13px; margin: 0;">
                  &copy; {{ date('Y') }} YourBrand Inc. All rights reserved.<br>
                  123 Design Avenue, Suite 456, New York, NY 10001
                </p>
              </div>
            </td>
          </tr>
          
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
