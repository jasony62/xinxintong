<?php
class webservice {

    public function Hello() 
    {
        return "Hello";
    }

    public function Add($a, $b) 
    {
        return $a + $b;
    }

    public function SendDbshx($userid, $text, $url) 
    {
        $restUrl = 'http://';
        $restUrl .= '10.22.250.26';
        $restUrl .= '/rest/cus/crccre/message/template/senddbshx?';
        $restUrl .= "userid=$userid";
        $restUrl .= "&text=$text";
        !empty($url) && $restUrl .= "&url=$url";

        $ch = curl_init($restUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if (false === ($response = curl_exec($ch))) {
            $err = curl_error($ch);
            curl_close($ch);
            return 'false';
        }
        curl_close($ch);

        $result = json_decode($response);
        if ($result->err_code !== 0)
            return 'false';

        return 'true';
    }
}

$server = new SoapServer('webservice.wsdl', array('soap_version' => SOAP_1_2));
$server->setClass("webservice"); 
$server->handle(); 
