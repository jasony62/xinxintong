<?php
/**
 * 铁建地产组织机构 
 */
class org_model extends TMS_MODEL {
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
            $this->soap = new SoapClient(
                'http://um.crccre.cn/webservices/adgrouptree.asmx?wsdl', 
                array(
                    'soap_version' => SOAP_1_2,
                    'encoding'=>'utf-8',
                    'exceptions'=>true, 
                    'trace'=>1, 
                )
            );
        }
        return $this->soap;
    }
    /**
     * 按层级获得节点数据
     */
    public function nodes($pid=null)
    {
        $depts = array();
        if (empty($pid)) {
            $param = new stdClass;
            $param->RequestCode = ''; 
            $rst = $this->soap()->GetRootList($param);
            $xml = new SimpleXMLElement($rst->GetRootListResult);
            foreach ($xml->children() as $xnode) {
                $guid = ''.$xnode->attributes()->id;
                $depts[] = $this->getNodeByGUID($guid);
            }
        } else {
            $param = new stdClass;
            $param->GUID = $pid;    
            $rst = $this->soap()->GetNodesByGUID($param);
            $xml = new SimpleXMLElement('<xml>'.$rst->GetNodesByGUIDResult.'</xml>');
            foreach ($xml->children() as $xnode) {
                $attributes = $xnode->attributes();
                $dept = array();
                foreach ($attributes as $k => $v)
                    $dept[$k] = ''.$v;
                $depts[] = $dept;
            }
        }

        return $depts;
    }
    /**
     *
     */
    public function getNodeByGUID($guid)
    {
        $param = new stdClass;
        $param->guid = $guid;
        $rst = $this->soap()->GetNodeByGUID($param);
        $xml = new SimpleXMLIterator($rst->GetNodeByGUIDResult);
        $xml->rewind();
        $xnode = $xml->current();
        $attributes = $xnode->attributes();
        $node = array();
        foreach ($attributes as $k => $v)
            $node[$k] = ''.$v;

        return $node;
    }
    /**
     *
     */
    public function getNodeByCode($code)
    {
        $nodes = array();
        $param = new stdClass;
        $param->code = $code;
        $rst = $this->soap()->GetNodeByCode($param);
        $xml = new SimpleXMLElement($rst->GetNodeByCodeResult);
        foreach ($xml->children() as $node) {
            $attributes = $node->attributes();
            $node = array();
            foreach ($attributes as $k => $v)
                $node[$k] = ''.$v;
            $nodes[] = $node;
        }

        return $nodes;
    }
    /**
     *
     */
    public function getNodesByTitleType($titleType)
    {
        $nodes = array();
        $param = new stdClass;
        $param->titleType = $titleType;    
        $rst = $this->soap()->GetNodesByTitleType($param);
        $xml = new SimpleXMLElement($rst->GetNodesByTitleTypeResult);
        foreach ($xml->children() as $xnode) {
            $attributes = $xnode->attributes();
            $node = array();
            foreach ($attributes as $k => $v)
                $node[$k] = ''.$v;
            $nodes[] = $node;
        }

        return $nodes;
    }
}
