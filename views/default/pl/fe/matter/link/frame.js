ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt', 'member.xxt', 'channel.fe.pl']);
ngApp.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
	$routeProvider.when('/rest/pl/fe/matter/link', {
		templateUrl: '/views/default/pl/fe/matter/link/setting.html?_=2',
		controller: 'ctrlSetting',
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/link/setting.html?_=2',
		controller: 'ctrlSetting'
	});
	$locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlLink', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	var ls = $location.search();
	$scope.id = ls.id;
	$scope.siteId = ls.site;
	http2.get('/rest/pl/fe/matter/link/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
		$scope.editing = rsp.data;
		$scope.entryUrl = 'http://' + location.host + '/rest/site/fe/matter/link?site=' + $scope.siteId + '&id=' + $scope.id;
	});
}]);
ngApp.controller('ctrlSetting', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
	var modifiedData = {};
	$scope.modified = false;
	$scope.urlsrcs = {
		'0': '外部链接',
		'1': '多图文',
		'2': '频道',
		'3': '内置回复',
	};
	$scope.linkparams = {
		'{{openid}}': '用户标识(openid)',
		'{{site}}': '公众号标识',
	};
	var getInitData = function() {
		http2.get('/rest/pl/fe/matter/link/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
			editLink(rsp.data);
		});
	};
	var editLink = function(link) {
		if (link.params) {
			var p;
			for (var i in link.params) {
				p = link.params[i];
				p.customValue = $scope.linkparams[p.pvalue] ? false : true;
			}
		}
		$scope.editing = link;
		$scope.persisted = angular.copy(link);
		$('[ng-model="editing.title"]').focus();
	};
	window.onbeforeunload = function(e) {
		var message;
		if ($scope.modified) {
			message = '修改还没有保存，是否要离开当前页面？',
				e = e || window.event;
			if (e) {
				e.returnValue = message;
			}
			return message;
		}
	};
	$scope.remove = function() {
		http2.get('/rest/pl/fe/matter/link/remove?site=' + $scope.siteId + '&id=' + $scope.id, function() {
			location.href = '/rest/pl/fe/site/console?site=' + $scope.siteId;
		});
	};
	$scope.submit = function() {
		http2.post('/rest/pl/fe/matter/link/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function() {
			modifiedData = {};
			$scope.modified = false;
		});
	};
	$scope.update = function(n) {
		modifiedData[n] = $scope.editing[n];
		if (n === 'urlsrc' && $scope.editing.urlsrc != 0) {
			$scope.editing.open_directly = 'N';
			modifiedData.open_directly = 'N';
		} else if (n === 'method' && $scope.editing.method === 'POST') {
			$scope.editing.open_directly = 'N';
			modifiedData.open_directly = 'N';
		} else if (n === 'open_directly' && $scope.editing.open_directly == 'Y') {
			$scope.editing.access_control = 'N';
			modifiedData.access_control = 'N';
			modifiedData.authapis = '';
		} else if (n === 'access_control' && $scope.editing.access_control == 'N') {
			var p;
			for (var i in $scope.editing.params) {
				p = $scope.editing.params[i];
				if (p.pvalue == '{{authed_identity}}') {
					window.alert('只有在进行访问控制的情况下，才可以指定和用户身份相关的信息！');
					$scope.editing.access_control = 'Y';
					modifiedData.access_control = 'Y';
					return false;
				}
			}
			modifiedData.authapis = '';
		}
		$scope.modified = true;
	};
	$scope.setPic = function() {
		var options = {
			callback: function(url) {
				$scope.app.pic = url + '?_=' + (new Date()) * 1;
				$scope.update('pic');
			}
		};
		mediagallery.open($scope.siteId, options);
	};
	$scope.removePic = function() {
		$scope.editing.pic = '';
		$scope.update('pic');
	};
	$scope.addParam = function() {
		http2.get('/rest/pl/fe/matter/link/paramAdd?site=' + $scope.siteId + '&linkid=' + $scope.editing.id, function(rsp) {
			var oNewParam = {
				id: rsp.data,
				pname: 'newparam',
				pvalue: ''
			};
			if ($scope.editing.urlsrc === '3' && $scope.editing.url === '9') oNewParam.pname = 'channelid';
			$scope.editing.params.push(oNewParam);
		});
	};
	$scope.updateParam = function(updated, name) {
		if (updated.pvalue === '{{authed_identity}}' && $scope.editing.access_control === 'N') {
			window.alert('只有在进行访问控制的情况下，才可以指定和用户身份相关的信息！');
			updated.pvalue = '';
		}
		if (updated.pvalue !== '{{authed_identity}}')
			updated.authapi_id = 0;
		// 参数中有额外定义，需清除
		var p = {
			pname: updated.pname,
			pvalue: encodeURIComponent(updated.pvalue),
			authapi_id: updated.authapi_id
		};
		http2.post('/rest/pl/fe/matter/link/paramUpd?site=' + $scope.siteId + '&id=' + updated.id, p);
	};
	$scope.removeParam = function(removed) {
		http2.get('/rest/mp/matter/link/removeParam?id=' + removed.id, function(rsp) {
			var i = $scope.editing.params.indexOf(removed);
			$scope.editing.params.splice(i, 1);
		});
	};
	$scope.changePValueMode = function(p) {
		p.pvalue = '';
	};
	$scope.$watch('editing.urlsrc', function(nv) {
		switch (nv) {
			case '1':
				if ($scope.news === undefined) {
					http2.get('/rest/pl/fe/matter/news/list?site=' + $scope.siteId + '&cascade=N', function(rsp) {
						$scope.news = rsp.data;
					});
				}
				break;
			case '2':
				if ($scope.channels === undefined) {
					http2.get('/rest/pl/fe/matter/channel/list?site=' + $scope.siteId + '&cascade=N', function(rsp) {
						$scope.channels = rsp.data;
					});
				}
				break;
			case '3':
				if ($scope.inners === undefined) {
					http2.get('/rest/pl/fe/matter/inner/list?site=' + $scope.siteId, function(rsp) {
						$scope.inners = rsp.data;
					});
				}
				break;
		}
	});
	getInitData();
}]);