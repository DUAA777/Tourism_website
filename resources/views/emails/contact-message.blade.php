<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Form Message</title>
</head>
<body style="margin:0;padding:24px;font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;color:#111827;">
    <div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:16px;padding:32px;">
        <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#ff6b2c;font-weight:700;">Yalla Nemshi Contact Form</p>
        <h1 style="margin:0 0 24px;font-size:28px;line-height:1.2;color:#111827;">New message received</h1>

        <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-bottom:24px;">
            <tr>
                <td style="padding:10px 0;width:140px;font-weight:700;color:#374151;">Name</td>
                <td style="padding:10px 0;color:#111827;">{{ $contactData['name'] }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0;width:140px;font-weight:700;color:#374151;">Email</td>
                <td style="padding:10px 0;color:#111827;">{{ $contactData['email'] }}</td>
            </tr>
            <tr>
                <td style="padding:10px 0;width:140px;font-weight:700;color:#374151;">Subject</td>
                <td style="padding:10px 0;color:#111827;">{{ $contactData['subject'] }}</td>
            </tr>
        </table>

        <div style="padding:20px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;">
            <p style="margin:0 0 10px;font-weight:700;color:#374151;">Message</p>
            <p style="margin:0;white-space:pre-line;line-height:1.7;color:#111827;">{{ $contactData['message'] }}</p>
        </div>
    </div>
</body>
</html>
