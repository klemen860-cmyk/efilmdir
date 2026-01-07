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

// AG2M4 Referer Eklememesi İçin (Altyazı erişimi için gerekebilir)
if (strpos($url, 'ag2m4') !== false)
    $referer = "https://ag2m4.cfd/";

/* =====================
   SAYFAYI ÇEK
===================== */
$opts = [
    "http" => [
        "method" => "GET",
        "header" =>
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
            "Referer: $referer\r\n"
    ]
];

$context = stream_context_create($opts);
$content = @file_get_contents($url, false, $context);

/* =====================
   VERİ AVLA (m3u8 ve vtt)
===================== */
$videoUrl = null;
$subtitleUrl = null;

if ($content) {

    // 1️⃣ m3u8 Bulma (Orijinal Mantık)
    if (preg_match('/https?:\/\/[^"\']+\.m3u8[^"\']*/i', $content, $m))
        $videoUrl = $m[0];

    if (!$videoUrl && preg_match('/file\s*:\s*[\'"]([^\'"]+\.m3u8[^\'"]*)/i', $content, $m))
        $videoUrl = $m[1];

    // 2️⃣ Altyazı (vtt) Bulma (YENİ EK)
    // Embed içindeki altyazı dosyasını yakalar
    if (preg_match('/(?:file|src|label)\s*[:=]\s*[\'"]([^\'"]+\.vtt[^\'"]*)[\'"]/i', $content, $sub)) {
        $subtitleUrl = $sub[1];
        if (strpos($subtitleUrl, '//') === 0) $subtitleUrl = "https:" . $subtitleUrl;
    }
}

/* =====================
   OYNATICIYA VER
===================== */

// Eğer bir altyazı bulunduysa, bazı akıllı oynatıcıların (VLC/MX) 
// tanıması için başlık (header) olarak ekliyoruz.
if ($subtitleUrl) {
    header("X-Subtitle-URL: $subtitleUrl");
}

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
