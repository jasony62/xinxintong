var path = require("path");
module.exports = {
    entry: {
        'home': ['./views/default/home.js'],
        'site/fe/matter/article/main': ['./views/default/site/fe/matter/article/main.js'],
        'site/fe/matter/enroll/input': ['./views/default/site/fe/matter/enroll/input.js'],
        'site/fe/matter/enroll/view': ['./views/default/site/fe/matter/enroll/view.js'],
        'site/fe/matter/enroll/list': ['./views/default/site/fe/matter/enroll/list.js'],
        'site/fe/matter/enroll/remark': ['./views/default/site/fe/matter/enroll/remark.js'],
        'site/fe/matter/enroll/repos': ['./views/default/site/fe/matter/enroll/repos.js'],
        'site/fe/matter/enroll/preview': ['./views/default/site/fe/matter/enroll/preview.js'],
        'site/fe/matter/enroll/template': ['./views/default/site/fe/matter/enroll/template.js'],
    },
    output: {
        path: path.resolve(__dirname, 'bundles/default/'),
        filename: '[name].js'
    }
}
