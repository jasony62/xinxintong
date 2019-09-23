<?php
namespace pl\fe;

class upgrade extends \TMS_CONTROLLER {
    /**
     *
     */
    public function get_access_rule() {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'do';

        return $rule_action;
    }
    /**
     * 修改上传图片数据
     */
    public function do_action($site, $page = 1, $size = 10) {
        $model = $this->model();

        $q = [
            'id,aid,record_id,schema_id,value',
            'xxt_enroll_record_data',
            ['upgrade_flag' => 0, 'value' => (object) ['op' => 'like', 'pat' => '%compact.jpg']],
        ];
        $q2['o'] = 'id';
        $q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];

        $recDatas = $model->query_objs_ss($q, $q2);
        foreach ($recDatas as $recData) {
            $imageUrls = explode(',', $recData->value);
            $newImageUrls = [];
            foreach ($imageUrls as $imageUrl) {
                $imageUrl = str_replace('//', '/', $imageUrl);
                $matches = [];
                if (preg_match('/upload\/(.*?)\/_user/', $imageUrl, $matches)) {
                    $siteId = $matches[1];
                    $recData->siteid = $siteId;
                    $baseURL = 'kcfinder/upload/' . $siteId . '/_user/';
                    if (substr_count($imageUrl, $baseURL) > 1) {
                        if (file_exists($imageUrl)) {
                            $imageRelativeUrl = str_replace($baseURL, '', $imageUrl);
                            $newValue = $baseURL . str_replace('medium.compact', 'compact', $imageRelativeUrl);
                            if (copy($imageUrl, $newValue)) {
                                $newImageUrls[] = $newValue;
                                $originalImageUrl = str_replace('.compact', '', $newValue);
                                if (file_exists($originalImageUrl)) {
                                    // 生成中等压缩文件
                                    $fsUser = $this->model('fs/local', $siteId, '_user');
                                    $aCompactResult = $fsUser->compactImage($originalImageUrl, 'medium', 1200);
                                    if (true === $aCompactResult[0]) {
                                        // do nothing
                                        $recData->medium[] = $aCompactResult[1];
                                    }
                                }
                            }
                        }
                    } else {
                        $originalImageUrl = str_replace('.compact', '', $imageUrl);
                        if (file_exists($originalImageUrl)) {
                            // 生成中等压缩文件
                            $fsUser = $this->model('fs/local', $siteId, '_user');
                            $aCompactResult = $fsUser->compactImage($originalImageUrl, 'medium', 1200);
                            if (true === $aCompactResult[0]) {
                                // do nothing
                                $recData->medium[] = $aCompactResult[1];
                            }
                        }
                    }
                }
            }
            if (count($newImageUrls)) {
                $recData->newValue = implode(',', $newImageUrls);
                // 更新数据
                $ret = $model->update('xxt_enroll_record_data', ['value' => $recData->newValue, 'upgrade_flag' => 1], ['id' => $recData->id]);
                if ($ret) {
                    $record = $model->query_obj_ss(['data', 'xxt_enroll_record', ['id' => $recData->record_id]]);
                    $newData = json_decode($record->data);
                    $newData->{$recData->schema_id} = $recData->newValue;
                    $newData = $model->escape($model->toJson($newData));
                    $model->update('xxt_enroll_record', ['data' => $newData, 'upgrade_flag' => 1], ['id' => $recData->record_id]);
                }
            } else {
                $model->update('xxt_enroll_record_data', ['upgrade_flag' => 1], ['id' => $recData->id]);
            }
        }

        return new \ResponseData($recDatas);
    }
}