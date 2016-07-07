xxtApp.controller('abCtrl',['$scope','http2',function($scope,http2){
    var getAddressbooks = function() {
        http2.get('/rest/pl/fe/matter/addressbook/get', function(rsp) {
            $scope.addressbooks = rsp.data;
        });
    };
    $scope.create = function() {
        http2.get('/rest/pl/fe/matter/addressbook/create', function(rsp) {
            location.href = '/page/pl/fe/matter/addressbook/edit?id='+rsp.data;
        });
    };
    $scope.edit = function(addressbook) {
        location.href = '/page/mp/app/addressbook/edit?id='+addressbook.id;
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
