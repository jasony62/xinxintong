var timestamp, minutes;
timestamp = new Date();
minutes = timestamp.getMinutes();
minutes = Math.floor(minutes / 5) * 5;
timestamp.setMinutes(minutes);
timestamp.setMilliseconds(0);
timestamp.setSeconds(0);
require.config({
    waitSeconds: 0,
    paths: {
        "domReady": '/static/js/domReady',
        "enrollSchema": '/views/default/pl/fe/matter/enroll/lib/enroll.schema',
        "enrollPage": '/views/default/pl/fe/matter/enroll/lib/enroll.page',
        "enrollService": '/views/default/pl/fe/matter/enroll/lib/enroll.service',
        "groupService": '/views/default/pl/fe/matter/group/lib/group.service',
        "page": '/views/default/pl/fe/matter/enroll/lib/page',
        "schema": '/views/default/pl/fe/matter/enroll/lib/schema',
        "wrap": '/views/default/pl/fe/matter/enroll/lib/wrap',
        "editor": '/views/default/pl/fe/matter/enroll/lib/editor',
        "frame": '/views/alpha/pl/fe/matter/enroll/frame',
    },
    urlArgs: function(id, url) {
        if (/domReady/.test(id)) {
            return '';
        }
        return "?bust=" + (timestamp * 1);
    }
});
window.MATTER_TYPE = 'Enroll'; // 为了支持动态加载服务模块
require(['frame']);