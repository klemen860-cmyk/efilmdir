<?php
// Tüm Player'lara ve tarayıcılara izin ver
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if(isset($_GET['id'])){
    $url = $_GET['id'];
    // Player'ların videoyu görmesi için yönlendirmeyi yap
    header("Location: " . $url, true, 301); 
    exit;
} else {
    echo "Sistem Hazir! Player linkini bekliyor.";
}
?>
