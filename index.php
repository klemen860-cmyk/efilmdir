<?php
header("Access-Control-Allow-Origin: *");

if (isset($_GET['id'])) {
    $url = $_GET['id'];
    
    // 1. ADIM: Siteye uygun anahtarları (Referer) hazırla
    $referer = "https://google.com/";
    if (strpos($url, 'trgoals') !== false) $referer = "https://trgoals1495.xyz/";
    if (strpos($url, 'dizipal') !== false) $referer = "https://dizipal.website/";

    $options = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
                        "Referer: $referer\r\n"
        ]
    ];
    $context = stream_context_create($options);
    
    // 2. ADIM: Sayfayı tara ve video linkini bul
    $content = @file_get_contents($url, false, $context);

    if ($content) {
        // Hem normal hem de gizli (\/) linkleri bulan en güçlü Regex
        preg_match('/https?(?::|\\\\:)\/\/(?:[^"\']|\\\\\/)+\.(?:m3u8|mp4|mkv)(?:[^"\']|\\\\\/)*/', $content, $matches);
        
        if (isset($matches[0])) {
            $videoUrl = str_replace('\/', '/', $matches[0]);
            
            // 3. ADIM: PotPlayer'a temiz yönlendirme yap (En garantisi budur)
            header("Location: " . $videoUrl, true, 301);
            exit;
        }
    }
    
    // Bulamazsa mecbur orijinal linke gönder
    header("Location: " . $url, true, 301);
} else {
    echo "Sistem Resetlendi. Ag2m4 ve Digerleri Hazir.";
}
?>
