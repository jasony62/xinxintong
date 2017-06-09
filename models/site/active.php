<?php
namespace site;
/**
 * 站点管理员
 */
class active_model extends \TMS_MODEL {
	/**
	 * [add 增加站点活跃数]
	 * @param [type]  $site      [description]
	 * @param [type]  $user      [description]
	 * @param integer $activeNum [description]
	 * @param [type]  $operation [description]
	 */
	public function add($site, $user, $activeNum = 0, $operation){
		$this->setOnlyWriteDbConn(true);
		list($year, $month, $day) = explode('-', date('Y-n-j'));
		
		$q = [
			'id,year,year_active_sum,month,month_active_sum,day,day_active_sum,active_sum,user_active_sum,operation_active_sum',
			'xxt_site_active',
			['siteid' => $site, 'active_last_op' => 'Y']
		];
		// 站点活跃数总数
		$actives = $this->query_objs_ss($q);
		/* 并发情况下有可能产生多条数据 */
		$active_sum = 0;
		$year_active_sum = 0;
		$month_active_sum = 0;
		$day_active_sum = 0;
		if(count($actives)){
			foreach ($actives as $active) {
				$this->update('xxt_site_active', ['active_last_op' => 'N'], "id = {$active->id}");
				$active->active_sum > $active_sum && $active_sum = $active->active_sum;
				if($active->year == $year){
					$active->year_active_sum > $year_active_sum && $year_active_sum = $active->year_active_sum;
					if($active->month == $month){
						$active->month_active_sum > $month_active_sum && $month_active_sum = $active->month_active_sum;
						if($active->day == $day){
							$active->day_active_sum > $day_active_sum && $day_active_sum = $active->day_active_sum;
						}
					}
				}
			}
		}
		//增加活跃数
		$log = new \stdClass;
		$log->active_sum = (int) $active_sum + (int) $activeNum;
		$log->year_active_sum = (int) $year_active_sum + (int) $activeNum;
		$log->month_active_sum = (int) $month_active_sum + (int) $activeNum;
		$log->day_active_sum = (int) $day_active_sum + (int) $activeNum;
		// 战点下指定用户活跃数总数
		$q[2] = ['siteid' => $site, 'userid' => $user->uid, 'user_last_op' => 'Y'];
		$user_active_sum = 0;
		if($activeUser = $this->query_obj_ss($q)){
			$this->update('xxt_site_active', ['user_last_op' => 'N'], "id = {$activeUser->id}");
			$user_active_sum = $activeUser->user_active_sum;
		}
		$log->user_active_sum = (int) $user_active_sum + (int) $activeNum;

		// 战点下指定操作活跃数总数
		$q[2] = ['siteid' => $site, 'operation' => $operation, 'operation_last_op' => 'Y'];
		$activeOps = $this->query_objs_ss($q);
		$operation_active_sum = 0;
		if(count($activeOps)){
			foreach ($activeOps as $activeOp) {
				$this->update('xxt_site_active', ['operation_last_op' => 'N'], "id = {$activeOp->id}");
				$activeOp->operation_active_sum > $operation_active_sum && $operation_active_sum = $activeOp->operation_active_sum;
			}
		}
		$log->operation_active_sum = (int) $operation_active_sum + (int) $activeNum;
		//
		$log->siteid = $site;
		$log->userid = $user->uid;
		$log->nickname = $this->escape($user->nickname);
		$log->operation = $operation;
		$log->operation_at = time();
		$log->year = $year;
		$log->month = $month;
		$log->day = $day;
		$log->active_one_num = $activeNum;
		$log->active_last_op = 'Y';
		$log->user_last_op = 'Y';
		$log->operation_last_op = 'Y';

		$id = $this->insert('xxt_site_active', $log, true);
		$log->id = $id;

		return $log;
	}
	/**
	 * 
	 */
	public function byUser($site = '', $user){
		$q = [
			'*',
			'xxt_site_active',
			['userid' => $user->uid, 'user_last_op' => 'Y']
		];
		if(!empty($site)){
			$q[2]['siteid'] = $site;
		}

		$active = $this->query_obj_ss($q);

		return $active;
	}
}