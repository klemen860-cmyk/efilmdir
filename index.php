<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain"); // PotPlayer'ın kafası karışmasın

if (isset($_GET['id'])) {
    $targetUrl = $_GET['id'];
    
    // Sayfa içeriğini çekmek için ayarlar
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $content = @file_get_contents($targetUrl, false, $context);

    if ($content) {
        // Sayfa içinde .m3u8 veya .mp4 geçen linkleri ara (Regex)
        preg_match('/https?:\/\/[^"\']+\.(?:m3u8|mp4|mkv)[^"\']*/', $content, $matches);
        
        if (isset($matches[0])) {
            // Gerçek video linkini bulduk, şimdi oraya yönlendir
            header("Location: " . $matches[0], true, 301);
            exit;
        }
    }
    
    // Eğer bulamazsa doğrudan linke yönlendir (B planı)
    header("Location: " . $targetUrl, true, 301);
} else {
    echo "Goldvod Tarzi Sistem Aktif. Link Bekleniyor...";
}
?>
