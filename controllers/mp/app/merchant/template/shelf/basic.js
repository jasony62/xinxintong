app.register.controller('shelfCtrl', ['$scope', '$http', 'Catelog', 'Product', function($scope, $http, Catelog, Product) {
	var facCatelog, facProduct, options;
	facCatelog = new Catelog($scope.$parent.mpid, $scope.$parent.shopId);
	facProduct = new Product($scope.$parent.mpid, $scope.$parent.shopId);
	options = {
		propValues: []
	};
	facCatelog.get().then(function(catelogs) {
		$scope.catelogs = catelogs;
		if (catelogs.length) {
			$scope.selectedCatelog = catelogs[0];
			$scope.listProduct();
		}
	});
	$scope.filterOpened = false;
	$scope.toggleFilter = function() {
		$scope.filterOpened = !$scope.filterOpened;
	};
	$scope.clickOption = function(prop, propValue) {
		propValue._selected = !propValue._selected;
		if (propValue._selected) {
			options.propValues.push(propValue.id);
		} else {
			options.propValues.splice(options.propValues.indexOf(propValue.id), 1);
		}
	};
	$scope.listProduct = function() {
		var vids;
		vids = options.propValues.join(',');
		facProduct.list($scope.selectedCatelog.id, vids).then(function(data) {
			$scope.products = data.products;
			$scope.toggleFilter();
		});
	};
}]);