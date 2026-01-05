<?php
header("Access-Control-Allow-Origin: *");
if(isset($_GET['id'])){
    header("Location: " . $_GET['id'], true, 301);
    exit;
}
?>
