'use strict';
define(["require", "angular", "angular-route"], function(require, angular) {
	var app = angular.module('app', ['ngRoute']);
	/* 延迟加载模块 */
	angular._lazyLoadModule = function(moduleName) {
		var m = angular.module(moduleName);
		console.log('register module:' + moduleName);
		/* 应用的injector，和config中的injector不是同一个，是instanceInject，返回的是通过provider.$get创建的实例 */
		var $injector = angular.element(document).injector();
		/* 递归加载依赖的模块 */
		angular.forEach(m.requires, function(r) {
			angular._lazyLoadModule(r);
		});
		/* 用provider的injector运行模块的controller，directive等等 */
		angular.forEach(m._invokeQueue, function(invokeArgs) {
			try {
				var provider = app.providers.$injector.get(invokeArgs[0]);
				provider[invokeArgs[1]].apply(provider, invokeArgs[2]);
			} catch (e) {
				console.error('load module invokeQueue failed:' + e.message, invokeArgs);
			}
		});
		/* 用provider的injector运行模块的config */
		angular.forEach(m._configBlocks, function(invokeArgs) {
			try {
				app.providers.$injector.invoke.apply(app.providers.$injector, invokeArgs[2]);
			} catch (e) {
				console.error('load module configBlocks failed:' + e.message, invokeArgs);
			}
		});
		/* 用应用的injector运行模块的run */
		angular.forEach(m._runBlocks, function(fn) {
			$injector.invoke(fn);
		});
	};
	app.config(['$injector', '$locationProvider', '$routeProvider', '$controllerProvider', function($injector, $locationProvider, $routeProvider, $controllerProvider) {
		/*＊
		 ＊ config中的injector和应用的injector不是同一个，是providerInjector，获得的是provider，而不是通过provider创建的实例
		 ＊ 这个injector通过angular无法获得，所以在执行config的时候把它保存下来
		*/
		app.providers = {
			$injector: $injector,
			$controllerProvider: $controllerProvider
		};
		/* 必须设置生效，否则下面的设置不生效 */
		$locationProvider.html5Mode(true);
		/* 根据url的变化加载内容 */
		$routeProvider.when('/test/lazyspa/page1', {
			template: '<div>page1</div><div ng-include="\'page1.html\'"></div>',
			controller: 'ctrlPage1'
		}).when('/test/lazyspa/page2', {
			template: '<div ng-controller="ctrlModule1"><div>page2</div><div><button ng-click="openDialog()">open dialog</button></div></div>',
			resolve: {
				load: ['$q', function($q) {
					var defer = $q.defer();
					/* 动态加载angular模块 */
					require(['/test/lazyspa/module1.js'], function(loader) {
						loader.onload && loader.onload(function() {
							defer.resolve();
						});
					});
					return defer.promise;
				}]
			}
		}).otherwise({
			template: '<div>main</div>',
		});
	}]);
	app.controller('ctrlMain', ['$scope', '$location', function($scope, $location) {
		console.log('main controller');
		/* 根据业务逻辑自动到缺省的视图 */
		$location.url('/test/lazyspa/page1');
	}]);
	app.controller('ctrlPage1', ['$scope', '$templateCache', function($scope, $templateCache) {
		/* 用这种方式，ng-include配合，根据业务逻辑动态获取页面内容 */
		/* 动态的定义controller */
		app.providers.$controllerProvider.register('ctrlPage1Dyna', ['$scope', function($scope) {
			$scope.openAlert = function() {
				alert('page1 alert');
			};
		}]);
		/* 动态定义页面内容 */
		$templateCache.put('page1.html', '<div ng-controller="ctrlPage1Dyna"><button ng-click="openAlert()">alert</button></div>');
	}]);
	require(['domReady!'], function(document) {
		angular.bootstrap(document, ["app"]);
		window.loading.finish();
	});
});