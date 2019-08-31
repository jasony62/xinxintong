<?php
namespace matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户请假
 */
class leave_model extends \matter\base_model {
    private static $_aFields = [
        'id' => true,
        'g_transid' => true,
        'aid' => true,
        'userid' => true,
        'nickname' => true,
        'begin_at' => true,
        'end_at' => true,
        'apply_at' => true,
        'approve_at' => true,
        'cancel_at' => true,
        'state' => true,
    ];
    /**
     *
     */
    protected function id() {
        return 'id';
    }
    /**
     *
     */
    protected function table() {
        return 'xxt_group_leave';
    }
    /**
     *
     */
    public function fieldNames() {
        return array_keys(self::$_aFields);
    }
    /**
     *
     */
    public function add($oApp, $oGrpUser, $oProto) {
        $oNewLeave = new \stdClass;
        $fieldNames = $this->fieldNames();
        foreach ($fieldNames as $fname) {
            switch ($fname) {
            case 'begin_at':
            case 'end_at':
            case 'approve_at':
            case 'confirm_at':
                $oNewLeave->{$fname} = isset($oProto->{$fname}) ? (int) $oProto->{$fname} : 0;
                break;
            case 'apply_at':
                if (isset($oProto->apply_at)) {
                    $oNewLeave->apply_at = (int) $oProto->apply_at;
                } else {
                    $oNewLeave->apply_at = (int) $this->tmsTransactionBeginAt();
                }
                break;
            case 'aid':
                $oNewLeave->aid = $oApp->id;
            case 'userid':
                $oNewLeave->userid = $oGrpUser->userid;
                break;
            case 'nickname':
                $oNewLeave->nickname = $oGrpUser->nickname;
                break;
            case 'state':
                $oNewLeave->state = 1;
                break;
            case 'g_transid':
                $oNewLeave->g_transid = $this->tmsTransactionId();
                break;
            case 'id':
                break;
            }
        }

        $oNewLeave->id = $this->insert($this->table(), $oNewLeave, true);

        return $oNewLeave;
    }
    /**
     *
     */
    public function modify2($id, $oProto) {
        $aUpdated = [];

        foreach ($oProto as $k => $v) {
            switch ($k) {
            case 'begin_at':
            case 'end_at':
                $aUpdated[$k] = (int) $v;
                break;
            }
        }
        if (empty($aUpdated)) {
            return false;
        }
        $ret = $this->update($this->table(), $aUpdated, ['id' => $id]);

        return $ret;
    }
    /**
     *
     */
    public function close($id) {
        $oRemoved = new \stdClass;
        $oRemoved->state = 0;
        $oRemoved->g_transid = $this->tmsTransactionId();

        $ret = $this->update($this->table(), $oRemoved, ['id' => $id]);

        return $ret;
    }
    /**
     * 列表
     *
     * @param string $appId
     * @param array $aOptions
     */
    public function byApp($appId, $aOptions = []) {
        $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';

        $q = [
            $fields,
            $this->table(),
            ['aid' => $appId, 'state' => 1],
        ];
        $leaves = $this->query_objs_ss($q);

        return $leaves;
    }
    /**
     *
     */
    public function byUser($appId, $userid, $aOptions = []) {
        $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';

        $q = [
            $fields,
            $this->table(),
            ['aid' => $appId, 'state' => 1, 'userid' => $userid],
        ];
        $leaves = $this->query_objs_ss($q);

        return $leaves;
    }
    /**
     * 是否正在请假中
     */
    public function isOnLeave($leaves, $startAt, $endAt) {
        if (empty($leaves)) {
            return false;
        }
        foreach ($leaves as $oLeave) {
            if ($oLeave->begin_at < $startAt && $oLeave->end_at > $endAt) {
                return $oLeave;
            }
        }
        return false;
    }
}