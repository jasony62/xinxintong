<?php
class saestore_model {
    
    protected $domain;

    public function __construct($mpid, $domain='attachment') 
    {
        $this->domain = $domain;
        
        $this->storage = new SaeStorage();
    }
    /**
     * 上传文件
     */
    public function upload($destFile, $srcFile)
    {
        return $this->storage->upload($this->domain, $destFile, $srcFile);
    }
    /**
     *
     */
    public function getListByPath($dir)
    {
        return $this->storage->getListByPath($this->domain, $dir, 1000);   
    }
    
    public function read($filename) 
    {
        return $this->storage->read($this->domain, $filename);
    }
    
    public function write($filename, $content) 
    {
        return $this->storage->write($this->domain, $filename, $content);
    }
    
    public function delete($filename) 
    {
        return $this->storage->delete($this->domain, $filename);
    }
}
