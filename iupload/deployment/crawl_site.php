<?php
// Simple site crawler for localhost:8000 to detect broken links and error pages

$startUrl = $argv[1] ?? 'http://localhost:8000/';
$maxPages = 500; // safety limit

function isAsset($url) {
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === '') return false;
    $assets = ['jpg','jpeg','png','gif','svg','webp','avif','css','js','ico','pdf','zip','tar','gz','sql','db','mp4','mov','webm','woff','woff2','ttf','otf','map'];
    return in_array($ext, $assets, true);
}

function resolveUrl($base, $rel) {
    // Ignore fragments and javascript/mailto/tel
    if ($rel === '' || $rel[0] === '#') return null;
    $lrel = strtolower($rel);
    if (str_starts_with($lrel, 'mailto:') || str_starts_with($lrel, 'tel:') || str_starts_with($lrel, 'javascript:')) return null;

    $p = parse_url($base);
    $scheme = $p['scheme'] ?? 'http';
    $host = $p['host'] ?? 'localhost';
    $port = isset($p['port']) ? ':' . $p['port'] : '';

    // Absolute URL
    if (preg_match('#^https?://#i', $rel)) {
        return $rel;
    }

    // Protocol-relative
    if (str_starts_with($rel, '//')) {
        return $scheme . ':' . $rel;
    }

    // Root-relative
    if ($rel[0] === '/') {
        return "$scheme://$host$port$rel";
    }

    // Relative to base path
    $basePath = $p['path'] ?? '/';
    // If base is a file, strip filename
    if (!str_ends_with($basePath, '/')) {
        $basePath = substr($basePath, 0, strrpos($basePath, '/') + 1);
    }
    // Normalize path segments
    $full = $basePath . $rel;
    $segments = [];
    foreach (explode('/', $full) as $seg) {
        if ($seg === '' || $seg === '.') continue;
        if ($seg === '..') { array_pop($segments); continue; }
        $segments[] = $seg;
    }
    return "$scheme://$host$port/" . implode('/', $segments);
}

function fetch($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'VivoCrawler/1.0',
        CURLOPT_HEADER => true,
    ]);
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($errno) {
        return [
            'status' => 0,
            'headers' => '',
            'body' => '',
            'error' => $error,
        ];
    }

    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    return [
        'status' => $status,
        'headers' => $headers,
        'body' => $body,
        'error' => null,
    ];
}

function extractLinks($baseUrl, $html) {
    $links = [];
    if (trim($html) === '') return $links;
    // Fallback to regex if DOM extension is unavailable
    if (!class_exists('DOMDocument')) {
        if (preg_match_all('#<a\s+[^>]*href=["\']([^"\'#]+)["\']#i', $html, $m)) {
            foreach ($m[1] as $href) {
                $resolved = resolveUrl($baseUrl, $href);
                if ($resolved) $links[] = $resolved;
            }
        }
        return array_values(array_unique($links));
    }
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if (!$dom->loadHTML($html)) return $links;
    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//a[@href]') as $a) {
        if ($a instanceof DOMElement) {
            $href = $a->getAttribute('href');
            $resolved = resolveUrl($baseUrl, $href);
            if ($resolved) $links[] = $resolved;
        }
    }
    return array_values(array_unique($links));
}

$start = parse_url($startUrl);
if (!$start || !isset($start['host'])) {
    fwrite(STDERR, "Invalid start URL: $startUrl\n");
    exit(1);
}
$originHost = $start['host'];
$originScheme = $start['scheme'] ?? 'http';
$originPort = $start['port'] ?? 80;

$queue = new SplQueue();
$queue->enqueue($startUrl);
$visited = [];
$results = [];
$broken = [];
$errors = [];

echo "🕷️  Crawling $startUrl (host $originHost:$originPort)\n";
echo str_repeat('=', 60) . "\n";

$count = 0;
while (!$queue->isEmpty() && $count < $maxPages) {
    $url = $queue->dequeue();
    if (isset($visited[$url])) continue;
    $visited[$url] = true;
    $count++;

    // stay within same origin
    $p = parse_url($url);
    if (!$p || ($p['host'] ?? '') !== $originHost) continue;
    if (isset($p['scheme']) && strtolower($p['scheme']) !== $originScheme) continue;
    if (isset($p['port']) && (int)$p['port'] !== (int)$originPort) continue;
    if (isAsset($url)) continue;

    $res = fetch($url);
    $status = $res['status'];
    $body = $res['body'];
    $err = $res['error'];

    $label = $status ? "[$status]" : "[ERR]";
    echo sprintf("%-8s %s\n", $label, $url);

    $results[$url] = $status;
    if ($status >= 400 || $status === 0) {
        $broken[] = [$url, $status, $err];
        continue;
    }

    // detect PHP errors in content
    if (preg_match('/(Fatal error|Warning:|Notice:|Undefined|Parse error|PDOException)/i', $body)) {
        $errors[] = $url;
    }

    // enqueue links
    foreach (extractLinks($url, $body) as $link) {
        // Limit to same host and not assets
        if (isAsset($link)) continue;
        $lp = parse_url($link);
        if (!$lp) continue;
        if (($lp['host'] ?? '') !== $originHost) continue;
        if (isset($lp['scheme']) && strtolower($lp['scheme']) !== $originScheme) continue;
        if (isset($lp['port']) && (int)$lp['port'] !== (int)$originPort) continue;
        if (!isset($visited[$link])) $queue->enqueue($link);
    }
}

echo "\n" . str_repeat('-', 60) . "\n";
echo "Crawled pages: " . count($results) . " (visited: " . count($visited) . ")\n";
echo "Broken links (HTTP >= 400 or error): " . count($broken) . "\n";
if ($broken) {
    foreach ($broken as [$u, $s, $e]) {
        echo "  - $u => status: $s" . ($e ? ", error: $e" : '') . "\n";
    }
}
echo "Pages with PHP error strings detected: " . count($errors) . "\n";
if ($errors) {
    foreach ($errors as $u) {
        echo "  - $u\n";
    }
}

if (!$broken && !$errors) {
    echo "\n✅ No broken links or error strings detected.\n";
} else {
    echo "\n❗ Issues detected. See lists above.\n";
}

exit(($broken || $errors) ? 2 : 0);
?>
