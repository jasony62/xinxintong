app = angular.module('app', []);
app.controller('ctrl', ['$scope', function($scope) {
	$scope.export = function() {
		var blob, data;
		data = ["abc,123,xyz\nxyz,789,abc"];
		blob = new Blob(data, {
			type: "text/plain;charset=utf-8"
		});
		saveAs(blob, 'hello.txt');
	};
}]);