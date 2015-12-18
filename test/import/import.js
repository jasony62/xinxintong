app = angular.module('app', []);
app.controller('ctrl', ['$scope', function($scope) {
	window.chooseFile = function(file) {
		console.log('file', file);
		var fReader;
		fReader = new FileReader();
		fReader.onload = function(evt) {
			var content;
			content = evt.target.result;
			content = content.split('\r\n');
			console.log('ttt', content.length);
		};
		fReader.readAsText(file);
	};
}]);