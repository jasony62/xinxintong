var path = require("path");
module.exports = {
    entry: {
        '/fe/matter/article/main': ['./views/default/site/fe/matter/article/main.js']
    },
    output: {
        path: path.resolve(__dirname, 'bundles/default/site'),
        filename: '[name].js'
    }
}
