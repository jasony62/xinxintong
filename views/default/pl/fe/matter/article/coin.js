define(['frame'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlCoin', ['$scope', 'http2', '$uibModal', '$timeout', 'srvLog', function($scope, http2, $uibModal, $timeout, srvLog) {
		var actions = [{
			name: 'site.matter.article.read',
			desc: '用户A打开图文页面'
		}, {
			name: 'site.matter.article.share.friend',
			desc: '用户A分享图文给公众号好友',
		}, {
			name: 'site.matter.article.share.timeline',
			desc: '用户A分享图文至朋友圈',
		}, {
			name: 'site.matter.article.discuss.like',
			desc: '用户A对图文点赞',
		}, {
			name: 'site.matter.article.discuss.comment',
			desc: '用户A对图文评论',
		}];
		$scope.rules = {};
		actions.forEach(function(act) {
			var name;
			name = act.name;
			$scope.rules[name] = {
				act: name,
				desc: act.desc,
				actor_delta: 0,
			};
		});
		$scope.save = function() {
			var filter = 'ID:' + $scope.id,
				posted = [],
				url, rule;

			for (var k in $scope.rules) {
				rule = $scope.rules[k];
				if (rule.id || rule.actor_delta != 0) {
					var data;
					data = {
						act: rule.act,
						actor_delta: rule.actor_delta,
						matter_type: 'article',
						matter_filter: filter
					};
					rule.id && (data.id = rule.id);
					posted.push(data);
				}
			}
			url = '/rest/pl/fe/matter/article/coin/saveRules?site=' + $scope.siteId;
			http2.post(url, posted, function(rsp) {
				for (var k in rsp.data) {
					$scope.rules[k].id = rsp.data[k];
				}
			});
		};
		$scope.fetchRules = function() {
			var url;
			url = '/rest/pl/fe/matter/article/coin/rules?site=' + $scope.siteId + '&id=' + $scope.id;
			http2.get(url, function(rsp) {
				rsp.data.forEach(function(rule) {
					var rule2 = $scope.rules[rule.act];
					rule2.id = rule.id;
					rule2.actor_delta = rule.actor_delta;
				});
			});
		};
		var cLog;
		$scope.cLog = cLog = {
			page: {},
			list: function() {
				var _this = this;
				srvLog.list($scope.id, this.page, $scope.siteId).then(function(logs) {
					_this.logs = logs;
				});
			}
		};
		$scope.fetchRules();
		cLog.list();
	}]);
});