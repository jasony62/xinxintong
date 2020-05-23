module.exports = [
  "$parse",
  "tmsLocation",
  "http2",
  "tmsSchema",
  function ($parse, LS, http2, tmsSchema) {
    var Input, _ins;
    Input = function () {};
    Input.prototype.check = function (oRecData, oApp, oPage) {
      var oSchemaWrap, oSchema, value, sCheckResult;
      if (oPage.dataSchemas && oPage.dataSchemas.length) {
        for (var i = 0; i < oPage.dataSchemas.length; i++) {
          oSchemaWrap = oPage.dataSchemas[i];
          oSchema = oSchemaWrap.schema;
          /* 隐藏题和协作题不做检查 */
          if (
            (!oSchema.visibility ||
              !oSchema.visibility.rules ||
              oSchema.visibility.rules.length === 0 ||
              oSchema.visibility.visible) &&
            oSchema.cowork !== "Y" &&
            oSchema._visible !== false
          ) {
            if (oSchema.type && oSchema.type !== "html") {
              value = $parse(oSchema.id)(oRecData);
              sCheckResult = tmsSchema.checkValue(oSchema, value);
              if (true !== sCheckResult) {
                return sCheckResult;
              }
            }
          }
        }
      }
      return true;
    };
    Input.prototype.submit = function (
      oRecord,
      oRecData,
      tags,
      oSupplement,
      type,
      taskId
    ) {
      var url, d, oPosted, tagsByScchema;

      oPosted = angular.copy(oRecData);

      if (oRecord.enroll_key) {
        /* 更新已有填写记录 */
        url = LS.j("record/" + (type || "submit"), "site", "app");
        url += "&ek=" + oRecord.enroll_key;
      } else {
        /* 添加新记录 */
        if (oRecord.round) {
          /* 指定了填写轮次 */
          url =
            LS.j("record/" + (type || "submit"), "site", "app") +
            "&rid=" +
            oRecord.round.rid;
        } else {
          url = LS.j("record/" + (type || "submit"), "site", "app", "rid");
        }
      }
      /* 所属任务 */
      if (taskId) url += "&task=" + taskId;
      for (var i in oPosted) {
        d = oPosted[i];
        if (
          angular.isArray(d) &&
          d.length &&
          d[0].imgSrc !== undefined &&
          d[0].serverId !== undefined
        ) {
          d.forEach(function (d2) {
            delete d2.imgSrc;
          });
        }
      }
      tagsByScchema = {};
      if (Object.keys && Object.keys(tags).length > 0) {
        for (var schemaId in tags) {
          tagsByScchema[schemaId] = [];
          tags[schemaId].forEach(function (oTag) {
            tagsByScchema[schemaId].push(oTag.id);
          });
        }
      }
      return http2.post(
        url,
        {
          data: oPosted,
          tag: tags,
          supplement: oSupplement,
        },
        {
          autoBreak: false,
        }
      );
    };
    return {
      ins: function () {
        if (!_ins) {
          _ins = new Input();
        }
        return _ins;
      },
    };
  },
];
