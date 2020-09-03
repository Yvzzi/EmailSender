# EmailSender

极简的邮件发送助手

## 1. EmailSender Cli

### 1.1 单元数据

当使用该程序时, 当你只需要**编译一次**然后发给目标, 编译模板使用的**数据**称为单元数据. 所以一来说**一次编译的模板**只发给**一个目标用户**.

### 1.2 多元数据

由于程序可以在一个**任务**中编译**多个模板**, 发给多个用户. 此时该**多元数据**为**单元数据**的**数组**. 实际上两种数据的载体都是一样的, 比如xslx文件, 只是使用用途不一致, 所以这里给了一个区分. 在多元模式下, 实际上就是, 把多元数据下的每份数据作为单元数据->渲染模板->发送. 就是说使用多元数据+多元模式(下面任务配置文件那里有说)就会多次渲染多次发送. 每一次的数据是其中一份的数据.

### 1.3. 任务内的模板写法

> 如{{$id}}@mail.xxx.com

### 1.4. 任务字段

| 字段       | 类型            | 范围                 | 作用                                                         | 是否可使用模板写法 |
| ---------- | --------------- | -------------------- | ------------------------------------------------------------ | ------------------ |
| mode       | string          | "single"\|"multiple" | 指定如何解析data, 即**单元**或**多元**                       | 否                 |
| template   | string          | 无                   | 指定提供的模板                                               | 否                 |
| data       | string          | 无                   | 提供数据的文件支持xlsx/json等                                | 否                 |
| primaryKey | string          | 无                   | 规定为单元数据的一个主键, 主要用于生成文件时便于识别如规定为name. 那么当单元数据下name对于值为Tom时生成测试文件为Tom.html | 否                 |
| interval   | array           | 两个元素             | 规定发一封后的休息时间, 如[25, 30]表示随机休息25-30s         | 否                 |
| subject    | string          | 无                   | 邮件主题                                                     | 是                 |
| to         | array\<string\> | 无                   | 发送至的地址如 "{{$name}}@mail.com"                          | 是                 |
| attachment | array\<string\> | 无                   | 发送的附件                                                   | 是                 |



### 1.5. 文件格式支持

**任务**请用json
**数据**可选excel/json/yml

### 1.6. 使用方法

#### 1.6.1 发送邮件预测试

```bash
php ./../src/EmailSenderCli.php  -c 邮件配置文件 -f 任务文件 -o 测试输出目录
```
打开测试目录就能看到生成的测试文件啦

#### 1.6.1 发送邮件

```bash
php ./../src/EmailSenderCli.php  -c 邮件配置文件 -f 任务文件
```

## 2. 模板的写法

首先我们这样想, 渲染模板的数据都是一个array或者一个map, 我们把这个顶层的数据(用来渲染整个文档的变量)(单元或者多元) 叫做```$data```, 这个map里面可能有很多键, 这些**一级**的变量(就是不是那种map的某key下的值(也是map)下某key对于的值)我们都可以直接用一个变量表示 ```$键名```(这是一个方便你的魔法), 当然你也可以用```$data["键"]```来使用它.

对于多元模式下, 渲染n次, 每次的数据都作为\$data.
对于单元模式下, 渲染1次, 数据直接作为\$data.

这里介绍几种模板标记, 它们根据**数据**被转换为相应的字符串,

```html
{{: php 表达式, 不用以;结尾}} 执行一个php表达式
{{# $var "子模板"}} 用$var变量去渲染子模板
{{$var}} 输出$var的值来替换掉"{{$var}}"
{{% "%.2f%2d" .5 5}} format输出


{{each $array}} 其实这里使用的也是 $data["array"]
	<li>{{$value}}</li>
{{end}}
遍历输出若干个<li>{{$value}}</li> 
这里用$value变量表示$array的每一元素$value, $key表示对应的$key
例如 $array为 ["a" => "a0", "b" => "b0"]那么输出
<li>a_0</li> 
<li>b_0</li> 

用选择语句选择输出
{{if $a > 5}}
	<p>{{233}}</p>
{{else}}
	<p>{{255}}</p>
{{end}}


定义函数定义后可调用
{{function format():}}
	{{: return 'Copyright ' . date("Y")}}
{{endf}}

```

### 2.1 模板举例说明

在EmailSender/resources下放了不少精致简洁的模板可以在里面找字符串 {{某某}}, 这个就是对应的它使用的模板变量, 这里讲解一下如何使用它们, 下面我们用\<!-- 注释 --\>表示注释,

