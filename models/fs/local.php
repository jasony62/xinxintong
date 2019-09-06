<?php
class local_model {

    // 当前公众平台
    protected $siteId;
    // 用于分割不同类型的存储资源
    protected $bucket;
    // 起始存储位置
    protected $rootDir;

    public function __construct($siteId, $bucket) {
        $this->siteId = $siteId;

        $this->bucket = $bucket;

        $this->rootDir = TMS_UPLOAD_DIR . "$this->siteId" . '/' . TMS_MODEL::toLocalEncoding($bucket);
        /* 检查根目录是否存在，不存在就创建 */
        !file_exists($this->rootDir) && mkdir($this->rootDir, 0777, true);
    }

    public function __get($attr) {
        if (isset($this->{$attr})) {
            return $this->{$attr};
        } else {
            return null;
        }
    }
    /**
     * 返回指定文件名的文件的存储位置
     */
    public function getPath($filename, $bEncoding = true) {
        $path = $this->rootDir;
        if (strpos($filename, '/') !== 0) {
            $path .= '/';
        }
        if ($bEncoding) {
            $path .= TMS_MODEL::toLocalEncoding($filename);
        } else {
            $path .= $filename;
        }

        return $path;
    }
    /**
     * 将上传的文件文件保存在指定位置
     *
     * return bool
     */
    public function upload($filename, $destName, $destDir) {
        //$absDir = $this->rootDir . '/' . TMS_MODEL::toLocalEncoding($destDir);
        $absDir = $this->rootDir . '/' . $destDir;
        // 目录是否存在
        !is_dir($absDir) && mkdir($absDir, 0777, true);
        // 文件的完整路径
        $filePath = $absDir . '/' . TMS_MODEL::toLocalEncoding($destName);
        // move the temporary file
        return move_uploaded_file($filename, $filePath);
    }
    /**
     *
     */
    public function getListByPath($dir) {
        //return $this->storage->getListByPath($this->domain, $this->siteId . '/' . $dir, 1000);
    }
    /**
     *
     */
    public function read($filename) {
        $absPath = $this->rootDir . '/' . TMS_MODEL::toLocalEncoding($filename);
        return file_get_contents($absPath);
    }
    /**
     * 创建并打开文件
     */
    public function createAndOpen($filename) {
        /* 文件的完整路径 */
        $absPath = $this->rootDir . (strpos($filename, '/') === 0 ? '' : '/') . $filename;
        /* 文件目录是否存在，不存在则创建 */
        $dirname = dirname($absPath);
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }

        $fp = fopen($absPath, 'w');

        return $fp;
    }
    /**
     *
     * @param string $filename
     * @param string $content
     */
    public function write($filename, $content) {
        /* 文件的完整路径 */
        $absPath = $this->rootDir . '/' . $filename;

        /* 文件目录是否存在，不存在则创建 */
        $dirname = dirname($absPath);
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }

        /* 将内容写入文件 */
        if (is_resource($content)) {
            $segs = explode('.', $filename);
            $ext = end($segs);
            if (!empty($ext) && in_array($ext, ['jpg', 'png'])) {
                $func = 'image' . ['jpg' => 'jpeg', 'png' => 'png'][$ext];
                if ($func($content, $absPath)) {
                    return $absPath;
                }
            }
        } else if (is_string($content)) {
            if (($fp = fopen($absPath, 'w')) !== false) {
                fwrite($fp, $content);
                fclose($fp);
                return $absPath;
            }
        }

        return false;
    }
    /**
     *
     */
    public function delete($filename) {
        $abs = $this->rootDir . '/' . TMS_MODEL::toLocalEncoding($filename);
        if (file_exists($abs)) {
            return unlink($abs);
        } else {
            return false;
        }
    }
    /**
     * 和alioss兼容
     */
    public function writeFile($dir, $filename, $content) {
        $filename = $dir . '/' . $filename;

        return $this->write($filename, $content);
    }
    /**
     * 和alioss兼容
     */
    public function remove($url) {
        die('not support.');
    }
    /**
     *
     */
    public function getFile($url) {
        die('not support.');
    }
    /**
     * 压缩图片
     */
    public function compactImage($imageUrl) {
        $source_info = getimagesize($imageUrl);
        if (false === $source_info) {
            return [false];
        }

        list($source_width, $source_height) = $source_info;

        if ($source_width > 500 || $source_height > 500) {
            $source_mime = $source_info['mime'];
            switch ($source_mime) {
            case 'image/gif':
                $source_image = imagecreatefromgif($imageUrl);
                break;

            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($imageUrl);
                break;

            case 'image/png':
                $source_image = imagecreatefrompng($imageUrl);
                break;
            }
            if (isset($source_image)) {
                // 压缩后的图片
                $target_width = $source_width;
                $target_height = $source_height;
                while ($target_width > 500 || $target_height > 550) {
                    $target_width = (int) ($target_width / 2);
                    $target_height = (int) ($target_height / 2);
                }
                $target_image = imagecreatetruecolor($target_width, $target_height);
                imagecopyresampled($target_image, $source_image, 0, 0, 0, 0, $target_width, $target_height, $source_width, $source_height);

                // 压缩后的文件名
                $segs = explode('.', $imageUrl);
                $ext = end($segs);
                $compactImageUrl = str_replace($ext, 'compact.' . $ext, $imageUrl);
                $newUrl = $this->write($compactImageUrl, $target_image);

                imagedestroy($target_image);

                return [true, $newUrl];
            }
        }

        return [false];
    }
}