'use strict';
var utilSchema = {};
utilSchema.isEmpty = function(oSchema, value) {
    if (value === undefined) {
        return true;
    }
    switch (oSchema.type) {
        case 'multiple':
            for (var p in value) {
                //至少有一个选项
                if (value[p] === true) {
                    return false;
                }
            }
            return true;
        default:
            return value.length === 0;
    }
};
utilSchema.checkRequire = function(oSchema, value) {
    if (value === undefined || this.isEmpty(oSchema, value)) {
        return '请填写必填题目［' + oSchema.title + '］';
    }
    return true;
};
utilSchema.checkFormat = function(oSchema, value) {
    if (oSchema.format === 'number') {
        if (!/^-{0,1}[0-9]+(.[0-9]+){0,1}$/.test(value)) {
            return '题目［' + oSchema.title + '］请输入数值';
        }
    } else if (oSchema.format === 'name') {
        if (value.length < 2) {
            return '题目［' + oSchema.title + '］请输入正确的姓名（不少于2个字符）';
        }
    } else if (oSchema.format === 'mobile') {
        if (!/^1(3[0-9]|4[57]|5[0-35-9]|7[0135678]|8[0-9])\d{8}$/.test(value)) {
            return '题目［' + oSchema.title + '］请输入正确的手机号（11位数字）';
        }
    } else if (oSchema.format === 'email') {
        if (!/^[A-Za-z\d]+([-_.][A-Za-z\d]+)*@([A-Za-z\d]+[-.])+[A-Za-z\d]{2,4}$/.test(value)) {
            return '题目［' + oSchema.title + '］请输入正确的邮箱';
        }
    }
    return true;
};
utilSchema.checkCount = function(oSchema, value) {
    if (oSchema.count !== undefined && value.length > oSchema.count) {
        return '［' + oSchema.title + '］超出上传数量（' + oSchema.count + '）限制';
    }
    return true;
};
utilSchema.checkValue = function(oSchema, value) {
    var sCheckResult;
    if (oSchema.required && oSchema.required === 'Y') {
        if (true !== (sCheckResult = this.checkRequire(oSchema, value))) {
            return sCheckResult;
        }
    }
    if (value) {
        if (oSchema.type === 'shorttext' && oSchema.format) {
            if (true !== (sCheckResult = this.checkFormat(oSchema, value))) {
                return sCheckResult;
            }
        }
        if (oSchema.type === 'multiple' && oSchema.limitChoice === 'Y' && oSchema.range) {
            var opCount = 0;
            for (var i in value) {
                if (value[i]) {
                    opCount++;
                }
            }
            if (opCount < oSchema.range[0] || opCount > oSchema.range[1]) {
                return '【' + oSchema.title + '】中最多只能选择(' + oSchema.range[1] + ')项，最少需要选择(' + oSchema.range[0] + ')项';
            }
        }
        if (/image|file/.test(oSchema.type) && oSchema.count) {
            if (true !== (sCheckResult = this.checkCount(oSchema, value))) {
                return sCheckResult;
            }
        }
    }
    return true;
};
utilSchema.loadRecord = function(schemasById, dataOfPage, dataOfRecord) {
    if (!dataOfRecord) return false;
    var schemaId, oSchema, value;
    for (schemaId in dataOfRecord) {
        if (schemaId === 'member') {
            dataOfPage.member = angular.extend(dataOfPage.member, dataOfRecord.member);
        } else if (oSchema = schemasById[schemaId]) {
            if (/score|url/.test(oSchema.type)) {
                dataOfPage[schemaId] = dataOfRecord[schemaId];
                if ('url' === oSchema.type) {
                    dataOfPage[schemaId]._substitute = utilSchema.urlSubstitute(dataOfPage[schemaId]);
                }
            } else if (dataOfRecord[schemaId].length) {
                if (oSchema.type === 'image') {
                    value = dataOfRecord[schemaId].split(',');
                    dataOfPage[schemaId] = [];
                    for (var i in value) {
                        dataOfPage[schemaId].push({
                            imgSrc: value[i]
                        });
                    }
                } else if (oSchema.type === 'multiple') {
                    value = dataOfRecord[schemaId].split(',');
                    dataOfPage[schemaId] = {};
                    for (var i in value) dataOfPage[schemaId][value[i]] = true;
                } else {
                    dataOfPage[schemaId] = dataOfRecord[schemaId];
                }
            }
        }
    }
    return true;
};
/**
 * 给页面中的提交数据填充用户通讯录数据
 */
utilSchema.autoFillMember = function(schemasById, oUser, oPageDataMember) {
    if (oUser.members) {
        angular.forEach(schemasById, function(oSchema) {
            if (oSchema.schema_id && oUser.members[oSchema.schema_id]) {
                var oMember, attr, val;
                oMember = oUser.members[oSchema.schema_id];
                attr = oSchema.id.split('.');
                if (attr.length === 2) {
                    oPageDataMember[attr[1]] = oMember[attr[1]];
                } else if (attr.length === 3 && oMember.extattr) {
                    if (!oPageDataMember.extattr) {
                        oPageDataMember.extattr = {};
                    }
                    switch (oSchema.type) {
                        case 'multiple':
                            val = oMember.extattr[attr[2]];
                            if (angular.isObject(val)) {
                                oPageDataMember.extattr[attr[2]] = {};
                                for (var p in val) {
                                    if (val[p]) {
                                        oPageDataMember.extattr[attr[2]][p] = true;
                                    }
                                }
                            }
                            break;
                        default:
                            oPageDataMember.extattr[attr[2]] = oMember.extattr[attr[2]];
                    }
                }
            }
        });
    }
};
utilSchema.urlSubstitute = function(oUrlData) {
    var substitute;
    substitute = '';
    if (oUrlData) {
        if (oUrlData.title) {
            substitute += '【' + oUrlData.title + '】';
        }
        if (oUrlData.description) {
            substitute += oUrlData.description;
        }
    }
    substitute += '<a href="' + oUrlData.url + '">网页链接</a>';

    return substitute;
};
module.exports = utilSchema;