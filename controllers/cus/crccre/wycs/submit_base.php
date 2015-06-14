<?php
namespace cus\crccre\wycs;

require_once dirname(__FILE__).'/base.php';

class submit_base extends wycs_base {
    /**
     *
     */
    protected function doSubmit($mpid, $openid, $projectid, $data, $billType)
    {
        $picurls2 = array();
        if (!empty($data->pics)) {
            foreach ($data->pics as $serverId) {
                $rst = $this->downloadMedia($mpid, $serverId);
                if ($rst[0] === false)
                    return new \ResponseError($rst[1]);
                $picurls2[] = $rst[1];
            }
        }

        $picurls2 = implode('|', $picurls2);

        $param = new \stdClass;
        $param->clientid = $data->clientid;
        $param->content  = str_replace(' ', '', $data->content);
        $param->filepath = $picurls2;
        $param->billtype = $billType;
        $param->houseid  = $data->houseid;
        $param->isowner  = $data->isowner;
        $param->wechatid = $openid;
        $param->billid   = "";
        try {
            $rst = $this->soap()->submitBussinessBill($param);
        } catch (\Exception $e) {
            return new \ResponseError($e->getMessage());
        }
        $xml = simplexml_load_string($rst->return);
        $resultAttrs = $xml->result->attributes();
        if ((string)$resultAttrs['name'] === 'success')
            return new \ResponseData(array('id'=>(string)$xml->result->billid));
        else
            return new \ResponseError((string)$xml->result->failmessage);
    }
    /**
     *
     *
     * $projectid
     * $mediaid
     *
     */
    protected function downloadMedia($mpid, $mediaid)
    {
        $mpproxy = $this->model('mpproxy/wx', $mpid);
        $rst = $mpproxy->mediaGetUrl($mediaid);
        if ($rst[0] === false)
            return $rst;

        $downloadUrl = $rst[1];
        
        $ext = 'jpg';
        $response = file_get_contents($downloadUrl);
        $responseInfo = $http_response_header;
        foreach ($responseInfo as $loop) {
            if(strpos($loop, "Content-disposition") !== false){
                $disposition = trim(substr($loop, 21));
                $filename = explode(';', $disposition);
                $filename = array_pop($filename);
                $filename = explode('=', $filename);
                $filename = array_pop($filename);
                $filename = str_replace('"', '', $filename);
                $filename = explode('.', $filename);
                $ext = array_pop($filename);
                break;
            }
        }

        $storename = date("dHis").rand(10000,99999).".".$ext;
        $filedir = '/phone/'.date("Ym").'/';
        $filefulldir = TMS_UPLOAD_DIR.'wycs/'.$mpid.$filedir;
        $storeAt = $filefulldir.$storename;

        !file_exists($filefulldir) && mkdir($filefulldir, 0755, true);
          
        if(file_put_contents($storeAt, $response))
            return array(true, '/'.$storeAt);
        else
            return array(false, '保存文件失败');
    }
}
