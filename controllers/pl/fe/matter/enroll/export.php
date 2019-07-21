<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 导出记录活动数据
 */
class export extends main_base {
    /**
     * 导出记录中的图片
     */
    public function image_action($app) {
        if (false === ($oUser = $this->accountUser())) {
            die('请先登录系统');
        }

        $oNameSchema = null;
        $aImageSchemas = [];

        // 记录活动
        $oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'id,state,title,data_schemas,scenario,sync_mission_round', 'cascaded' => 'N']);
        if ($oApp === false || $oApp->state !== '1') {
            die('指定的活动不存在');
        }

        $schemas = $oApp->dynaDataSchemas;
        $modelSch = $this->model('matter\enroll\schema');
        // 加入关联活动的题目
        $modelSch->getUnionSchemas($oApp, $schemas);

        foreach ($schemas as $oSchema) {
            if ($oSchema->type === 'image') {
                $aImageSchemas[] = $oSchema;
            } else if ($oSchema->id === 'name' || (in_array($oSchema->title, ['姓名', '名称']))) {
                $oNameSchema = $oSchema;
            }
        }

        if (count($aImageSchemas) === 0) {
            die('活动不包含图片数据');
        }

        // 获得所有有效的填写记录
        $oResult = $this->model('matter\enroll\record')->byApp($oApp, null, (object) ['record' => (object) ['rid' => 'all']]);
        if ($oResult->total === 0) {
            die('填写记录为空');
        }
        $records = $oResult->records;
        // 转换数据
        $aImages = [];
        array_walk($records, function ($oRecord) use (&$aImages, $aImageSchemas) {
            // 处理登记项
            $oRecData = $oRecord->data;
            array_walk($aImageSchemas, function ($oSchema) use (&$aImages, $oRecData) {
                if (!empty($oRecData->{$oSchema->id})) {
                    $urls = explode(',', $oRecData->{$oSchema->id});
                    foreach ($urls as $url) {
                        $aImages[] = ['url' => $url, 'schema' => $oSchema, 'data' => $oRecData];
                    }
                }
            });
        });

        if (count($aImages) === 0) {
            die('填写记录中不包含图片');
        }

        // 输出
        $usedRecordName = [];
        // 输出打包文件
        $zipFilename = tempnam('/tmp', $oApp->id);
        $zip = new \ZipArchive;
        if ($zip->open($zipFilename, \ZIPARCHIVE::CREATE) === false) {
            die('无法打开压缩文件，或者文件创建失败');
        }
        foreach ($aImages as $image) {
            $imageFilename = TMS_APP_DIR . '/' . $image['url'];
            if (file_exists($imageFilename)) {
                $imageName = basename($imageFilename);
                /**
                 * 图片文件名称替换
                 */
                if (isset($oNameSchema)) {
                    $data = $image['data'];
                    if (!empty($data->{$oNameSchema->id})) {
                        $recordName = $data->{$oNameSchema->id};
                        if (isset($usedRecordName[$recordName])) {
                            $usedRecordName[$recordName]++;
                            $recordName = $recordName . '_' . $usedRecordName[$recordName];
                        } else {
                            $usedRecordName[$recordName] = 0;
                        }
                        $imageName = $recordName . '.' . explode('.', $imageName)[1];
                    }
                }
                $zip->addFile($imageFilename, $image['schema']->title . '/' . $imageName);
            }
        }
        $zip->close();

        if (!file_exists($zipFilename)) {
            exit("无法找到压缩文件");
        }
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename=' . $oApp->title . '.zip');
        header("Content-Type: application/zip");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . filesize($zipFilename));
        @readfile($zipFilename);

        exit;
    }
}