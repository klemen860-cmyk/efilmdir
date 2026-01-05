<?php
header("Access-Control-Allow-Origin: *");

if (isset($_GET['id'])) {
    $url = $_GET['id'];

    // Eğer doğrudan bir .m3u8 linki geldiyse (Proxy Modu)
    if (strpos($url, '.m3u8') !== false) {
        $options = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
                            "Referer: https://goldvod.org/\r\n" // Bazı m3u8'ler bu referer'ı ister
            ]
        ];
        $context = stream_context_create($options);
        $content = @file_get_contents($url, false, $context);
        
        if ($content) {
            header("Content-Type: application/vnd.apple.mpegurl");
            echo $content;
            exit;
        }
    }

    // Embed sayfaları için eski arama/bulma mantığı
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
