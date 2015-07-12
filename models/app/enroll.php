<?php
namespace app;

require_once dirname(dirname(__FILE__)).'/matter/enroll.php';

class enroll_model extends \matter\enroll_model {
    /**
     *
     * $aid string
     * $cascaded array []
     */
    public function &byId($aid, $fields='*') 
    {
        $q = array(
            $fields, 
            'xxt_enroll', 
            "id='$aid'"
        );
        if ($e = $this->query_obj_ss($q)) {
            ($fields === '*' || strpos($fields, 'entry_rule')) && $e->entry_rule = json_decode($e->entry_rule);
            /**
             * 页面内容
             */
            if ($fields === '*') {
                // form page
                $page = \TMS_APP::model('code/page')->byId($e->form_code_id, 'html,css,js');
                $page->id = 0;
                $page->name = 'form';
                $page->code_id = $e->form_code_id;
                $e->pages['form'] = $page;
                // other page
                $extraPages = $this->getPages($aid);
                foreach ($extraPages as $ep)
                    $e->pages[$ep->name] = $ep;
            }
        }

        return $e;
    }
    /**
     *
     */
    public function getPage($aid, $apid)
    {
        $select = 'ap.*,cp.html,cp.css,cp.js';
        $from = 'xxt_enroll_page ap,xxt_code_page cp';
        $where = "ap.aid='$aid' and ap.id=$apid and ap.code_id=cp.id";

        $q = array($select, $from, $where);

        $ep = $this->query_obj_ss($q);

        $code = \TMS_APP::model('code/page')->byId($ep->code_id);
        $ep->html = $code->html;
        $ep->css = $code->css;
        $ep->js = $code->js;
        $ep->ext_js = $code->ext_js;
        $ep->ext_css = $code->ext_css;

        return $ep;
    }
    /**
     *
     */
    public function getPages($aid)
    {
        $select = 'ap.*';
        $from = 'xxt_enroll_page ap';
        $where = "ap.aid='$aid'";

        $q = array($select, $from, $where);

        $eps = $this->query_objs_ss($q);
        foreach ($eps as &$ep) {
            $code = \TMS_APP::model('code/page')->byId($ep->code_id);
            $ep->html = $code->html;
            $ep->css = $code->css;
            $ep->js = $code->js;
            $ep->ext_js = $code->ext_js;
            $ep->ext_css = $code->ext_css;
        }

        return $eps;
    }
    /**
     * 创建活动页面
     */
    public function addPage($mpid, $aid, $data=null)
    {
        $uid = \TMS_CLIENT::get_client_uid();

        $code = \TMS_APP::model('code/page')->create($uid);

        $newPage = array(
            'mpid' => $mpid,
            'aid' => $aid,
            'creater' => $uid,
            'create_at' => time(),
            'type' => isset($data['type']) ? $data['type'] : 'V',
            'title' => isset($data['title']) ? $data['title'] : '新页面',
            'name' => 'z'.time(),
            'code_id' => $code->id
        );

        $apid = $this->insert('xxt_enroll_page', $newPage, true);

        $newPage['id'] = $apid;
        $newPage['html'] = '';
        $newPage['css'] = '';
        $newPage['js'] = '';

        return (object)$newPage;
    }
    /**
     *
     */
    public function delPage($aid, $apid)
    {
        $page = $this->getPage($aid, $apid);

        TMS_APP::model('code/page')->remove($page->code_id);

        $rst = $this->delete('xxt_enroll_page', "aid='$aid' and id=$apid");

        return $rst;
    }
    /**
     *
     * $mpid
     * $aid
     */
    public function getRounds($mpid, $aid)
    {
        $q = array(
            '*',
            'xxt_enroll_round',
            "mpid='$mpid' and aid='$aid'"
        );
        $q2 = array('o'=>'create_at desc');

        $rounds = $this->query_objs_ss($q, $q2);

        return $rounds;
    }
    /**
     *
     * $mpid
     * $aid
     */
    public function getLastRound($mpid, $aid)
    {
        $q = array(
            '*',
            'xxt_enroll_round',
            "mpid='$mpid' and aid='$aid'"
        );
        $q2 = array(
            'o'=>'create_at desc',
            'r'=>array('o'=>0,'l'=>1)
        );

        $rounds = $this->query_objs_ss($q, $q2);

        if (count($rounds) === 1)
            return $rounds[0];
        else
            return false;
    }
    /**
     * 获得启用状态的轮次
     * 一个登记活动只能有一个启用状态的轮次
     *
     * $mpid
     * $aid
     */
    public function getActiveRound($mpid, $aid)
    {
        $q = array(
            '*',
            'xxt_enroll_round',
            "mpid='$mpid' and aid='$aid' and state=1"
        );

        $round = $this->query_obj_ss($q);

        return $round;
    }
    /**
     * 活动登记（不包括登记数据）
     *
     * $mpid 运行的公众号，和openid和src相对应
     * $act
     * $openid
     * $vid
     * $mid
     */
    public function enroll($mpid, $act, $openid, $vid='', $mid='')
    {
        $ek = $this->genEnrollKey($mpid, $act->id);
        $i = array(
            'aid' => $act->id,
            'mpid' => $mpid,
            'enroll_at' => time(),
            'enroll_key' => $ek,
            'openid' => $openid,
            'vid' => $vid,
            'mid' => $mid,
        );

        if ($activeRound = $this->getActiveRound($mpid, $act->id))
            $i['rid'] = $activeRound->rid;

        $this->insert('xxt_enroll_record', $i, false);

        return $ek;
    }
    /**
     * 生成活动登记的key
     */
    public function genEnrollKey($mpid, $aid)
    {
        return md5(uniqid().$mpid.$aid);
    }
    /**
     * 检查用户是否已经登记
     *
     * 如果设置轮次，只坚持当前轮次是否已经登记
     */
    public function hasEnrolled($mpid, $aid, $openid)
    {
        if (empty($mpid) || empty($aid) || empty($openid))
            return false;
            
        $q = array(
            'count(*)',
            'xxt_enroll_record',
            "mpid='$mpid' and aid='$aid' and openid='$openid'"
        );

        if ($activeRound = $this->getActiveRound($mpid, $aid))
            $q[2] .= " and rid='$activeRound->rid'";

        $rst = (int)$this->query_val_ss($q);

        return $rst > 0;
    }
    /**
     * 获得指定用户最后一次报名的key
     *
     * 如果设置轮次，只坚持当前轮次的情况
     */
    public function getLastEnrollKey($mpid, $aid, $openid)
    {
        $q = array(
            'enroll_key',
            'xxt_enroll_record',
            "mpid='$mpid' and aid='$aid' and openid='$openid'"
        );
        if ($activeRound = $this->getActiveRound($mpid, $aid))
            $q[2] .= " and rid='$activeRound->rid'";
        $q2 = array(
            'o'=>'enroll_at desc',
            'r'=>array('o'=>0,'l'=>1),
        );
        $rolls = $this->query_objs_ss($q, $q2);

        if (count($rolls) === 1)
            return $rolls[0]->enroll_key;
        else
            return false;
    }
    /**
     * 获得用户的登记清单
     */
    public function getRecordById($ek)
    {
        $q = array(
            'e.enroll_key,e.enroll_at,e.signin_at,e.score,e.openid,f.nickname,e.rid',
            'xxt_enroll_record e left join xxt_fans f on e.mpid=f.mpid and e.openid=f.openid',
            "e.enroll_key='$ek'"
        );

        if ($record = $this->query_obj_ss($q))
            $record->data = $this->getRecordData($ek);

        return $record;
    }
    /**
     * 获得用户的登记清单
     */
    public function getRecordList($mpid, $aid, $openid)
    {
        if (!empty($openid)) {
            $q = array(
                '*',
                'xxt_enroll_record',
                "mpid='$mpid' and aid='$aid' and openid='$openid'"
            );
            if ($activeRound = $this->getActiveRound($mpid, $aid))
                $q[2] .= " and rid='$activeRound->rid'";

            $q2 = array('o'=>'enroll_at desc');

            $list = $this->query_objs_ss($q, $q2);

            return $list;
        } else
            return false;
    }
    /**
     * 获得一条登记记录的数据
     */
    public function getRecordData($enrollKey)
    {
        $q = array(
            'name,value',
            'xxt_enroll_record_data',
            "enroll_key='$enrollKey'"
        );
        $cusdata = array();
        $cdata = $this->query_objs_ss($q);
        if (count($cdata) > 0) {
            foreach ($cdata as $cd)
                $cusdata[$cd->name] = $cd->value;
        }
        return $cusdata;
    }
    /**
     * 保存登记的数据
     */
    public function setRollData($runningMpid, $aid, $ek, $data, $clean=false)
    {
        if ($clean)
            $this->delete('xxt_enroll_record_data', "aid='$aid' and enroll_key='$ek'");
        else
            $fields = $this->query_vals_ss(array('name','xxt_enroll_record_data', "aid='$aid' and enroll_key='$ek'"));

        if (!empty($data)) foreach ($data as $n=>$v) {
            /**
             * 插入自定义属性
             */
            if (is_array($v) && (isset($v[0]->serverId) || isset($v[0]->imgSrc))) {
                $vv = array();
                $fsuser = \TMS_APP::model('fs/user', $runningMpid);
                foreach ($v as $img) {
                    $rst = $fsuser->storeImg($img);
                    if (false === $rst[0])
                        return $rst;
                    $vv[] = $rst[1];
                }
                $vv = implode(',', $vv); 
            } else {
                /**
                 * 文本和选择题
                 */
                $vv = is_string($v) ? $this->escape($v) : implode(',', array_keys(array_filter((array)$v,function($i){return $i;})));
            }
            if (!empty($fields) && in_array($n, $fields)) {
                $this->update(
                    'xxt_enroll_record_data', 
                    array('value' => $vv), 
                    "aid='$aid' and enroll_key='$ek' and name='$n'"
                );
                unset($fields[array_search($n, $fields)]);
            } else {
                $ic = array(
                    'aid' => $aid,
                    'enroll_key' => $ek,
                    'name' => $n,
                    'value' => $vv
                );
                $this->insert('xxt_enroll_record_data', $ic, false);
            }
        }
        return array(true);
    } 
    /**
     * 评论
     */
    public function getRecordRemarks($ek, $page=1, $size=30)
    {
        $q = array(
            'r.*,f.nickname',
            'xxt_enroll_record e, xxt_enroll_record_remark r, xxt_fans f',
            "e.enroll_key='$ek' and e.enroll_key=r.enroll_key and e.mpid=f.mpid and r.openid=f.openid"
        );
        $q2 = array(
            'o' => 'r.create_at',
            'r' => array('o'=>($page-1)*$size, 'l'=>$size)
        );

        $remarks = $this->query_objs_ss($q, $q2);

        return $remarks;
    }
    /*
    * 所有发表过评论的用户
    */
    public function &getRecordRemarkers($ek)
    {
        $q = array(
            'distinct openid',
            'xxt_enroll_record_remark',
            "enroll_key='$ek'");

        $remarkers = $this->query_objs_ss($q);

        return $remarkers;
    }
    /**
     * 活动签到
     *
     * 如果用户已经做过活动登记，那么设置签到时间
     * 如果用户没有做个活动登记，那么要先产生一条登记记录，并记录签到时间
     */
    public function signin($mpid, $aid, $openid)
    {
        if ($ek = $this->getLastEnrollKey($mpid, $aid, $openid)) {
            $rst = $this->update(
                'xxt_enroll_record',
                array('signin_at'=>time()),
                "mpid='$mpid' and aid='$aid' and enroll_key='$ek'"
            );
            return true;
        } else {
            $ek = $this->enroll($mpid, (object)array('id'=>$aid), $openid);
            $rst = $this->update(
                'xxt_enroll_record',
                array('signin_at'=>time()),
                "mpid='$mpid' and aid='$aid' and enroll_key='$ek'"
            );
            return false;
        }

    }
    /**
     * 活动报名名单
     *
     * todo 临时
     *
     */
    public function getLotteryRoll($aid, $rid) 
    {
        /**
         * 获得活动的定义
         */
        $q = array(
            'a.wxyx_only,a.fans_only,a.access_control,p.html form_html',
            'xxt_enroll a,xxt_code_page p',
            "a.id='$aid' and a.form_code_id=p.id"
        );
        $act = $this->query_obj_ss($q);
        // 返回的结果
        $result = array(array(),array());

        $w = "e.aid='$aid'";
        $w .= " and not exists(select 1 from xxt_enroll_lottery l where e.enroll_key=l.enroll_key)";
        // todo 逻辑有问题，如果要求既是粉丝又是会员怎么办？
        if ($act->wxyx_only === 'Y') {
            $q = array(
                'e.id,e.enroll_key,f.nickname,e.openid,e.enroll_at,signin_at,e.tags',
                'xxt_enroll_record e left join xxt_fans f on e.mpid=f.mpid and e.openid=f.openid',
                $w
            );
        } else if ($act->fans_only === 'Y') {
            $q = array(
                'e.id,e.enroll_key,f.nickname,e.openid,e.enroll_at,signin_at,e.tags',
                'xxt_enroll_record e left join xxt_fans f on e.mpid=f.mpid and e.openid=f.openid',
                $w
            );
        } else {
            $q = array(
                'e.id,e.enroll_key,e.openid,e.enroll_at,signin_at,e.tags',
                'xxt_enroll_record e',
                $w
            );
        }
        $q2['o'] = 'e.enroll_at desc';
        /**
         * 获得填写的登记数据
         */ 
        if ($roll = $this->query_objs_ss($q, $q2)) {
            /**
             * 获得自定义数据的值
             */
            foreach ($roll as &$r) {
                $qc = array(
                    'name,value',
                    'xxt_enroll_record_data',
                    "enroll_key='$r->enroll_key'"
                );
                $cds = $this->query_objs_ss($qc);
                foreach ($cds as $cd) {
                    $r->{$cd->name} = $cd->value;
                }
            }
            /**
             * 删除没有填写报名信息数据
             */
            if ($roll) {
                $roll2 = array();
                foreach ($roll as $r2) {
                    if (empty($r2->name) && empty($r2->mobile))
                        continue;
                    $roll2[] = $r2;
                }
                $result[0] = $roll2;
            }
        }
        /**
         * 已经抽中的人
         */
        $q = array(
            'l.*,e.enroll_key',
            'xxt_enroll_lottery l,xxt_enroll_record e',
            "l.aid='$aid' and l.aid=e.aid and l.enroll_key=e.enroll_key and round_id='$rid'"
        );
        $q2 = array('o'=>'draw_at');
        if ($winners = $this->query_objs_ss($q, $q2)) {
            /**
             * 获得自定义数据的值
             */
            foreach ($winners as &$w) {
                $qc = array(
                    'name,value',
                    'xxt_enroll_record_data',
                    "enroll_key='$w->enroll_key'"
                );
                $cds = $this->query_objs_ss($qc);
                foreach ($cds as $cd) {
                    $w->{$cd->name} = $cd->value;
                }
            }
            $result[1] = $winners;
        }

        return $result;
    }
    /**
     * 活动中奖名单
     *
     * todo 临时
     *
     */
    public function getLotteryRounds($aid) 
    {
        /**
         * 获得活动的定义
         */
        $q = array(
            'wxyx_only,fans_only,access_control',
            'xxt_enroll',
            "id='$aid'"
        );
        $act = $this->query_obj_ss($q);
        /**
         * 获得抽奖的轮次
         */
        $q = array(
            '*',
            'xxt_enroll_lottery_round',
            "aid='$aid'"
        );
        $rounds = $this->query_objs_ss($q);

        return $rounds;
    }
    /**
     * 活动中奖名单
     *
     * todo 临时
     *
     */
    public function getLotteryWinners($aid, $rid=null) 
    {
        /**
         * 获得活动的定义
         */
        $q = array(
            'wxyx_only,fans_only,access_control',
            'xxt_enroll',
            "id='$aid'"
        );
        $act = $this->query_obj_ss($q);
        /**
         * 已经抽中的人
         */
        $q = array(
            'l.*,r.title,e.enroll_key',
            'xxt_enroll_lottery l,xxt_enroll_lottery_round r,xxt_enroll_record e',
            "l.aid='$aid' and l.round_id=r.round_id and l.aid=e.aid and l.enroll_key=e.enroll_key"
        );
        if (!empty($rid)) $q[2] .= " and l.round_id='$rid'";
        $q2 = array('o'=>'l.round_id,l.draw_at');
        if ($winners = $this->query_objs_ss($q, $q2)) {
            /**
             * 获得自定义数据的值
             */
            foreach ($winners as &$w) {
                $qc = array(
                    'name,value',
                    'xxt_enroll_record_data',
                    "enroll_key='$w->enroll_key'"
                );
                $cds = $this->query_objs_ss($qc);
                foreach ($cds as $cd)
                    $w->{$cd->name} = $cd->value;
            }
        }

        return $winners;
    }
    /**
     * 清除一条活动报名名单
     *
     * todo 是否应该清除日志？
     */
    public function removeRoll($aid, $key) 
    {
        $this->delete('xxt_enroll_record_data', "aid='$aid' and enroll_key='$key'"); 

        $rst = $this->delete('xxt_enroll_record', "aid='$aid' and enroll_key='$key'"); 

        return $rst;
    }
    /**
     * 清除活动报名名单
     *
     * todo 是否应该清除日志？
     */
    public function cleanRoll($aid) 
    {
        $this->delete('xxt_enroll_record_data', "aid='$aid'"); 

        $rst = $this->delete('xxt_enroll_record', "aid='$aid'"); 

        return $rst;
    }
    /**
     * 获得登记数据定义
     *
     * 数据项的定义需要从表单中获取
     * 表单中定义了数据项的id和name
     * 定义数据项都是input，所以首先应该将页面中所有input元素提取出来
     * 每一个元素中都有ng-model和title属相，ng-model包含了id，title是名称
     */
    public function getSchema($html) 
    {
        $defs = array();

        if (empty($html)) return $defs;

        if (preg_match_all('/<(div|li|option).+?wrap=.+?>.+?<\/(div|li|option)/i', $html, $wraps)) {
            $wraps = $wraps[0];
            foreach ($wraps as $wrap) {
                $def=array();$inp=array();$title=array();$ngmodel=array();$opval=array();$optit=array();
                if (!preg_match('/<input.+?>/', $wrap, $inp) && !preg_match('/<option.+?>/', $wrap, $inp) && !preg_match('/<textarea.+?>/', $wrap, $inp) && !preg_match('/wrap="img".+?>/', $wrap, $inp))
                    continue;
                $inp = $inp[0];
                if (preg_match('/title="(.*?)"/', $inp, $title))
                    $title = $title[1]; 
                if (preg_match('/type="radio"/', $inp)) {
                    /**
                     * for radio group.
                     */
                    if (preg_match('/ng-model="data\.(.+?)"/', $inp, $ngmodel))
                        $id = $ngmodel[1];

                    if (empty($id)) continue;

                    $existing = false;
                    foreach ($defs as &$d)
                        if ($existing = ($d['id'] === $id))
                            break;

                    if (!$existing) {
                        $defs[] = array('title'=>$title,'id'=>$id,'ops'=>array());
                        $d = &$defs[count($defs)-1];
                    }
                    if (preg_match('/value="(.+?)"/', $inp, $opval))
                        $op['v'] = $opval[1];
                    if (preg_match('/data-label="(.+?)"/', $wrap, $optit)) 
                        $op['l'] = $optit[1];
                    $d['ops'][] = $op;
                } else if (preg_match('/<option/', $inp)) {
                    /**
                     * for radio group.
                     */
                    if (preg_match('/name="data\.(.+?)"/', $inp, $ngmodel))
                        $id = $ngmodel[1];

                    if (empty($id)) continue;

                    $existing = false;
                    foreach ($defs as &$d)
                        if ($existing = ($d['id'] === $id))
                            break;

                    if (!$existing) {
                        $defs[] = array('title'=>$title,'id'=>$id,'ops'=>array());
                        $d = &$defs[count($defs)-1];
                    }
                    if (preg_match('/value="(.+?)"/', $inp, $opval))
                        $op['v'] = $opval[1];
                    if (preg_match('/data-label="(.+?)"/', $wrap, $optit)) 
                        $op['l'] = $optit[1];
                    $d['ops'][] = $op;
                } else if (preg_match('/type="checkbox"/', $inp)) {
                    /**
                     * for checkbox group.
                     */
                    if (preg_match('/ng-model="data\.(.+?)\.(\d+?)"/', $inp, $ngmodel)) {
                        $id = $ngmodel[1];
                        $opval = $ngmodel[2];
                    }

                    if (empty($id) || !isset($opval)) continue;

                    $existing = false;
                    foreach ($defs as &$d)
                        if ($existing = ($d['id'] === $id))
                            break;

                    if (!$existing) {
                        $defs[] = array('title'=>$title,'id'=>$id,'ops'=>array());
                        $d = &$defs[count($defs)-1];
                    }
                    $op['v'] = $opval;
                    if (preg_match('/data-label="(.+?)"/', $wrap, $optit)) 
                        $op['l'] = $optit[1];
                    $d['ops'][] = $op;
                } else if (preg_match('/ng-repeat="img in data\.(.+?)"/', $inp, $ngrepeat)) {
                    $id = $ngrepeat[1];
                    $defs[] = array('title'=>$title,'id'=>$id,'type'=>'img');
                } else {
                    /**
                     * for text input/textarea.
                     */
                    if (preg_match('/ng-model="data\.(.+?)"/', $inp, $ngmodel))
                        $id = $ngmodel[1];

                    if (empty($id)) continue;

                    $defs[] = array('title'=>$title,'id'=>$id);
                }
            }
        }

        return $defs;
    }
    /**
     * 活动报名名单
     *
     * 1、如果活动仅限会员报名，那么要叠加会员信息
     * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
     *
     * $mpid
     * $aid
     * $options
     * --creater openid 
     * --visitor openid 
     * --page
     * --size
     * --rid 轮次id
     * --kw 检索关键词
     * --by 检索字段
     * 
     *
     * return
     * [0] 数据列表
     * [1] 数据总条数
     * [2] 数据项的定义
     */
    public function getRecords($mpid, $aid, $options=null) 
    {
        if ($options) {
            is_array($options) && $options = (object)$options;
            $creater = isset($options->creater) ? $options->creater : null;
            $visitor = isset($options->visitor) ? $options->visitor : '';
            $orderby = isset($options->orderby) ? $options->orderby : '';
            $page = isset($options->page) ? $options->page : null;
            $size = isset($options->size) ? $options->size : null;
            $rid = null;
            if (!empty($options->rid)) {
                if ($options->rid==='ALL')
                    $rid = null;
                else if (!empty($options->rid))
                    $rid = $options->rid;
            } else if ($activeRound = $this->getActiveRound($mpid, $aid))
                $rid = $activeRound->rid;

            $kw = isset($options->kw) ? $options->kw : null; 
            $by = isset($options->by) ? $options->by : null;
            $contain = isset($options->contain) ? $options->contain : null;
        }
        /**
         * 获得活动的定义
         */
        $q = array(
            'a.wxyx_only,a.fans_only,a.access_control,p.html form_html',
            'xxt_enroll a,xxt_code_page p',
            "a.id='$aid' and a.form_code_id=p.id"
        );
        $act = $this->query_obj_ss($q);
        
        $result = array(); // 返回的结果
        /**
         * 获得数据项定义
         */
        $result[2] = $this->getSchema($act->form_html);
        $extraPages = $this->getPages($aid);
        foreach ($extraPages as $ep)
            if ($ep->type === 'I') {
                $defs = $this->getSchema($ep->html);
                $result[2] = array_merge($result[2], $defs);
            }

        $contain = isset($contain) ? explode(',',$contain) : array();
        $w = "e.mpid='$mpid' and aid='$aid'";
        !empty($creater) && $w .= " and e.openid='$creater'";
        !empty($rid) && $w .= " and e.rid='$rid'";
        if (!empty($kw) && !empty($by)) {
            switch ($by) {
            case 'mobile':
                $kw && $w .= " and mobile like '%$kw%'";
                break;
            case 'nickname':
                $kw && $w .= " and nickname like '%$kw%'";
                break;
            }
        }
        // tags
        if (!empty($options->tags)) {
            $aTags = explode(',', $options->tags);
            foreach ($aTags as $tag) {
                $w .= "and concat(',',e.tags,',') like '%,$tag,%'";
            }
        }
        // todo 逻辑有问题，如果要求既是粉丝又是会员怎么办？
        if ($act->wxyx_only === 'Y') {
            $q = array(
                'e.enroll_key,f.fid,f.nickname,f.openid,f.headimgurl,e.enroll_at,signin_at,e.tags,e.score,s.score myscore,e.remark_num',
                "xxt_enroll_record e left join xxt_fans f on e.mpid=f.mpid and e.openid=f.openid left join xxt_enroll_record_score s on s.enroll_key=e.enroll_key and s.openid='$visitor'",
                $w
            );
        } else if ($act->fans_only === 'Y') {
            $q = array(
                'e.enroll_key,f.fid,f.nickname,f.openid,f.headimgurl,e.enroll_at,signin_at,e.tags,e.score,s.score myscore,e.remark_num',
                "xxt_enroll_record e left join xxt_fans f on e.mpid=f.mpid and e.openid=f.openid left join xxt_enroll_record_score s on s.enroll_key=e.enroll_key and s.openid='$visitor'",
                $w
            );
        } else if ($act->access_control === 'Y') {
            $q = array(
                'e.enroll_key,m.name,m.mobile,e.enroll_at,e.signin_at,e.tags,e.score,e.remark_num,m.mid',
                "xxt_enroll_record e left join xxt_member m on m.forbidden='N' and e.mid=m.mid",
                $w
            );
        } else {
            $q = array(
                'e.enroll_key,e.enroll_at,e.signin_at,e.score,e.remark_num,e.tags',
                'xxt_enroll_record e',
                $w
            );
        }
        $q2['r']['o'] = ($page-1) * $size;
        $q2['r']['l'] = $size;
        switch ($orderby) {
        case 'time':
            $q2['o'] = 'e.enroll_at desc';
            break;
        case 'score':
            $q2['o'] = 'e.score desc';
            break;
        default:
            $q2['o'] = 'e.enroll_at desc';
        }
        /**
         * 获得填写的登记数据
         */ 
        if ($roll = $this->query_objs_ss($q, $q2)) {
            /**
             * 获得自定义数据的值
             */
            foreach ($roll as &$r) {
                $qc = array(
                    'name,value',
                    'xxt_enroll_record_data',
                    "enroll_key='$r->enroll_key'"
                );
                $cds = $this->query_objs_ss($qc);
                $r->data = new \stdClass;
                foreach ($cds as $cd)
                    $r->data->{$cd->name} = $cd->value;
            }
            $result[0] = $roll;
            if (in_array('total', $contain)) {
                $q[0] = 'count(*)';
                $total = (int)$this->query_val_ss($q);
                $result[1] = $total;
            }
        }

        return $result;
    }
    /**
     * 活动报名名单
     *
     * 1、如果活动仅限会员报名，那么要叠加会员信息
     * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
     *
     * $mpid
     * $aid
     * $options
     * --creater openid 
     * --visitor openid 
     * --page
     * --size
     * --rid 轮次id
     * --kw 检索关键词
     * --by 检索字段
     * 
     *
     * return
     * [0] 数据列表
     * [1] 数据总条数
     * [2] 数据项的定义
     */
    public function getParticipants($mpid, $aid, $options=null) 
    {
        if ($options) {
            is_array($options) && $options = (object)$options;
            $rid = null;
            if (!empty($options->rid)) {
                if ($options->rid==='ALL')
                    $rid = null;
                else if (!empty($options->rid))
                    $rid = $options->rid;
            } else if ($activeRound = $this->getActiveRound($mpid, $aid))
                $rid = $activeRound->rid;

            $kw = isset($options->kw) ? $options->kw : null; 
            $by = isset($options->by) ? $options->by : null;
        }
        $w = "e.mpid='$mpid' and aid='$aid'";
        !empty($rid) && $w .= " and e.rid='$rid'";
        // tags
        if (!empty($options->tags)) {
            $aTags = explode(',', $options->tags);
            foreach ($aTags as $tag) $w .= "and concat(',',e.tags,',') like '%,$tag,%'";
        }
        // todo need support?
        if (!empty($kw) && !empty($by)) {
            switch ($by) {
            case 'mobile':
                $kw && $w .= " and mobile like '%$kw%'";
                break;
            case 'nickname':
                $kw && $w .= " and nickname like '%$kw%'";
                break;
            }
        }
        // 活动参与人
        $q = array(
            'distinct e.openid',
            "xxt_enroll_record e",
            $w
        );
        /**
         * 获得填写的登记数据
         */ 
        $participants = $this->query_vals_ss($q);
        
        return $participants;
    }
    /**
     * 统计登记信息
     *
     * todo 图片的流的选择是写死的
     *
     * 只统计radio/checkbox类型的数据项
     *
     * return
     * name => array(l=>label,c=>count)
     *
     */
    public function getStat($aid)
    {
        /**
         * 获得活动的定义
         */
        $q = array(
            'p.html form_html',
            'xxt_enroll a,xxt_code_page p',
            "a.id='$aid' and a.form_code_id=p.id"
        );
        $act = $this->query_obj_ss($q);
        // 记录返回的结果
        $defsAndCnt = array();
        /**
         * 获得扩展数据项
         * 数据项的定义需要从表单中获取
         * 表单中定义了数据项的id和name
         * 定义数据项都是input，所以首先应该将页面中所有input元素提取出来
         * 每一个元素中都有ng-model和title属相，ng-model包含了id，title是名称
         */
        if (!empty($act->form_html)) {
            $wraps = array();
            if (preg_match_all('/<div.+?wrap=.+?>.+?<\/div/i', $act->form_html, $wraps)) {
                $wraps = $wraps[0];
                foreach ($wraps as $wrap) {
                    $def=array();$inp=array();$title=array();$ngmodel=array();$opval=array();$optit=array();
                    if (!preg_match('/<input.+?>/', $wrap, $inp))
                        continue;
                    $inp = $inp[0];
                    if (preg_match('/title="(.*?)"/', $inp, $title))
                        $title = $title[1]; 
                    if (preg_match('/type="radio"/', $inp)) {
                        /**
                         * for radio group.
                         */
                        if (preg_match('/ng-model="data\.(.+?)"/', $inp, $ngmodel))
                            $id = $ngmodel[1];

                        $existing = false;
                        foreach ($defsAndCnt as &$d)
                            if ($existing = ($d['id'] === $id))
                                break;

                        if (!$existing) {
                            $defsAndCnt[] = array('title'=>$title,'id'=>$id,'ops'=>array());
                            $d = &$defsAndCnt[count($defsAndCnt)-1];
                        }
                        if (preg_match('/value="(.+?)"/', $wrap, $opval))
                            $op['v'] = $opval[1];
                        if (preg_match('/data-label="(.+?)"/', $wrap, $optit)) 
                            $op['l'] = $optit[1];
                        /**
                         * 获取数据
                         */
                        $q = array(
                            'count(*)',
                            'xxt_enroll_record_data',
                            "name='$id' and value='{$op['v']}'"
                        );
                        $op['c'] = $this->query_val_ss($q);

                        $d['ops'][] = $op;
                    } else if (preg_match('/type="checkbox"/', $wrap)) {
                        /**
                         * for checkbox group.
                         */
                        if (preg_match('/ng-model="data\.(.+?)\.(\d+?)"/', $wrap, $ngmodel)) {
                            $id = $ngmodel[1];
                            $opval = $ngmodel[2];
                        }
                        $existing = false;
                        foreach ($defsAndCnt as &$d)
                            if ($existing = ($d['id'] === $id))
                                break;

                        if (!$existing) {
                            $defsAndCnt[] = array('title'=>$title,'id'=>$id,'ops'=>array());
                            $d = &$defsAndCnt[count($defsAndCnt)-1];
                        }
                        $op['v'] = $opval;
                        if (preg_match('/data-label="(.+?)"/', $wrap, $optit)) 
                            $op['l'] = $optit[1];
                        /**
                         * 获取数据
                         */
                        $q = array(
                            'count(*)',
                            'xxt_enroll_record_data',
                            "name='$id' and FIND_IN_SET($opval, value)"
                        );
                        $op['c'] = $this->query_val_ss($q);
                        //
                        $d['ops'][] = $op;
                    }
                }
            }
        }

        return $defsAndCnt;
    }
    /**
     * 当前访问用户是否已经点了赞
     *
     * $openid
     * $ek
     */
    public function rollPraised($openid, $ek)
    {
        $q = array(
            'score',
            'xxt_enroll_record_score',
            "enroll_key='$ek' and openid='$openid'"
        );

        return 1 === (int)$this->query_val_ss($q);
    }
    /**
     * 登记总的赞数
     *
     * $ek
     */
    public function rollScore($ek)
    {
        $q = array(
            'count(*)',
            'xxt_enroll_record_score',
            "enroll_key='$ek'" 
        );
        $score = (int)$this->query_val_ss($q);

        return $score;
    }
}
