<?php
include '../../lib/qrcode/qrlib.php';
// outputs image directly into browser, as PNG stream
QRcode::png('http://www.xxtonline.com/rest/site/fe/matter/enroll?site=3c47b66895deb61299d4ca7cd110163d&app=56ed199b26e2f');