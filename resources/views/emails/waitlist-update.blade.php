<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $emailUpdate->subject }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .update-message { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📢 TriviChain Update</h1>
            <p>{{ $emailUpdate->subject }}</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $waitlistMember->name ?? 'there' }}!</h2>
            
            <p>We hope you're as excited as we are about the progress we're making.</p>
            
            <div class="update-message">
                <h3>Latest Update:</h3>
                {!! nl2br(e($emailUpdate->message)) !!}
            </div>
            
            <p>Thank you for being part of our journey. We can't wait to share more updates with you soon!</p>
            
            <p>Best regards,<br>
            <strong>The TriviChain Team</strong></p>
        </div>
        
        <div class="footer">
            <p>You're receiving this email because you joined our waitlist.</p>
            <p>This is update #{{ $waitlistMember->updates_received + 1 }} we're sending you.</p>
            <p>TriviChain - Building the Future of Blockchain Gaming</p>
        </div>
    </div>
</body>
</html>