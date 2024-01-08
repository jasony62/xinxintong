import log4js from 'log4js'
import path from 'path'
import fs from 'fs'

let cnfpath = path.resolve(process.cwd() + '/config/log4js.js')
if (fs.existsSync(cnfpath)) {
  const log4jsConfig = (await import(process.cwd() + '/config/log4js.js'))
    .default
  log4js.configure(log4jsConfig)
} else {
  log4js.configure({
    appenders: {
      consoleout: { type: 'console' },
    },
    categories: {
      default: {
        appenders: ['consoleout'],
        level: 'debug',
      },
    },
  })
}

import { TmsKoa } from 'tms-koa'
const tmsKoa = new TmsKoa()

tmsKoa.startup({})
