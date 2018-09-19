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
        '/default/site/fe/matter/enroll/list': ['./views/default/site/fe/matter/enroll/list.js'],
        '/default/site/fe/matter/enroll/event': ['./views/default/site/fe/matter/enroll/event.js'],
        '/default/site/fe/matter/enroll/cowork': ['./views/default/site/fe/matter/enroll/cowork.js'],
        '/default/site/fe/matter/enroll/share': ['./views/default/site/fe/matter/enroll/share.js'],
        '/default/site/fe/matter/enroll/repos': ['./views/default/site/fe/matter/enroll/repos.js'],
        '/default/site/fe/matter/enroll/favor': ['./views/default/site/fe/matter/enroll/favor.js'],
        '/default/site/fe/matter/enroll/topic': ['./views/default/site/fe/matter/enroll/topic.js'],
        '/default/site/fe/matter/enroll/rank': ['./views/default/site/fe/matter/enroll/rank.js'],
        '/default/site/fe/matter/enroll/score': ['./views/default/site/fe/matter/enroll/score.js'],
        '/default/site/fe/matter/enroll/votes': ['./views/default/site/fe/matter/enroll/votes.js'],
        '/default/site/fe/matter/enroll/marks': ['./views/default/site/fe/matter/enroll/marks.js'],
        '/default/site/fe/matter/enroll/preview': ['./views/default/site/fe/matter/enroll/preview.js'],
        '/default/site/fe/matter/enroll/template': ['./views/default/site/fe/matter/enroll/template.js'],
        '/default/site/fe/matter/signin/signin': ['./views/default/site/fe/matter/signin/signin.js'],
        '/default/site/fe/matter/signin/view': ['./views/default/site/fe/matter/signin/view.js'],
        '/default/site/fe/matter/signin/preview': ['./views/default/site/fe/matter/signin/preview.js'],
        '/default/site/fe/matter/plan/main': ['./views/default/site/fe/matter/plan/main.js'],
        '/default/site/fe/invite/access': ['./views/default/site/fe/invite/access.js'],
        '/alpha/home': ['./views/alpha/home.js'],
        '/alpha/site/home': ['./views/alpha/site/home.js'],
        '/alpha/site/fe/user/member': ['./views/alpha/site/fe/user/member.js'],
        '/alpha/site/fe/matter/article/main': ['./views/alpha/site/fe/matter/article/main.js'],
        '/alpha/site/fe/matter/link/main': ['./views/alpha/site/fe/matter/link/main.js'],
        '/alpha/site/fe/matter/channel/main': ['./views/alpha/site/fe/matter/channel/main.js'],
        '/alpha/site/fe/matter/enroll/input': ['./views/alpha/site/fe/matter/enroll/input.js'],
        '/alpha/site/fe/matter/enroll/view': ['./views/alpha/site/fe/matter/enroll/view.js'],
        '/alpha/site/fe/matter/enroll/list': ['./views/alpha/site/fe/matter/enroll/list.js'],
        '/alpha/site/fe/matter/enroll/event': ['./views/alpha/site/fe/matter/enroll/event.js'],
        '/alpha/site/fe/matter/enroll/cowork': ['./views/alpha/site/fe/matter/enroll/cowork.js'],
        '/alpha/site/fe/matter/enroll/share': ['./views/alpha/site/fe/matter/enroll/share.js'],
        '/alpha/site/fe/matter/enroll/repos': ['./views/alpha/site/fe/matter/enroll/repos.js'],
        '/alpha/site/fe/matter/enroll/favor': ['./views/alpha/site/fe/matter/enroll/favor.js'],
        '/alpha/site/fe/matter/enroll/topic': ['./views/alpha/site/fe/matter/enroll/topic.js'],
        '/alpha/site/fe/matter/enroll/rank': ['./views/alpha/site/fe/matter/enroll/rank.js'],
        '/alpha/site/fe/matter/enroll/score': ['./views/alpha/site/fe/matter/enroll/score.js'],
        '/alpha/site/fe/matter/enroll/votes': ['./views/alpha/site/fe/matter/enroll/votes.js'],
        '/alpha/site/fe/matter/enroll/marks': ['./views/alpha/site/fe/matter/enroll/marks.js'],
        '/alpha/site/fe/matter/enroll/preview': ['./views/alpha/site/fe/matter/enroll/preview.js'],
        '/alpha/site/fe/matter/enroll/template': ['./views/alpha/site/fe/matter/enroll/template.js'],
        '/alpha/site/fe/matter/signin/signin': ['./views/alpha/site/fe/matter/signin/signin.js'],
        '/alpha/site/fe/matter/signin/view': ['./views/alpha/site/fe/matter/signin/view.js'],
        '/alpha/site/fe/matter/signin/preview': ['./views/alpha/site/fe/matter/signin/preview.js'],
        '/alpha/site/fe/matter/plan/main': ['./views/alpha/site/fe/matter/plan/main.js'],
        '/alpha/site/fe/invite/access': ['./views/alpha/site/fe/invite/access.js']
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
        }]
    }
}