<?php
require_once dirname(__FILE__) . '/local.php';
/**
 * 用户存储
 */
class user_model
{

  private $siteId;
  /**
   * 文件存储服务
   */
  private $service;

  public function __construct($siteId, $dirName = '_user')
  {
    $this->siteId = $siteId;
    $this->service = new local_model($siteId, $dirName);
  }
  /**
   * 保存文件
   */
  protected function writeFile($dir, $filename, $fileUpload)
  {
    return $this->service->writeFile($dir, $filename, $fileUpload);
  }
  /**
   * $url
   */
  public function remove($url)
  {
    return $this->service->remove($url);
  }
  /**
   *
   */
  public function get($url)
  {
    return $this->service->getFile($url);
  }
  /**
   * 存储指定url对应的文件
   */
  private function _storeImageUrl($url, $ext = 'jpg')
  {
    /* 下载文件 */
    $imageContent = file_get_contents($url);
    $aResponseInfo = $http_response_header;
    foreach ($aResponseInfo as $loop) {
      if (strpos($loop, "Content-disposition") !== false) {
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
    $storename = date("is") . rand(10000, 99999) . "." . $ext;
    /**
     * 写到指定位置
     */
    $newUrl = $this->writeFile($dir, $storename, $imageContent);

    return [true, $newUrl];
  }
  /**
   * 存储base64的文件数据
   * 最多可能保存3份文件：原始尺寸，中等尺寸（小于1000px），紧凑尺寸（小于500px）
   */
  private function storeBase64Image($data)
  {
    $imgInfo = $this->_getBase64ImageInfo($data);
    if ($imgInfo[0] === false) return $imgInfo;

    $pic = $imgInfo[1];
    $ext = $imgInfo[2];
    $dir = date("ymdH"); // 每个小时分一个目录
    $storename = date("is") . rand(10000, 99999) . "." . $ext; // 2位分，2位秒，5位随机数，扩展名
    /**
     * 保存原始数据
     */
    $newUrl = $this->writeFile($dir, $storename, $pic);
    /**
     * 保存压缩数据
     */
    if (method_exists($this->service, 'compactImage')) {
      $originalUrl = $newUrl;
      // 中压缩数据
      $aCompactResult = $this->service->compactImage($originalUrl, 'medium', 1200);
      if (true === $aCompactResult[0]) {
        $newUrl = $aCompactResult[1];
        // 高压缩数据
        $aCompactResult = $this->service->compactImage($originalUrl, 'compact', 480);
        if (true === $aCompactResult[0]) {
          $newUrl = $aCompactResult[1];
        }
      }
    }

    return [true, $newUrl];
  }
  /**
   * 存储base64的文件数据(头像)
   */
  private function storeBase64ImageAvatar($data, $creatorId = null)
  {
    $imgInfo = $this->_getBase64ImageInfo($data);
    if ($imgInfo[0] === false) return $imgInfo;

    $pic = $imgInfo[1];
    $ext = $imgInfo[2];
    $dir = empty($creatorId) ? date("ymdH") : $creatorId;
    $storename = date("is") . rand(10000, 99999) . "." . $ext;
    /**
     * 保存
     */
    $newUrl = $this->writeFile($dir, $storename, $pic);

    return [true, $newUrl];
  }
  /**
   * 获取base64 图片类型和主体
   */
  private function _getBase64ImageInfo($data) {
      $matches = []; 
      $rst = preg_match('/data:image\/(.+?);base64\,/', $data, $matches);
      if (1 !== $rst) return [false, '图片数据格式错误'];

      list($header, $ext) = $matches;
      $ext === 'jpeg' && $ext = 'jpg';

      // 检查格式
      if (!in_array($ext, ["png", "jpg", "jpeg", "gif", "bmp"])) return [false, '图片上传失败：只能上传png、jpg、gif、bmp格式图片'];
      
      $pic = base64_decode(str_replace($header, "", $data));

      return [true, $pic, $ext];
  }
  /**
   *
   * $img
   */
  public function storeImg($img)
  {
    if (empty($img->imgSrc) && !isset($img->serverId)) {
      return [false, '图片数据为空'];
    }
    if (isset($img->serverId)) {
      /**
       * wx jssdk
       */
      if (($snsConfig = TMS_APP::model('sns\wx')->bySite($this->siteId)) && $snsConfig->joined === 'Y') {
        $snsProxy = TMS_APP::model('sns\wx\proxy', $snsConfig);
      } else if (($snsConfig = TMS_APP::model('sns\wx')->bySite('platform')) && $snsConfig->joined === 'Y') {
        $snsProxy = TMS_APP::model('sns\wx\proxy', $snsConfig);
      } else if ($snsConfig = TMS_APP::model('sns\qy')->bySite($this->siteId)) {
        if ($snsConfig->joined === 'Y') {
          $snsProxy = TMS_APP::model('sns\qy\proxy', $snsConfig);
        }
      }
      $rst = $snsProxy->mediaGetUrl($img->serverId);
      if ($rst[0] !== false) {
        $rst = $this->_storeImageUrl($rst[1]);
      }
    } else if (isset($img->imgSrc)) {
      if (0 === strpos($img->imgSrc, 'http')) {
        /**
         * url
         */
        $rst = $this->_storeImageUrl($img->imgSrc);
      } else if (false !== strpos($img->imgSrc, TMS_UPLOAD_DIR)) {
        /**
         * 已经上传本地的
         */
        $rst = [true, $img->imgSrc];
      } else if (1 === preg_match('/data:image(.+?);base64/', $img->imgSrc)) {
        /**
         * base64
         */
        if (isset($img->imgType) && $img->imgType === 'avatar') {
          if (isset($img->creatorId)) {
            $rst = $this->storeBase64ImageAvatar($img->imgSrc, $img->creatorId);
          } else {
            $rst = $this->storeBase64ImageAvatar($img->imgSrc);
          }
        } else {
          $rst = $this->storeBase64Image($img->imgSrc);
        }
      }
    }

    if (isset($rst)) {
      return $rst;
    } else {
      return [false, '图片上传失败：只能上传png、jpg、gif、bmp格式图片'];
    }
  }
  /**
   * 保存微信录音文件
   *
   * @param $oVoice
   */
  public function storeWxVoice(&$oVoice)
  {
    if (!isset($oVoice->serverId)) {
      return [false, '录音数据为空'];
    }

    if (($snsConfig = TMS_APP::model('sns\wx')->bySite($this->siteId)) && $snsConfig->joined === 'Y') {
      $snsProxy = TMS_APP::model('sns\wx\proxy', $snsConfig);
    } else if (($snsConfig = TMS_APP::model('sns\wx')->bySite('platform')) && $snsConfig->joined === 'Y') {
      $snsProxy = TMS_APP::model('sns\wx\proxy', $snsConfig);
    } else if ($snsConfig = TMS_APP::model('sns\qy')->bySite($this->siteId)) {
      if ($snsConfig->joined === 'Y') {
        $snsProxy = TMS_APP::model('sns\qy\proxy', $snsConfig);
      }
    }

    $rst = $snsProxy->mediaGetUrl($oVoice->serverId);
    if ($rst[0] !== false) {
      $rst = $this->_storeWxVoiceUrl($rst[1], $oVoice);
    }

    $oVoice->url = $rst[1];
    unset($oVoice->serverId);

    return $rst;
  }
  /**
   * 从指定的url下载微信录音数据，并保存成文件
   */
  private function _storeWxVoiceUrl($url, &$oVoice)
  {
    /* 下载文件 */
    $voiceContent = file_get_contents($url);

    /* 文件保存到本地 */
    $tempname = uniqid();
    $localFs = new local_model($this->siteId, '_temp');
    $amr = $localFs->writeFile('', $tempname . '.amr', $voiceContent);
    if (false === $amr) return [false, '写入文件失败（1）'];

    $mp3 = str_replace('amr', 'mp3', $amr);

    /* 将amr转换成mp3格式 */
    $command = "ffmpeg -i $amr $mp3";
    $output = [];
    exec($command, $output);
    //if (!empty($output)) return [false, json_encode($output)];

    $voiceContent = $localFs->read($tempname . '.mp3');
    if (empty($voiceContent)) return [false, '转换文件格式失败'];
    $oVoice->size = strlen($voiceContent);
    $oVoice->type = 'audio/mpeg';

    /* 写到指定位置 */
    $dir = date("ymdH"); // 每个小时分一个目录
    $storename = date("is") . rand(10000, 99999) . ".mp3";
    $newUrl = $this->writeFile($dir, $storename, $voiceContent);

    /* 删除临时文件 */
    $localFs->delete($tempname . '.amr');
    $localFs->delete($tempname . '.mp3');

    return [true, $newUrl];
  }
}
