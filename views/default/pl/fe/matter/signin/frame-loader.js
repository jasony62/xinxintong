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
        "signinService": '/views/default/pl/fe/matter/signin/lib/signin.service',
        "page": '/views/default/pl/fe/matter/enroll/lib/page',
        "schema": '/views/default/pl/fe/matter/enroll/lib/schema',
        "wrap": '/views/default/pl/fe/matter/enroll/lib/wrap',
        "editor": '/views/default/pl/fe/matter/enroll/lib/editor',
        "frame": '/views/default/pl/fe/matter/signin/frame',
    },
    urlArgs: function(id, url) {
        if (/domReady/.test(id)) {
            return '';
        }
        return "?bust=" + (timestamp * 1);
    }
});
window.MATTER_TYPE = 'Signin'; // 为了支持动态加载服务模块
require(['frame']);