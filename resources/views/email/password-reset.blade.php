<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('reset_subject', [], $locale ?? 'en') }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7fb; -webkit-font-smoothing: antialiased;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f7fb; padding: 40px 20px;">
    <tr>
      <td align="center">
        <!-- Main Email Container -->
        <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.06); margin: 0 auto; max-width: 600px; width: 100%;">

          <!-- Hero Section with Gradient -->
          <tr>
            <td align="center" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 60px 30px;">
                <!-- Lock Icon -->
                <img src="https://img.icons8.com/?size=160&id=VsK2OvtWxTM6&format=png&color=ffffff" alt="Lock" width="80" style="display: block; margin-bottom: 24px;">
                <!-- Heading -->
                <h1 style="color: #ffffff; font-size: 30px; margin: 0; font-weight: 800; letter-spacing: -0.5px;">
                    {{ __('reset_subject', [], $locale ?? 'en') }}
                </h1>
                <p style="color: #d1fae5; font-size: 16px; margin: 12px 0 0 0; font-weight: 400;">
                    Secure your account in just a few clicks.
                </p>
            </td>
          </tr>

          <!-- Body Content -->
          <tr>
            <td style="padding: 40px 40px 10px 40px;">
              <h2 style="color: #1f2937; font-size: 22px; font-weight: 600; margin-top: 0; margin-bottom: 12px;">
                  {{ __('reset_greeting', ['name' => $name], $locale ?? 'en') }}
              </h2>
              <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 16px 0;">
                  {{ __('reset_intro', [], $locale ?? 'en') }}
              </p>
            </td>
          </tr>

          <!-- Expiry Warning Box -->
          <tr>
            <td style="padding: 0 40px 24px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="background-color: #fff7ed; border-radius: 10px; border-left: 4px solid #f59e0b; width: 100%;">
                <tr>
                  <td style="padding: 16px 20px;">
                    <p style="color: #92400e; font-size: 14px; margin: 0; font-weight: 600;">
                        ⏱ {{ __('reset_expiry', [], $locale ?? 'en') }}
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Call to Action -->
          <tr>
            <td align="center" style="padding: 10px 40px 40px 40px;">
              <a href="{{ $resetUrl }}"
                 style="background-color: #059669; color: #ffffff; text-decoration: none; padding: 18px 44px; border-radius: 50px; font-size: 16px; font-weight: 600; display: inline-block; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);">
                {{ __('reset_action', [], $locale ?? 'en') }}
              </a>
            </td>
          </tr>

          <!-- Fallback URL -->
          <tr>
            <td style="padding: 0 40px 32px 40px;">
              <p style="color: #6b7280; font-size: 13px; line-height: 1.6; margin: 0;">
                  {{ __('reset_no_click', [], $locale ?? 'en') }}<br>
                  <a href="{{ $resetUrl }}" style="color: #059669; word-break: break-all; text-decoration: none; font-size: 12px;">
                      {{ $resetUrl }}
                  </a>
              </p>
            </td>
          </tr>

          <!-- Footer Notice -->
          <tr>
            <td align="center" style="background-color: #f8fafc; padding: 32px 40px; border-top: 1px solid #f1f5f9;">
              <p style="color: #4b5563; font-size: 14px; margin: 0 0 16px 0;">
                  {{ __('reset_footer', [], $locale ?? 'en') }}
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
