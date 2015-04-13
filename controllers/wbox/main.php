<?php
require_once dirname(dirname(__FILE__)).'/xxt_base.php';

class main extends xxt_base {
    /**
     *
     */
    private $box;
    /**
     *
     */
    public function __construct() 
    {
        if (isset($_SESSION['WRITER_BOX']))
            $this->box = $_SESSION['WRITER_BOX'];
        else 
            $this->box = false;
    }
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * path = /writer/$code
     */
    public function view_action($path) 
    {
        /**
         * 获得公众号给予投稿人的code
         * 通过code可以获得mpid，src，和openid，以及登陆方式
         * 登陆方式有3中：不验证，验证码，二维码
         *
         */
        $code = str_replace('/wbox/', '', $path);

        $q = array(
            'code,mpid,src,openid,auth_mode,auth_code',
            'xxt_writer_box',
            "code='$code'"
        );
        if (!($this->box = $this->model()->query_obj_ss($q)))
            die('invalid parameters!');
        /**
         * 将通过验证的数据保存在session中
         */
        $_SESSION['WRITER_BOX'] = $this->box;

        if ($this->box->auth_mode == 0) {
            /**
             * 不验证
             */
        } else if ($this->box->auth_mode == 1) {
            /**
             * 验证码验证
             */
            TPL::assign('auth_mode', 1);
        } else if ($this->box->auth_mode == 2) {
            /**
             * 二维码验证
             */
            TPL::assign('auth_mode', 2);
        }

        parent::view_action($path);
    }
    /**
     * 身份认证
     */
    public function auth_action()
    {
        $posted = $this->getPostJson();

        if ($this->box === false)
            die('invalid data.');

        if (empty($this->box->auth_code)) {
            $q = array(
                'auth_code',
                'xxt_writer_box',
                "code='{$this->box->code}'"
            );
            if (!($this->box->auth_code = $this->model()->query_val_ss($q)))
                return new ResponseError('数据错误！');
        }
        if ($this->box->auth_code === $posted->ac) {
            /**
             * 验证码只能使用一次，验证通过后清除
             */
            $rst = $this->model()->update(
                'xxt_writer_box',
                array('auth_code'=>''),
                "code='{$this->box->code}'"
            );
            return new ResponseData($rst);
        } else
            return new ResponseError('验证码错误！');
    }
    /**
     * 获得投稿图文的列表
     */
    public function list_action($tag=null, $page=1, $size=15, $order='time', $fields=array())
    {
        if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
            /**
             * fields
             */
            if (empty($fields)) {
                $s = 'a.id,a.title,a.code,a.pic,a.summary,a.url,a.body,a.create_at,a.modify_at,a.used';
            } else {
                if (is_string($fields))
                    $fields = explode(',', $fields);
                $s = 'a.'.implode(',a.', array_diff($fields, array('tag')));
            }
            /**
             * where
             */
            $w = "a.mpid='{$this->box->mpid}' and a.writer='{$this->box->openid}' and a.src='{$this->box->src}' and a.state=1";
            if (empty($tag)) {
                $q = array($s, 'xxt_article a', $w);
                if ($order === 'title')
                    $q2['o'] = 'CONVERT(a.title USING gbk ) COLLATE gbk_chinese_ci';
                else 
                    $q2['o'] = 'a.modify_at desc';
            } else {
                /**
                 * 按标签过滤
                 */
                is_array($tag) && $tag = implode(',',$tag); 
                $w .= " and a.mpid=at.mpid and a.id=at.res_id and at.tag_id in($tag)";
                $q = array($s, 'xxt_article a,xxt_article_tag at', $w);
                $q2['g'] = 'a.id';
                if ($order === 'title')
                    $q2['o'] = 'count(*),CONVERT(a.title USING gbk ) COLLATE gbk_chinese_ci';
                else 
                    $q2['o'] = 'count(*) desc,a.modify_at desc';
            }
            $q2['r'] = array('o'=>($page-1)*$size, 'l'=>$size);
            if ($articles = $this->model()->query_objs_ss($q, $q2)) {
                /**
                 * 获得总数
                 */
                $q[0] = 'count(*)';
                $amount = (int)$this->model()->query_val_ss($q);
                /**
                 * 获得每篇文章的标签数据
                 */
                if (empty($fields) || in_array('tag', $fields)) {
                    foreach ($articles as &$a) {
                        $ids[] = $a->id;
                        $map[$a->id] = &$a;
                    }
                    $rels = $this->model('tag')->tagsByRes($ids, 'article');
                    foreach ($rels as $aid => &$tags) {
                        $map[$aid]->tags = $tags;
                    }
                }
                return new ResponseData(array($articles, $amount)); 
            }
            return new ResponseData(array(array(),0));
        } else {
            parent::view_action('/wbox/list');
        }
    }
}
