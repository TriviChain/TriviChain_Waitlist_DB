{{ $emailUpdate->subject }}

Hello {{ $waitlistMember->name ?? 'there' }}!

We hope you're as excited as we are about the progress we're making.

Latest Update:
{{ $emailUpdate->message }}

Thank you for being part of our journey. We can't wait to share more updates with you soon!

Best regards,
The TriviChain Team

---
You're receiving this email because you joined our waitlist.
This is update #{{ $waitlistMember->updates_received + 1 }} we're sending you.
TriviChain - Building the Future of Blockchain Gaming