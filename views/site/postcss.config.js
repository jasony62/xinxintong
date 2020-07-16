module.exports = {
  plugins: {
    'postcss-import': {
      resolve(id) {
        if (/^@theme\//.test(id)) {
          return id.replace('@theme', process.env.VUE_APP_THEME_ROOT)
        }
        return id
      },
    },
  },
}
