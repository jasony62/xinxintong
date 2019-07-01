<?php
namespace matter\enroll;
/**
 * 填写记录间的关联
 */
class entity_model extends \TMS_MODEL {
    /**
     * 类型
     */
    const TYPE_INTTOSTR = [1 => 'record', 2 => 'data', 3 => 'remark', 4 => 'topic', 5 => 'tag', 101 => 'article'];
    const TYPE_STRTOINT = ['record' => 1, 'data' => 2, 'remark' => 3, 'topic' => 4, 'tag' => 5, 'article' => 101];
    /**
     * 获得记录活动实体对象
     */
    public function findEntity($id, $type, $aOptions = []) {
        switch ($type) {
        case 'record':
            $oEntity = $this->model('matter\enroll\record')->byPlainId($id, $aOptions);
            break;
        case 'data':
        case 'remark':
            $oEntity = $this->model('matter\enroll\\' . $type)->byId($id, $aOptions);
            break;
        case 'topic':
            $oEntity = $this->model('matter\enroll\topic', null)->byId($id, $aOptions);
            break;
        case 'tag':
            $oEntity = $this->model('matter\enroll\tag2')->byId($id, $aOptions);
            break;
        case 'article':
            $oEntity = $this->model('matter\article')->byId($id);
            break;
        default:
            $oEntity = false;
        }

        if ($oEntity) {
            $oEntity->type = $type;
            $oEntity->intType = self::TYPE_STRTOINT[$oEntity->type];
        }

        return $oEntity;
    }
    /**
     * 实体所属的记录
     */
    public function recordByEntity($oEntity, $aOption = []) {
        $fields = empty($aOption['fields']) ? 'id,siteid,aid,state' : $aOption['fields'];

        $modelRec = $this->model('matter\enroll\record');
        switch ($oEntity->type) {
        case 'record':
            $oRecord = $modelRec->byPlainId($oEntity->id, ['fields' => $fields]);
            break;
        case 'data':
            $oRecData = $this->model('matter\enroll\data')->byId($oEntity->id, ['fields' => 'enroll_key']);
            $oRecord = $modelRec->byId($oRecData->enroll_key, ['fields' => $fields]);
            break;
        case 'remark':
            $oRemark = $this->model('matter\enroll\remark')->byId($oEntity->id, ['fields' => 'enroll_key']);
            $oRecord = $modelRec->byId($oRemark->enroll_key, ['fields' => $fields]);
            break;
        default:
            $oRecord = false;
        }

        return $oRecord;
    }
}