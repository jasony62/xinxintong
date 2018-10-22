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
        "mschemaService": '/views/default/pl/fe/site/mschema/mschema.service',
        "schema": '/views/default/pl/fe/matter/enroll/lib/schema',
        "wrap": '/views/default/pl/fe/matter/enroll/lib/wrap',
        "frame": '/views/default/pl/fe/site/mschema',
    },
    urlArgs: function(id, url) {
        if (/domReady/.test(id)) {
            return '';
        }
        return "?bust=" + (timestamp * 1);
    }
});
window.MATTER_TYPE = 'Mschema'; // 为了支持动态加载服务模块
require(['frame']);