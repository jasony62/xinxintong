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

    var p, value;
    for (p in dataOfRecord) {
        if (p === 'member') {
            /* 提交的数据覆盖自动填写的联系人数据 */
            if (angular.isString(dataOfRecord.member)) {
                dataOfRecord.member = JSON.parse(dataOfRecord.member);
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
utilSchema.autoFillMember = function(user, member) {
    var member2, eles;
    if (user && member && member.schema_id && user.members) {
        if (member2 = user.members[member.schema_id]) {
            if (angular.isString(member2.extattr)) {
                if (member2.extattr.length) {
                    member2.extattr = JSON.parse(member2.extattr);
                } else {
                    member2.extattr = {};
                }
            }
            eles = document.querySelectorAll("[ng-model^='data.member']");
            angular.forEach(eles, function(ele) {
                var attr;
                attr = ele.getAttribute('ng-model');
                attr = attr.replace('data.member.', '');
                attr = attr.split('.');
                if (attr.length == 2) {
                    !member.extattr && (member.extattr = {});
                    member.extattr[attr[1]] = member2.extattr[attr[1]];
                } else {
                    member[attr[0]] = member2[attr[0]];
                }
            });
        }
    }
};
module.exports = utilSchema;
