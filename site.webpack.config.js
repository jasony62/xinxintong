var path = require("path");
module.exports = {
    entry: {
        '/default/home': ['./views/default/home.js'],
        '/default/site/home': ['./views/default/site/home.js'],
        '/default/site/fe/user/member': ['./views/default/site/fe/user/member.js'],
        '/default/site/fe/matter/article/main': ['./views/default/site/fe/matter/article/main.js'],
        '/default/site/fe/matter/link/main': ['./views/default/site/fe/matter/link/main.js'],
        '/default/site/fe/matter/channel/main': ['./views/default/site/fe/matter/channel/main.js'],
        '/default/site/fe/matter/enroll/input': ['./views/default/site/fe/matter/enroll/input.js'],
        '/default/site/fe/matter/enroll/view': ['./views/default/site/fe/matter/enroll/view.js'],
        '/default/site/fe/matter/enroll/cowork': ['./views/default/site/fe/matter/enroll/cowork.js'],
        '/default/site/fe/matter/enroll/share': ['./views/default/site/fe/matter/enroll/share.js'],
        '/default/site/fe/matter/enroll/repos': ['./views/default/site/fe/matter/enroll/repos.js'],
        '/default/site/fe/matter/enroll/topic': ['./views/default/site/fe/matter/enroll/topic.js'],
        '/default/site/fe/matter/enroll/score': ['./views/default/site/fe/matter/enroll/score.js'],
        '/default/site/fe/matter/enroll/preview': ['./views/default/site/fe/matter/enroll/preview.js'],
        '/default/site/fe/matter/enroll/template': ['./views/default/site/fe/matter/enroll/template.js'],
        '/default/site/fe/matter/enroll/activities': ['./views/default/site/fe/matter/enroll/activities.js'],
        '/default/site/fe/matter/enroll/summary': ['./views/default/site/fe/matter/enroll/summary.js'],
        '/default/site/fe/matter/enroll/people': ['./views/default/site/fe/matter/enroll/people.js'],
        '/default/site/fe/matter/signin/signin': ['./views/default/site/fe/matter/signin/signin.js'],
        '/default/site/fe/matter/signin/view': ['./views/default/site/fe/matter/signin/view.js'],
        '/default/site/fe/matter/signin/preview': ['./views/default/site/fe/matter/signin/preview.js'],
        '/default/site/fe/matter/group/main': ['./views/default/site/fe/matter/group/main.js'],
        '/default/site/fe/matter/group/team': ['./views/default/site/fe/matter/group/team.js'],
        '/default/site/fe/matter/group/invite': ['./views/default/site/fe/matter/group/invite.js'],
        '/default/site/fe/matter/plan/main': ['./views/default/site/fe/matter/plan/main.js'],
        '/default/site/fe/invite/access': ['./views/default/site/fe/invite/access.js']
    },
    output: {
        path: path.resolve(__dirname, 'bundles'),
        filename: '[name].js'
    },
    module: {
        loaders: [{
            test: /\.html$/,
            loader: 'raw-loader'
        }, {
            test: /\.css$/,
            loader: 'style-loader!css-loader'
        }, {
            test: /\.less$/,
            loader: 'style-loader!css-loader!less-loader'
        }, {
            test: /\.(jpg|png|jpeg|gif|svg)$/,
            loader: 'url-loader',
            options: {
                limit: 100000,
                name: './static/img/[name].[hash:7].[ext]'
            }
        }]
    }
}