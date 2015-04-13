<?php
$view['template'] = '/mp/matter/article/edit';
/**
 * 是否支持按文章编号检索
 */
if ($k = TMS_APP::model('reply')->canCodesearch($_SESSION['mpid']))
    TPL::assign('codesearchKeyword', $k);
