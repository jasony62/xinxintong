/**
 * Created by lishuai on 2017/3/23.
 */
define(['main'], function(ngApp) {
    'use strict';

    ngApp.provider.controller('ctrlMessage',['$scope', 'http2', '$uibModal', '$timeout', function($scope, http2, $uibModal, $timeout){
		var page,
			baseURL = '/rest/pl/fe/site/user/';
		$scope.page = page = {
			at: 1,
			size: 10,
			j: function(){
				return '&page=' + this.at + '&size=' + this.size;
			}
		};

		$scope.doSearch = function(){
			//获取消息记录
			//http2.get('/rest/site/fe/user/history/appList?site='+$scope.siteId+'&uid='+$scope.userId+page.j(), function(rsp){
			//	$scope.message = rsp.data;
				$scope.message = [
				{
					title:'消息1',
					time:'1490579741',
					state:'Y',
					case:'',
					remark:'工作上报通知 ：q 工作名称：q 工作编码：q 上报人：q 上报人所属部门：q 工作编码：q 上报人：q 上报人所属部门：q 上报时间：q ：q'
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
					remark:'工作上报通知 ：q 工作名称：q 工作编码：q 上报q 工作名称：q 工作编码：q 上报人：q 上报人所属部门：q 上报时间：q ：q'
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
		};
		$scope.doSearch();
		$scope.notify = function(){
			var modal,
				size = 'lg';
			modal = $uibModal.open({
				templateUrl: 'notify.html',
				controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi ,http2){
					//获取所有通知列表
					var url, page2 ;
					$scope2.aChecked = [];
					$scope2.message = {};
					$scope2.page2 = page2 = {
						at: 1,
						size: 10,
						j: function(){
							return '&page=' + this.at + '&size=' + this.size;
						}
					};
					url= '/rest/pl/fe/matter/tmplmsg/list?site=' + $scope.siteId;
					url += page.j();
					url += '&cascaded=Y';
					$scope2.doSearch2 = function(){
						http2.post(url,{}, function(rsp){
							$scope2.matters = rsp.data;
							$scope2.page2.total = $scope2.matters.length;
						});
					};
					$scope2.ok = function () {
						$mi.close($scope2.message);
					};

					$scope2.cancel = function () {
						$mi.dismiss('cancel');
					};
					$scope2.doCheck = function(matter) {
						$scope2.aChecked = [matter];
						$scope2.pickedTmplmsg = matter;
					};
					$scope2.doSearch2();
				}],
				size: size,

			});
			modal.result.then(function(message){
				http2.post('',{},function(){
					noticebox.success('发送完成');
					//3s后 刷新信息列表 获取状态
					$timeout(function(){
						$scope.doSearch();
					},3000)
				})
			})
		}
    }]);
	ngApp.provider.controller('ctrlNotify',['$scope', '$uibModalInstance', 'http2', function($scope2, $mi ,http2){
		//获取所有通知列表
		var url, page ;
		$scope2.page = page = {
			at: 1,
			site: 30,
			j: function(){
				return '&at=' + this.at + '&site=' + this.site;
			}
		};
		url= '/rest/pl/fe/matter/tmplmsg/list?site=' + $scope.siteId;
		url += page.j();
		url += '&cascaded=Y';
		http2.get(url, function(rsp){

		})

	}])
});
