<?php
namespace cus\crccre\member;

require_once dirname(dirname(dirname(dirname(__FILE__)))).'/member_base.php';
/**
 * crccre企业号认证用户接口
 */
class qyauth extends \member_base {
    /**
     *
     */
    private $soap;
    /**
     *
     */
    public function __construct()
    {
        $this->authurl = '/rest/cus/crccre/member/qyauth';
    }
    /**
     *
     */
    protected function soap() 
    {
        if (!isset($this->soap)) {
            ini_set('soap.wsdl_cache_enabled', '0');
            $this->soap = new \SoapClient(
                'http://um.crccre.cn/webservices/adgrouptree.asmx?wsdl', 
                array(
                    'soap_version' => SOAP_1_2,
                    'encoding'=>'utf-8',
                    'exceptions'=>true, 
                    'trace'=>1, 
                )
            );
        }
        return $this->soap;
    }
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'import2Qy';
        $rule_action['actions'][] = 'sync2Qy';

        return $rule_action;
    }
    /**
     *
     */
    private function uploadUser2Qy($mpid, $user, &$uCounter, &$existUsers, &$warning) 
    {
        $proxy = $this->model('mpproxy/qy', $mpid);

        if (!array_key_exists($user['useraccount'], $existUsers)) {
            $rst = $proxy->userCreate($user['useraccount'], $user);
            $uCounter++;
        } else {
            unset($existUsers[$user['useraccount']]);
            $rst = $proxy->userUpdate($user['useraccount'], $user);
        }
        if ($rst[0] === false) {
            $w = array($user['useraccount'], $rst[1]);
            if (false !== strpos($rst[1], '60003'))    
                $w['department'] = $user['department']; 
            if (false !== strpos($rst[1], '40003'))    
                $w['guid'] = $user['guid']; 
            $warning[] = $w;
        }
    }
    /**
     *
     */
    private function uploadDept2Qy($mpid, $dept, &$dCounter, &$existDepts, &$localDepts, &$warning)
    {
        $localDepts[$dept['guid']] = $dept['id'];
        $proxy = $this->model('mpproxy/qy', $mpid);
        if (!array_key_exists($dept['id'], $existDepts)) {
            $rst = $proxy->departmentCreate($dept['title'], $dept['pid'], $dept['order'], $dept['id']);
            if ($rst[0] === false) {
                if (false !== strpos($rst[1], '60008')) {
                    $rst2 = $proxy->departmentCreate($dept['title'].'_'.$dept['order'], $dept['pid'], $dept['order'], $dept['id']);
                    if ($rst2[0] === false) {
                        $warning[] = array($dept['guid'], $rst[1]);
                        return $rst;
                    }
                } else {
                    $warning[] = array($dept['guid'], $rst[1]);
                    return $rst;
                }
            }
            $dCounter++;
        } else {
            unset($existDepts[$dept['id']]);
            /**
             * 更新部门
             */
        }
        $children = $this->model('cus/org')->nodes($dept['guid']);
        foreach ($children as $order=>$child) {
            if ($child['titletype'] === '5')
                continue;

            $child['order'] = $order + 1;
            $child['pid'] = $dept['id'];
            $rst = $this->uploadDept2Qy($mpid, $child, $dCounter, $existDepts, $localDepts, $warning);
            if ($rst[0] === false)
                $warning[] = array($dept['guid'], $rst[1]);
        }

        return array(true);
    }
    /**
     * 将内部组织结构数据全量导入到企业号通讯录 
     *
     * $mpid
     * $authid
     * $next
     * $step
     */
    public function import2Qy_action($mpid, $authid, $next=null, $step=0)
    {
        if (empty($next)) {
            /**
             * 获得企业号通讯录中已有的所有部门
             */
            $rst = $this->model('mpproxy/qy', $mpid)->departmentList(1);
            if ($rst[0] === false)
                return new \ResponseError($rst[1]);
            $existDepts = array();
            foreach ($rst[1]->department as $rdept)
                $existDepts[$rdept->id] = $rdept;

            $_SESSION['existDepts'] = $existDepts;
            return new \ResponseData(array('param'=>array('next'=>1)));
        }
        /**
         * 更新部门数据
         */
        if ($next == 1) {
            $warning = array(); $dCounter=0;
            $existDepts = $_SESSION['existDepts'];
            $localDepts = array();
            $nodes = $this->model('cus/org')->nodes();
            foreach ($nodes as $order=>$node) {
                $node['order'] = $order+1;
                $node['pid'] = 1;
                $this->uploadDept2Qy($mpid, $node, $dCounter, $existDepts, $localDepts, $warning);
            }

            $_SESSION['existDepts'] = $existDepts;
            $_SESSION['dCounter'] = $dCounter;
            $_SESSION['localDepts'] = $localDepts;
            $_SESSION['warning'] = $warning;
            return new \ResponseData(array('param'=>array('next'=>2)));
        }
        /**
         * 获得企业号通讯录中已有的所有的用户
         */
        if ($next == 2) {
            $localDepts = $_SESSION['localDepts'];
            $uploadUsers = array();  
            $rst = $this->model('mpproxy/qy', $mpid)->userSimpleList(1);
            if ($rst[0] === false)
                return new \ResponseError($rst[1]);
            $existUsers = array();
            foreach ($rst[1]->userlist as $ruser)
                $existUsers[$ruser->userid] = $ruser;

            $_SESSION['existUsers'] = $existUsers;
            return new \ResponseData(array('param'=>array('next'=>3)));
        }
        /**
         * 获得本地用户数据
         */
        if ($next == 3) {
            $localDepts = $_SESSION['localDepts'];
            $existUsers = $_SESSION['existUsers'];
            $uploadUsers = array();
            $nodes = $this->model('cus/org')->getNodesByTitleType('5');
            foreach ($nodes as $node) {
                if (isset($uploadUsers[$node['useraccount']]))
                    $user = $uploadUsers[$node['useraccount']];
                else {
                    $mobile = empty($node['mobile']) ? '151'.rand(1000,9999).'0000':$node['mobile'];
                    $user = array(
                        'guid'=>$node['guid'],
                        'useraccount'=>$node['useraccount'],
                        'name'=>$node['title'],
                        'mobile'=>$mobile,
                        'department'=>array(),
                    );
                }
                if (isset($localDepts[$node['parentid']]))
                    $user['department'][] = $localDepts[$node['parentid']]; 
                $uploadUsers[$node['useraccount']] = $user;

            }
            $_SESSION['uploadUsers'] = $uploadUsers;
            return new \ResponseData(array('param'=>array('next'=>4)));
        }
        /**
         * 更新用户数据
         */
        if ($next == 4) {
            $uCounter = isset($_SESSION['uCounter']) ? $_SESSION['uCounter'] : 0;
            $warning = $_SESSION['warning'];
            $existUsers = $_SESSION['existUsers'];
            $uploadUsers = $_SESSION['uploadUsers'];
            $counter = 0;
            $user = current($uploadUsers);
            while ($user) {
                $this->uploadUser2Qy($mpid, $user, $uCounter, $existUsers, $warning);
                unset($uploadUsers[$user['useraccount']]);
                $counter++;
                if ($counter === 100) {
                    $_SESSION['uploadUsers'] = $uploadUsers;
                    $_SESSION['existUsers'] = $existUsers;
                    $_SESSION['warning'] = $warning;
                    $_SESSION['uCounter'] = $uCounter;
                    $step++;
                    return new \ResponseData(array('param'=>array('next'=>4,'step'=>$step,'left'=>count($uploadUsers))));
                } 
                $user = next($uploadUsers);
            }
        }
        /**
         * 清理数据
         */
        $existDepts = $_SESSION['existDepts'];
        $dCounter = $_SESSION['dCounter'];
        unset($_SESSION['dCounter']);
        unset($_SESSION['uCounter']);
        unset($_SESSION['existDepts']);
        unset($_SESSION['localDepts']);
        unset($_SESSION['existUsers']);
        unset($_SESSION['uploadUsers']);

        return new \ResponseData(array($dCounter, $existDepts, $uCounter, $existUsers, $warning));
    }
    /**
     * 将内部组织结构数据增量导入到企业号通讯录 
     *
     * $mpid
     * $authid
     */
    public function sync2Qy_action($mpid, $authid)
    {
        return new \ResponseData('ok');
    }
    /**
     * 将内部组织结构数据增量导入到企业号通讯录 
     *
     * $mpid
     * $authid
     */
    public function syncFromQy_action($mpid, $authid)
    {
        return new \ResponseError('not support');
    }
    /**
     * 返回组织机构组件
     */
    public function memberSelector_action($authid)
    {
        $addon = array(
            'js'=>'/views/default/cus/crccre/member/memberSelector.js',
            'view'=>"/rest/cus/crccre/member/auth/organization?authid=$authid"
        );
        return new \ResponseData($addon);
    }
    /**
     *
     */
    public function organization_action($authid)
    {
        $this->view_action('/cus/crccre/member/memberSelector');
    }
}
