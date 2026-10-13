<?php
$text = strtolower('Enter your UPI PIN to receive refund of Rs 4999 into your account.');
$phrases = ['enter upi pin to receive', 'scan to receive', 'pin to accept',
            'cashback qr', 'refund qr', 'enter pin to claim', 'approved refund',
            'army official verification', 'pin to deposit'];
foreach ($phrases as $p) {
    echo (str_contains($text, $p) ? 'MATCH: ' : 'NO:    ') . $p . "\n";
}
echo "\nText: " . $text . "\n";
echo "\nDoes 'upi pin to receive' appear? " . (str_contains($text, 'upi pin to receive') ? 'YES' : 'NO') . "\n";
