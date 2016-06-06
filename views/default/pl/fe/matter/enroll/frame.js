ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'matters.xxt', 'channel.fe.pl']);
ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider) {
	ngApp.provider = {
		controller: $controllerProvider.register,
		directive: $compileProvider.directive
	};
	$routeProvider.when('/rest/pl/fe/matter/enroll/schema', {
		templateUrl: '/views/default/pl/fe/matter/enroll/schema.html?_=3',
		controller: 'ctrlSchema',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/schema.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/page', {
		templateUrl: '/views/default/pl/fe/matter/enroll/page.html?_=4',
		controller: 'ctrlPage',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/page.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/event', {
		templateUrl: '/views/default/pl/fe/matter/enroll/event.html?_=2',
		controller: 'ctrlEntry',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/event.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/record', {
		templateUrl: '/views/default/pl/fe/matter/enroll/record.html?_=3',
		controller: 'ctrlRecord',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/record.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/stat', {
		templateUrl: '/views/default/pl/fe/matter/enroll/stat.html?_=1',
		controller: 'ctrlStat',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/stat.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/coin', {
		templateUrl: '/views/default/pl/fe/matter/enroll/coin.html?_=1',
		controller: 'ctrlCoin',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/coin.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/publish', {
		templateUrl: '/views/default/pl/fe/matter/enroll/publish.html?_=2',
		controller: 'ctrlRunning',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/publish.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/config', {
		templateUrl: '/views/default/pl/fe/matter/enroll/config.html?_=2',
		controller: 'ctrlConfig',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/config.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/enroll/app.html?_=3',
		controller: 'ctrlApp',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/app.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	});
	$locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlFrame', ['$scope', '$location', '$modal', '$q', 'http2', function($scope, $location, $modal, $q, http2) {
	var ls = $location.search(),
		modifiedData = {},
		PageBase = {
			arrange: function(mapOfAppSchemas) {
				var dataSchemas = this.data_schemas,
					actSchemas = this.act_schemas,
					userSchemas = this.user_schemas;
				this.data_schemas = dataSchemas && dataSchemas.length ? JSON.parse(dataSchemas) : [];
				this.act_schemas = actSchemas && actSchemas.length ? JSON.parse(actSchemas) : [];
				this.user_schemas = userSchemas && userSchemas.length ? JSON.parse(userSchemas) : [];
				if (this.data_schemas.length) {
					if (this.type === 'I') {
						var pageSchemas = [];
						angular.forEach(this.data_schemas, function(pageSchema) {
							mapOfAppSchemas[pageSchema.id] && pageSchemas.push(mapOfAppSchemas[pageSchema.id]);
						});
						this.data_schemas = pageSchemas;
					} else if (this.type === 'V') {
						angular.forEach(this.data_schemas, function(config) {
							if (config.pattern === 'record') {
								mapOfAppSchemas[config.schema.id] && (config.schema = mapOfAppSchemas[config.schema.id]);
							}
						});
					} else if (this.type === 'L') {
						angular.forEach(this.data_schemas, function(config) {
							if (config.pattern === 'record-list') {
								var listSchemas = [];
								angular.forEach(config.schemas, function(schema) {
									listSchemas.push(mapOfAppSchemas[schema.id] ? mapOfAppSchemas[schema.id] : schema);
								});
								config.schemas = listSchemas;
							}
						});
					}
				} else if (angular.isObject(this.data_schemas)) {
					this.data_schemas = [];
				}
			},
			containInput: function(schema) {
				var i, l;
				if (this.type === 'I') {
					for (i = 0, l = this.data_schemas.length; i < l; i++) {
						if (this.data_schemas[i].id === schema.id) {
							return this.data_schemas[i];
						}
					}
				} else if (this.type === 'V') {
					if (this.data_schemas.record) {
						for (i = 0, l = this.data_schemas.record.length; i < l; i++) {
							if (this.data_schemas.record[i].schema.id === schema.id) {
								return this.data_schemas.record[i].schema;
							}
						}
					}
					if (this.data_schemas.list) {
						var list, j, k;
						for (i = 0, l = this.data_schemas.list.length; i < l; i++) {
							list = this.data_schemas.list[i];
							for (j = 0, k = list.schemas.length; j < k; j++) {
								if (list.schemas[j].id === schema.id) {
									return list.schemas[j];
								}
							}
						}
					}
				}
				return false;
			},
			removeInput: function(schema) {
				var i, l;
				if (this.type === 'I') {
					for (i = 0, l = this.data_schemas.length; i < l; i++) {
						if (this.data_schemas[i].id === schema.id) {
							return this.data_schemas.splice(i, 1);
						}
					}
				}
				return false;
			},
			containAct: function(schema) {
				var i, l;
				for (i = 0, l = this.act_schemas.length; i < l; i++) {
					if (this.act_schemas[i].id === schema.id) {
						return this.act_schemas[i];
					}
				}
				return false;
			},
			containStatic: function(schema) {
				if (this.type === 'V') {
					for (i = 0, l = this.data_schemas.length; i < l; i++) {
						if (this.data_schemas[i].id === schema.id) {
							return this.data_schemas[i];
						}
					}
				}
				return false;
			},
			containList: function(schema) {
				if (this.type === 'L') {
					for (i = 0, l = this.data_schemas.length; i < l; i++) {
						if (this.data_schemas[i].id === schema.id) {
							return this.data_schemas[i];
						}
					}
				}
				return false;
			},
			removeAct: function(schema) {
				var i, l;
				for (i = 0, l = this.act_schemas.length; i < l; i++) {
					if (this.act_schemas[i].id === schema.id) {
						return this.act_schemas.splice(i, 1);
					}
				}
				return false;
			},
			removeStatic: function(config) {
				if (this.type === 'V') {
					for (var i = 0, l = this.data_schemas.length; i < l; i++) {
						if (this.data_schemas[i].id === config.id) {
							if (config.schema) {
								for (var j = 0, k = this.data_schemas[i].schemas.length; j < k; j++) {
									if (this.data_schemas[i].schemas[j].id === config.schema.id) {
										return this.data_schemas[i].schemas.splice(j, 1);
									}
								}
							} else {
								return this.data_schemas.splice(i, 1);
							}
						}
					}
				}
				return false;
			},
			updateBySchema: function(schema) {
				if (this.type === 'V' || this.type === 'L') {
					var $html = $('<div>' + this.html + '</div>');
					$html.find("[schema='" + schema.id + "']").find('label').html(schema.title);
					this.html = $html.html();
				}
			},
			removeBySchema: function(schema) {
				if (this.type === 'V' || this.type === 'L') {
					var $html = $('<div>' + this.html + '</div>');
					$html.find("[schema='" + schema.id + "']").remove();
					this.html = $html.html();
				}
			},
			appendRecord: function(config) {
				if (config.schema === undefined) {
					console.error('WrapLib.record.embed: schema is empty.', config);
					return false;
				}
				var wrapAttrs, wrapHtml, newWrap, $newHtml;
				/* make wrap */
				wrapAttrs = wrapLib.record.wrapAttrs(config);
				wrapHtml = wrapLib.record.schemaHtml(config.schema);
				newWrap = $('<div></div>').attr(wrapAttrs).append(wrapHtml);
				/* update page */
				$newHtml = $('<div>' + this.html + '</div>');
				$newHtml.find("[wrap='static']:last").after(newWrap);
				this.html = $newHtml.html();

				return true;
			}
		};
	$scope.id = ls.id;
	$scope.siteId = ls.site;
	$scope.modified = false;
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
	$scope.back = function() {
		history.back();
	};
	$scope.submit = function() {
		var defer = $q.defer();
		http2.post('/rest/pl/fe/matter/enroll/update?site=' + $scope.siteId + '&app=' + $scope.id, modifiedData, function(rsp) {
			$scope.modified = false;
			modifiedData = {};
			defer.resolve(rsp.data);
		});
		return defer.promise;
	};
	$scope.update = function(name) {
		if (['entry_rule'].indexOf(name) !== -1) {
			modifiedData[name] = encodeURIComponent($scope.app[name]);
		} else if (name === 'tags') {
			modifiedData.tags = $scope.app.tags.join(',');
		} else {
			modifiedData[name] = $scope.app[name];
		}
		$scope.modified = true;

		return $scope.submit();
	};
	$scope.createPage = function() {
		var deferred = $q.defer();
		$modal.open({
			templateUrl: '/views/default/pl/fe/matter/enroll/component/createPage.html?_=2',
			backdrop: 'static',
			controller: ['$scope', '$modalInstance', function($scope, $mi) {
				$scope.options = {};
				$scope.ok = function() {
					$mi.close($scope.options);
				};
				$scope.cancel = function() {
					$mi.dismiss();
				};
			}],
		}).result.then(function(options) {
			http2.post('/rest/pl/fe/matter/enroll/page/add?site=' + $scope.siteId + '&app=' + $scope.id, options, function(rsp) {
				var page = rsp.data;
				angular.extend(page, PageBase);
				page.arrange();
				$scope.app.pages.push(page);
				deferred.resolve(page);
			});
		});

		return deferred.promise;
	};
	$scope.getApp = function() {
		http2.get('/rest/pl/fe/matter/enroll/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
			var app = rsp.data,
				mapOfAppSchemas = {};
			app.tags = (!app.tags || app.tags.length === 0) ? [] : app.tags.split(',');
			app.type = 'enroll';
			app.data_schemas = app.data_schemas && app.data_schemas.length ? JSON.parse(app.data_schemas) : [];
			angular.forEach(app.data_schemas, function(schema) {
				mapOfAppSchemas[schema.id] = schema;
			});
			app.entry_rule.scope === undefined && (app.entry_rule.scope = 'none');
			angular.forEach(app.pages, function(page) {
				angular.extend(page, PageBase);
				page.arrange(mapOfAppSchemas);
			});
			//$scope.persisted = angular.copy(app);
			$scope.app = app;
			$scope.url = 'http://' + location.host + '/rest/site/fe/matter/enroll?site=' + $scope.siteId + '&app=' + $scope.id;
		});
	};
	http2.get('/rest/pl/fe/site/snsList?site=' + $scope.siteId, function(rsp) {
		$scope.sns = rsp.data;
	});
	http2.get('/rest/pl/fe/site/member/schema/list?valid=Y&site=' + $scope.siteId, function(rsp) {
		$scope.memberSchemas = rsp.data;
	});
	$scope.getApp();
}]);