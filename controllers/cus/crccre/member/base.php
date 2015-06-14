<?php
namespace cus\crccre\member;

require_once dirname(dirname(dirname(dirname(__FILE__)))).'/member_base.php';
/**
 * crccre用户认证
 */
class crccre_member_base extends \member_base {
    /**
     *
     */
    private $soap;
    /**
     *
     */
    protected function soap() 
    {
        if (!isset($this->soap)) {
            ini_set('soap.wsdl_cache_enabled', '0');
            try {
                $this->soap = new \SoapClient(
                    'http://um.crccre.cn/webservices/adgrouptree.asmx?wsdl', 
                    array(
                        'soap_version' => SOAP_1_2,
                        'encoding'=>'utf-8',
                        'exceptions'=>true, 
                        'trace'=>1, 
                    )
                );
            } catch (\Exception $e) {
                die('exception: '.$e->getMessage());
            }
        }
        return $this->soap;
    }
    /**
     * 获得所有上级部门
     */
    protected function getSupDepartment($mpid, $guid, &$depts)
    {
        try {
            $param = new \stdClass;
            $param->guid = $guid;
            $ret = $this->soap->GetNodeByGUID($param);
            $xml = new \SimpleXMLElement($ret->GetNodeByGUIDResult);
            foreach ($xml->children() as $node) {
                $attributes = $node->attributes();
                $titletype = ''.$attributes['titletype'];
                if (1 === preg_match('/^[1,2,3].*/', $titletype)) {
                    $dept = array();
                    foreach ($node->attributes() as $k => $v)
                        $dept[$k] = ''.$v;
                    $q = array(
                        'id',
                        'xxt_member_department',
                        "mpid='$mpid' and extattr like '%\"guid\":\"".$dept['guid']."\"%'"
                    );
                    $deptid = $this->model()->query_val_ss($q); 
                    $dept['deptid'] = $deptid; 
                    $depts[] = $dept;

                }
                $parentid = ''.$attributes['parentid'];
                if (!empty($parentid) && $titletype !== '1')
                    $this->getSupDepartment($mpid, $parentid, $depts);
            }
        } catch (\Exception $e) {
            die('exception: '.$e->getMessage());
        }
    }
}
