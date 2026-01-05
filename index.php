<?php
header("Access-Control-Allow-Origin: *");
if(isset($_GET['id'])){
    header("Location: " . $_GET['id'], true, 302);
    exit;
} else {
    echo "Sistem Hazir!";
}
?>
