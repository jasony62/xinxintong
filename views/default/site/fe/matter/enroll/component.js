var ngMod = angular.module('component.enroll', []);

ngMod.directive('tmsDropdown', ['$compile', function($compile) {
	return {
		restrict: 'AE',
		replace: true,
		template: '<div>' +
                    '<div class="site-dropdown" uib-dropdown>' +
                        '<a href uib-dropdown-toggle class="site-dropdown-title">'+ 
                            '<span ng-bind="data.default.value"></span>' +
                            '<span class="glyphicon glyphicon-menu-up"></span>' +
                        '</a>' +
                        '<ul class="dropdown-menu site-dropdown-menu" uib-dropdown-menu>' +
                            '<li ng-repeat="item in data.menu" >' +
                                '<a href ng-click="select(item.key)">{{item.value}}</a>' +
                            '</li>' +
                        '</ul>' +
                    '</div>' +
                 '</div>',
		scope: {
			ngModel: '=',
			data: '=',
			switchMenu: '&'
		},
		link: function(scope, elems, attrs) {
			var $elem = $(elems);

			scope.select = function(key) { 
				for (var item in scope.data.menu) {
                    if (scope.data.menu[item].key === key) {
                        $elem.find(".site-dropdown-title > span").eq(0).text(scope.data.menu[item].value);
                    }
                }               
                scope.ngModel = scope.data.menu[this.$index].key;
                scope.switchMenu(key);
			} 
		}
	}
}]);