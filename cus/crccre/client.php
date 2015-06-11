<?php
ini_set('soap.wsdl_cache_enabled', '0');
$soap = new SoapClient(
    'http://10.22.250.26/cus/crccre/webservice.php?wsdl', 
    array(
        'soap_version' => SOAP_1_2,
        'encoding'=>'utf-8',
        'exceptions'=>true, 
        'trace'=>1, 
    )
);
try {
//	echo $soap->Hello();
$ret = $soap->SendDbshx('testkwt', 'test', 'www.baidu.com');
echo json_encode($ret);
} catch (Exception $e) {
	echo 'exception:'.$e->getMessage();
}
//echo $soap->Add(1, 2);
