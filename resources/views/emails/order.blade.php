<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Received</title>
</head>

<body>
    <h1>Hello, {{ $user->name }}</h1>
    <p>Your order (ID: {{ $order->id }}) has been received.</p>
    <!-- Customize the email content here -->
</body>
</html>
