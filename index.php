<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain"); // PotPlayer'ın kafası karışmasın

if (isset($_GET['id'])) {
    $targetUrl = $_GET['id'];
    
    // Sayfa içeriğini çekmek için ayarlar (Daha fazla siteyi kandırmak için header eklendi)
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
                        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $content = @file_get_contents($targetUrl, false, $context);

    if ($content) {
        // 1. AŞAMA: Önce senin mevcut Regex sisteminle temiz linkleri ara
        preg_match('/https?:\/\/[^"\']+\.(?:m3u8|mp4|mkv)[^"\']*/', $content, $matches);
        
        // 2. AŞAMA: Eğer bulunamazsa, dizipal gibi sitelerin gizlediği ters eğik çizgili (\/) linkleri ara
        if (!isset($matches[0])) {
            preg_match('/https?(?::|\\\\:)\/\/(?:[^"\']|\\\\\/)+\.(?:m3u8|mp4|mkv)(?:[^"\']|\\\\\/)*/', $content, $matches);
        }

        if (isset($matches[0])) {
            // Linkteki ters eğik çizgileri (\/) temizle
            $videoUrl = str_replace('\/', '/', $matches[0]);
            
            // Gerçek video linkini bulduk, şimdi oraya yönlendir
            header("Location: " . $videoUrl, true, 301);
            exit;
        }
    }
    
    // Eğer bulamazsa doğrudan linke yönlendir (B planı)
    header("Location: " . $targetUrl, true, 301);
} else {
    echo "Goldvod Tarzi Sistem Aktif. Link Bekleniyor...";
}
?>
