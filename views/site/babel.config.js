module.exports = {
  presets: ['@vue/cli-plugin-babel/preset'],
  plugins: [
    [
      'import',
      {
        libraryName: 'vant',
        libraryDirectory: 'es',
        style: true,
      },
      'vant',
    ],
    [
      'import',
      {
        libraryName: 'tms-vue-ui',
        style: true,
      },
    ],
  ],
}
