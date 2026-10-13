<?php
require_once "C:/xampp/htdocs/AntiScam/app/Support/Autoloader.php";
App\Support\Autoloader::register();

$svc = new App\Services\Analysis\UrlAnalysisService();

// Test 1: sample phishing APK URL from task description
$r1 = $svc->analyze("http://192.168.1.5/login/verify-kyc.apk", "url");
echo "=== Test 1: phishing APK URL ===\n";
echo "Risk:     " . $r1["risk_level"] . "\n";
echo "Score:    " . $r1["score"] . "\n";
echo "Findings: " . count($r1["findings"]) . "\n";
echo "Tokens:   " . count($r1["url_tokens"] ?? []) . "\n\n";

// Test 2: malformed / empty URL
$r2 = $svc->analyze("   ", "url");
echo "=== Test 2: empty input ===\n";
echo "Risk:     " . $r2["risk_level"] . "\n";
echo "Score:    " . $r2["score"] . "\n";
echo "Findings: " . count($r2["findings"]) . "\n\n";

// Test 3: SBI KYC SMS
$sms = "Dear SBI user, your YONO account has been suspended. Update KYC at http://sbi-kyc-update-portal.in/login within 2 hours.";
$r3 = $svc->analyze($sms, "sms");
echo "=== Test 3: SBI KYC SMS ===\n";
echo "Risk:     " . $r3["risk_level"] . "\n";
echo "Score:    " . $r3["score"] . "\n";
echo "Findings: " . count($r3["findings"]) . "\n\n";

// Test 4: UPI reverse trick
$upi = "Enter your UPI PIN to receive refund of Rs 4999 into your account.";
$r4 = $svc->analyze($upi, "upi");
echo "=== Test 4: UPI reverse trick ===\n";
echo "Risk:     " . $r4["risk_level"] . "\n";
echo "Score:    " . $r4["score"] . "\n";
echo "Findings: " . count($r4["findings"]) . "\n\n";

// Test 5: Clean HTTPS URL (should score low)
$r5 = $svc->analyze("https://onlinesbi.sbi/personal/login.html", "url");
echo "=== Test 5: official SBI URL ===\n";
echo "Risk:     " . $r5["risk_level"] . "\n";
echo "Score:    " . $r5["score"] . "\n";
echo "Findings: " . count($r5["findings"]) . "\n\n";

echo "ALL SMOKE TESTS COMPLETE\n";
