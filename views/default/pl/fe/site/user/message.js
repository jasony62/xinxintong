/**
 * Created by lishuai on 2017/3/23.
 */
define(['main'], function(ngApp) {
    'use strict';

    ngApp.provider.controller('ctrlMessage',['$scope', 'http2', function($scope, http2){
		var page,
			baseURL = '/rest/pl/fe/site/user/';
		$scope.page = page = {
			at: 1,
			site: 30,
			j: function(){
				return '&at=' + this.at + '&site=' + this.site;
			}
		};
		//获取消息记录
		//http2.get('/rest/site/fe/user/history/appList?site='+$scope.siteId+'&uid='+$scope.userId+page.j(), function(rsp){
		//	$scope.message = rsp.data;
			$scope.message = [
				{
					title:'消息1',
					time:'1490579741',
					state:'Y',
					case:'',
					remark:'工作上报通知 ：q 工作名称：q 工作编码：q 上报人：q 上报人所属部门：q 上报时间：q ：q'
				},
				{
					title:'消息2',
					time:'1490579741',
					state:'N',
					case:'用户拒绝',
					remark:'工作上报通知 ：q 工作名称：q 工作编码：q 上报人：q 上报人所属部门：q 上报时间：q ：q'
				},
				{
					title:'消息3',
					time:'1490579741',
					state:'N',
					case:'用户拒绝用户拒绝用户拒绝用户拒绝用户拒绝',
					remark:'工作上报通知 ：q 工作名称：q 工作编码：q 上报人：q 上报人所属部门：q 上报时间：q ：q'
				},
				{
					title:'消息4',
					time:'1490579741',
					state:'Y',
					case:'',
					remark:'工作上报通知 ：q 工作名称：q 工作编码：q 上报人：q 上报人所属部门：q 上报时间：q ：q'
				},
			];
		//});

    }])
});
