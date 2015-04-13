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
    // echo $soap->Hello();
    // echo $soap->Add(1, 2);
    $ret = $soap->SendDbshx('testkwt', 'test');
    echo json_encode($ret);
} catch (Exception $e) {
    echo 'exception:'.$e->getMessage();
}
