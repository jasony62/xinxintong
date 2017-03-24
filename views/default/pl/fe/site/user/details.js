/**
 * Created by lishuai on 2017/3/23.
 */
define(['main'], function(ngApp) {
    'use strict';

    ngApp.provider.controller('ctrlDetails',['$scope', 'http2', '$uibModal', 'noticebox', function($scope, http2, $uibModal, noticebox){
        var baseURL = '/rest/pl/fe/site/user/';
		//获取同步信息
        http2.get(baseURL + 'profile/get?site=' + $scope.siteId + '&userid=' + $scope.userId, function(rsp){
            var members, mapMemberSchemas = {};
            $scope.user = rsp.data.user;
            //$scope.members = rsp.data.members;
            if (rsp.data.members) {
            	angular.forEach($scope.memberSchemas, function(schema) {
            		mapMemberSchemas[schema.id] = schema;
            	});
            	members = rsp.data.members;
            	angular.forEach(members, function(member) {
            		member.schema = mapMemberSchemas[member.schema_id];
            		member.schema.tags = [{
            			name: 'abc'
            		}, {
            			name: 'xyz'
            		}];
            		member.tags2 = [];
            	});
            	$scope.members = members;
            }
        });
		//获取 增加公众号信息
		http2.get(baseURL + 'fans/getsnsinfo?site=' + $scope.siteId + '&uid=' + $scope.userId, function(rsp){
			$scope.fans = rsp.data;
			$scope.fans.wx && ($scope.wx = $scope.fans.wx);
			$scope.fans.qy && ($scope.qy = $scope.fans.qy);
			$scope.fans.yx && ($scope.yx = $scope.fans.yx);
		});
		$scope.canFieldShow = function(schema, name) {
			return schema['attr_' + name].charAt(0) === '0';
		};
		//增加-ok
		$scope.addMember = function(schema) {
			$uibModal.open({
				templateUrl: 'memberEditor.html',
				backdrop: 'static',
				controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
					$scope.schema = schema;
					$scope.member = {
						extattr: {}
					};
					$scope.canShow = function(name) {
						return schema['attr_' + name].charAt(0) === '0';
					};
					$scope.close = function() {
						$mi.dismiss();
					};
					$scope.ok = function() {
						$mi.close($scope.member);
					};
				}]
			}).result.then(function(member) {
				http2.post(baseURL + 'memberAdd?site=' + $scope.siteId + '&userid=' + $scope.userId + '&schema=' + schema.id, member, function(rsp) {
					member = rsp.data;
					member.extattr = JSON.parse(decodeURIComponent(member.extattr.replace(/\+/g, '%20')));
					member.schema = schema;
					!$scope.members && ($scope.members = []);
					$scope.members.push(member);
				});
			});
		};
		//修改-ok
		$scope.editMember = function(member) {
			$uibModal.open({
				templateUrl: 'memberEditor.html',
				backdrop: 'static',
				controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
					$scope.schema = member.schema;
					$scope.member = angular.copy(member);
					$scope.canShow = function(name) {
						return $scope.schema && $scope.schema['attr_' + name].charAt(0) === '0';
					};
					$scope.close = function() {
						$mi.dismiss();
					};
					$scope.ok = function() {
						$mi.close({
							action: 'update',
							data: $scope.member
						});
					};
					$scope.remove = function() {
						$mi.close({
							action: 'remove'
						});
					};
				}]
			}).result.then(function(rst) {
				if (rst.action === 'update') {
					var newData, i, ea;
					newData = {
						verified: rst.data.verified,
						name: rst.data.name,
						mobile: rst.data.mobile,
						email: rst.data.email,
						email_verified: rst.data.email_verified,
						extattr: rst.data.extattr
					};
					http2.post(baseURL + 'memberUpd?site=' + $scope.siteId + '&id=' + member.id, newData, function(rsp) {
						angular.extend(member, newData);
					});
				} else if (rst.action === 'remove') {
					http2.get(baseURL + 'memberDel?site=' + $scope.siteId + '&id=' + member.id, function() {
						$scope.members.splice($scope.members.indexOf(member), 1);
					});
				}
			});
		};
		$scope.syns = function(openId){
			//普通用户的标识，对当前公众号唯一 openid
			var url  = '/rest/mp/user/fans/refreshOne?openid=' + openId;
			http.get(url, function(rsp){
				noticebox('完成同步');
			})
		}
    }])
});
