<?php
include "webservice.php";
include dirname(dirname(dirname(__FILE__)))."/includes/SoapDiscovery.class.php";

$disc = new SoapDiscovery('webservice', 'soap'); 
$disc->getWSDL();
