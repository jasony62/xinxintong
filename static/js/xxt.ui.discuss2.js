define(['angular'], function(angular) {
    'use strict';
    var ngMod = angular.module('discuss.ui.xxt', []);
    ngMod.service('tmsDiscuss', function() {
        this.showSwitch = function(siteId, threadKey, title, url) {
            var eSwitch;
            eSwitch = document.createElement('div');
            eSwitch.innerHTML = '<i class="glyphicon glyphicon-comment"></i>';
            eSwitch.classList.add('tms-discuss-switch');
            eSwitch.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                location.href = '/rest/site/fe/discuss?site=' + siteId + '&threadKey=' + threadKey + '&title=' + title;
            }, true);
            document.body.appendChild(eSwitch);
        }
    });
    return ngMod;
});