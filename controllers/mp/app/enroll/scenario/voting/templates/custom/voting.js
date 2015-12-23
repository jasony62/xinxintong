(function() {
	app.register.controller('customCtrl', function($scope) {
		$scope.chooseOption = function(prop, op) {
			$scope.data[prop] === undefined && ($scope.data[prop] = {});
			$scope.data[prop][op] = !$scope.data[prop][op];
		};
	});
})();