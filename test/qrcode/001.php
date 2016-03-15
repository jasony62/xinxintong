<?php
include '../../lib/qrcode/qrlib.php';
// outputs image directly into browser, as PNG stream
QRcode::png('http://www.baidu.com');