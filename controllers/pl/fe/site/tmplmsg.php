<?php
namespace pl\fe\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 通知消息
 */
class tmplmsg extends \pl\fe\base {
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

		$wx=$this->model('sns\wx');
		$config = $wx->bySite($site);
		if (!$config || $config->joined === 'N') {
			return new \ResponseError('未与微信公众号连接，无法同步微信模板消息!');
		}

		$proxy=$this->model('sns\wx\proxy',$config);
		$rst=$proxy->templateList();

		if($rst[0]===false){
			return new \ResponseError($rst[1]);
		}
		
		$templates=$rst[1]->template_list;
		$d['siteid']=$site;
		$d['mpid']=$site;
		$d['creater']=$user->id;
		$d['create_at']=time();
		$p['siteid']=$site;

		foreach ($templates as $k => $v) {
			$d['templateid']=$v->template_id;
			$tmp[]=$v->template_id;
			$d['title']=$v->title;
			$d['example']=$v->example;
			//同步模板
			if($id=$wx->query_val_ss(['id','xxt_tmplmsg',['siteid'=>$site,'templateid'=>$v->template_id]])){
				$wx->update('xxt_tmplmsg',$d,"id='$id'");
			}else{
				$id=$wx->insert('xxt_tmplmsg',$d);
			}
			//同步参数
			$p['tmplmsg_id']=$id;
			$e=array();
			$content=preg_replace(["/：{/","/\s*/"], [":{"], trim($v->content));

			if(!preg_match('/{{.+?}:{.+?}}/', $content,$m)){
				$r1=explode("}}", $content);
				//去掉空行的字符串
				array_pop($r1);
			
				foreach ($r1 as $k1 => $v1) {
					$arr=explode(':', $v1);
					if(isset($arr[1])){
						$p['pname']=substr(trim($arr[1]),2,-5);
						$p['plabel']=$arr[0];
					}else{
						$p['pname']=substr(trim($v1),2,-5);
						$p['plabel']='';
					}

					$pid=$wx->query_val_ss([
						'id',
						'xxt_tmplmsg_param',
						['siteid'=>$site,'tmplmsg_id'=>$p['tmplmsg_id'],'pname'=>$p['pname']]
						]);

					if($pid){
						$wx->update('xxt_tmplmsg_param',$p,"id='$pid'");
					}else{
						$wx->insert('xxt_tmplmsg_param',$p);
					}
				}
			}else{
				$word=$m[0];
				$str=strstr($content,$word);
				$param1=explode(':', $word);

				$e[substr($param1[0], 2,-7)]=$l1=substr($param1[1], 2,-7);
				$e[$l1]='';

				$content=substr($str, strlen($word));
				$arr2=explode("}}", $content);
				array_pop($arr2);

				foreach ($arr2 as $k2 => $v2) {	
					$crr=explode(":", $v2);
			
					if(isset($crr[1])){
						$e[substr($crr[1],2,-5)]=$crr[0];
					}else{
						$e[substr($crr[0],2,-5)]='';
					}
				}

				foreach ($e as $k3 => $v3) {
					$p['pname']=$k3;
					$p['plabel']=$v3;

					$pid=$wx->query_val_ss([
						'id',
						'xxt_tmplmsg_param',
						['siteid'=>$site,'tmplmsg_id'=>$p['tmplmsg_id'],'pname'=>$p['pname']]
						]);

					if($pid){
						$wx->update('xxt_tmplmsg_param',$p,"id='$pid'");
					}else{
						$wx->insert('xxt_tmplmsg_param',$p);
					}	
				}		
			}
		}
		$one=$wx->query_objs_ss(['templateid','xxt_tmplmsg',"siteid='$site' and templateid!=''"]);

		foreach ($one as $v0) {
			$two[]=$v0->templateid;
		}
		//将本地原来有实际上微信管理端已经删除的模板ID 设置为‘’ 表示本地删除
		if($rest=array_diff($two,$tmp)){
			foreach ($rest as $v4) {
				$wx->update('xxt_tmplmsg',['templateid'=>''],['siteid'=>$site,'templateid'=>$v4]);
			}
		}

		return new \ResponseData('ok');
	}
}