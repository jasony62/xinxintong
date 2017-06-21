var path = require("path");
module.exports = {
    entry: {
        '/home': ['./views/default/home.js'],
        '/site/home': ['./views/default/site/home.js'],
        '/site/fe/matter/article/main': ['./views/default/site/fe/matter/article/main.js'],
        '/site/fe/matter/enroll/input': ['./views/default/site/fe/matter/enroll/input.js'],
        '/site/fe/matter/enroll/view': ['./views/default/site/fe/matter/enroll/view.js'],
        '/site/fe/matter/enroll/list': ['./views/default/site/fe/matter/enroll/list.js'],
        '/site/fe/matter/enroll/remark': ['./views/default/site/fe/matter/enroll/remark.js'],
        '/site/fe/matter/enroll/repos': ['./views/default/site/fe/matter/enroll/repos.js'],
        '/site/fe/matter/enroll/rank': ['./views/default/site/fe/matter/enroll/rank.js'],
        '/site/fe/matter/enroll/score': ['./views/default/site/fe/matter/enroll/score.js'],
        '/site/fe/matter/enroll/preview': ['./views/default/site/fe/matter/enroll/preview.js'],
        '/site/fe/matter/enroll/template': ['./views/default/site/fe/matter/enroll/template.js'],
        '/site/fe/matter/signin/signin': ['./views/default/site/fe/matter/signin/signin.js'],
        '/site/fe/matter/signin/view': ['./views/default/site/fe/matter/signin/view.js'],
        '/site/fe/matter/signin/preview': ['./views/default/site/fe/matter/signin/preview.js'],
    },
    output: {
        path: path.resolve(__dirname, 'bundles/default'),
        filename: '[name].js'
    },
    module: {
        loaders: [{
            test: /\.css$/,
            loader: 'style-loader!css-loader'
        }]
    }
}
