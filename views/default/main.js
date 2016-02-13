define(['require', 'angular'], function(require, angular) {
    'use strict';
    var site = location.search.match('site=(.*)')[1];
    var loadCss = function(url) {
        var link, head;
        link = document.createElement('link');
        link.href = url + '?_=3';
        link.rel = 'stylesheet';
        head = document.querySelector('head');
        head.appendChild(link);
    };
    var app = angular.module('app', []);
    app.controller('ctrlMain', ['$scope', '$http', function($scope, $http) {
        loadCss("https://res.wx.qq.com/open/libs/weui/0.3.0/weui.min.css");
        window.loading.finish();
    }]);
});