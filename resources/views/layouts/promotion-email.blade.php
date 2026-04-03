<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Promotion Preview</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6;">
    <h2>{{ $subject }}</h2>

    <p>Hello {{ $audience }},</p>

    <p>
        We are excited to offer you an exclusive discount of <strong>{{ $percentage }}%</strong>.
    </p>

    <p>
        Use promo code <strong>{{ $code }}</strong> at checkout to claim your offer.
    </p>

    <p>
        This promotion is available for a limited time only.
    </p>

    <p>Best regards,<br>Marketing Team</p>
</body>

</html>