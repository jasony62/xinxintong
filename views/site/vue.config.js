module.exports = {
  publicPath: `/ue/site/fe`,
  outputDir: `../../ue/site/fe`,
  pages: {
    mission: {
      entry: 'src/mission.js',
      template: 'public/index.html',
      filename: 'mission/index.html',
      title: '项目',
      chunks: ['chunk-vendors', 'chunk-common', 'mission'],
    },
  },
  devServer: {
    proxy: 'http://localhost:8000',
  },
}
