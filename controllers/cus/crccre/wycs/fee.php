<?php
namespace cus\crccre\wycs;

require_once dirname(__FILE__).'/base.php';
/**
 *
 */
class fee extends wycs_base {
    /**
     *
     */
    public function get_action($mpid, $houseid='', $mocker='') 
    {
        $projectid = $this->getProjectId($mpid);

        $openid = empty($mocker) ? $this->getCookieOAuthUser($mpid) : $mocker;

        $rst = $this->customInfo($projectid, $openid);
        if ($rst[0] === false)
            return new \ResponseError($rst[1]);

        $customInfo = $rst[1];
        if (empty($houseid)) {
            $myhouse = $customInfo['houselist'][0];
            $houseid = $myhouse['id'];
        }

        $param = new \stdClass;
        $param->houseid = $houseid;
        $param->date = null;
        $param->pageamount = 100;
        $param->beginnum = 0;

        $rst = $this->soap()->queryOwningFee($param);
        $xml = simplexml_load_string($rst->return);
        $resultAttrs = $xml->result->attributes();
        if ((string)$resultAttrs['name'] === 'success') {
            $sum = array();
            foreach($xml->result->sum->attributes() as $n => $v) {
                $sum[$n] = (string)$v;
            }
            $fees = array();
            if (isset($xml->result->feelist)) foreach($xml->result->feelist->children() as $nodefee) {
                $fee = array();
                foreach ($nodefee->attributes() as $n => $v)
                    $fee[$n] = (string)$v;
                $fees[] = $fee;
            }
            return new \ResponseData(array('fees'=>$fees, 'sum'=>$sum));
        } else
            return new \ResponseError((string)$xml->result->failmessage);

    }
}
