<?php
namespace mi;

class matter_page_base {
    /**
     *
     */
    protected function __construct(&$matter, $openid)
    {
        $this->matter = $matter;
        $this->openid = $openid;
    }
    /**
     * 返回素材对象
     *
     * 至少包含：
     * mpid
     *
     */
    public function &getMatter()
    {
        return $this->matter;
    }
}
