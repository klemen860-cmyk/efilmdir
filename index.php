<?php
header("Access-Control-Allow-Origin: *");

if (!isset($_GET['id'])) {
    echo "Sistem Resetlendi. Ag2m4 ve Digerleri Hazir.";
    exit;
}

$url = $_GET['id'];

/* =====================
   REFERER TESPİTİ
===================== */
$referer = "https://google.com/";

if (strpos($url, 'trgoals') !== false)
    $referer = "https://trgoals1495.xyz/";

if (strpos($url, 'dizipal') !== false)
    $referer = "https://dizipal.website/";

if (strpos($url, 'dplayer82') !== false)
    $referer = "https://sn.dplayer82.site/";

/* =====================
   SAYFAYI ÇEK
===================== */
$opts = [
    "http" => [
        "method" => "GET",
        "header" =>
            "User-Agent: Mozilla/5.0\r\n" .
            "Referer: $referer\r\n"
    ]
];

$context = stream_context_create($opts);
$content = @file_get_contents($url, false, $context);

/* =====================
   m3u8 AVLA
===================== */
$videoUrl = null;

if ($content) {

    // 1️⃣ Direkt m3u8
    if (preg_match('/https?:\/\/[^"\']+\.m3u8[^"\']*/i', $content, $m))
        $videoUrl = $m[0];

    // 2️⃣ Kaçışlı m3u8
    if (!$videoUrl && preg_match('/https?(?::|\\\\:)\/\/(?:[^"\']|\\\\\/)+\.m3u8/i', $content, $m))
        $videoUrl = str_replace('\/', '/', $m[0]);

    // 3️⃣ base64 içinde m3u8
    if (!$videoUrl && preg_match('/atob\([\'"]([^\'"]+)[\'"]\)/', $content, $m)) {
        $decoded = base64_decode($m[1]);
        if (preg_match('/https?:\/\/[^"\']+\.m3u8/i', $decoded, $mm))
            $videoUrl = $mm[0];
    }
}

/* =====================
   OYNATICIYA VER
===================== */
if ($videoUrl) {

    header("Content-Type: application/vnd.apple.mpegurl");
    header("Location: $videoUrl", true, 302);
    exit;
}

/* =====================
   SON ÇARE
===================== */
header("Location: $url", true, 302);
exit;
