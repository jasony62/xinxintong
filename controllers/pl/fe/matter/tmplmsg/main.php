<?php
namespace pl\fe\matter\tmplmsg;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 通知消息
 */
class main extends \pl\fe\matter\base {
	/**
	 * @param int $id
	 */
	public function get_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\tmplmsg');

		$tmplmsg = $modelTmpl->byId($id, ['cascaded' => 'Y']);

		return new \ResponseData($tmplmsg);
	}
	/**
	 *
	 */
	public function list_action($site, $cascaded = 'N') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\tmplmsg');

		$tmplmsgs = $modelTmpl->bySite($site, ['cascaded' => $cascaded]);

		return new \ResponseData($tmplmsgs);
	}
	/**
	 *
	 */
	public function mappingGet_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$mapping = $this->model('matter\tmplmsg')->mappingById($id);

		return new \ResponseData($mapping);
	}
	/**
	 * 创建模板消息
	 */
	public function create_action($site, $title = '新模板消息') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();

		$d['siteid'] = $site;
		$d['mpid'] = $site;
		$d['creater'] = $user->id;
		$d['create_at'] = time();
		$d['title'] = $title;

		$id = $model->insert('xxt_tmplmsg', $d, true);

		$q = array(
			"t.*",
			'xxt_tmplmsg t',
			"t.id=$id",
		);

		$tmplmsg = $model->query_obj_ss($q);

		return new \ResponseData($tmplmsg);
	}
	/**
	 * 删除模板消息
	 *
	 * $id
	 */
	public function remove_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$rst = $this->model()->update(
			'xxt_tmplmsg',
			array('state' => 0),
			"siteid='$site' and id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 更新模板消息属性
	 */
	public function update_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_tmplmsg',
			$nv,
			"siteid='$site' and id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * $tid tmplmsg's id
	 */
	public function addParam_action($site, $tid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$p['siteid'] = $site;
		$p['tmplmsg_id'] = $tid;

		$id = $this->model()->insert('xxt_tmplmsg_param', $p, true);

		return new \ResponseData($id);
	}
	/**
	 *
	 * 更新参数定义
	 *
	 * $id parameter's id
	 */
	public function updateParam_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_tmplmsg_param',
			$nv,
			"id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * $pid parameter's id
	 */
	public function removeParam_action($site, $pid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->delete('xxt_tmplmsg_param', "id=$pid");

		return new \ResponseData($rst);
	}
	/**
	 * 获取微信公众号的模板列表并同步更新到本地数据库
	 *
	 */
	public function synTemplateList_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$proxy=\TMS_APP::M('pl\sns\wx\proxy');

		$rst=$proxy->templateList();

		if($rst[0]===false){
			return new \ResponseError($rst[1]);
		}

		$templates=json_decode($rst[1]);
		$d['siteid']=$site;
		$d['mpid']=$site;
		$d['creater']=$user->uid;
		$d['create_at']=time();
		$p['siteid']=$site;

		foreach ($templates as $k => $v) {
			$d['templateid']=$v->template_id;
			$d['title']=$v->title;
			$d['example']=$v->example;
			//同步模板
			if($id=$proxy->query_val_ss(['id','xxt_tmplmsg',['siteid'=>$site,'templateid'=>$v->template_id]])){
				$proxy->update('xxt_tmplmsg',$d,"id='$id'");
			}else{
				$id=$proxy->insert('xxt_tmplmsg',$d);
			}
			//同步参数
			$content=$v->content;
			$p['tmplmsg_id']=$id;
			
		}
	}
}