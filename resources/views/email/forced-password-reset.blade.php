<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mandatory Password Reset Required</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7fb; -webkit-font-smoothing: antialiased;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f7fb; padding: 40px 20px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.06); margin: 0 auto; max-width: 600px; width: 100%;">

          <!-- Hero — Red/Amber alert gradient -->
          <tr>
            <td align="center" style="background: linear-gradient(135deg, #dc2626 0%, #f59e0b 100%); padding: 60px 30px;">
                <img src="https://img.icons8.com/?size=160&id=FBbxilYqDXMP&format=png&color=ffffff" alt="Security Alert" width="80" style="display: block; margin-bottom: 24px;">
                <h1 style="color: #ffffff; font-size: 28px; margin: 0; font-weight: 800; letter-spacing: -0.5px;">
                    Action Required: Reset Your Password
                </h1>
                <p style="color: #fef3c7; font-size: 16px; margin: 12px 0 0 0; font-weight: 400;">
                    Your account security requires immediate attention.
                </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding: 40px 40px 10px 40px;">
              <h2 style="color: #1f2937; font-size: 22px; font-weight: 600; margin-top: 0; margin-bottom: 12px;">
                  Hello {{ $name }},
              </h2>
              <p style="color: #4b5563; font-size: 16px; line-height: 1.7; margin: 0 0 16px 0;">
                  Our security team has flagged your account and requires you to reset your password
                  immediately. <strong>You will not be able to access your account until this is done.</strong>
              </p>
            </td>
          </tr>

          <!-- Reason box -->
          <tr>
            <td style="padding: 0 40px 24px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="background-color: #fef2f2; border-radius: 10px; border-left: 4px solid #ef4444; width: 100%;">
                <tr>
                  <td style="padding: 20px 24px;">
                    <p style="color: #7f1d1d; font-size: 14px; font-weight: 700; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">
                        Reason for Mandatory Reset
                    </p>
                    <p style="color: #991b1b; font-size: 15px; margin: 0; line-height: 1.6;">
                        {{ $reason ?? 'Suspicious activity was detected on your account, or an administrative security policy requires a password change.' }}
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- What happens next -->
          <tr>
            <td style="padding: 0 40px 24px 40px;">
              <p style="color: #4b5563; font-size: 15px; line-height: 1.7; margin: 0 0 8px 0;">
                  <strong>What happens if you don't reset your password?</strong><br>
                  Your account will remain locked until the password is changed. All active sessions have been invalidated.
              </p>
              <p style="color: #4b5563; font-size: 15px; line-height: 1.7; margin: 0;">
                  If you did not initiate this and believe your account may be compromised, reset your password immediately and contact our support team.
              </p>
            </td>
          </tr>

          <!-- CTA -->
          <tr>
            <td align="center" style="padding: 10px 40px 40px 40px;">
              <a href="{{ $resetUrl }}"
                 style="background-color: #dc2626; color: #ffffff; text-decoration: none; padding: 18px 44px; border-radius: 50px; font-size: 16px; font-weight: 700; display: inline-block; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);">
                Reset My Password Now
              </a>
            </td>
          </tr>

          <!-- Fallback URL -->
          <tr>
            <td style="padding: 0 40px 32px 40px;">
              <p style="color: #6b7280; font-size: 13px; line-height: 1.6; margin: 0;">
                  If you can't click the button, copy and paste this URL into your browser:<br>
                  <a href="{{ $resetUrl }}" style="color: #dc2626; word-break: break-all; text-decoration: none; font-size: 12px;">{{ $resetUrl }}</a>
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="background-color: #f8fafc; padding: 32px 40px; border-top: 1px solid #f1f5f9;">
              <p style="color: #4b5563; font-size: 14px; margin: 0 0 8px 0;">
                  This reset link will expire in <strong>15 minutes</strong>.
              </p>
              <p style="color: #6b7280; font-size: 13px; margin: 0 0 16px 0;">
                  If you need help, contact us at
                  <a href="mailto:support@yourbrand.com" style="color: #dc2626; text-decoration: none;">support@yourbrand.com</a>
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
