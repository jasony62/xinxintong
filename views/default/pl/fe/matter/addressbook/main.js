xxtApp.controller('abCtrl',['$scope','http2',function($scope,http2){
    var getAddressbooks = function() {
        http2.get('/rest/mp/app/addressbook/get', function(rsp) {
            $scope.addressbooks = rsp.data;
        });
    };
    $scope.create = function() {
        http2.get('/rest/mp/app/addressbook/create', function(rsp) {
            location.href = '/page/mp/app/addressbook/edit?id='+rsp.data;
        });
    };
    $scope.edit = function(addressbook) {
        location.href = '/page/mp/app/addressbook/edit?id='+addressbook.id;
    };
    $scope.remove = function(event, addressbook, index){
        event.preventDefault();
        event.stopPropagation();
        http2.get('/rest/mp/app/addressbook/remove?abid='+addressbook.id, function(rsp) {
            $scope.addressbooks.splice(index, 1);
        });
    };
    $scope.doSearch = function() {
        getAddressbooks();
    };
    $scope.doSearch();
}]);
