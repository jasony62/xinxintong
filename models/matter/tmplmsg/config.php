<?php
namespace matter\tmplmsg;
/**
 * 模版消息参数影射关系
 * 通过模板消息发送事件时，需要讲事件的信息和模板消息的参数进行映射，这样才能拼装出模板消息
 */
class config_model extends \TMS_MODEL {
	/**
	 * 返回模板消息参数映射关系
	 *
	 * @param string $id 模版消息映射关系ID
	 * @param array $options
	 *
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : 'id,msgid,mapping';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'N';

		$q = [
			$fields,
			'xxt_tmplmsg_mapping',
			"id='$id'",
		];
		$config = $this->query_obj_ss($q);

		if ($config) {
			$config->mapping = empty($config->mapping) ? (new stdClass) : json_decode($config->mapping);
			if ($cascaded === 'Y') {
				if (!empty($config->msgid)) {
					$config->tmplmsg = $this->model('matter\tmplmsg')->byId($config->msgid, ['cascaded' => 'Y']);
				}
			}
		}

		return $config;
	}
	/**
	 * 获取模板消息id和参数
	 */
	public function getTmplConfig($oMatter, $noticeName, $aOptions = []) {
		$oParams = new \stdClass;
		$aOptions2 = [];
		$aOptions2['onlySite'] = empty($aOptions['onlySite']) ? false : $aOptions['onlySite'];
		$oTimerTask = empty($aOptions['timerTask']) ? null : $aOptions['timerTask'];
		$oNotice = $this->model('site\notice')->byName($oMatter->siteid, $noticeName, $aOptions2);
		if ($oNotice === false) {
			return [false, '没有指定事件的模板消息1'];
		}
		$oTmplConfig = $this->byId($oNotice->tmplmsg_config_id, ['cascaded' => 'Y']);
		if (empty($oTmplConfig->tmplmsg) || empty($oTmplConfig->msgid)) {
			return [false, '没有指定事件的模板消息2'];
		}
		foreach ($oTmplConfig->tmplmsg->params as $param) {
			if (!isset($oTmplConfig->mapping->{$param->pname})) {
				continue;
			}
			$oMapping = $oTmplConfig->mapping->{$param->pname};
			if (!empty($oMapping->src)) {
				switch ($oMapping->src) {
				case 'matter':
					if (isset($oMatter->{$oMapping->id})) {
						$value = $oMatter->{$oMapping->id};
					} else if ($oMapping->id === 'event_at') {
						$value = date('Y-m-d H:i:s');
					} else {
						$value = '';
					}
					break;
				case 'timerTask':
					if (!empty($oTimerTask) && isset($oTimerTask->{$oMapping->id})) {
						$value = $oTimerTask->{$oMapping->id};
						if ('page' === $oMapping->id) {
							$value .= '，请及时浏览';
						}
					} else {
						if ('page' === $oMapping->id) {
							$value .= '请及时浏览';
						} else {
							$value = '';
						}
					}
					break;
				case 'text':
					$value = $oMapping->name;
					break;
				}
			}
			$oParams->{$param->pname} = isset($value) ? $value : '';
		}
		if (!empty($aOptions['noticeURL'])) {
			$oParams->url = $aOptions['noticeURL'];
		}

		$data = new \stdClass;
		$data->tmplmsgId = $oTmplConfig->msgid;
		$data->oParams = $oParams;

		return [true, $data];
	}
}