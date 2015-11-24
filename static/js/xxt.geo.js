(function() {
    window.xxt === undefined && (window.xxt = {});
    window.xxt.geo = {
        options: {},
        getAddress: function($http, deferred, mpid) {
            var promise;
            promise = deferred.promise;
            if (window.wx) {
                window.wx.getLocation({
                    success: function(res) {
                        var url = '/rest/app/enroll/locationGet';
                        url += '?mpid=' + mpid;
                        url += '&lat=' + res.latitude;
                        url += '&lng=' + res.longitude;
                        $http.get(url).success(function(rsp) {
                            if (rsp.err_code === 0) {
                                deferred.resolve({
                                    errmsg: 'ok',
                                    lat: res.latitude,
                                    lng: res.longitude,
                                    address: rsp.data.address
                                });
                            } else {
                                deferred.resolve({
                                    errmsg: rsp.err_msg
                                });
                            }
                        });
                    }
                });
            } else {
                try {
                    var nav = window.navigator;
                    if (nav !== null) {
                        var geoloc = nav.geolocation;
                        if (geoloc !== null) {
                            geoloc.getCurrentPosition(function(position) {
                                var url = '/rest/app/enroll/locationGet';
                                url += '?mpid=' + mpid;
                                url += '&lat=' + position.coords.latitude;
                                url += '&lng=' + position.coords.longitude;
                                $http.get(url).success(function(rsp) {
                                    if (rsp.err_code === 0) {
                                        deferred.resolve({
                                            errmsg: 'ok',
                                            lat: position.coords.latitude,
                                            lng: position.coords.longitude,
                                            address: rsp.data.address
                                        });
                                    } else {
                                        deferred.resolve({
                                            errmsg: rsp.err_msg
                                        });
                                    }
                                });
                            }, function() {
                                deferred.resolve({
                                    errmsg: '获取地理位置失败'
                                })
                            });
                        } else {
                            deferred.resolve({
                                errmsg: "无法获取地理位置"
                            });
                        }
                    } else {
                        deferred.resolve({
                            errmsg: "无法获取地理位置"
                        });
                    }
                } catch (e) {
                    alert('exception:' + e.message);
                }
            }
            return promise;
        },
    };
})();