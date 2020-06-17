'use strict'
require('../../../../../../asset/css/buttons.css')
require('./input.css')

require('../../../../../../asset/js/xxt.ui.image.js')
require('../../../../../../asset/js/xxt.ui.geo.js')
require('../../../../../../asset/js/xxt.ui.url.js')
require('../../../../../../asset/js/xxt.ui.paste.js')
require('../../../../../../asset/js/xxt.ui.editor.js')
require('../../../../../../asset/js/xxt.ui.schema.js')

require('./_asset/ui.round.js')
require('./_asset/ui.task.js')

window.moduleAngularModules = [
  'round.ui.enroll',
  'task.ui.enroll',
  'paste.ui.xxt',
  'editor.ui.xxt',
  'url.ui.xxt',
  'schema.ui.xxt',
]

import factoryInput from './input/factory_input'
import directiveTmsImageInput from './input/directive_tmsImageInput'
import directiveTmsFileInput from './input/directive_tmsFileInput'
import directiveTmsVoiceInput from './input/directive_tmsVoiceInput'

var ngApp = require('./main.js')
ngApp.oUtilSubmit = require('../_module/submit.util.js')
ngApp.config([
  '$compileProvider',
  function ($compileProvider) {
    $compileProvider.aHrefSanitizationWhitelist(
      /^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/
    )
  },
])
ngApp.factory('Input', factoryInput)
ngApp.directive('tmsImageInput', directiveTmsImageInput)
ngApp.directive('tmsFileInput', directiveTmsFileInput)
ngApp.directive('tmsVoiceInput', directiveTmsVoiceInput)
ngApp.controller('ctrlWxUploadFileTip', [
  '$scope',
  function ($scope) {
    $scope.domId = ''
    $scope.isIos = /iphone|ipad/i.test(navigator.userAgent)
    $scope.closeTip = function () {
      var domTip = document.querySelector($scope.domId)
      var evt = document.createEvent('HTMLEvents')
      evt.initEvent('hide', false, false)
      domTip.dispatchEvent(evt)
    }
  },
])
ngApp.controller('ctrlInput', [
  '$scope',
  '$parse',
  '$q',
  '$uibModal',
  'Input',
  'tmsLocation',
  'http2',
  'noticebox',
  'tmsPaste',
  'tmsUrl',
  'tmsSchema',
  '$compile',
  'enlRound',
  'enlTask',
  function (
    $scope,
    $parse,
    $q,
    $uibModal,
    Input,
    LS,
    http2,
    noticebox,
    tmsPaste,
    tmsUrl,
    tmsSchema,
    $compile,
    enlRound,
    enlTask
  ) {
    function fnHidePageActions() {
      var domActs, domAct
      if ((domActs = document.querySelectorAll('[wrap=button]'))) {
        angular.forEach(domActs, function (domAct) {
          domAct.style.display = 'none'
        })
      }
    }
    /**
     * 控制只读题目
     */
    function fnToggleReadonlySchemas(dataSchemas) {
      dataSchemas.forEach(function (oSchemaWrap) {
        var oSchema, domSchemas
        if ((oSchema = oSchemaWrap.schema)) {
          domSchemas = document.querySelectorAll(
            '[wrap=input][schema="' + oSchema.id + '"] input'
          )
          domSchemas.forEach(function (one) {
            oSchema.readonly === 'Y'
              ? one.setAttribute('disabled', true)
              : one.removeAttribute('disabled')
          })
        }
      })
    }
    /**
     * 控制轮次题目的可见性
     */
    function fnToggleRoundSchemas(dataSchemas, oRecordData) {
      dataSchemas.forEach(function (oSchemaWrap) {
        var oSchema, domSchema
        if ((oSchema = oSchemaWrap.schema)) {
          domSchema = document.querySelector(
            '[wrap=input][schema="' +
            oSchema.id +
            '"],[wrap=html][schema="' +
            oSchema.id +
            '"]'
          )
          if (domSchema) {
            if (
              oSchema.hideByRoundPurpose &&
              oSchema.hideByRoundPurpose.length
            ) {
              var bVisible = true
              if (
                oSchema.hideByRoundPurpose.indexOf(
                  $scope.record.round.purpose
                ) !== -1
              ) {
                bVisible = false
              }
              oSchema._visible = bVisible
              domSchema.classList.toggle('hide', !bVisible)
              /* 被隐藏的题目需要清除数据 */
              if (false === bVisible) {
                $parse(oSchema.id).assign(oRecordData, undefined)
              }
            }
          }
        }
      })
    }
    /**
     * 控制关联题目的可见性
     */
    function fnToggleAssocSchemas(dataSchemas, oRecordData) {
      dataSchemas.forEach((oSchemaWrap) => {
        var oSchema, domSchema
        if ((oSchema = oSchemaWrap.schema)) {
          domSchema = document.querySelector(
            '[wrap=input][schema="' +
            oSchema.id +
            '"],[wrap=html][schema="' +
            oSchema.id +
            '"]'
          )
          if (domSchema) {
            if (
              oSchema.visibility &&
              oSchema.visibility.rules &&
              oSchema.visibility.rules.length
            ) {
              var bVisible = tmsSchema.getSchemaVisible(oSchema, oRecordData);
              domSchema.classList.toggle("hide", !bVisible)
              oSchema.visibility.visible = bVisible
              /* 被隐藏的题目需要清除数据 */
              if (false === bVisible) {
                $parse(oSchema.id).assign(oRecordData, undefined)
              }
            } else if (oSchema.type === 'multitext' && oSchema.cowork === 'Y') {
              domSchema.classList.toggle('hide', true)
            }
          }
        }
      })
    }
    /**
     * 控制题目关联选项的显示
     */
    function fnToggleAssocOptions(pageDataSchemas, oRecordData) {
      pageDataSchemas.forEach(function (oSchemaWrap) {
        var oSchema, oConfig
        if ((oConfig = oSchemaWrap.config) && (oSchema = oSchemaWrap.schema)) {
          if (
            oSchema.ops &&
            oSchema.ops.length &&
            oSchema.optGroups &&
            oSchema.optGroups.length
          ) {
            oSchema.optGroups.forEach(function (oOptGroup) {
              if (
                oOptGroup.assocOp &&
                oOptGroup.assocOp.schemaId &&
                oOptGroup.assocOp.v
              ) {
                if (
                  $parse(oOptGroup.assocOp.schemaId)(oRecordData) !==
                  oOptGroup.assocOp.v
                ) {
                  oSchema.ops.forEach(function (oOption) {
                    var domOption
                    if (oOption.g && oOption.g === oOptGroup.i) {
                      if (
                        oSchema.type === 'single' &&
                        oConfig.component === 'S'
                      ) {
                        domOption = document.querySelector(
                          'option[name="data.' +
                          oSchema.id +
                          '"][value=' +
                          oOption.v +
                          ']'
                        )
                        if (domOption && domOption.parentNode) {
                          domOption.parentNode.removeChild(domOption)
                        }
                      } else {
                        if (oSchema.type === 'single') {
                          domOption = document.querySelector(
                            'input[name=' +
                            oSchema.id +
                            '][value=' +
                            oOption.v +
                            ']'
                          )
                        } else if (oSchema.type === 'multiple') {
                          domOption = document.querySelector(
                            'input[ng-model="data.' +
                            oSchema.id +
                            '.' +
                            oOption.v +
                            '"]'
                          )
                        }
                        if (
                          domOption &&
                          (domOption = domOption.parentNode) &&
                          (domOption = domOption.parentNode)
                        ) {
                          domOption.classList.add('option-hide')
                        }
                      }
                      if (oSchema.type === 'single') {
                        if (oRecordData[oSchema.id] === oOption.v) {
                          oRecordData[oSchema.id] = ''
                        }
                      } else {
                        if (
                          oRecordData[oSchema.id] &&
                          oRecordData[oSchema.id][oOption.v]
                        ) {
                          delete oRecordData[oSchema.id][oOption.v]
                        }
                      }
                    }
                  })
                } else {
                  oSchema.ops.forEach(function (oOption) {
                    var domOption, domSelect
                    if (oOption.g && oOption.g === oOptGroup.i) {
                      if (
                        oSchema.type === 'single' &&
                        oConfig.component === 'S'
                      ) {
                        domSelect = document.querySelector(
                          'select[ng-model="data.' + oSchema.id + '"]'
                        )
                        if (domSelect) {
                          domOption = domSelect.querySelector(
                            'option[name="data.' +
                            oSchema.id +
                            '"][value=' +
                            oOption.v +
                            ']'
                          )
                          if (!domOption) {
                            domOption = document.createElement('option')
                            domOption.setAttribute('value', oOption.v)
                            domOption.setAttribute('name', 'data.' + oSchema.id)
                            domOption.innerHTML = oOption.l
                            domSelect.appendChild(domOption)
                          }
                        }
                      } else {
                        if (oSchema.type === 'single') {
                          domOption = document.querySelector(
                            'input[name=' +
                            oSchema.id +
                            '][value=' +
                            oOption.v +
                            ']'
                          )
                        } else if (oSchema.type === 'multiple') {
                          domOption = document.querySelector(
                            'input[ng-model="data.' +
                            oSchema.id +
                            '.' +
                            oOption.v +
                            '"]'
                          )
                        }
                        if (
                          domOption &&
                          (domOption = domOption.parentNode) &&
                          (domOption = domOption.parentNode)
                        ) {
                          domOption.classList.remove('option-hide')
                        }
                      }
                    }
                  })
                }
              }
            })
          }
        }
      })
    }
    /**
     * 添加辅助功能
     */
    function fnAssistant(pageDataSchemas) {
      pageDataSchemas.forEach(function (oSchemaWrap) {
        var oSchema, domSchema, domRequireAssist
        if ((oSchema = oSchemaWrap.schema)) {
          domSchema = document.querySelector(
            '[wrap=input][schema="' + oSchema.id + '"]'
          )
          if (domSchema) {
            switch (oSchema.type) {
              case 'longtext':
                domRequireAssist = document.querySelector(
                  'textarea[ng-model="data.' + oSchema.id + '"]'
                )
                if (domRequireAssist) {
                  domRequireAssist.addEventListener('paste', function (e) {
                    var text
                    e.preventDefault()
                    text = e.clipboardData.getData('text/plain')
                    tmsPaste
                      .onpaste(text, {
                        filter: {
                          whiteSpace: oSchema.filterWhiteSpace === 'Y',
                        },
                      })
                      .then(function (value) {
                        if (!document.execCommand('insertHTML', false)) {
                          domRequireAssist.value = value
                        }
                      })
                  })
                }
                break
            }
            /* 必填题目 */
            if (oSchema.required && oSchema.required === 'Y') {
              domSchema.classList.add('schema-required')
            }
          }
        }
      })
    }
    /**
     * 给关联选项添加选项nickname
     */
    function fnAppendOpNickname(dataSchemas) {
      dataSchemas.forEach(function (oSchema) {
        var domSchema
        domSchema = document.querySelector(
          '[wrap=input][schema="' + oSchema.id + '"]'
        )
        if (domSchema && oSchema.dsOps && oSchema.showOpNickname === 'Y') {
          switch (oSchema.type) {
            case 'multiple':
              if (oSchema.ops && oSchema.ops.length) {
                var domOptions
                domOptions = document.querySelectorAll(
                  '[wrap=input][schema="' +
                  oSchema.id +
                  '"] input[type=checkbox][ng-model]'
                )
                oSchema.ops.forEach(function (oOp, index) {
                  var domOption, spanNickname
                  if ((domOption = domOptions[index])) {
                    if (oOp.ds && oOp.ds.nickname) {
                      domOption = domOption.parentNode
                      spanNickname = document.createElement('span')
                      spanNickname.classList.add('option-nickname')
                      spanNickname.innerHTML = '[' + oOp.ds.nickname + ']'
                      domOption.appendChild(spanNickname)
                    }
                  }
                })
              }
              break
          }
        }
      })
    }
    /**
     * 给关联选项添加选项nickname
     */
    function fnAppendOpDsLink(dataSchemas) {
      dataSchemas.forEach(function (oSchema) {
        var domSchema
        domSchema = document.querySelector(
          '[wrap=input][schema="' + oSchema.id + '"]'
        )
        if (
          domSchema &&
          oSchema.dsOps &&
          oSchema.dsOps.app &&
          oSchema.dsOps.app.id &&
          oSchema.showOpDsLink === 'Y'
        ) {
          switch (oSchema.type) {
            case 'multiple':
              if (oSchema.ops && oSchema.ops.length) {
                var domOptions
                domOptions = document.querySelectorAll(
                  '[wrap=input][schema=' +
                  oSchema.id +
                  '] input[type=checkbox][name=' +
                  oSchema.id +
                  '][ng-model]'
                )
                oSchema.ops.forEach(function (oOp, index) {
                  var domOption, spanLink
                  if ((domOption = domOptions[index])) {
                    if (oOp.ds && oOp.ds.ek) {
                      domOption = domOption.parentNode
                      spanLink = document.createElement('span')
                      spanLink.classList.add('option-link')
                      spanLink.innerHTML = '[详情]'
                      spanLink.addEventListener('click', function (e) {
                        e.preventDefault()
                        var url
                        url = LS.j('', 'site')
                        url += '&app=' + oSchema.dsOps.app.id
                        url += '&page=cowork'
                        url += '&ek=' + oOp.ds.ek
                        location.href = url
                      })
                      domOption.appendChild(spanLink)
                    }
                  }
                })
              }
              break
          }
        }
      })
    }

    function doTask(seq, nextAction, type) {
      var task = _tasksOfBeforeSubmit[seq]
      task().then(function (rsp) {
        seq++
        seq < _tasksOfBeforeSubmit.length
          ? doTask(seq, nextAction, type)
          : doSubmit(nextAction, type)
      })
    }

    function doSubmit(nextAction, type) {
      _facInput
        .submit(
          $scope.record,
          $scope.data,
          $scope.tag,
          $scope.supplement,
          type,
          $scope.forQuestionTask
        )
        .then(
          function (rsp) {
            var url
            if (type == 'save') {
              noticebox.success(
                '保存成功，关闭页面后，再次打开时自动恢复当前数据。确认数据填写完成后，请继续【提交】数据。'
              )
            } else {
              _oSubmitState.finish()
              if (nextAction === 'closeWindow') {
                $scope.closeWindow()
              } else if (nextAction === '_autoForward') {
                // 根据指定的进入规则自动跳转到对应页面
                url = LS.j('', 'site', 'app')
                location.replace(url)
              } else if (nextAction && nextAction.length) {
                url = LS.j('', 'site', 'app')
                url += '&page=' + nextAction
                url += '&ek=' + rsp.data.enroll_key
                location.replace(url)
              } else {
                if ($scope.record.enroll_key === undefined) {
                  $scope.record = {
                    enroll_key: rsp.data.enroll_key,
                  }
                }
                $scope.$broadcast('xxt.app.enroll.submit.done', rsp.data)
              }
            }
          },
          function () {
            // reject
            _oSubmitState.finish()
          }
        )
    }

    /* 获得页面编辑的记录 */
    function fnAfterGetRecord(oRecord) {
      /* 同轮次的其他记录 */
      if (parseInt(_oApp.count_limit) !== 1 && oRecord.enroll_key) {
        http2
          .post(LS.j('record/list', 'site', 'app') + '&sketch=Y', {
            record: {
              rid: oRecord.round.rid,
            },
          })
          .then(function (rsp) {
            var records
            records = rsp.data.records || []
            $scope.recordsOfRound = {
              records: records,
              page: {
                size: 1,
                total: rsp.data.total,
              },
              shift: function () {
                fnGetRecord(records[this.page.at - 1].enroll_key)
              },
            }
            for (var i = 0, l = records.length; i < l; i++) {
              if (records[i].enroll_key === oRecord.enroll_key) {
                $scope.recordsOfRound.page.at = i + 1
                break
              }
            }
          })
      } else {
        $scope.recordsOfRound = {
          records: [],
          page: {
            size: 1,
            total: 0,
          },
        }
      }
      // 设置按钮初始状态
      var submitActs = []
      if (_oPage.actSchemas && _oPage.actSchemas.length) {
        _oPage.actSchemas.forEach(function (a) {
          if (a.name === 'submit') {
            a.disabled = false
            submitActs.push(a)
          }
        })
      }
      if (oRecord.round) {
        if (oRecord.round.start_at > 0) {
          if (oRecord.round.start_at * 1000 > new Date() * 1) {
            noticebox.warn(
              '活动轮次【' +
              oRecord.round.title +
              '】还没开始，不能提交、修改、保存或删除填写记录！'
            )
            submitActs.forEach(function (a) {
              a.disabled = true
            })
          }
        }
        if (oRecord.round.end_at > 0) {
          if (oRecord.round.end_at * 1000 < new Date() * 1) {
            noticebox.warn(
              '活动轮次【' +
              oRecord.round.title +
              '】已结束，不能提交、修改、保存或删除填写记录！'
            )
            submitActs.forEach(function (a) {
              a.disabled = true
            })
          }
        }
      }
      /* 判断多项类型 */
      if (_oApp.dynaDataSchemas.length) {
        angular.forEach(_oApp.dynaDataSchemas, function (oSchema) {
          if (oSchema.type == 'multitext') {
            $scope.data[oSchema.id] === undefined &&
              ($scope.data[oSchema.id] = [])
          }
        })
      }

      tmsSchema.autoFillMember(
        _oApp._schemasById,
        $scope.user,
        $scope.data.member
      )

      tmsSchema.loadRecord(_oApp._schemasById, $scope.data, oRecord.data)

      $scope.record = oRecord
      if (oRecord.supplement) {
        $scope.supplement = oRecord.supplement
      }
      /*设置页面分享信息*/
      $scope.setSnsShare(oRecord, {
        newRecord: LS.s().newRecord,
      })
      /*根据加载的数据设置页面*/
      fnAfterLoad(_oApp, _oPage, oRecord, $scope.data)
    }

    /* 获得要编辑记录 */
    function fnGetRecord(ek) {
      var urlLoadRecord
      if (ek) {
        urlLoadRecord = LS.j('record/get', 'site', 'app') + '&ek=' + ek
      } else {
        if (LS.s().newRecord === 'Y') {
          urlLoadRecord =
            LS.j('record/get', 'site', 'app', 'rid') + '&loadLast=N'
        } else {
          urlLoadRecord =
            LS.j('record/get', 'site', 'app', 'rid', 'ek') +
            '&loadLast=' +
            _oApp.open_lastroll +
            '&withSaved=Y'
        }
      }
      $scope.data = {
        member: {},
      }
      http2
        .get(urlLoadRecord, {
          autoBreak: false,
          autoNotice: false,
        })
        .then(
          function (rsp) {
            var oRecord
            oRecord = rsp.data
            fnAfterGetRecord(oRecord)
          },
          function (rsp) {
            if (LS.s().newRecord === 'Y' && LS.s().rid) {
              _facRound.get([LS.s().rid]).then(function (aRounds) {
                if (aRounds && aRounds.length === 1) {
                  fnAfterGetRecord({
                    round: aRounds[0],
                  })
                }
              })
            } else {
              fnAfterGetRecord({
                round: oRound ? oRound : _oApp.appRound,
              })
            }
          }
        )
    }

    function fnGetRecordByRound(oRound) {
      var urlLoadRecord

      urlLoadRecord = LS.j('record/get', 'site', 'app') + '&rid=' + oRound.rid
      $scope.data = {
        member: {},
      }
      http2
        .get(urlLoadRecord, {
          autoBreak: false,
          autoNotice: false,
        })
        .then(
          function (rsp) {
            fnAfterGetRecord(rsp.data)
          },
          function (rsp) {
            if (LS.s().newRecord === 'Y' && LS.s().rid) {
              _facRound.get([LS.s().rid]).then(function (aRounds) {
                if (aRounds && aRounds.length === 1) {
                  fnAfterGetRecord({
                    round: aRounds[0],
                  })
                }
              })
            } else {
              fnAfterGetRecord({
                round: oRound ? oRound : _oApp.appRound,
              })
            }
          }
        )
    }

    /* 页面和记录数据加载完成 */
    function fnAfterLoad(oApp, oPage, oRecord, oRecordData) {
      var dataSchemas
      dataSchemas = oPage.dataSchemas
      // 设置题目的默认值
      tmsSchema.autoFillDefault(_oApp._schemasById, $scope.data)
      // 控制题目是否只读
      fnToggleReadonlySchemas(dataSchemas)
      // 控制题目的轮次可见性
      fnToggleRoundSchemas(dataSchemas)
      // 控制关联题目的可见性
      fnToggleAssocSchemas(dataSchemas, oRecordData)
      // 控制题目关联选项的可见性
      fnToggleAssocOptions(dataSchemas, oRecordData)
      // 添加辅助功能
      fnAssistant(dataSchemas)
      // 从其他活动生成的选项的昵称
      fnAppendOpNickname(oApp.dynaDataSchemas)
      // 从其他活动生成的选项的详情链接
      fnAppendOpDsLink(oApp.dynaDataSchemas)
      // 跟踪数据变化
      $scope.$watch(
        'data',
        function (nv, ov) {
          if (nv !== ov) {
            _oSubmitState.modified = true
            // 控制关联题目的可见性
            fnToggleAssocSchemas(dataSchemas, oRecordData)
            // 控制题目关联选项的可见性
            fnToggleAssocOptions(dataSchemas, oRecordData)
          }
        },
        true
      )
    }

    window.onbeforeunload = function (e) {
      var message
      if (_oSubmitState.modified) {
        message = '已经修改的内容还没有保存，确定离开？'
        e = e || window.event
        if (e) {
          e.returnValue = message
        }
        return message
      }
    }

    var _facInput,
      _tasksOfBeforeSubmit,
      _oSubmitState,
      _oApp,
      _oPage,
      _StateCacheKey,
      _tkRound

    _tasksOfBeforeSubmit = []
    _facInput = Input.ins()
    $scope.tag = {}
    $scope.supplement = {}
    $scope.submitState = _oSubmitState = ngApp.oUtilSubmit.state

    $scope.beforeSubmit = function (fn) {
      if (_tasksOfBeforeSubmit.indexOf(fn) === -1) {
        _tasksOfBeforeSubmit.push(fn)
      }
    }
    $scope.gotoHome = function () {
      location.href =
        '/rest/site/fe/matter/enroll?site=' +
        _oApp.siteid +
        '&app=' +
        _oApp.id +
        '&page=repos'
    }
    $scope.removeItem = function (items, index) {
      noticebox.confirm('删除此项，确定？').then(function () {
        items.splice(index, 1)
      })
    }
    $scope.addItem = function (schemaId) {
      $uibModal
        .open({
          templateUrl: 'writeItem.html',
          controller: [
            '$scope',
            '$uibModalInstance',
            function ($scope2, $mi) {
              $scope2.data = {
                content: '',
              }
              $scope2.cancel = function () {
                $mi.dismiss()
              }
              $scope2.ok = function () {
                var content
                if (window.tmsEditor && window.tmsEditor.finish) {
                  content = window.tmsEditor.finish()
                  $scope2.data.content = content
                  $mi.close({
                    content: content,
                  })
                }
              }
            },
          ],
          windowClass: 'modal-remark auto-height',
          backdrop: 'static',
        })
        .result.then(function (data) {
          var item = {
            id: 0,
            value: '',
          }
          item.value = data.content
          if (
            !$scope.data[schemaId] ||
            !angular.isArray($scope.data[schemaId])
          ) {
            $scope.data[schemaId] = []
          }
          $scope.data[schemaId].push(item)
        })
    }
    $scope.editItem = function (schema, index) {
      var oItem = schema[index]
      $uibModal
        .open({
          templateUrl: 'writeItem.html',
          controller: [
            '$scope',
            '$uibModalInstance',
            function ($scope2, $mi) {
              $scope2.data = {
                content: oItem.value,
              }
              $scope2.cancel = function () {
                $mi.dismiss()
              }
              $scope2.ok = function () {
                var content
                if (window.tmsEditor && window.tmsEditor.finish) {
                  content = window.tmsEditor.finish()
                  $scope2.data.content = content
                  $mi.close({
                    content: content,
                  })
                }
              }
            },
          ],
          windowClass: 'modal-remark auto-height',
          backdrop: 'static',
        })
        .result.then(function (data) {
          oItem.value = data.content
        })
    }
    $scope.submit = function (event, nextAction, type) {
      var checkResult
      /*多项填空题，如果值为空则删掉*/
      for (var k in $scope.data) {
        if (
          k !== 'member' &&
          $scope.app._schemasById[k] &&
          $scope.app._schemasById[k].type == 'multitext'
        ) {
          angular.forEach($scope.data[k], function (item, index) {
            if (item.value === '') {
              $scope.data[k].splice(index, 1)
            }
          })
        }
      }
      if (!_oSubmitState.isRunning()) {
        _oSubmitState.start(event, _StateCacheKey, type)
        if (
          $scope.record.round.purpose !== 'C' ||
          type === 'save' ||
          true ===
          (checkResult = _facInput.check(
            $scope.data,
            $scope.app,
            $scope.page
          ))
        ) {
          _tasksOfBeforeSubmit.length
            ? doTask(0, nextAction, type)
            : doSubmit(nextAction, type)
        } else {
          _oSubmitState.finish()
          const [failReason, oFailSchema] = checkResult
          const failEle = document.querySelector(
            `[wrap=input][schema=${oFailSchema.id}]`
          )
          failEle.scrollIntoView({ block: 'center' })
          failEle.classList.add('check-failed')
          let fnRemoveClass = () => {
            failEle.classList.remove('check-failed')
            failEle.removeEventListener('focus', fnRemoveClass)
          }
          failEle.addEventListener('focus', fnRemoveClass, true)
          noticebox.warn(failReason)
        }
      }
    }
    $scope.getMyLocation = function (prop) {
      window.xxt.geo
        .getAddress(http2, $q.defer(), LS.p.site)
        .then(function (data) {
          $scope.data[prop] = data.address
        })
    }
    $scope.pasteUrl = function (schemaId) {
      tmsUrl
        .fetch($scope.data[schemaId], {
          description: true,
          text: true,
        })
        .then(function (oResult) {
          var oData
          oData = angular.copy(oResult.summary)
          oData._text = oResult.text
          $scope.data[schemaId] = oData
        })
    }
    $scope.editSupplement = function (schemaId) {
      var str = $scope.supplement[schemaId]
      if (!str) {
        str = ''
      }
      $uibModal
        .open({
          templateUrl: 'writeItem.html',
          controller: [
            '$scope',
            '$uibModalInstance',
            function ($scope2, $mi) {
              $scope2.data = {
                content: str,
              }
              $scope2.cancel = function () {
                $mi.dismiss()
              }
              $scope2.ok = function () {
                var content
                if (window.tmsEditor && window.tmsEditor.finish) {
                  content = window.tmsEditor.finish()
                  $scope2.data.content = content
                  $mi.close({
                    content: content,
                  })
                }
              }
            },
          ],
          windowClass: 'modal-remark auto-height',
          backdrop: 'static',
        })
        .result.then(function (data) {
          $scope.supplement[schemaId] = data.content
        })
    }
    /**
     * 填写历史数据
     */
    $scope.dataBySchema = function (schemaId) {
      var oRecordData, oHandleSchema, url
      url =
        '/rest/site/fe/matter/enroll/repos/dataBySchema?site=' +
        _oApp.siteid +
        '&app=' +
        _oApp.id
      if ((oHandleSchema = $scope.schemasById[schemaId])) {
        oRecordData = $scope.data
        $uibModal
          .open({
            templateUrl: 'dataBySchema.html',
            controller: [
              '$scope',
              '$uibModalInstance',
              function ($scope2, $mi) {
                let oAssocData = {}
                let oPage
                let data
                $scope2.page = oPage = {
                  size: 7,
                }
                $scope2.data = data = {
                  keyword: '',
                }
                $scope2.cancel = function () {
                  $mi.dismiss()
                }
                $scope2.ok = function () {
                  $mi.close($scope2.data)
                }
                $scope2.search = function (keyword) {
                  if (undefined !== keyword) {
                    data.keyword = keyword
                  }
                  http2
                    .post(
                      `${url}&schema=${oHandleSchema.id}&keyword=${data.keyword}`,
                      oAssocData,
                      {
                        page: oPage,
                      }
                    )
                    .then((oResult) => {
                      let oData = oResult.data[oHandleSchema.id]
                      if (oHandleSchema.type == 'multitext') {
                        oData.records.pop()
                      }
                      $scope2.records = oData.records
                      oPage.total = oData.total
                    })
                }
                if (
                  oHandleSchema.history === 'Y' &&
                  oHandleSchema.historyAssoc &&
                  oHandleSchema.historyAssoc.length
                ) {
                  oHandleSchema.historyAssoc.forEach(function (assocSchemaId) {
                    if (oRecordData[assocSchemaId]) {
                      oAssocData[assocSchemaId] = oRecordData[assocSchemaId]
                    }
                  })
                }
                $scope2.search()
              },
            ],
            backdrop: 'static',
          })
          .result.then(function (oResult) {
            var assocSchemaIds = []
            if (oResult.selected) {
              $scope.data[oHandleSchema.id] = oResult.selected
              /* 检查是否存在关联题目，自动完成数据填写 */
              _oApp.dynaDataSchemas.forEach((oOther) => {
                if (
                  oOther.id !== oHandleSchema.id &&
                  oOther.history === 'Y' &&
                  oOther.historyAssoc &&
                  oOther.historyAssoc.indexOf(oHandleSchema.id) !== -1
                ) {
                  assocSchemaIds.push(oOther.id)
                }
              })
              if (assocSchemaIds.length) {
                var oPosted = {}
                oPosted[oHandleSchema.id] = $scope.data[oHandleSchema.id]
                http2
                  .post(url + '&schema=' + assocSchemaIds.join(','), oPosted)
                  .then((rsp) => {
                    for (var schemaId in rsp.data) {
                      if (
                        rsp.data[schemaId].records &&
                        rsp.data[schemaId].records.length
                      ) {
                        $scope.data[schemaId] =
                          rsp.data[schemaId].records[0].value
                      }
                    }
                  })
              }
            }
          })
      }
    }
    $scope.score = function (schemaId, opIndex, number) {
      var oSchema, oOption

      if (!(oSchema = $scope.schemasById[schemaId])) return
      if (!(oOption = oSchema.ops[opIndex])) return

      if ($scope.data[oSchema.id] === undefined) {
        $scope.data[oSchema.id] = {}
        oSchema.ops.forEach(function (oOp) {
          $scope.data[oSchema.id][oOp.v] = 0
        })
      }

      $scope.data[oSchema.id][oOption.v] = number
    }
    $scope.lessScore = function (schemaId, opIndex, number) {
      var oSchema, oOption

      if (!$scope.schemasById) return false
      if (!(oSchema = $scope.schemasById[schemaId])) return false
      if (!(oOption = oSchema.ops[opIndex])) return false
      if ($scope.data[oSchema.id] === undefined) {
        return false
      }
      return $scope.data[oSchema.id][oOption.v] >= number
    }
    /* 切换轮次，获取和轮次对应的数据，如果有多条数据怎么办？返回最后填写的1条 */
    $scope.shiftRound = function (oRound) {
      fnGetRecordByRound(oRound)
    }
    /* 当前轮次下新建记录 */
    $scope.newRecord = function () {
      $scope.data = {
        member: {},
      }
      fnAfterGetRecord({
        round: $scope.record.round ? $scope.record.round : _oApp.appRound,
      })
    }
    $scope.doAction = function (event, oAction) {
      switch (oAction.name) {
        case 'submit':
          $scope.submit(event, oAction.next)
          break
        case 'gotoPage':
          $scope.gotoPage(event, oAction.next)
          break
        case 'save':
          $scope.submit(event, oAction.next, 'save')
          break
        case 'closeWindow':
          $scope.closeWindow()
          break
      }
    }
    $scope.$on('xxt.app.enroll.ready', function (event, params) {
      var schemasById, pasteContains

      _oApp = params.app
      _oPage = params.page
      _StateCacheKey =
        'xxt.app.enroll:' + _oApp.id + '.user:' + $scope.user.uid + '.cacheKey'
      $scope.schemasById = schemasById = _oApp._schemasById
      /* 不再支持在页面中直接显示按钮 */
      fnHidePageActions()
      /* 用户已经登记过或保存过，恢复之前的数据 */
      fnGetRecord()
      /* 活动提问任务 */
      if (_oApp.questionConfig && _oApp.questionConfig.length)
        new enlTask(_oApp).list('question').then((tasks) => {
          $scope.questionTasks = tasks
          if (tasks.length === 1) {
            $scope.forQuestionTask = tasks[0].id
          }
        })
      else $scope.questionTasks = []
      /* 活动轮次列表 */
      if (_oApp.roundCron && _oApp.roundCron.length) {
        _tkRound = new enlRound(_oApp)
        _tkRound.list().then((oResult) => {
          $scope.rounds = oResult.rounds
        })
      } else $scope.rounds = []
      /* 如果有文件上传题，设置限制条件 */
      if (
        _oApp.dynaDataSchemas.some((schema) => /file|image/.test(schema.type))
      )
        http2.get('/tmsappconfig.php').then(function (rsp) {
          $scope.fileConfig = {
            allowtype: rsp.fileContentTypeWhite,
            maxsize: rsp.fileMaxSize,
          }
        })
      /* 记录页面访问日志 */
      if (
        !_oApp.scenarioConfig.close_log_access ||
        _oApp.scenarioConfig.close_log_access !== 'Y'
      )
        $scope.logAccess()
      /* 微信不支持上传文件，指导用户进行处理 */
      if (/MicroMessenger|iphone|ipad/i.test(navigator.userAgent)) {
        if (
          _oApp.entryRule &&
          _oApp.entryRule.scope &&
          _oApp.entryRule.scope.member === 'Y'
        ) {
          for (var i = 0, ii = params.page.dataSchemas.length; i < ii; i++) {
            if (params.page.dataSchemas[i].schema.type === 'file') {
              var domTip, evt
              domTip = document.querySelector('#wxUploadFileTip')
              evt = document.createEvent('HTMLEvents')
              evt.initEvent('show', false, false)
              domTip.dispatchEvent(evt)
              break
            }
          }
        }
      }
      /*动态添加粘贴图片*/
      if (!$scope.isSmallLayout) {
        pasteContains = document.querySelectorAll('ul.img-tiles')
        angular.forEach(pasteContains, function (pastecontain) {
          var oSchema, html, $html
          oSchema = schemasById[pastecontain.getAttribute('name')]
          html = '<li class="img-picker img-edit">'
          html +=
            '<button class="btn btn-default" ng-click="pasteImage(\'' +
            oSchema.id +
            "',$event," +
            oSchema.count +
            ')">点击按钮<br>Ctrl+V<br>粘贴截图'
          html +=
            '<div contenteditable="true" tabindex="-1" style="width:1px;height:1px;position:fixed;left:-100px;overflow:hidden;"></div>'
          html += '</button>'
          html += '</li>'
          $html = $compile(html)($scope)
          angular.element(pastecontain).append($html)
        })
      }
    })
  },
])
