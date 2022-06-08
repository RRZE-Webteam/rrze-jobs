<?php
include 'phpqrcode/qrlib.php';

QRcode::png(filter_var($_GET['url'], FILTER_SANITIZE_URL) . '#' . filter_var($_GET['collapse'], FILTER_SANITIZE_STRING));
