<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';

/**
 * 记录活动附件
 */
class attachment extends base {
    /**
     * 下载附件
     */
    public function get_action($app, $attachment) {
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if ($oApp === false || $oApp->state !== '1') {
            $this->outputError('指定的记录活动不存在，请检查参数是否正确');
        }
        /**
         * 获取附件
         */
        $q = [
            '*',
            'xxt_matter_attachment',
            ['matter_id' => $oApp->id, 'matter_type' => 'enroll', 'id' => $attachment],
        ];
        if (false === ($oAtt = $modelApp->query_obj_ss($q))) {
            die('指定的附件不存在');
        }

        if (strpos($oAtt->url, 'local') !== 0) {
            die(' 数据错误');
        }
        $fs = $this->model('fs/local', $oApp->siteid, '附件');
        //header("Content-Type: application/force-download");
        header("Content-Type: $oAtt->type");
        header("Content-Disposition: attachment; filename=" . $oAtt->name);
        header('Content-Length: ' . $oAtt->size);
        echo $fs->read(str_replace('local://', '', $oAtt->url));

        exit;
    }
    /**
     * 下载题目中的文件
     */
    public function download_action($app, $file) {
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if ($oApp === false || $oApp->state !== '1') {
            die('指定的记录活动不存在，请检查参数是否正确');
        }
        if (empty($file)) {
            die('参数错误');
        }

        $file = $modelApp->unescape($file);
        $file = json_decode($file);

        // 附件是否存在;
        $file->url = TMS_APP_DIR . '/' . $file->url;
        if (!file_exists($file->url)) {
            die('指定的附件不存在');
        }

        //设置脚本的最大执行时间，设置为0则无时间限制
        set_time_limit(0);

        header("Content-Type: $file->type");
        Header("Accept-Ranges: bytes");
        header('Content-Length: ' . $file->size);
        header("Content-Disposition: attachment; filename=" . $file->name);

        //针对大文件，规定每次读取文件的字节数为4096字节，直接输出数据
        $read_buffer = 4096;
        $handle = fopen($file->url, 'rb');
        //总的缓冲的字节数
        $sum_buffer = 0;

        //只要没到文件尾，就一直读取
        while (!feof($handle) && $sum_buffer < $file->size) {
            echo fread($handle, $read_buffer);
            $sum_buffer += $read_buffer;
        }

        //关闭句柄
        fclose($handle);
        exit;
    }
}