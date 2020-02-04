<?php
include 'phpqrcode/qrlib.php';

QRcode::png($_GET['url'].'#'.$_GET['collapse']);
?>