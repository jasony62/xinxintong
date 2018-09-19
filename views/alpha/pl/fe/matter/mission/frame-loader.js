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
        "missionService": '/views/default/pl/fe/matter/mission/lib/mission.service',
        "enrollService": '/views/default/pl/fe/matter/enroll/lib/enroll.service',
        "signinService": '/views/default/pl/fe/matter/signin/lib/signin.service',
        "page": '/views/default/pl/fe/matter/enroll/lib/page',
        "schema": '/views/default/pl/fe/matter/enroll/lib/schema',
        "wrap": '/views/default/pl/fe/matter/enroll/lib/wrap',
        "frame": '/views/default/pl/fe/matter/mission/frame',
    },
    urlArgs: function(id, url) {
        if (/domReady/.test(id)) {
            return '';
        }
        return "?bust=" + (timestamp * 1);
    }
});
require(['frame']);