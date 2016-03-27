(function() {
	app.register.controller('customCtrl', function($scope) {
		$scope.chooseOption = function(event, prop, op) {
			event.preventDefault();
			event.stopPropagation();
			var ct;
			ct = event.currentTarget;
			if (ct.hasAttribute('disabled')) {
				return false;
			}
			$scope.data[prop] === undefined && ($scope.data[prop] = {});
			if ($scope.data[prop][op]) {
				ct.classList.remove('checked');
				$scope.data[prop][op] = false;
			} else {
				ct.classList.add('checked');
				$scope.data[prop][op] = true;
			}
		};
	});
})();