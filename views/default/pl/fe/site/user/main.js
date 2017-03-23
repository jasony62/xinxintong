angular.module('profile.user.xxt', ['ui.bootstrap', 'ui.tms']).
controller('ctrlProfile', ['$scope', 'http2', '$uibModalInstance', '$uibModal', 'params', function($scope, http2, $mi, $uibModal, params) {
	var baseURL = '/rest/pl/fe/site/user/profile/',
		userid = params.userid;
	$scope.siteId = params.siteId;
	$scope.memberSchemas = params.memberSchemas;
	http2.get(baseURL + 'get?site=' + params.siteId + '&userid=' + userid, function(rsp) {
		var members, mapMemberSchemas = {};
		$scope.user = rsp.data.user;
		if (rsp.data.members) {
			angular.forEach(params.memberSchemas, function(schema) {
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
	$scope.close = function() {
		$mi.dismiss();
	};
	$scope.canFieldShow = function(schema, name) {
		return schema['attr_' + name].charAt(0) === '0';
	};
	$scope.$on('tag.xxt.combox.done', function(event, aSelected, index) {
		var i, j, existing, aNewTags = [],
			aTagIds = [],
			member = $scope.members[index];
		for (i in aSelected) {
			existing = false;
			for (j in member.tags) {
				if (aSelected[i].name === member.tags[j].name) {
					existing = true;
					break;
				}
			}!existing && aNewTags.push(aSelected[i]);
		}
		for (i in aNewTags) {
			aTagIds.push(aNewTags[i].id);
		}
		http2.post(baseURL + 'tagAdd?site=' + params.siteId + '&userid=' + userid + '&id=' + member.id, aTagIds, function(rsp) {
			member.tags2 = member.tags2.concat(aNewTags);
			member.tags = rsp.data;
		});
	});
	$scope.$on('tag.xxt.combox.add', function(event, added, index) {
		console.log('dddd', arguments);
	});
	$scope.$on('tag.xxt.combox.del', function(event, removed, index) {
		var member = $scope.user.members[index];
		http2.get(baseURL + 'tagDel?site=' + params.siteId + '&userid=' + userid + '&id=' + member.id + '&tagid=' + removed.id, function(rsp) {
			member.tags2.splice(member.tags2.indexOf(removed), 1);
			member.tags = rsp.data;
		});
	});
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
			http2.post(baseURL + 'memberAdd?site=' + params.siteId + '&userid=' + userid + '&schema=' + schema.id, member, function(rsp) {
				member = rsp.data;
				member.extattr = JSON.parse(decodeURIComponent(member.extattr.replace(/\+/g, '%20')));
				member.schema = schema;
				!$scope.members && ($scope.members = []);
				$scope.members.push(member);
			});
		});
	};
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
}]).
factory('userProfile', function($uibModal) {
	var Profile = {},
		open;
	open = function(siteId, userid, memberSchemas, options) {
		$uibModal.open({
			templateUrl: '/views/default/pl/fe/_module/profile.html?_=1',
			controller: 'ctrlProfile',
			backdrop: 'static',
			size: 'lg',
			windowClass: 'auto-height',
			resolve: {
				params: function() {
					return {
						siteId: siteId,
						userid: userid,
						memberSchemas: memberSchemas
					}
				}
			}
		});
	};
	Profile.open = function(siteId, userid, memberSchemas, options) {
		options = angular.extend({}, options);
		open(siteId, userid, memberSchemas, options);
	};
	return Profile;
});