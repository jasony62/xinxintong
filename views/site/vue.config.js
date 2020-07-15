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
  css: {
    loaderOptions: {
      less: {
        globalVars: {
          'brand-primary': process.env.VUE_APP_BRAND_PRIMARY,
          'brand-primary-text': process.env.VUE_APP_BRAND_PRIMARY_TEXT,
          'brand-primary-outline': process.env.VUE_APP_BRAND_PRIMARY_OUTLINE,
          'brand-second': process.env.VUE_APP_BRAND_SECOND,
          'brand-second-text': process.env.VUE_APP_BRAND_SECOND_TEXT,
          'brand-second-outline': process.env.VUE_APP_BRAND_SECOND_OUTLINE,
        },
      },
    },
  },
  devServer: {
    proxy: 'http://localhost:8000',
  },
}
