var path = require("path");
module.exports = {
    entry: {
        'home': ['./views/default/home.js'],
        '/fe/matter/article/main': ['./views/default/site/fe/matter/article/main.js'],
        '/fe/matter/enroll/input': ['./views/default/site/fe/matter/enroll/input.js'],
        '/fe/matter/enroll/view': ['./views/default/site/fe/matter/enroll/view.js'],
        '/fe/matter/enroll/list': ['./views/default/site/fe/matter/enroll/list.js'],
        '/fe/matter/enroll/remark': ['./views/default/site/fe/matter/enroll/remark.js'],
        '/fe/matter/enroll/repos': ['./views/default/site/fe/matter/enroll/repos.js'],
        '/fe/matter/enroll/preview': ['./views/default/site/fe/matter/enroll/preview.js'],
        '/fe/matter/enroll/template': ['./views/default/site/fe/matter/enroll/template.js'],
        '/fe/matter/signin/signin': ['./views/default/site/fe/matter/signin/signin.js'],
        '/fe/matter/signin/view': ['./views/default/site/fe/matter/signin/view.js'],
        '/fe/matter/signin/preview': ['./views/default/site/fe/matter/signin/preview.js'],
    },
    output: {
        path: path.resolve(__dirname, 'bundles/default/'),
        filename: '[name].js'
    },
    module: {
        loaders: [{
            test: /\.css$/,
            loader: 'style-loader!css-loader'
        }]
    }
}
