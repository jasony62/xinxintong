(function() {
	ngApp.provider.controller('ctrlMember', ['$scope', '$location', 'http2', function($scope, $location, http2) {
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
	}]);
})();