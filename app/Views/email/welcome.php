<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Provisioned</title>
</head>
<body style="font-family: sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px;">
        <h2 style="color: #333;">System Access Granted</h2>
        <hr style="border: 0; border-top: 1px solid #eee;">
        <p>Hello <strong><?= esc($name) ?></strong>,</p>
        <p>Your profile execution matrix has been fully synchronized with our central node.</p>
        <p><strong>Registered Node Identifier:</strong> <?= esc($email) ?></p>
        <br>
        <p style="font-size: 12px; color: #777;">This is an automated operational transmission from your CI4 cluster.</p>
    </div>
</body>
</html>