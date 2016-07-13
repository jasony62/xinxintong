<?php
include '../../lib/qrcode/qrlib.php';
// outputs image directly into browser, as PNG stream
$url = $_GET['url'];
QRcode::png($url);