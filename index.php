<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain");

if (isset($_GET['id'])) {
    $targetUrl = $_GET['id'];
    
    // Hedef sitenin ana dizinini referer olarak belirle
    $parsedUrl = parse_url($targetUrl);
    $host = $parsedUrl['scheme'] . "://" . $parsedUrl['host'];

    $options = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
                        "Referer: $host/\r\n" . 
                        "Accept: */*\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $content = @file_get_contents($targetUrl, false, $context);

    if ($content) {
        // Hem normal hem de gizli (\/) m3u8 linklerini tara
        preg_match('/https?(?::|\\\\:)\/\/(?:[^"\']|\\\\\/)+\.(?:m3u8|mp4|mkv)(?:[^"\']|\\\\\/)*/', $content, $matches);

        if (isset($matches[0])) {
            $videoUrl = str_replace('\/', '/', $matches[0]);
            
            // Yönlendirme yaparken de referer korumasını aşmaya çalış
            header("Location: " . $videoUrl, true, 301);
            exit;
        }
    }
    header("Location: " . $targetUrl, true, 301);
} else {
    echo "Sistem Aktif. Link Bekleniyor...";
}
?>
