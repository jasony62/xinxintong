require.config({
    paths: {
        "domReady": '/static/js/domReady',
        "enrollService": '/views/default/pl/fe/matter/enroll/lib/enroll.service',
        "frame": '/views/default/pl/fe/matter/enroll/frame',
        "page": '/views/default/pl/fe/matter/enroll/lib/page',
        "schema": '/views/default/pl/fe/matter/enroll/lib/schema',
        "wrap": '/views/default/pl/fe/matter/enroll/lib/wrap',
        "editor": '/views/default/pl/fe/matter/enroll/lib/editor',
    },
    urlArgs: "bust=" + (new Date() * 1)
});
require(['frame']);
