app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt', 'channel.fe.pl']);
app.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
	$routeProvider.when('/rest/pl/fe/matter/custom', {
		templateUrl: '/views/default/pl/fe/matter/custom/setting.html?_=2',
		controller: 'ctrlSetting',
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/custom/setting.html?_=2',
		controller: 'ctrlSetting'
	});
	$locationProvider.html5Mode(true);
}]);
app.controller('ctrlCustom', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	var ls = $location.search();
	$scope.id = ls.id;
	$scope.siteId = ls.site;
	http2.get('/rest/pl/fe/matter/custom/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
		var url;
		$scope.editing = rsp.data;
		url = 'http://' + location.host + '/rest/site/fe/matter?site=' + ls.site + '&id=' + ls.id + '&type=custom';
		$scope.entry = {
			url: url,
			qrcode: '/rest/pl/fe/matter/custom/qrcode?url=' + encodeURIComponent(url),
		};
	});
}]);
app.controller('ctrlSetting', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
	var modifiedData = {};
	$scope.modified = false;
	$scope.back = function() {
		history.back();
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
	$scope.submit = function() {
		http2.post('/rest/pl/fe/matter/custom/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function() {
			modifiedData = {};
			$scope.modified = false;
		});
	};
	$scope.update = function(name) {
		$scope.modified = true;
		modifiedData[name] = name === 'body' ? encodeURIComponent($scope.editing[name]) : $scope.editing[name];
	};
	$scope.copy = function() {
		http2.get('/rest/pl/fe/matter/custom/copy?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
			location.href = '/rest/pl/fe/matter/custom?site=' + $scope.siteId + '&id=' + rsp.data;
		});
	};
	$scope.remove = function() {
		http2.get('/rest/pl/fe/matter/custom/remove?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
			location.href = '/rest/pl/fe/site/console?site=' + $scope.siteId;
		});
	};
	$scope.setPic = function() {
		var options = {
			callback: function(url) {
				$scope.editing.pic = url + '?_=' + (new Date()) * 1;
				$scope.update('pic');
			}
		};
		mediagallery.open($scope.siteId, options);
	};
	$scope.removePic = function() {
		$scope.editing.pic = '';
		$scope.update('pic');
	};
	$scope.gotoCode = function() {
		var name = $scope.editing.body_page_name;
		if (name && name.length) {
			window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + name);
		} else {
			http2.get('/rest/pl/fe/code/create?site=' + $scope.siteId, function(rsp) {
				var nv = {
					'page_id': rsp.data.id,
					'body_page_name': rsp.data.name
				};
				http2.post('/rest/pl/fe/matter/custom/update?site=' + $scope.siteId + '&id=' + $scope.id, nv, function() {
					$scope.editing.page_id = rsp.data.id;
					$scope.editing.body_page_name = rsp.data.name;
					window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name);
				});
			});
		}
	};
	$scope.selectTemplate = function() {
		templateShop.choose('custom').then(function(data) {
			http2.get('/rest/pl/fe/matter/custom/pageByTemplate?id=' + $scope.editing.id + '&template=' + data.id, function(rsp) {
				$scope.editing.page_id = rsp.data;
				location.href = '/rest/code?pid=' + rsp.data;
			});
		});
	};
	$scope.saveAsTemplate = function() {
		var matter, editing;
		editing = $scope.editing;
		matter = {
			id: editing.id,
			type: 'custom',
			title: editing.title,
			pic: editing.pic,
			summary: editing.summary
		};
		templateShop.share($scope.siteId, matter).then(function() {
			$scope.$root.infomsg = '成功';
		});
	};
	$scope.$on('tag.xxt.combox.done', function(event, aSelected) {
		var aNewTags = [];
		angular.forEach(aSelected, function(selected) {
			var existing = false;
			angular.forEach($scope.editing.tags, function(tag) {
				if (selected.title === tag.title) {
					existing = true;
				}
			});
			!existing && aNewTags.push(selected);
		});
		http2.post('/rest/pl/fe/matter/custom/tag/add?site=' + $scope.siteId + '&id=' + $scope.id, aNewTags, function(rsp) {
			$scope.editing.tags = $scope.editing.tags.concat(aNewTags);
		});
	});
	$scope.$on('tag.xxt.combox.add', function(event, newTag) {
		var oNewTag = {
			title: newTag
		};
		http2.post('/rest/pl/fe/matter/custom/tag/add?site=' + $scope.siteId + '&id=' + $scope.id, [oNewTag], function(rsp) {
			$scope.editing.tags.push(oNewTag);
		});
	});
	$scope.$on('tag.xxt.combox.del', function(event, removed) {
		http2.post('/rest/pl/fe/matter/custom/tag/remove?site=' + $scope.siteId + '&id=' + $scope.id, [removed], function(rsp) {
			$scope.editing.tags.splice($scope.editing.tags.indexOf(removed), 1);
		});
	});
	http2.get('/rest/pl/fe/matter/tag/list?site=' + $scope.siteId + '&resType=article&subType=0', function(rsp) {
		$scope.tags = rsp.data;
	});
}]);