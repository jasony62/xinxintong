'use strict'
var ngMod = angular.module('schema.ui.xxt', [])
ngMod.service('tmsSchema', [
  '$filter',
  '$sce',
  '$parse',
  function ($filter, $sce, $parse) {
    var _that = this,
      _mapOfSchemas
    this.config = function (schemas) {
      if (angular.isString(schemas)) {
        schemas = JSON.parse(schemas)
      }
      if (angular.isArray(schemas)) {
        _mapOfSchemas = {}
        schemas.forEach(function (schema) {
          _mapOfSchemas[schema.id] = schema
        })
      } else {
        _mapOfSchemas = schemas
      }
    }
    this.isEmpty = function (oSchema, value) {
      if (value === undefined) {
        return true
      }
      switch (oSchema.type) {
        case 'multiple':
          for (var p in value) {
            //至少有一个选项
            if (value[p] === true) {
              return false
            }
          }
          return true
        default:
          return value.length === 0
      }
    }
    this.checkRequire = function (oSchema, value) {
      if (value === undefined || this.isEmpty(oSchema, value)) {
        return '请填写必填题目［' + oSchema.title + '］'
      }
      return true
    }
    this.checkFormat = function (oSchema, value) {
      if (oSchema.format === 'number') {
        if (!/^-{0,1}[0-9]+(.[0-9]+){0,1}$/.test(value)) {
          return '题目［' + oSchema.title + '］请输入数值'
        }
      } else if (oSchema.format === 'name') {
        if (value.length < 2) {
          return (
            '题目［' + oSchema.title + '］请输入正确的姓名（不少于2个字符）'
          )
        }
      } else if (oSchema.format === 'mobile') {
        if (
          !/^1(3[0-9]|4[57]|5[0-35-9]|7[0135678]|8[0-9]|9[0-9])\d{8}$/.test(
            value
          )
        ) {
          return '题目［' + oSchema.title + '］请输入正确的手机号（11位数字）'
        }
      } else if (oSchema.format === 'email') {
        if (
          !/^[A-Za-z\d]+([-_.][A-Za-z\d]+)*@([A-Za-z\d]+[-.])+[A-Za-z\d]{2,4}$/.test(
            value
          )
        ) {
          return '题目［' + oSchema.title + '］请输入正确的邮箱'
        }
      }
      return true
    }
    this.checkCount = function (oSchema, value) {
      if (
        oSchema.count != 0 &&
        oSchema.count !== undefined &&
        value.length > oSchema.count
      ) {
        return (
          '［' + oSchema.title + '］超出上传数量（' + oSchema.count + '）限制'
        )
      }
      return true
    }
    this.checkValue = function (oSchema, value) {
      var sCheckResult
      if (oSchema.required && oSchema.required === 'Y') {
        if (true !== (sCheckResult = this.checkRequire(oSchema, value))) {
          return sCheckResult
        }
      }
      if (value) {
        if (oSchema.type === 'shorttext' && oSchema.format) {
          if (true !== (sCheckResult = this.checkFormat(oSchema, value))) {
            return sCheckResult
          }
        }
        if (
          oSchema.type === 'multiple' &&
          oSchema.limitChoice === 'Y' &&
          oSchema.range
        ) {
          var opCount = 0
          for (var i in value) {
            if (value[i]) {
              opCount++
            }
          }
          if (opCount < oSchema.range[0] || opCount > oSchema.range[1]) {
            return (
              '【' +
              oSchema.title +
              '】中最多只能选择(' +
              oSchema.range[1] +
              ')项，最少需要选择(' +
              oSchema.range[0] +
              ')项'
            )
          }
        }
        if (/image|file/.test(oSchema.type) && oSchema.count) {
          if (true !== (sCheckResult = this.checkCount(oSchema, value))) {
            return sCheckResult
          }
        }
      }
      return true
    }
    this.loadRecord = function (schemasById, dataOfPage, dataOfRecord) {
      if (!dataOfRecord) return false
      var p, value
      for (p in dataOfRecord) {
        if (p === 'member') {
          dataOfPage.member = angular.extend(
            dataOfPage.member,
            dataOfRecord.member
          )
        } else if (schemasById[p] !== undefined) {
          const schema = schemasById[p]
          if ('score' === schema.type) {
            dataOfPage[p] = dataOfRecord[p]
          } else if ('url' === schema.type) {
            const originalValue = dataOfRecord[p]
            originalValue._text = this.urlSubstitute(originalValue)
            dataOfPage[p] = dataOfRecord[p]
          } else if (/image|multiple/.test(schema.type)) {
            if (dataOfRecord[p].length) {
              if (schema.type === 'image') {
                value = dataOfRecord[p].split(',')
                dataOfPage[p] = []
                for (var i in value) {
                  dataOfPage[p].push({
                    imgSrc: value[i],
                  })
                }
              } else if (schema.type === 'multiple') {
                /*多选题将数组转为对象*/
                value = dataOfRecord[p].split(',')
                dataOfPage[p] = {}
                for (var i in value) dataOfPage[p][value[i]] = true
              }
            }
          } else {
            dataOfPage[p] = dataOfRecord[p]
          }
        }
      }
      return true
    }
    /**
     * 给页面中的提交数据填充用户通讯录数据
     */
    this.autoFillMember = function (schemasById, oUser, oPageDataMember) {
      if (oUser.members) {
        angular.forEach(schemasById, function (oSchema) {
          if (oSchema.mschema_id && oUser.members[oSchema.mschema_id]) {
            var oMember, attr, val
            oMember = oUser.members[oSchema.mschema_id]
            attr = oSchema.id.split('.')
            if (attr.length === 2) {
              oPageDataMember[attr[1]] = oMember[attr[1]]
            } else if (attr.length === 3 && oMember.extattr) {
              if (!oPageDataMember.extattr) {
                oPageDataMember.extattr = {}
              }
              switch (oSchema.type) {
                case 'multiple':
                  val = oMember.extattr[attr[2]]
                  if (angular.isObject(val)) {
                    oPageDataMember.extattr[attr[2]] = {}
                    for (var p in val) {
                      if (val[p]) {
                        oPageDataMember.extattr[attr[2]][p] = true
                      }
                    }
                  }
                  break
                default:
                  oPageDataMember.extattr[attr[2]] = oMember.extattr[attr[2]]
              }
            }
          }
        })
      }
    }
    /**
     * 给页面中的提交数据填充题目默认值
     */
    this.autoFillDefault = function (schemasById, oPageData) {
      angular.forEach(schemasById, function (oSchema) {
        if (oSchema.defaultValue && oPageData[oSchema.id] === undefined) {
          oPageData[oSchema.id] = oSchema.defaultValue
        }
      })
    }
    this.value2Text = function (oSchema, value) {
      var label,
        aVal,
        aLab = []

      if ((label = value)) {
        if (oSchema.ops && oSchema.ops.length) {
          if (oSchema.type === 'single') {
            for (var i = 0, ii = oSchema.ops.length; i < ii; i++) {
              if (oSchema.ops[i].v === label) {
                label = oSchema.ops[i].l
                break
              }
            }
          } else if (oSchema.type === 'multiple') {
            aVal = []
            for (var k in label) {
              if (label[k]) {
                aVal.push(k)
              }
            }
            oSchema.ops.forEach(function (op) {
              aVal.indexOf(op.v) !== -1 && aLab.push(op.l)
            })
            label = aLab.join(',')
          }
        }
      } else {
        label = ''
      }
      return label
    }
    this.value2Html = function (oSchema, val) {
      if (!val || !oSchema) return ''

      if (oSchema.ops && oSchema.ops.length) {
        if (oSchema.type === 'score') {
          var label = ''
          oSchema.ops.forEach(function (op, index) {
            if (val[op.v] !== undefined) {
              label += '<div>' + op.l + ':' + val[op.v] + '</div>'
            }
          })
          label = label.replace(/\s\/\s$/, '')
          return label
        } else if (angular.isString(val)) {
          var aVal,
            aLab = []
          aVal = val.split(',')
          oSchema.ops.forEach(function (op, i) {
            aVal.indexOf(op.v) !== -1 && aLab.push(op.l)
          })
          if (aLab.length) return aLab.join(',')
        } else if (angular.isObject(val) || angular.isArray(val)) {
          val = JSON.stringify(val)
        }
      }
      return val
    }
    this.txtSubstitute = function (oTxtData) {
      return oTxtData.replace(/\n/g, '<br>')
    }
    this.urlSubstitute = function (oUrlData) {
      var text
      text = ''
      if (oUrlData) {
        if (oUrlData.title) {
          text += '【' + oUrlData.title + '】'
        }
        if (oUrlData.description) {
          text += oUrlData.description
        }
      }
      text += '<a href="' + oUrlData.url + '">网页链接</a>'

      return text
    }
    this.optionsSubstitute = function (oSchema, value) {
      var val,
        aVal,
        aLab = []
      if ((val = value)) {
        if (oSchema.ops && oSchema.ops.length) {
          if (oSchema.type === 'score') {
            var label = '',
              flag = false
            oSchema.ops.forEach(function (op, index) {
              if (val[op.v] !== undefined) {
                label += '<div>' + op.l + ':' + val[op.v] + '</div>'
                flag = false
              } else {
                return (flag = true)
              }
            })
            label = flag ? val : label.replace(/\s\/\s$/, '')
            return label
          } else if (oSchema.type === 'single' || oSchema.type === 'multiple') {
            if (angular.isString(val)) {
              aVal = val.split(',')
              oSchema.ops.forEach(function (op) {
                aVal.indexOf(op.v) !== -1 && aLab.push(op.l)
              })
              val = aLab.join(',')
            } else {
              return val
            }
          } else if (angular.isObject(val) || angular.isArray(val)) {
            val = JSON.stringify(val)
          }
        }
      } else {
        val = ''
      }
      return val
    }
    this.dateSubstitute = function (value) {
      if (!value || !parseInt(value)) return ''
      return (
        '<span>' + $filter('date')(value * 1000, 'yy-MM-dd HH:mm') + '</span>'
      )
    }
    this.forTable = function (record, mapOfSchemas) {
      function _memberAttr(oMember, oSchema) {
        var keys, originalValue, afterValue
        if (oMember) {
          keys = oSchema.id.split('.')
          if (keys.length === 2) {
            return oMember[keys[1]]
          } else if (keys.length === 3 && oMember.extattr) {
            if ((originalValue = oMember.extattr[keys[2]])) {
              switch (oSchema.type) {
                case 'single':
                  if (oSchema.ops && oSchema.ops.length) {
                    for (var i = oSchema.ops.length - 1; i >= 0; i--) {
                      if (originalValue === oSchema.ops[i].v) {
                        afterValue = oSchema.ops[i].l
                      }
                    }
                  }
                  break
                case 'multiple':
                  if (oSchema.ops && oSchema.ops.length) {
                    afterValue = []
                    oSchema.ops.forEach(function (op) {
                      originalValue[op.v] && afterValue.push(op.l)
                    })
                    afterValue = afterValue.join(',')
                  }
                  break
                default:
                  afterValue = originalValue
              }
            }
            return afterValue
          } else {
            return ''
          }
        } else {
          return ''
        }
      }

      function _forTable(oRecord, mapOfSchemas) {
        var oSchema,
          type,
          data = {}
        if (oRecord.data && mapOfSchemas) {
          for (var schemaId in mapOfSchemas) {
            oSchema = mapOfSchemas[schemaId]
            type = oSchema.type
            /* 分组活动导入数据时会将member题型改为shorttext题型 */
            if (oSchema.mschema_id && oRecord.data.member) {
              type = 'member'
            }
            switch (type) {
              case 'image':
                var imgs
                if (oRecord.data[oSchema.id]) {
                  if (angular.isString(oRecord.data[oSchema.id])) {
                    imgs = oRecord.data[oSchema.id].split(',')
                  } else {
                    imgs = oRecord.data[oSchema.id]
                  }
                } else {
                  imgs = []
                }
                data[oSchema.id] = imgs
                break
              case 'file':
              case 'voice':
                var files = oRecord.data[oSchema.id]
                  ? oRecord.data[oSchema.id]
                  : {}
                data[oSchema.id] = files
                break
              case 'multitext':
                var multitexts
                if ((multitexts = oRecord.data[oSchema.id])) {
                  /* 为什么需要进行两次转换？ */
                  if (angular.isString(multitexts)) {
                    try {
                      multitexts = JSON.parse(multitexts)
                      if (angular.isString(multitexts)) {
                        multitexts = JSON.parse(multitexts)
                      }
                    } catch (e) {
                      multitexts = []
                    }
                  }
                } else {
                  multitexts = []
                }
                data[oSchema.id] = multitexts
                break
              case 'date':
                data[oSchema.id] =
                  oRecord.data[oSchema.id] &&
                  angular.isNumber(oRecord.data[oSchema.id])
                    ? oRecord.data[oSchema.id]
                    : 0
                break
              case 'url':
                data[oSchema.id] = oRecord.data[oSchema.id]
                if (data[oSchema.id]) {
                  data[oSchema.id]._text =
                    '【' +
                    data[oSchema.id].title +
                    '】' +
                    data[oSchema.id].description
                }
                break
              case 'longtext':
                if (oSchema.format === 'markdown') {
                  let text = oRecord.data[oSchema.id] ?? ''
                  text = text.replaceAll('\\n', '\n')
                  let mdtext = marked.parse(text)
                  data[oSchema.id] = $sce.trustAsHtml(mdtext)
                } else {
                  data[oSchema.id] = $sce.trustAsHtml(
                    oRecord.data[oSchema.id] ?? ''
                  )
                }
                break
              default:
                try {
                  if (/^member\./.test(oSchema.id)) {
                    data[oSchema.id] = _memberAttr(oRecord.data.member, oSchema)
                  } else {
                    var htmlVal = _that.value2Html(
                      oSchema,
                      oRecord.data[oSchema.id]
                    )
                    data[oSchema.id] = angular.isString(htmlVal)
                      ? $sce.trustAsHtml(htmlVal)
                      : htmlVal
                  }
                } catch (e) {
                  console.log(e, oSchema, oRecord.data[oSchema.id])
                }
            }
          }
          oRecord._data = data
        }
        return oRecord
      }
      var map
      if (mapOfSchemas && angular.isArray(mapOfSchemas)) {
        map = {}
        mapOfSchemas.forEach(function (oSchema) {
          map[oSchema.id] = oSchema
        })
        mapOfSchemas = map
      }
      return _forTable(record, mapOfSchemas ? mapOfSchemas : _mapOfSchemas)
    }
    this.forEdit = function (schema, data) {
      if (schema.type === 'file') {
        var files
        if (data[schema.id] && data[schema.id].length) {
          files = data[schema.id]
          files.forEach(function (file) {
            if (file.url && angular.isString(file.url)) {
              file.url && $sce.trustAsUrl(file.url)
            }
          })
        }
        data[schema.id] = files
      } else if (schema.type === 'multiple') {
        var obj = {},
          value
        if (data[schema.id] && data[schema.id].length) {
          value = data[schema.id].split(',')
          value.forEach(function (p) {
            obj[p] = true
          })
        }
        data[schema.id] = obj
      } else if (schema.type === 'image') {
        var value = data[schema.id],
          obj = []
        if (value && value.length) {
          value = value.split(',')
          value.forEach(function (p) {
            obj.push({
              imgSrc: p,
            })
          })
        }
        data[schema.id] = obj
      }

      return data
    }
    /* 将1条记录的所有指定题目的数据变成字符串 */
    this.strRecData = function (oRecData, schemas, oOptions) {
      var str, schemaData, fnSchemaFilter, fnDataFilter

      if (!schemas || schemas.length === 0) {
        return ''
      }

      if (oOptions) {
        if (oOptions.fnSchemaFilter) fnSchemaFilter = oOptions.fnSchemaFilter
        if (oOptions.fnDataFilter) fnDataFilter = oOptions.fnDataFilter
      }

      str = ''
      schemas.forEach(function (oSchema) {
        if (!fnSchemaFilter || fnSchemaFilter(oSchema)) {
          schemaData = $parse(oSchema.id)(oRecData)
          switch (oSchema.type) {
            case 'image':
              if (schemaData && schemaData.length) {
                str += '<span>'
                schemaData.forEach(function (imgSrc) {
                  str += '<img src="' + imgSrc + '" />'
                })
                str += '</span>'
              }
              break
            case 'file':
              if (schemaData && schemaData.length) {
                schemaData.forEach(function (oFile) {
                  str +=
                    '<span><a href="' +
                    oFile.url +
                    '" target="_blank">' +
                    oFile.name +
                    '</a></span>'
                })
              }
              break
            case 'date':
              if (schemaData > 0) {
                str =
                  '<span>' +
                  $filter('date')(schemaData * 1000, 'yy-MM-dd HH:mm') +
                  '</span>'
              }
              break
            case 'shortext':
            case 'longtext':
              str += schemaData
              break
            case 'multitext':
              if (schemaData && schemaData.length) {
                for (var i = schemaData.length - 1; i >= 0; i--) {
                  if (!fnDataFilter || fnDataFilter(schemaData[i].id)) {
                    str += schemaData[i].value
                  }
                }
              }
              break
          }
        }
      })

      return str
    }
    /**
     * 通讯录记录中的扩展属性转化为用户可读内容
     */
    this.member = {
      getExtattrsUIValue: function (schemas, oMember) {
        var oExtattrUIValue = {}

        schemas.forEach(function (oExtAttr) {
          if (/single|multiple/.test(oExtAttr.type)) {
            if (oMember.extattr[oExtAttr.id]) {
              oExtattrUIValue[oExtAttr.id] = _that.value2Text(
                oExtAttr,
                oMember.extattr[oExtAttr.id]
              )
            }
          } else {
            oExtattrUIValue[oExtAttr.id] = oMember.extattr[oExtAttr.id]
          }
        })

        return oExtattrUIValue
      },
    }
    this.getSchemaVisible = function (oSchema, oRecordData) {
      var bVisible, oRule, oRuleVal
      if (oSchema.visibility.logicOR) {
        bVisible = false
        for (var i = 0, ii = oSchema.visibility.rules.length; i < ii; i++) {
          oRule = oSchema.visibility.rules[i]
          oRuleVal = $parse(oRule.schema)(oRecordData)
          if (oRuleVal) {
            if (oRuleVal === oRule.op || oRuleVal[oRule.op]) {
              bVisible = true
              break
            }
          }
        }
      } else {
        bVisible = true
        for (var i = 0, ii = oSchema.visibility.rules.length; i < ii; i++) {
          oRule = oSchema.visibility.rules[i]
          oRuleVal = $parse(oRule.schema)(oRecordData)
          if (!oRuleVal || (oRuleVal !== oRule.op && !oRuleVal[oRule.op])) {
            bVisible = false
            break
          }
        }
      }
      return bVisible
    }
  },
])
