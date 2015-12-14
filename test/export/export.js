app = angular.module('app', []);
app.controller('ctrl', ['$scope', function($scope) {
	$scope.export = function() {
		var blob, data;
		data = ["abc\t123\txyz\nxyz\t789\tabc"];
		blob = new Blob(data, {
			type: "text/plain;charset=utf-8"
		});
		saveAs(blob, 'hello.cvs');
	};
}]);