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
utilSchema.loadRecord = function(schemasById, dataOfPage, dataOfRecord, oUser) {
    /* 自动填写通讯录联系人 */

    if (!dataOfRecord) return false;
    var p, value;
    for (p in dataOfRecord) {
        if (p === 'member') {
            /* 提交的数据覆盖自动填写的联系人数据 */
            //if (angular.isString(dataOfRecord.member)) {
            //    dataOfRecord.member = JSON.parse(dataOfRecord.member);
            //}
            if (oUser.members && oUser.members.length) {

            }
            dataOfPage.member = angular.extend(dataOfPage.member, dataOfRecord.member);

        } else if (schemasById[p] !== undefined) {
            var schema = schemasById[p];
            if (schema.type === 'score') {
                dataOfPage[p] = dataOfRecord[p];
            } else if (dataOfRecord[p].length) {
                if (schemasById[p].type === 'image') {
                    value = dataOfRecord[p].split(',');
                    dataOfPage[p] = [];
                    for (var i in value) {
                        dataOfPage[p].push({
                            imgSrc: value[i]
                        });
                    }
                } else if (schemasById[p].type === 'multiple') {
                    value = dataOfRecord[p].split(',');
                    dataOfPage[p] = {};
                    for (var i in value) dataOfPage[p][value[i]] = true;
                } else {
                    dataOfPage[p] = dataOfRecord[p];
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
            if (oSchema.type === 'member' && oSchema.schema_id && oUser.members[oSchema.schema_id]) {
                var oMember, attr;
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
                            break;
                        default:
                            oPageDataMember.extattr[attr[2]] = oMember.extattr[attr[2]];
                    }
                }
            }
        });
    }
};
module.exports = utilSchema;