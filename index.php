<?php
header("Access-Control-Allow-Origin: *");

if (isset($_GET['id'])) {
    $url = $_GET['id'];

    // Eğer link doğrudan .m3u8 ise, PotPlayer/VLC için özel M3U formatı oluştur
    if (strpos($url, '.m3u8') !== false) {
        header('Content-Type: application/x-mpegurl');
        header('Content-Disposition: attachment; filename="play.m3u"');
        
        echo "#EXTM3U\n";
        echo "#EXTINF:-1,Canli Yayin\n";
        echo "#EXTVLCOPT:http-referrer=https://trgoals1495.xyz/\n";
        echo "#EXTVLCOPT:http-user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\n";
        echo $url;
        exit;
    }

    // Normal web sayfaları (ag2m4 vb.) için arama yapmaya devam et
    $options = ["http" => ["header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"]];
    $context = stream_context_create($options);
    $content = @file_get_contents($url, false, $context);

    if ($content) {
        preg_match('/https?(?::|\\\\:)\/\/(?:[^"\']|\\\\\/)+\.(?:m3u8|mp4|mkv)(?:[^"\']|\\\\\/)*/', $content, $matches);
        if (isset($matches[0])) {
            $videoUrl = str_replace('\/', '/', $matches[0]);
            // Bulunan link .m3u8 ise sistemi başa döndür (Referer eklemesi için)
            if (strpos($videoUrl, '.m3u8') !== false) {
                header("Location: ?id=" . urlencode($videoUrl), true, 301);
            } else {
                header("Location: " . $videoUrl, true, 301);
            }
            exit;
        }
    }
    header("Location: " . $url, true, 301);
}
?>
