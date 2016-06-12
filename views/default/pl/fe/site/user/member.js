(function() {
	ngApp.provider.controller('ctrlMember', ['$scope', '$uibModal', '$location', 'http2', function($scope, $uibModal, $location, http2) {
		$scope.$watch('memberSchemas', function(nv) {
			if (!nv) return;
			$scope.schema = $scope.$parent.currentMemberSchema($location.search().schema);
			$scope.searchBys = [];
			$scope.schema.attr_name[0] == 0 && $scope.searchBys.push({
				n: '姓名',
				v: 'name'
			});
			$scope.schema.attr_mobile[0] == 0 && $scope.searchBys.push({
				n: '手机号',
				v: 'mobile'
			});
			$scope.schema.attr_email[0] == 0 && $scope.searchBys.push({
				n: '邮箱',
				v: 'email'
			});
			$scope.page = {
				at: 1,
				size: 30,
				keyword: '',
				searchBy: $scope.searchBys[0].v
			};
			$scope.doSearch(1);
		});
		$scope.doSearch = function(page) {
			page && ($scope.page.at = page);
			var url, filter = '';
			if ($scope.page.keyword !== '') {
				filter = '&kw=' + $scope.page.keyword;
				filter += '&by=' + $scope.page.searchBy;
			}
			url = '/rest/pl/fe/site/member/list?site=' + $scope.siteId + '&schema=' + $scope.schema.id;
			url += '&page=' + $scope.page.at + '&size=' + $scope.page.size + filter
			url += '&contain=total';
			http2.get(url, function(rsp) {
				var i, member, members = rsp.data.members;
				for (i in members) {
					member = members[i];
					if (member.extattr) {
						try {
							member.extattr = JSON.parse(member.extattr);
						} catch (e) {
							member.extattr = {};
						}
					}
				}
				$scope.members = members;
				$scope.page.total = rsp.data.total;
			});
		};
		$scope.editMember = function(member) {
			$uibModal.open({
				templateUrl: 'memberEditor.html',
				backdrop: 'static',
				resolve: {
					schema: function() {
						return angular.copy($scope.schema);
					}
				},
				controller: ['$uibModalInstance', '$scope', 'schema', function($mi, $scope, schema) {
					$scope.schema = schema;
					$scope.member = angular.copy(member);
					$scope.canShow = function(name) {
						return schema && schema['attr_' + name].charAt(0) === '0';
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
					var data = rst.data,
						newData = {
							verified: data.verified,
							name: data.name,
							mobile: data.mobile,
							email: data.email,
							email_verified: data.email_verified,
							extattr: data.extattr
						},
						i, ea;
					for (i in $scope.schema.extattr) {
						ea = $scope.schema.extattr[i];
						newData[ea.id] = rst.data[ea.id];
					}
					http2.post('/rest/pl/fe/site/member/update?site=' + $scope.siteId + '&id=' + member.id, newData, function(rsp) {
						angular.extend(member, newData);
					});
				} else if (rst.action === 'remove') {
					http2.get('/rest/pl/fe/site/member/remove?site=' + $scope.siteId + '&id=' + member.id, function() {
						$scope.members.splice($scope.members.indexOf(member), 1);
					});
				}
			});
		};
	}]);
})();