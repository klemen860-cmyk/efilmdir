<?php
header("Access-Control-Allow-Origin: *");

if (isset($_GET['id'])) {
    $url = $_GET['id'];
    
    // URL'den hangi site olduğunu anla
    $host = parse_url($url, PHP_URL_HOST);

    // Eğer doğrudan m3u8 linki ise veya korumalı bir site ise Proxy/Tünel moduna gir
    if (strpos($url, '.m3u8') !== false || strpos($url, 'dizipal') !== false) {
        
        // Siteye göre dinamik Referer belirle
        $referer = "https://google.com/";
        if (strpos($url, 'trgoals') !== false) $referer = "https://trgoals1495.xyz/";
        if (strpos($url, 'dizipal') !== false) $referer = "https://dizipal.website/";

        $options = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
                            "Referer: $referer\r\n" .
                            "Origin: " . rtrim($referer, '/') . "\r\n"
            ]
        ];
        
        $context = stream_context_create($options);
        $content = @file_get_contents($url, false, $context);
        
        if ($content) {
            // Eğer bu bir m3u8 dosyası ise doğrudan içeriği bas (Tünelle)
            if (strpos($url, '.m3u8') !== false) {
                header("Content-Type: application/vnd.apple.mpegurl");
                echo $content;
                exit;
            }
            
            // Eğer bu bir HTML sayfasıysa (Dizipal sayfası gibi), içindeki linki ara
            preg_match('/https?(?::|\\\\:)\/\/(?:[^"\']|\\\\\/)+\.(?:m3u8|mp4|mkv)(?:[^"\']|\\\\\/)*/', $content, $matches);
            if (isset($matches[0])) {
                $videoUrl = str_replace('\/', '/', $matches[0]);
                // Bulduğun video linkini tekrar kendi sunucun üzerinden (id ile) çalıştır ki koruma aşılmaya devam etsin
                header("Location: ?id=" . urlencode($videoUrl), true, 301);
                exit;
            }
        }
    }

    // Klasik yöntem (ag2m4 gibi siteler için hızlı yönlendirme)
    $options = ["http" => ["header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"]];
    $context = stream_context_create($options);
    $content = @file_get_contents($url, false, $context);

    if ($content) {
        preg_match('/https?(?::|\\\\:)\/\/(?:[^"\']|\\\\\/)+\.(?:m3u8|mp4|mkv)(?:[^"\']|\\\\\/)*/', $content, $matches);
        if (isset($matches[0])) {
            $videoUrl = str_replace('\/', '/', $matches[0]);
            header("Location: " . $videoUrl, true, 301);
            exit;
        }
    }
    header("Location: " . $url, true, 301);
}
?>
