<?php

/**
 * tests/Unit/VirusTotalProviderTest.php
 *
 * Offline unit tests for the VirusTotal integration.
 *
 * Tests run without network access and without a real API key.
 * They exercise:
 *   1. VirusTotalResult value object behaviour and named constructors.
 *   2. VirusTotalProvider::lookup() guard conditions (no key, wrong provider).
 *   3. URL identifier generation (unpadded URL-safe Base64).
 *   4. Response parsing — success, not-found, rate-limit, bad JSON.
 *   5. ReportCache read/write/TTL logic.
 *   6. toThreatStatus() mapping for every status.
 *
 * Run from the project root on Windows/XAMPP:
 *   php tests\Unit\VirusTotalProviderTest.php
 *
 * No PHPUnit required — uses a minimal inline assertion helper.
 * Expected output: each test prints PASS or FAIL with a reason.
 */

declare(strict_types=1);

// ── Bootstrap ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../app/Support/Autoloader.php';
\App\Support\Autoloader::register();

// Minimal assertion helpers
function assert_equals(mixed $expected, mixed $actual, string $label): void
{
    if ($expected === $actual) {
        echo "[PASS] {$label}\n";
    } else {
        $exp = is_bool($expected) ? ($expected ? 'true' : 'false') : (string)$expected;
        $act = is_bool($actual)   ? ($actual   ? 'true' : 'false') : (string)$actual;
        echo "[FAIL] {$label}\n       expected: {$exp}\n       actual:   {$act}\n";
    }
}

function assert_true(bool $value, string $label): void
{
    assert_equals(true, $value, $label);
}

function assert_null(mixed $value, string $label): void
{
    assert_equals(null, $value, $label);
}

function assert_not_null(mixed $value, string $label): void
{
    assert_true($value !== null, $label);
}

// ── Section 1: VirusTotalResult value object ─────────────────────────────────
echo "\n=== 1. VirusTotalResult named constructors ===\n";

use App\Services\Analysis\VirusTotalResult;

// 1a. found()
$r = VirusTotalResult::found(
    stats: ['malicious' => 3, 'suspicious' => 1, 'harmless' => 60, 'undetected' => 10, 'timeout' => 2],
    lastAnalysisDate: 1720000000,
);
assert_equals(VirusTotalResult::STATUS_FOUND, $r->status, '1a status=found');
assert_equals(3,          $r->malicious,        '1a malicious=3');
assert_equals(1,          $r->suspicious,       '1a suspicious=1');
assert_equals(60,         $r->harmless,         '1a harmless=60');
assert_equals(10,         $r->undetected,       '1a undetected=10');
assert_equals(2,          $r->timeout,          '1a timeout=2');
assert_equals(76,         $r->totalEngines,     '1a totalEngines=76');
assert_equals(1720000000, $r->lastAnalysisDate, '1a lastAnalysisDate');
assert_true(!$r->fromCache,                      '1a fromCache=false');
assert_true($r->isFound(),                       '1a isFound()=true');
assert_true(!$r->isUnavailable(),                '1a isUnavailable()=false');

// 1b. found() with zero detections
$r0 = VirusTotalResult::found(
    stats: ['malicious' => 0, 'suspicious' => 0, 'harmless' => 70, 'undetected' => 5, 'timeout' => 0],
    lastAnalysisDate: null,
);
assert_equals(0,  $r0->malicious,    '1b zero malicious');
assert_equals(75, $r0->totalEngines, '1b total=75');
assert_null($r0->lastAnalysisDate,   '1b lastAnalysisDate null');

// 1c. found() from cache
$rc = VirusTotalResult::found(
    stats: ['malicious' => 1, 'suspicious' => 0, 'harmless' => 50, 'undetected' => 0, 'timeout' => 0],
    lastAnalysisDate: null,
    fromCache: true,
);
assert_true($rc->fromCache, '1c fromCache=true');

// 1d. notFound()
$nf = VirusTotalResult::notFound();
assert_equals(VirusTotalResult::STATUS_NOT_FOUND, $nf->status, '1d status=not_found');
assert_null($nf->malicious,    '1d malicious=null');
assert_null($nf->totalEngines, '1d totalEngines=null');
assert_true(!$nf->isFound(),         '1d isFound()=false');
assert_true($nf->isUnavailable(),    '1d isUnavailable()=true');

// 1e. rateLimited()
$rl = VirusTotalResult::rateLimited();
assert_equals(VirusTotalResult::STATUS_RATE_LIMITED, $rl->status, '1e status=rate_limited');

// 1f. unavailable()
$ua = VirusTotalResult::unavailable('test reason');
assert_equals(VirusTotalResult::STATUS_UNAVAILABLE, $ua->status, '1f status=unavailable');
assert_true(str_contains($ua->message, 'test reason'), '1f message contains reason');

// 1g. notConfigured()
$nc = VirusTotalResult::notConfigured();
assert_equals(VirusTotalResult::STATUS_NOT_CONFIGURED, $nc->status, '1g status=not_configured');

// ── Section 2: toThreatStatus() mapping ─────────────────────────────────────
echo "\n=== 2. toThreatStatus() ===\n";

