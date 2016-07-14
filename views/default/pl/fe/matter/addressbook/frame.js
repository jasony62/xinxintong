ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt']);
ngApp.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
  $routeProvider.when('/rest/pl/fe/matter/addressbook', {
    templateUrl: '/views/default/pl/fe/matter/addressbook/main.html?_=2',
    controller: 'ctrlSetting',
  }).otherwise({
    templateUrl: '/views/default/pl/fe/matter/addressbook/main.html?_=2',
    controller: 'ctrlSetting'
  });
  $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlAddressbook', ['$scope', '$location', 'http2', function($scope, $location, http2) {
  var ls = $location.search();
  $scope.id = ls.id;
  $scope.siteId = ls.site;
}]);
ngApp.controller('ctrlSetting', ['$scope', 'http2', function($scope, http2) {
    var getAddressbooks = function() {
        http2.get('/rest/pl/fe/matter/addressbook/get', function(rsp) {
            $scope.addressbooks = rsp.data;
        });
    };
    $scope.create = function() {
        http2.get('/rest/pl/fe/matter/addressbook/create', function(rsp) {
            /*location.href = '/page/mp/app/addressbook/edit?id='+rsp.data;*/
            location.href = '/page/pl/fe/matter/addressbook/edit?id='+rsp.data;
        });
    };
    $scope.edit = function(addressbook) {
        /*location.href = '/page/mp/app/addressbook/edit?id='+addressbook.id;*/
        location.href = '/page/pl/fe/matter/addressbook/edit?id='+addressbook.id;
    };
    $scope.remove = function(event, addressbook, index){
        event.preventDefault();
        event.stopPropagation();
        http2.get('/rest/pl/fe/matter/addressbook/remove?abid='+addressbook.id, function(rsp) {
            $scope.addressbooks.splice(index, 1);
        });
    };
    $scope.doSearch = function() {
        getAddressbooks();
    };
    $scope.doSearch();
}]);
