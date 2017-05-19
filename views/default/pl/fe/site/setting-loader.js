require.config({
	waitSeconds: 0,
    paths: {
        "domReady": '/static/js/domReady',
        "main": '/views/default/pl/fe/site/setting',
    },
    urlArgs: "bust=" + (new Date() * 1)
});
require(['main']);
