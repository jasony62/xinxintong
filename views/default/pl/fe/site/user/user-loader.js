/**
 * Created by lishuai on 2017/3/22.
 */
require.config({
    paths: {
        "domReady": '/static/js/domReady',
        "main": '/views/default/pl/fe/site/user/home',
    },
    urlArgs: "bust=" + (new Date() * 1)
});
require(['main']);