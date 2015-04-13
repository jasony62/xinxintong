<?php
/**
 *
 */
class user_model {
 
    protected $aliossUrl = 'http://xinxintong.oss-cn-hangzhou.aliyuncs.com';

    protected $mpid;

    protected $bucket = 'xinxintong';

    public function __construct($mpid) 
    {
        $this->mpid = $mpid;

        $this->rootDir = "$this->mpid/_user";
    }
    /**
     *
     */
    protected function &get_alioss() 
    {
        require_once dirname(dirname(dirname(__FILE__))).'/lib/ali-oss/sdk.class.php';
        $oss_sdk_service = new ALIOSS();

        //设置是否打开curl调试模式
        $oss_sdk_service->set_debug_mode(FALSE);

        return $oss_sdk_service;
    }
    /**
     * 将文件上传到alioss
     */
    protected function moveUploadFile($dir, $filename, $fileUpload) 
    {
        $target = "$this->rootDir/$dir/$filename";

        $alioss = $this->get_alioss();
        $rsp = $alioss->upload_file_by_file($this->bucket, $target, $fileUpload);

        return "$this->aliossUrl/$target";
    }
    /**
     * 将指定url的文件转存到oss
     */
    public function storeUrl($url) 
    {
        /**
         * 下载文件
         */
        $ext = 'jpg';
        $response = file_get_contents($url);
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
        $dir = date("ymdH"); // 每个小时分一个目录
        $storename = date("is").rand(10000,99999).".".$ext;
        /**
         * 写到临时文件中
         */
        $tmpfname = tempnam($dir, 'xxt');
        $handle = fopen($tmpfname, "w");
        fwrite($handle, $response);
        /**
         * 写到alioss
         */
        $newUrl = $this->moveUploadFile($dir, $storename, $tmpfname);

        fclose($handle);

        return array(true, $newUrl);
    }
    /**
     * 存储base64的文件数据
     */
    public function storeBase64($data)
    {
        $matches = array();
        $rst = preg_match('/data:image\/(.+?);base64\,/', $data, $matches);
        if (1 !==  $rst)
            return array(false, 'xxx数据格式错误'.$rst);

        list($header, $ext) = $matches;
        $ext === 'jpeg' && $ext = 'jpg';

        $pic = base64_decode(str_replace($header, "", $data));

        $dir = date("ymdH"); // 每个小时分一个目录
        $storename = date("is").rand(10000,99999).".".$ext;
        /**
         * 写到临时文件中
         */
        $tmpfname = tempnam($dir, 'xxt');
        $handle = fopen($tmpfname, "w");
        fwrite($handle, $pic);
        /**
         * 写到alioss
         */
        $newUrl = $this->moveUploadFile($dir, $storename, $tmpfname);

        fclose($handle);

        return array(true, $newUrl);
    }
    /**
     *
     * $mpid
     * $img
     */
    public function storeImg($mpid, $img)
    {
        if (empty($img->imgSrc) && !isset($img->serverId))
            return array('数据为空');

        if (0 === strpos($img->imgSrc, 'http'))
            /**
             * url
             */
            $rst = $this->storeUrl($img->imgSrc);
        else if (1 === preg_match('/data:image(.+?);base64/', $img->imgSrc))
            /**
             * base64
             */
            $rst = $this->storeBase64($img->imgSrc);
        else if (isset($img->serverId)) {
            /**
             * wx jssdk
             */
            $rst = TMS_APP::model('mpproxy/wx', $mpid)->mediaGetUrl($img->serverId);
            if ($rst[0] === false)
                return $rsg;
            $rst = $this->storeUrl($rst[1]);
        } else
            return array(false, '数据格式错误');

        return $rst;
    }
}
