'use strict';
define(["require", "angular"], function(require, angular) {
	var module = angular.module('util.site.tms', []);
	module.service('PageLoader', ['$q', function($q) {
		this.render = function($scope, data) {
			var defer = $q.defer();
			if (data.ext_css && data.ext_css.length) {
				angular.forEach(data.ext_css, function(css) {
					var link, head;
					link = document.createElement('link');
					link.href = css.url;
					link.rel = 'stylesheet';
					head = document.querySelector('head');
					head.appendChild(link);
				});
			}
			if (data.ext_js && data.ext_js.length) {
				var i, l, loadJs;
				i = 0;
				l = data.ext_js.length;
				loadJs = function() {
					var js;
					js = data.ext_js[i];
					$.getScript(js.url, function() {
						i++;
						if (i === l) {
							if (data.js && data.js.length) {
								$scope.$apply(
									function dynamicjs() {
										eval(data.js);
										defer.resolve();
									}
								);
							}
						} else {
							loadJs();
						}
					});
				};
				loadJs();
			} else if (data.js && data.js.length) {
				(function dynamicjs() {
					eval(data.js);
					defer.resolve();
				})();
			} else {
				defer.resolve();
			}
			return defer.promise;
		}
	}]);
	module.factory('PageUrl', [function() {
		var PU;
		PU = function(baseUrl, fields) {
			this.baseUrl = baseUrl;
			this.fields = fields;
			this.params = (function extract() {
				var ls, search;
				ls = location.search;
				search = {};
				angular.forEach(fields, function(q) {
					var match, pattern;
					pattern = new RegExp(q + '=([^&]*)');
					match = ls.match(pattern);
					search[q] = match ? match[1] : '';
				});
				return search;
			})();
		};
		PU.prototype.j = function(method) {
			var i = 1,
				l = arguments.length,
				url = this.baseUrl,
				search = [];
			method && method.length && (url += '/' + method);
			for (; i < l; i++) {
				search.push(arguments[i] + '=' + this.params[arguments[i]]);
			};
			search.length && (url += '?' + search.join('&'));
			return url;
		};
		return {
			ins: function(baseUrl, fields) {
				return new PU(baseUrl, fields);
			}
		};
	}]);
	return module;
});