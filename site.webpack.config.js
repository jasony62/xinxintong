var path = require("path");
module.exports = {
    entry: {
        '/home': ['./views/default/home.js'],
        '/site/home': ['./views/default/site/home.js'],
        '/site/fe/user/member': ['./views/default/site/fe/user/member.js'],
        '/site/fe/matter/article/main': ['./views/default/site/fe/matter/article/main.js'],
        '/site/fe/matter/link/main': ['./views/default/site/fe/matter/link/main.js'],
        '/site/fe/matter/channel/main': ['./views/default/site/fe/matter/channel/main.js'],
        '/site/fe/matter/enroll/input': ['./views/default/site/fe/matter/enroll/input.js'],
        '/site/fe/matter/enroll/view': ['./views/default/site/fe/matter/enroll/view.js'],
        '/site/fe/matter/enroll/list': ['./views/default/site/fe/matter/enroll/list.js'],
        '/site/fe/matter/enroll/action': ['./views/default/site/fe/matter/enroll/action.js'],
        '/site/fe/matter/enroll/remark': ['./views/default/site/fe/matter/enroll/remark.js'],
        '/site/fe/matter/enroll/repos': ['./views/default/site/fe/matter/enroll/repos.js'],
        '/site/fe/matter/enroll/repos2': ['./views/default/site/fe/matter/enroll/repos2.js'],
        '/site/fe/matter/enroll/rank': ['./views/default/site/fe/matter/enroll/rank.js'],
        '/site/fe/matter/enroll/score': ['./views/default/site/fe/matter/enroll/score.js'],
        '/site/fe/matter/enroll/preview': ['./views/default/site/fe/matter/enroll/preview.js'],
        '/site/fe/matter/enroll/template': ['./views/default/site/fe/matter/enroll/template.js'],
        '/site/fe/matter/signin/signin': ['./views/default/site/fe/matter/signin/signin.js'],
        '/site/fe/matter/signin/view': ['./views/default/site/fe/matter/signin/view.js'],
        '/site/fe/matter/signin/preview': ['./views/default/site/fe/matter/signin/preview.js'],
        '/site/fe/matter/plan/main': ['./views/default/site/fe/matter/plan/main.js'],
        '/site/fe/invite/access': ['./views/default/site/fe/invite/access.js']
    },
    output: {
        path: path.resolve(__dirname, 'bundles/default'),
        filename: '[name].js'
    },
    module: {
        loaders: [{
            test: /\.css$/,
            loader: 'style-loader!css-loader'
        }, {
            test: /\.less$/,
            loader: 'style-loader!css-loader!less-loader'
        }]
    }
}