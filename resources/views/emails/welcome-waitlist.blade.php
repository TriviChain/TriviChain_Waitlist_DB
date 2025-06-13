<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to TriviChain Waitlist</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome to TriviChain!</h1>
            <p>You're now on our exclusive waitlist</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $waitlistMember->name ?? 'there' }}!</h2>
            
            <p>Thank you for joining the TriviChain waitlist! We're thrilled to have you as part of our early community.</p>
            
            <p><strong>What happens next?</strong></p>
            <ul>
                <li>🚀 We'll keep you updated on our development progress</li>
                <li>🎯 You'll get early access when we launch</li>
                <li>💎 Exclusive benefits for waitlist members</li>
                <li>📧 Regular updates about exciting features</li>
            </ul>
            
            <p>We're working hard to bring you something amazing, and we can't wait to share our progress with you!</p>
            
            <div style="text-align: center;">
                <a href="{{ env('APP_URL') }}" class="button">Visit Our Website</a>
            </div>
            
            <p>Stay tuned for updates!</p>
            
            <p>Best regards,<br>
            <strong>The TriviChain Team</strong></p>
        </div>
        
        <div class="footer">
            <p>You're receiving this email because you joined our waitlist at {{ $waitlistMember->joined_at->format('M d, Y') }}.</p>
            <p>TriviChain - Building the Future of Blockchain Gaming</p>
        </div>
    </div>
</body>
</html>