```html
{{: $include = "到resources/example的路径"}}
<!-- 这里用顶层变量$data去渲染子模板, 所以子模板渲染时的顶层变量也是这个$data -->
{{# $data "$include/content.tmpl"}}
    <h1>Hi, This is a Example</h1>
    <hr/>
    <p>我是用来填充的,我是用来填充的,我是用来填充的,我是用来填充的,我是用来填充的,我是用来填充的,我是用来填充的.</p>
<!-- 这里使用的也是$data["list"] -->
    {{# $list "$include/attrList.tmpl"}}
    <br/>
<!-- 这里使用的也是$data["btn"] -->
    {{# $btn "$include/btn.tmpl"}}
    <br/>
    {{# $purchase "$include/purchase.tmpl"}}
    {{# $sub "$include/subBody.tmpl"}}
{{# $data "$include/contentEnd.tmpl"}}
```

你可以自行组合使用里面的模板也可以使用你自个找的或者制作的模板. 如果你要使用我那里的预制模板EmailSender/resources, 注意前后有 ```content``` 和 ```contentEnd```要组合在一起.

```
{{: $include = "到resources/example的路径"}}

{{# $data "$include/content.tmpl"}}

{{# $data "$include/contentEnd.tmpl"}}
```

## 3. 数据的写法

### 3.1 如果你用Json

举个例子发送录取邮件

这个只能用于发送给一个人 我们把它叫做**单元数据**, 前面提到过

```json
{
    "email": "2616464@mail.com",
    "name": "asa"
}
```

这个能用于发送给多个人 我们把它叫做**多元数据**, 前面提到过

```json
[
    {
        "email": "2616464@mail.com",
        "name": "asa"
    },
    {
        "email": "54656@mail.com",
        "name": "zzz"
    }
]
```

这些数据在渲染时我们使用就可以通过$data来使用, 比如**单元模式**下 \$data["email"]="2616464@mail.com", 

多元模式下 第一次渲染 \$data["email"] = "2616464@mail.com"
第二次渲染 \$data["email"] = "54656@mail.com"

## 3.3 如果你是要用xlsx

我们采用如下方式模拟map和array

Sheet: Sheet1

| name     | copyright | btn       | List       |
| -------- | --------- | --------- | ---------- |
| ProductA | 神秘组织  | &SheetBtn | &SheetList |
| ProductB | 神秘组织2 | &SheetBtn | &SheetList |

Sheet: SheetBtn

| body                  | text          |
| --------------------- | ------------- |
| 我们是南科大最强团队  | 点击加入我们  |
| 我们是南科大最强团队2 | 点击加入我们2 |

Sheet: SheetList

| 0                          | 1                          | 2                          |      |      |
| -------------------------- | -------------------------- | -------------------------- | ---- | ---- |
| <strong>A</strong>-只要111 | <strong>B</strong>-只要222 | <strong>C</strong>-只要333 |      |      |
| <strong>A</strong>-不要钱  | <strong>B</strong>-不要钱  | <strong>C</strong>-不要钱  |      |      |

简单介绍一下 &SheetBtn可以引用到其他的Sheet并把它作为一个map或者array
这里对应的json大概是这样, 如果你启用了多元数据的模式的话

```
[
	{
		"name": "productA"
		...,
		"btn": {
			"body": "我们是南科大最强团队",
			"text": "点击..."
		},
		"list": [
			"<strong>A</strong>-只要111",
			"<strong>B</strong>-只要222",
			"<strong>C</strong>-只要333"
		]
    },
    {
		"name": "productB"
		...,
		"btn": {
			"body": "我们是南科大最强团队2",
			"text": "点击..."
		},
		"list": [
			"<strong>A</strong>-不要钱",
			...
		]
    }
]
```

如果你使用单元数据的模式的话

Sheet: Sheet1

| name     | copyright | btn       | List       |
| -------- | --------- | --------- | ---------- |
| ProductA | 神秘组织  | &SheetBtn | &SheetList |

Sheet: SheetBtn

| body                 | text         |
| -------------------- | ------------ |
| 我们是南科大最强团队 | 点击加入我们 |

Sheet: SheetList

| 0                          | 1                          | 2                          |      |      |
| -------------------------- | -------------------------- | -------------------------- | ---- | ---- |
| <strong>A</strong>-只要111 | <strong>B</strong>-只要222 | <strong>C</strong>-只要333 |      |      |

对应json是

```

{
	"name": "productA"
	...,
    "btn": {
        "body": "我们是南科大最强团队",
        "text": "点击..."
    },
    "list": [
        "<strong>A</strong>-只要111",
        "<strong>B</strong>-只要222",
        "<strong>C</strong>-只要333"
    ]
}
```

## 4. 试一试

example文件夹下有测试脚本, 你可以用编辑方式打开它看里面的命令是什么样子的 (先不要管task4), 运行task1-3需要先配置一下mailSetting.json和对应在task目录下的task.json, task3.bat是一个可以在本地运行的脚本, 它会直接输出一个文件来记录生成的邮件, 防止你粗心的发送后发现发错了, 你可以双击浏览器打开那些生成的邮件来预览

## 5.TODO

因为不常使用, 未添加BBC等功能