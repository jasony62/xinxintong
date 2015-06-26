xxtApp.controller('channelCtrl',['$scope','http2',function($scope,http2){
    $scope.matterTypes = [
        {value:'article',title:'单图文',url:'/rest/mp/matter'},
        {value:'link',title:'链接',url:'/rest/mp/matter'}
    ];
    $scope.acceptMatterTypes = [
        {name:'',title:'任意'},
        {name:'article',title:'单图文'},
        {name:'link',title:'链接'},
        {name:'enroll',title:'登记活动'},
        {name:'lottery',title:'抽奖活动'},
        {name:'wall',title:'信息墙'},
        {name:'contribute',title:'投稿活动'}
    ];
    $scope.volumes = ['1','2','3','4','5','6','7','8','9','10'];
    $scope.doSearch = function() {
        var url = '/rest/mp/matter/channel?cascade=N', params = {};
        $scope.fromParent && $scope.fromParent === 'Y' && (params.src = 'p');
        http2.post(url, params, function(rsp) {
            $scope.channels = rsp.data;
            if ($scope.channels.length > 0)
                $scope.edit($scope.channels[0]);
        });
    };
    var arrangeMatters = function() {
        $scope.matters = $scope.editing.matters;
        if ($scope.editing.top_type) {
            $scope.topMatter = $scope.matters[0];
            $scope.matters = $scope.matters.slice(1);
        } else
            $scope.topMatter = false;
        if ($scope.editing.bottom_type) {
            var l = $scope.matters.length;
            $scope.bottomMatter = $scope.matters[l-1];
            $scope.matters = $scope.matters.slice(0,l-1);
        } else
            $scope.bottomMatter = false;
    };
    var postFixed = function(pos, params) {
        http2.post('/rest/mp/matter/channel/setfixed?id='+$scope.editing.id+'&pos='+pos, params, function(rsp) {
            if (pos === 'top') {
                $scope.editing.top_type = params.t;
                $scope.editing.top_id = params.id;
            } else if (pos === 'bottom') {
                $scope.editing.bottom_type = params.t;
                $scope.editing.bottom_id = params.id;
            }
            $scope.editing.matters = rsp.data;
            arrangeMatters();
        });
    };
    var editChannel = function(channel) {
        if (channel.url === undefined)
            channel.url = 'http://'+location.host+'/rest/mi/matter?mpid='+$scope.mpid+'&id='+channel.id+'&type=channel';
        $scope.editing = channel;
        arrangeMatters();
    };
    $scope.create = function() {
        var obj = {
            title: '新频道',
            volume: 5
        };
        http2.post('/rest/mp/matter/channel/create', obj, function(rsp) {
            $scope.channels.splice(0, 0, rsp.data);
            $scope.edit(rsp.data);
        });
    };
    $scope.edit = function(channel) {
        if (channel.cascade === undefined) {
            http2.get('/rest/mp/matter/channel/cascade?id='+channel.id, function(rsp) {
                channel.matters = rsp.data.matters;
                channel.acl = rsp.data.acl;
                editChannel(channel);
            });
            channel.cascade='y';
        } else {
            editChannel(channel);
        }
    };
    $scope.deleteOne = function(event,index) {
        event.preventDefault();
        event.stopPropagation();
        http2.get('/rest/mp/matter/channel/delete?id='+$scope.editing.id, function(rsp) {
            $scope.channels.splice(index,1);
            if ($scope.index == $scope.channels.length)
                $scope.edit($scope.channels[index-1]);
            else if ($scope.channels.length > 0)
                $scope.edit($scope.channels[index]);
        });
    };
    $scope.update = function(name){
        var nv = {};
        nv[name] = $scope.editing[name];
        http2.post('/rest/mp/matter/channel/update?id='+$scope.editing.id, nv);
    };
    $scope.setFixed = function(pos,clean) {
        if (!clean) {
            $scope.$broadcast('mattersgallery.open', function(aSelected, matterType){
                if (aSelected.length === 1) {
                    var params = {t:matterType,id:aSelected[0].id};
                    postFixed(pos, params);
                }
            });
        } else {
            var params = {t:null,id:null};
            postFixed(pos, params);
        }
    };
    $scope.removeMatter = function(matter) {
        var removed = {id:matter.id, type:matter.type.toLowerCase()};
        http2.post('/rest/mp/matter/channel/removeMatter?reload=Y&id='+$scope.editing.id, removed, function(rsp) {
            $scope.editing.matters = rsp.data;
            arrangeMatters();
        });
    };
    http2.get('/rest/mp/mpaccount/feature?fields=matter_visible_to_creater', function(rsp) {
        $scope.features = rsp.data;
    });
    $scope.doSearch();
}]);
