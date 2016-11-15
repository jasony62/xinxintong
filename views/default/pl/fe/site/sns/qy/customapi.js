define(['main'], function(ngApp) {
	'use strict';
	//企业号同步
	ngApp.provider.controller('ctrlCustomapi', ['$scope', 'http2', function($scope, http2) {
		//初始值
		$scope.page = {//分页
			at :1,
			size:5
		};
		//同步属性
		$scope.type = 'syncFromQy' ;//企业号日志查询
		$scope.syncType = 'department';//部门状态
		//获取日志
		//接口 /rest/site/fe/user/member/syncLog' + ?site=' + $scope.siteId + '&type=' + $scope.type  + '&page=' + $scope.page.at +'&size=' + $scope.page.size
		$scope.doSearch = function(page,syncType){
			var url =  '/rest/site/fe/user/member/syncLog';
			page && ($scope.page.at = page );
			if(syncType && type !==$scope.syncType ){$scope.type = type;}
			url += '?site=' + $scope.siteId ;
			url += '&type=' + $scope.type ;
			url += '&syncType=' + $scope.syncType ;
			url += '&page=' + $scope.page.at ;
			url += '&size=' + $scope.page.size ;
			http2.post(url,function(rsp){
				$scope.records = rsp.data.data;
				$scope.page.total = rsp.data.total;
			});
		};
		//同步一
		//同步接口 /rest/site/fe/user/member/syncFromQy + '?site=' + $scope.siteId +&authid=' + 0
		$scope.syn = function(){
			var url =  '/rest/site/fe/user/member/';
			url += $scope.type ;
			url += '?site=' + $scope.siteId ;
			url += '&schemaId=' + 0 ;
			http2.get(url,function(rsp){
				 if (rsp.err_code == 0) {
					 alert("同步" + rsp.data[0] + "个部门，" + rsp.data[1] + "个用户，" + rsp.data[2] + "个标签");
					 $scope.$root.progmsg = "同步" + rsp.data[0] + "个部门，" + rsp.data[1] + "个用户，" + rsp.data[2] + "个标签";
				}
				$scope.doSearch(1);
			});
		};
		$scope.doSearch(1);
	}]);
});