<?php
class matter_page_base {
    /**
     *
     */
    protected function __construct(&$matter, $openid, $src)
    {
        $this->matter = $matter;
        $this->openid = $openid;
        $this->src = $src;
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
