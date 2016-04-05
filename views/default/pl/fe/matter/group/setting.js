(function() {
	ngApp.provider.controller('ctrlSetting', ['$scope', '$location', 'http2', '$modal', 'mediagallery', function($scope, $location, http2, $modal, mediagallery) {
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
		$scope.run = function() {
			$scope.app.state = 2;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/group/running?site=' + $scope.siteId + '&id=' + $scope.id;
			});
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
			var nv = {
				pic: ''
			};
			http2.post('/rest/mp/app/group/update?aid=' + $scope.id, nv, function() {
				$scope.app.pic = '';
			});
		};
		$scope.gotoCode = function() {
			var app, url;
			app = $scope.app;
			if (app.page_code_id != 0) {
				window.open('/rest/code?pid=' + app.page_code_id, '_self');
			} else {
				url = '/rest/pl/fe/matter/group/page/create?site=' + $scope.siteId + '&app=' + app.id;
				http2.get(url, function(rsp) {
					app.page_code_id = rsp.data;
					window.open('/rest/code?pid=' + app.page_code_id, '_self');
				});
			}
		};
		$scope.resetCode = function() {
			var app, url;
			if (window.confirm('重置操作将丢失已做修改，确定？')) {
				app = $scope.app;
				url = '/rest/pl/fe/matter/group/page/reset?site=' + $scope.siteId + '&app=' + app.id;;
				http2.get(url, function(rsp) {
					window.open('/rest/code?pid=' + app.page_code_id, '_self');
				});
			}
		};
	}]);
	ngApp.provider.controller('ctrlRound', ['$scope', 'http2', function($scope, http2) {
		$scope.aTargets = null;
		$scope.addRound = function() {
			http2.get('/rest/pl/fe/matter/group/round/add?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
				$scope.rounds.push(rsp.data);
			});
		};
		$scope.open = function(round) {
			$scope.editingRound = round;
			$scope.aTargets = (!round || round.targets.length === 0) ? [] : eval(round.targets);
		};
		$scope.updateRound = function(name) {
			var nv = {};
			nv[name] = $scope.editingRound[name];
			http2.post('/rest/pl/fe/matter/group/round/update?site=' + $scope.siteId + '&app=' + $scope.id + '&rid=' + $scope.editingRound.round_id, nv);
		};
		$scope.removeRound = function() {
			http2.post('/rest/pl/fe/matter/group/round/remove?site=' + $scope.siteId + '&app=' + $scope.id + '&rid=' + $scope.editingRound.round_id, function(rsp) {
				var i = $scope.rounds.indexOf($scope.editingRound);
				$scope.rounds.splice(i, 1);
			});
		};
		$scope.addTarget = function() {
			var target = {
				tags: []
			};
			$scope.aTargets.push(target);
		};
		$scope.removeTarget = function(i) {
			$scope.aTargets.splice(i, 1);
		};
		$scope.saveTargets = function() {
			var arr = [];
			for (var i in $scope.aTargets)
				arr.push({
					tags: $scope.aTargets[i].tags
				});
			$scope.editingRound.targets = JSON.stringify(arr);
			$scope.updateRound('targets');
		};
		$scope.$on('tag.xxt.combox.done', function(event, aSelected, state) {
			var aNewTags = [];
			for (var i in aSelected) {
				var existing = false;
				for (var j in $scope.aTargets[state].tags) {
					if (aSelected[i] === $scope.aTargets[state].tags[j]) {
						existing = true;
						break;
					}
				}!existing && aNewTags.push(aSelected[i]);
			}
			$scope.aTargets[state].tags = $scope.aTargets[state].tags.concat(aNewTags);
		});
		$scope.$on('tag.xxt.combox.add', function(event, newTag, state) {
			$scope.aTargets[state].tags.push(newTag);
			if ($scope.aTags.indexOf(newTag) === -1) {
				$scope.aTags.push(newTag);
				$scope.update('tags');
			}
		});
		$scope.$on('tag.xxt.combox.del', function(event, removed, state) {
			$scope.aTargets[state].tags.splice($scope.aTargets[state].tags.indexOf(removed), 1);
		});
		$scope.$watch('app', function(nv) {
			if (!nv) return;
			$scope.aTags = $scope.app.tags;
			http2.get('/rest/pl/fe/matter/group/round/list?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
				$scope.rounds = rsp.data;
			});
		});
	}]);
})();