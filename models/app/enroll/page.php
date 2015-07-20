<?php
namespace app\enroll;

class page_model extends \TMS_MODEL {
    /**
     * 根据活动
     */
    public function &byEnroll($id)
    {
        // form page
        $q = array(
            'form_code_id', 
            'xxt_enroll', 
            "id='$id'"
        );
        $e = $this->query_obj_ss($q);
        $page = \TMS_APP::model('code/page')->byId($e->form_code_id, 'html,css,js');
        $page->id = 0;
        $page->name = 'form';
        $page->type = 'I';
        $page->code_id = $e->form_code_id;
        $pages['form'] = $page;
        
        // others
        $q = array(
            'ap.*', 
            'xxt_enroll_page ap',
            "ap.aid='$id'"
        );
        $eps = $this->query_objs_ss($q);
        foreach ($eps as &$ep) {
            $code = \TMS_APP::model('code/page')->byId($ep->code_id);
            $ep->html = $code->html;
            $ep->css = $code->css;
            $ep->js = $code->js;
            $ep->ext_js = $code->ext_js;
            $ep->ext_css = $code->ext_css;
            $pages[$ep->name] = $ep;
        }

        return $pages;
    }
    /**
     * 从页面的html中提取登记项定义
     *
     * 数据项的定义需要从表单中获取
     * 表单中定义了数据项的id和name
     * 定义数据项都是input，所以首先应该将页面中所有input元素提取出来
     * 每一个元素中都有ng-model和title属相，ng-model包含了id，title是名称
     */
    public function &schemaByHtml($html, $size=null) 
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
                    //if (preg_match('/data-label="(.+?)"/', $wrap, $optit)) 
                    //    $op['l'] = $optit[1];
                    if (preg_match_all('/data-(.+?)="(.+?)"/', $wrap, $opAttrs)) {
                        for ($i = 0, $l = count($opAttrs[0]); $i < $l; $i++) {
                            $op[$opAttrs[1][$i]] = $opAttrs[2][$i];
                        }
                    }
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
        
        if ($size !== null && $size > 0 && $size < count($defs)) {
            /**
             * 随机获得指定数量的登记项
             */
            $randomDefs = array();
            $upper = count($defs) - 1;
            for ($i = 0; $i < $size; $i++) {
                $random = mt_rand(0, $upper);
                $randomDefs[] = $defs[$random];
                array_splice($defs, $random, 1);
                $upper--;
            }
            return $randomDefs;
        } else
            return $defs;
    }
    /**
     *
     */
    public function &schemaByEnroll($id)
    {
        $schema = array();
        
        $pages = $this->byEnroll($id);
        foreach ($pages as $p)
            if ($p->type === 'I') {
                $defs = $this->schemaByHtml($p->html);
                $schema = array_merge($schema, $defs);
            }
            
        return $schema;   
    }
}
