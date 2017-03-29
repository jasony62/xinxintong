/**
 * Created by lishuai on 2017/3/23.
 */
define(['frame'], function(ngApp) {
    'use strict';

    ngApp.provider.controller('ctrlHistory',['$scope', 'http2', function($scope, http2){
        var page,
			baseURL = '/rest/pl/fe/site/user/';
		$scope.page = page = {
			at: 1,
			site: 30,
			j: function(){
				return '&at=' + this.at + '&site=' + this.site;
			}
		};
		$scope.historys = {
			enroll: {
				title: '活动记录',
				content:[],
			},
			read: {
				title: '阅读记录',
				content:[],
			},
			favor: {
				title: '收藏记录',
				content:[],
			}
		};
		//获取活动记录
		//http2.get('/rest/site/fe/user/history/appList?site='+$scope.siteId+'&uid='+$scope.userId+page.j(), function(rsp){
			//$scope.history.enroll.content = rsp.data.app;
			$scope.historys.enroll.content = [{
				"matter_id": "588428b27e7b4",
				"matter_type": "enroll",
				"matter_title": "登记1",
				"operate_at": "1490579741"
			}, {
				"matter_id": "58d8723beff6f",
				"matter_type": "enroll",
				"matter_title": "登记2",
				"operate_at": "1490580259"
			}, {
				"matter_id": "58d87292c6419",
				"matter_type": "enroll",
				"matter_title": "登记3",
				"operate_at": "1490580316"
			}];
		//});
		//获取阅读记录
		//http2.get('/rest/site/fe/user/history/appList?site='+$scope.siteId+'&uid='+$scope.userId+page.j(), function(rsp){
			//$scope.history.read.content = rsp.data.app;
			$scope.historys.read.content = [{
				"matter_id": "588428b27e7b4",
				"matter_type": "enroll",
				"matter_title": "阅读1",
				"operate_at": "1490579741"
			}, {
				"matter_id": "58d8723beff6f",
				"matter_type": "enroll",
				"matter_title": "阅读2",
				"operate_at": "1490580259"
			}, {
				"matter_id": "58d87292c6419",
				"matter_type": "enroll",
				"matter_title": "阅读3",
				"operate_at": "1490580316"
			}];
		//});

		//获取收藏记录
		//http2.get('/rest/site/fe/user/favor/list?site='+$scope.siteId+'&uid'+$scope.userId+page.j(), function(rsp){
			//$scope.history.favor.content = rsp.data.app;
			$scope.historys.favor.content = [{
				"matter_id": "588428b27e7b4",
				"matter_type": "enroll",
				"matter_title": "收藏1",
				"operate_at": "1490579741"
			}, {
				"matter_id": "58d8723beff6f",
				"matter_type": "enroll",
				"matter_title": "收藏2",
				"operate_at": "1490580259"
			}, {
				"matter_id": "58d87292c6419",
				"matter_type": "enroll",
				"matter_title": "收藏3",
				"operate_at": "1490580316"
			}];
		//});
		//管理员打开活动
		//$scope.openApp = function(app){
		//	location.href = '/rest/pl/fe/matter/' + app.matter_type + '?id=' + app.matter_id + '&site=' + $scope.siteId;
		//}
    }])
});
