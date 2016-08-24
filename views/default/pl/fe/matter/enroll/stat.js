define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlStat', ['$scope', 'http2', '$timeout', function($scope, http2, $timeout) {
		function drawChart(item) {
			var categories = [],
				series = [];
			angular.forEach(item.ops, function(op) {
				categories.push(op.l);
				series.push(parseInt(op.c));
			});
			new Highcharts.Chart({
				chart: {
					type: 'bar',
					renderTo: item.id
				},
				title: {
					text: item.title
				},
				legend: {
					enabled: false
				},
				xAxis: {
					categories: categories
				},
				yAxis: {
					'title': '',
					allowDecimals: false
				},
				series: [{
					data: series
				}]
			});
		}
		$scope.$watch('app', function(app) {
			if (!app) return;
			var url = '/rest/pl/fe/matter/enroll/stat/get';
			url += '?site=' + $scope.siteId;
			url += '&app=' + app.id;
			http2.get(url, function(rsp) {
				$scope.stat = rsp.data;
				$timeout(function() {
					angular.forEach(rsp.data, function(item) {
						drawChart(item);
					});
				});
			});
		});
	}]);
});