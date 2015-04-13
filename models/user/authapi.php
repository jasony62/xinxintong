<?php
class authapi_model extends TMS_MODEL {
    /**
     * 认证接口定义
     */
    public function byId($authid, $fields='*') 
    {
        $q = array(
            $fields,
            'xxt_member_authapi',
            "authid=$authid",
        );
        $authapi = $this->query_obj_ss($q); 

        if (!empty($authapi->extattr))
            $authapi->extattr = json_decode($authapi->extattr);
        else
            $authapi->extattr = array();

        return $authapi;
    }
    /**
     *
     */
    public function byUrl($mpid, $url, $fields='*')
    {
        $q = array(
            $fields,
            'xxt_member_authapi',
            "mpid='$mpid' and url='$url'"
        );
        $authapi = $this->query_obj_ss($q);

        return $authapi;
    }
    /**
     * 进入用户身份认证页的说明
     */
    public function getEntryStatement($authid, $mpid, $src, $openid)
    {
        $authapi = $this->byId($authid, 'url,entry_statement'); 
        $r = $authapi->entry_statement;
        if (false !== strpos($r, '{{authapi}}')) {
            // auth page's url
            $url = "http://" . $_SERVER['HTTP_HOST'];
            $url .= $authapi->url;
            $url .= "?mpid=$mpid&authid=$authid&src=$src&openid=$openid";
            // require auth reply
            $r = str_replace('{{authapi}}', $url, $authapi->entry_statement);
        }

        return $r;
    }
    /**
     * 用户身份认证信息没有通过验证
     *
     * $authid
     * $runningMpid
     */
    public function getNotpassStatement($authid, $runningMpid, $src=null, $openid=null)
    {
        $authapi = $this->byId($authid, 'url,notpass_statement'); 
        $r = $authapi->notpass_statement;
        if (false !== strpos($r, '{{authapi}}')) {
            // auth page's url
            $url = "http://" . $_SERVER['HTTP_HOST'];
            $url .= $authapi->url;
            $url .= "?mpid=$runningMpid&authid=$authid";
            if (!empty($src) && !empty($openid))
                $url .= "&src=$src&openid=$openid";

            // require auth reply
            $r = str_replace('{{authapi}}', $url, $authapi->notpass_statement);
        }

        return $r;
    }
    /**
     * 用户身份认证信息没有在白名单中
     */
    public function getAclStatement($authid, $runningMpid, $src=null, $openid=null)
    {
        $authapi = $this->byId($authid, 'url,acl_statement'); 
        $r = $authapi->acl_statement;
        if (false !== strpos($r, '{{authapi}}')) {
            // auth page's url
            $url = "http://" . $_SERVER['HTTP_HOST'];
            $url .= $authapi->url;
            $url .= "?mpid=$runningMpid&authid=$authid";
            if (!empty($src) && !empty($openid))
                $url .= "&src=$src&openid=$openid";

            // require auth reply
            $r = str_replace('{{authapi}}', $url, $authapi->acl_statement);
        }

        return $r;
    }
}
