<?php
namespace cus\crccre\wycs;

require_once dirname(__FILE__).'/base.php';

class bill extends wycs_base {
    /**
     * 列出当前用户的所有单据
     *
     * $houseid
     */
    public function list_action($mpid, $houseid='', $mocker='') 
    {
        $projectid = $this->getProjectId($mpid);
        
        $openid = empty($mocker) ? $this->getCookieOAuthUser($mpid) : $mocker;
        
        if (empty($houseid)) {
            $rst = $this->customInfo($projectid, $openid);
            if ($rst[0] === false)
                return new \ResponseError($rst[1]);
            $customInfo = $rst[1];
            $myhouse = $customInfo['houselist'][0];
            $houseid = $myhouse['id'];
        }

        $param = new \stdClass;
        $param->houseid = $houseid;
        $param->date = null;
        $param->pageamount = 100;
        $param->beginnum = 0;
        $param->billtype = null;
        $param->billstate = null;

        $rst = $this->soap()->queryBussinessBill($param);
        $xml = simplexml_load_string($rst->return);
        $resultAttrs = $xml->result->attributes();
        if ((string)$resultAttrs['name'] === 'success' && !empty($xml->result->billlist)) {
            $sumAttrs = $xml->result->sum->attributes();
            $sum = (int)$sumAttrs['num'];
            $bills = array();
            foreach($xml->result->billlist->children() as $nodebill) {
                $bill = array();
                foreach ($nodebill->attributes() as $n => $v)
                    $bill[$n] = (string)$v;
                $bills[] = $bill;
            }
            return new \ResponseData((array('bills'=>$bills, 'sum'=>$sum)));
        } else
            return new \ResponseError((string)$xml->result->failmessage);
    }
    /**
     * 查询单据详细信息
     *
     * $id
     * $type
     * $from
     */
    public function detail_action($mpid, $billid, $type='维修', $from='维修', $houseid='', $mocker='') 
    {
        $projectid = $this->getProjectId($mpid);
        
        $openid = empty($mocker) ? $this->getCookieOAuthUser($mpid) : $mocker;
        
        if (empty($houseid)) {
            $rst = $this->customInfo($projectid, $openid);
            if ($rst[0] === false)
                return new \ResponseError($rst[1]);
            $customInfo = $rst[1];
            $myhouse = $customInfo['houselist'][0];
            $houseid = $myhouse['id'];
        }
        
        $param = new \stdClass;
        $param->billid = $billid;
        $param->billtype = $type;
        $param->billfrom = $from;

        $rst = $this->soap()->queryBillState($param);
        $xml = simplexml_load_string($rst->return);
        $resultAttrs = $xml->result->attributes();
        if ((string)$resultAttrs['name'] === 'success') {
            /**
             * process
             */
            $steps = array();
            foreach($xml->result->process->step as $nodestep) {
                $step = array();
                foreach ($nodestep->attributes() as $n => $v)
                    $step[$n] = (string)$v;
                $steps[] = $step;
            }
            /**
             * info
             */
            $bill = $this->billById($billid, $houseid);
            return new \ResponseData(array('bill'=>$bill,'steps'=>$steps));
        } else
            return new \ResponseError((string)$xml->result->failmessage);
    }
    /**
     * todo 应该替换成直接获取数据的接口
     */
    private function billById($billid, $houseid)
    {
        $param = new \stdClass;
        $param->houseid = $houseid;
        $param->date = null;
        $param->pageamount = 10000;
        $param->beginnum = 0;
        $param->billtype = null;
        $param->billstate = null;

        $rst = $this->soap()->queryBussinessBill($param);
        $xml = simplexml_load_string($rst->return);
        $resultAttrs = $xml->result->attributes();
        if ((string)$resultAttrs['name'] === 'success' && !empty($xml->result->billlist)) {
            $bill = null;
            foreach($xml->result->billlist->children() as $nodeBill) {
                $billAttrs = $nodeBill->attributes();
                if ((string)$billAttrs['id'] === $billid) {
                    $bill = array();
                    foreach ($nodeBill->attributes() as $n => $v)
                        $bill[$n] = (string)$v;
                    break;
                }
            }
            if (!empty($bill) && !empty($xml->result->filelist)) {
                foreach($xml->result->filelist->children() as $nodeFile) {
                    $fileAttrs = $nodeFile->attributes();
                    if ((string)$fileAttrs['pk'] === $billid) {
                        $file = array();
                        foreach ($nodeFile->attributes() as $n => $v)
                            $file[$n] = (string)$v;
                        $bill['files'][] = $file;
                    }
                }
                return $bill;
            }
            return $bill;
        }
        return null;
    }
}