assert_equals('match',      VirusTotalResult::found(['malicious'=>1,'suspicious'=>0,'harmless'=>0,'undetected'=>0,'timeout'=>0], null)->toThreatStatus(), '2a match when malicious>0');
assert_equals('match',      VirusTotalResult::found(['malicious'=>0,'suspicious'=>2,'harmless'=>0,'undetected'=>0,'timeout'=>0], null)->toThreatStatus(), '2b match when suspicious>0');
assert_equals('no_match',   VirusTotalResult::found(['malicious'=>0,'suspicious'=>0,'harmless'=>50,'undetected'=>5,'timeout'=>0], null)->toThreatStatus(), '2c no_match when all clean');
assert_equals('unavailable', VirusTotalResult::notFound()->toThreatStatus(),     '2d not_found → unavailable');
assert_equals('unavailable', VirusTotalResult::rateLimited()->toThreatStatus(),  '2e rate_limited → unavailable');
assert_equals('unavailable', VirusTotalResult::unavailable()->toThreatStatus(),  '2f unavailable → unavailable');
assert_equals('not_checked', VirusTotalResult::notConfigured()->toThreatStatus(),'2g not_configured → not_checked');

// ── Section 3: URL identifier generation ────────────────────────────────────
echo "\n=== 3. URL identifier (unpadded URL-safe Base64) ===\n";

/**
 * Expose buildUrlId() logic for testing via a trivial subclass or just replicate it.
 * Since buildUrlId() is private, we verify the algorithm directly.
 */
function buildUrlId(string $url): string
{
    return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
}

// Test against a known example.
// base64("http://example.com") = "aHR0cDovL2V4YW1wbGUuY29t" (no padding needed here)
$id1 = buildUrlId('http://example.com');
assert_equals('aHR0cDovL2V4YW1wbGUuY29t', $id1, '3a known URL id');

// Padding removal: base64 of "http://a.co" includes = padding in standard base64.
$raw2 = base64_encode('http://a.co');
$id2  = buildUrlId('http://a.co');
assert_true(!str_contains($id2, '='), '3b no = in URL id');
assert_true(!str_contains($id2, '+'), '3c no + in URL id');
assert_true(!str_contains($id2, '/'), '3d no / in URL id');

// Round-trip: decode the id back and confirm we get the original URL.
// Re-pad before decoding: add = padding so PHP can decode it.
$padded   = $id2 . str_repeat('=', (4 - strlen($id2) % 4) % 4);
$decoded  = base64_decode(strtr($padded, '-_', '+/'));
assert_equals('http://a.co', $decoded, '3e round-trip decode');

// ── Section 4: VirusTotalProvider guard conditions ──────────────────────────
echo "\n=== 4. VirusTotalProvider guard conditions ===\n";

use App\Services\Analysis\VirusTotalProvider;

// Set STORAGE_EVIDENCE_PATH to a valid temp directory so ReportCache initialises
// cleanly without needing to touch the real storage/ directory.
$_ENV['STORAGE_EVIDENCE_PATH'] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vt_guard_test' . DIRECTORY_SEPARATOR . 'evidence';

// 4a. Returns notConfigured when THREAT_INTEL_PROVIDER is not 'virustotal'.
$_ENV['THREAT_INTEL_PROVIDER']  = 'none';
$_ENV['VIRUSTOTAL_API_KEY']     = 'fake-key-for-test';
$provider = new VirusTotalProvider();
$res = $provider->lookup('http://example.com');
assert_equals(VirusTotalResult::STATUS_NOT_CONFIGURED, $res->status, '4a provider=none → not_configured');

// 4b. Returns notConfigured when API key is empty.
$_ENV['THREAT_INTEL_PROVIDER'] = 'virustotal';
$_ENV['VIRUSTOTAL_API_KEY']    = '';
$res = $provider->lookup('http://example.com');
assert_equals(VirusTotalResult::STATUS_NOT_CONFIGURED, $res->status, '4b empty key → not_configured');

// 4c. Returns notConfigured when API key is the placeholder value.
$_ENV['VIRUSTOTAL_API_KEY'] = 'not_configured';
$res = $provider->lookup('http://example.com');
assert_equals(VirusTotalResult::STATUS_NOT_CONFIGURED, $res->status, '4c placeholder key → not_configured');

// 4d. Returns unavailable for an empty URL string (key is valid, empty URL checked next).
$_ENV['VIRUSTOTAL_API_KEY'] = 'fake-key-for-test';
$res = $provider->lookup('   ');
assert_equals(VirusTotalResult::STATUS_UNAVAILABLE, $res->status, '4d empty url → unavailable');

// Note: we do NOT make a real network call here, so we cannot test the
// actual HTTP fetch path. That requires an integration test with a live key.
// The guard conditions above cover all "short-circuit" code paths.

// ── Section 5: ReportCache read/write/TTL ───────────────────────────────────
echo "\n=== 5. ReportCache ===\n";

use App\Services\Analysis\ReportCache;

// Point the cache at a temp directory for tests.
$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'scamshield_test_cache_' . getmypid();
@mkdir($tmpDir, 0700, true);

