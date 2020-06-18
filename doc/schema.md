标准的`JSONSchema`定义。扩展出表单定义，表格定义，列表定义等视图定义。

在基础类型上，通过指定`format`实现扩展。允许定义新的`format`，允许指定`format`需要需要扩展属性。

基础类型和`format`对应关系。

通过`formAttrs`说明在表单中约束条件。

通过`viewAttrs`说明在查看单条记录时的约束条件。

通过`listAttrs`说明在列表中约束条件。

交互界面属性，和类型和 format 的对应。

`format`和`attrs`可以扩展。

将页面特有的属性从题目中剔除放到页面的定义中，简化数据的定义。

交互逻辑和数据逻辑分离。

# JSONSchema

定义`schema`。

| 属性     | 类型   | 说明                                             | 必填 | 默认值                         |
| -------- | ------ | ------------------------------------------------ | ---- | ------------------------------ |
| \$schema | string | 声明该 json 是`JSONSchema定义`，而不是普通文档。 | 是   | http://json-schema.org/schema# |
| \$id     | string | 定义的唯一标识。                                 | 是   |                                |

定义单个属性（property）。

| 属性        | 说明     | 必填 | 默认值 |
| ----------- | -------- | ---- | ------ |
| type        | 属性类型 | 是   |        |
| title       | 属性标题 | 是   |        |
| description | 属性描述 | 是   |        |
| default     | 默认值   | 是   |        |
| examples    | 示例     | 是   |        |

支持的`type`取值为：`string`，`number`，`integer`，`object`，`array`，`boolean`，`null`。

每种类型支持特定属性，详细信息查看在线文档。

[Understanding JSON Schema](https://json-schema.org/understanding-json-schema/)

# 表单定义

## 通用属性

| 属性         | 说明                                         | 必填 | 默认值 | JSONSchema |
| ------------ | -------------------------------------------- | ---- | ------ | ---------- |
| id           | 题目 ID                                      | 是   |        |            |
| title        | 题目标题                                     | 是   |        | Y          |
| description  | 填写说明（描述说明题不支持）                 | 否   |        | Y          |
| required     | 必填（描述说明题不支持）                     | 是   | N      | ?          |
| type         | 题目类型                                     | 是   |        |            |
| readonly     | 只读（描述说明题不支持）                     | 是   | N      |            |
| supplement   | 允许填写补充内容（描述说明题不支持）         | 是   | N      |            |
| requireScore | 需要计分                                     | 是   | N      |            |
| scoreMode    | 作为测评（evaluation）或作为考题（question） | 是   | N      |            |
| shareable    | 填写内容是否可共享（描述说明题不支持）       | 是   | N      |            |

shareable

题目类型

| 题型       | 名称      | JSONSchema type |
| ---------- | --------- | --------------- |
| 单行填写题 | shorttext | string          |
| 多行填写题 | longtext  | string          |
| 多项填写题 | multitext | array           |
| 单选题     | single    | string          |
| 多选题     | multiple  | array           |
| 打分题     | score     | object          |
| 上传图片   | image     | object          |
| 上传文件   | file      | object          |
| 上传链接   | url       | object          |
| 微信录音   | voice     | object          |
| 描述说明   | html      | string          |
| 日期       | date      | integer         |

通过`format`指定属性的语义。在`JSONSchema`中，`format`只用于`string`类型，这里扩展到到`array`，`object`和`integer`。

## 题型自有属性

单行填写题（shorttext）

| 属性               |          | 说明                                 |
| ------------------ | -------- | ------------------------------------ |
| format             |          | 填写限制                             |
|                    | number   | 数值                                 |
|                    | name     | 姓名                                 |
|                    | mobile   | 手机                                 |
|                    | email    | 邮箱                                 |
|                    | caculate | 公式                                 |
| formula            |          | 当限制格式为公式时，记录指定的公式。 |
| unique             |          | 填写内容是否唯一。                   |
| showHistoryAtRepos |          | 在共享页查看历史数据。               |

多行填写题（longtext)

| 属性             | 说明                    |
| ---------------- | ----------------------- |
| filterWhiteSpace | 粘贴时提示过滤空白字符< |

多项填写题（multitext)

| 属性   |     | 说明       |
| ------ | --- | ---------- |
| cowork |     | 作为问答题 |

多选题（multiple）

| 属性        |     | 说明                         |
| ----------- | --- | ---------------------------- |
| limitChoice |     | 限制选择数量                 |
| range       |     | 数组，记录选择数量限制的范围 |

打分题（score）

| 属性  | 说明               |
| ----- | ------------------ |
| range | 数组，记录打分范围 |

单选题（single）

| 属性      | 说明                                         |
| --------- | -------------------------------------------- |
| component | 单选题选项展示形式，单选钮（R）或下拉框（S） |

单选题（single），多选题（multiple）

| 属性  | 说明                                       |
| ----- | ------------------------------------------ |
| align | 选项排列方式，垂直排列（V）或水平排列（H） |

单行填写题（shorttext），多行填写题（multitext）

| 属性    | 说明         | 必填 | 默认值 |
| ------- | ------------ | ---- | ------ |
| history | 选择已填数据 | 是   | N      |

图片（image），文件（file），微信录音（voice）

| 属性  | 说明                       | 必填 | 默认值 |
| ----- | -------------------------- | ---- | ------ |
| count | 限制上传数量，为空时不限制 | 否   | 0      |

单选（single），多选（multiple），单行填写（short|history）

| 属性  | 说明                     | 必填 | 默认值 |
| ----- | ------------------------ | ---- | ------ |
| asdir | 在共享页作为内容分类目录 | 否   | N      |

设置默认选项

设置选项可见条件

设置父题目

设置题目可见条件
