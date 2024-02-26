<?php
include 'phpqrcode/qrlib.php';

if (isset($_GET['url'], $_GET['collapse'])) {
    $url = filter_var($_GET['url'], FILTER_SANITIZE_URL);
    $collapse = '#' . htmlspecialchars($_GET['collapse'], ENT_QUOTES, 'UTF-8');


    QRcode::png(($url ?? '') . ($collapse ?? ''));
} else {
    echo "Error: 'url' and 'collapse' parameters are required.";
}