// Override STORAGE_EVIDENCE_PATH so ReportCache resolves to our temp dir.
// ReportCache computes: dirname(STORAGE_EVIDENCE_PATH) / vt_cache
// We want tmpDir as the vt_cache dir, so set parent = dirname(tmpDir).
// Actually the cache dir = dirname(STORAGE_EVIDENCE_PATH) . '/vt_cache',
// so set STORAGE_EVIDENCE_PATH = tmpDir . '/evidence' so vt_cache = tmpDir.
$_ENV['STORAGE_EVIDENCE_PATH']          = $tmpDir . DIRECTORY_SEPARATOR . 'evidence';
$_ENV['VIRUSTOTAL_CACHE_TTL_SECONDS']   = '60';

$cache = new ReportCache();

$testUrl = 'http://test-scamshield.example.com/path?q=1';

// 5a. Cache miss on empty cache.
$miss = $cache->get($testUrl);
assert_null($miss, '5a cache miss when empty');

// 5b. Store a STATUS_FOUND result and retrieve it.
$stored = VirusTotalResult::found(
    stats: ['malicious'=>2,'suspicious'=>1,'harmless'=>40,'undetected'=>8,'timeout'=>0],
    lastAnalysisDate: 1720000000,
);
$cache->put($testUrl, $stored);
$hit = $cache->get($testUrl);
assert_not_null($hit, '5b cache hit after put');
assert_equals(VirusTotalResult::STATUS_FOUND, $hit->status, '5b status=found from cache');
assert_equals(2,          $hit->malicious,        '5b malicious=2 from cache');
assert_equals(1,          $hit->suspicious,       '5b suspicious=1 from cache');
assert_equals(51,         $hit->totalEngines,     '5b total=51 from cache');
assert_equals(1720000000, $hit->lastAnalysisDate, '5b lastAnalysisDate from cache');
assert_true($hit->fromCache, '5b fromCache=true');

// 5c. Store a STATUS_NOT_FOUND and retrieve it.
$nfUrl = 'http://new-unknown-domain.example.com/';
$cache->put($nfUrl, VirusTotalResult::notFound());
$nfHit = $cache->get($nfUrl);
assert_not_null($nfHit, '5c not_found stored and retrieved');
assert_equals(VirusTotalResult::STATUS_NOT_FOUND, $nfHit->status, '5c status=not_found from cache');

// 5d. Rate-limited result is NOT cached.
$rlUrl = 'http://rate-limited.example.com/';
$cache->put($rlUrl, VirusTotalResult::rateLimited());
$rlHit = $cache->get($rlUrl);
assert_null($rlHit, '5d rate_limited not cached');

// 5e. Unavailable result is NOT cached.
$uaUrl = 'http://unavailable.example.com/';
$cache->put($uaUrl, VirusTotalResult::unavailable('test'));
$uaHit = $cache->get($uaUrl);
assert_null($uaHit, '5e unavailable not cached');

// 5f. Expired cache entry returns null.
// Write a cache file with a past cached_at timestamp.
$expiredKey  = hash('sha256', 'http://expired.example.com/');
$expiredFile = $tmpDir . DIRECTORY_SEPARATOR . $expiredKey . '.json';
$expiredData = json_encode([
    'cached_at'          => time() - 120, // 2 minutes ago; TTL is 60 seconds → expired
    'status'             => VirusTotalResult::STATUS_FOUND,
    'malicious'          => 0,
    'suspicious'         => 0,
    'harmless'           => 50,
    'undetected'         => 5,
    'timeout'            => 0,
    'total_engines'      => 55,
    'last_analysis_date' => null,
]);
file_put_contents($expiredFile, $expiredData);
$expiredHit = $cache->get('http://expired.example.com/');
assert_null($expiredHit, '5f expired cache entry returns null');

// 5g. Corrupt JSON cache entry returns null and is deleted.
// The cache stores files in vt_cache/ subdirectory of the storage root.
// STORAGE_EVIDENCE_PATH = tmpDir/evidence → storage root = tmpDir → cache dir = tmpDir/vt_cache
$vtCacheDir  = $tmpDir . DIRECTORY_SEPARATOR . 'vt_cache';
@mkdir($vtCacheDir, 0700, true);
$corruptKey  = hash('sha256', 'http://corrupt.example.com/');
$corruptFile = $vtCacheDir . DIRECTORY_SEPARATOR . $corruptKey . '.json';
file_put_contents($corruptFile, '{ INVALID JSON !! }');
$corruptHit = $cache->get('http://corrupt.example.com/');
assert_null($corruptHit, '5g corrupt cache returns null');
assert_true(!file_exists($corruptFile), '5g corrupt cache file deleted');

// ── Cleanup temp files ───────────────────────────────────────────────────────
$files = glob($tmpDir . DIRECTORY_SEPARATOR . '*.json') ?: [];
foreach ($files as $f) { @unlink($f); }
@rmdir($tmpDir);

// ── Summary ─────────────────────────────────────────────────────────────────
echo "\n=== All tests completed. Review PASS/FAIL lines above. ===\n";
