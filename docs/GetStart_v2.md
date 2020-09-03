# EmailSender

极简的邮件发送助手

## 1. 发送过程解析



程序读取任务的json文件, 该文件有如下几个参数

| 字段       | 类型            | 范围                 | 作用                                                 | 是否可使用模板写法 |
| ---------- | --------------- | -------------------- | ---------------------------------------------------- | ------------------ |
| mode       | string          | "single"\|"multiple" | 指定如何发送, 即**单发**或**群发**                   | 否                 |
| template   | string          | 无                   | 指定提供的模板                                       | 否                 |
| data       | string          | 无                   | 提供数据的文件支持xlsx/json等                        | 否                 |
| primaryKey | string          | 无                   | 使得测试输出文件唯一化                               | 否                 |
| interval   | array           | 两个元素             | 规定发一封后的休息时间, 如[25, 30]表示随机休息25-30s | 否                 |
| subject    | string          | 无                   | 邮件主题                                             | 是                 |
| to         | array\<string\> | 无                   | 发送至的地址如 "{{$name}}@mail.com"                  | 是                 |
| attachment | array\<string\> | 无                   | 发送的附件                                           | 是                 |

根据模式选择单发或者群发的不同对数据的处理也有一点点差异

## 2. 单发

将数据从data指定的文件中加载, 加载为一个map, 接着利用这个map对模板进行渲染, 然后发送。

什么是渲染？

模板：

```
你好，{{$name}}，恭喜你会员充值成功了！！！更多问题，你现在可以在{{$link}}获取你的专属权益！！！
```

数据：

```
{
	"name": "张三",
	"link": "http://xxx.com/user/zhang_san"
}
```

输出：

```
你好，张三，恭喜你会员充值成功了！！！更多问题，你现在可以在http://xxx.com/user/zhang_san获取你的专属权益！！！
```

渲染就是获取这个输出的过程。

## 3. 群发

将数据从data指定的文件中加载, 加载为一个map[]，即一个map的数组， 接着利用这个map[]分别对模板进行渲染, 然后分别发送。

如

模板：

```
你好，{{$name}}，恭喜你会员充值成功了！！！更多问题，你现在可以在{{$link}}获取你的专属权益！！！
```

数据：

```
[
    {
        "name": "张三",
        "link": "http://xxx.com/user/zhang_san"
    },
    {
    	"name": "李四",
        "link": "http://xxx.com/user/li_si"
    }
]
```

接着它就发给了两个人：张三和李四。

## 4. 任务中的是否可使用模板是什么意思

举个栗子，上面的会员通知有两个人，但是它们的邮箱是不一样的叭？那么不就是要分别指定邮箱了？所以可以写成这样

数据：

```
[
    {
        "name": "张三",
        "link": "http://xxx.com/user/zhang_san",
        "qq_id": "8848"
    },
    {
    	"name": "李四",
        "link": "http://xxx.com/user/li_si",
        "qq_id": "2233"
    }
]
```

任务配置文件：

```
{
	"mode": "multiple",
	...
	...
	"to": [
		"{{$qq_id}}@mail.qq.com"
	]
	...
}
```

## 4. 发送和测试

### 4.1 发送邮件预测试

```bash
php EmailSenderCli.phar  -c 邮件配置文件 -f 任务文件 -o 测试输出目录
```

打开测试目录就能看到生成的测试文件啦

什么是预测试？就是不发送邮件，而是存成文件，以供预览和检查。

**那么之前配置文件那里的primaryKey是啥？**就是如果你群发的话，预测试会生成很多文件鸭，那么你最好要一样就能看出哪个文件是发给谁的鸭？所以你比如上面的会员通知，primaryKey指定为 qq_id那么输出文件就是

```
8848.html
8848-info.json
2233.html
2233-info.json
```

### 4.2 发送邮件

```bash
php EmailSenderCli.phar  -c 邮件配置文件 -f 任务文件
```

## 2. 模板的写法

## 5. 如果要用xslx读取的数据，那么文件怎么写

### 5.0如果你是要用json

你就写成上面数据格式就行（上面介绍都是用的json格式），那么下面这里 **5** 你就不要康了

### 5.1 如果你是要用xlsx(觉得比较复杂那就别用它)

我们采用如下方式模拟map和array

Sheet: Sheet1

| name     | copyright | btn       | List       |
| -------- | --------- | --------- | ---------- |
| ProductA | 神秘组织  | &SheetBtn | &SheetList |
|          |           |           |            |

Sheet: SheetBtn

| body                 | text         |
| -------------------- | ------------ |
| 我们是南科大最强团队 | 点击加入我们 |
|                      |              |

Sheet: SheetList

| 0                          | 1                          | 2                          |      |      |
| -------------------------- | -------------------------- | -------------------------- | ---- | ---- |
| <strong>A</strong>-只要111 | <strong>B</strong>-只要222 | <strong>C</strong>-只要333 |      |      |
|                            |                            |                            |      |      |

这里&相当于引用的意思，每个sheet最上面的作为引用对象的key，程序会把这种东西转化为一个json对象，如上面的这个会转化为

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

### 5.2 如果你是要用xlsx(觉得比较复杂那就别用它)，并且群发

我们采用如下方式模拟map和array

Sheet: Sheet1

| NAME     | COPYRIGHT | BTN       | LIST       |
| :------- | :-------- | :-------- | :--------- |
| ProductA | 神秘组织  | &SheetBtn | &SheetList |
| ProductB | 神秘组织2 | &SheetBtn | &SheetList |

Sheet: SheetBtn

| BODY                  | TEXT          |
| :-------------------- | :------------ |
| 我们是南科大最强团队  | 点击加入我们  |
| 我们是南科大最强团队2 | 点击加入我们2 |

Sheet: SheetList

| 0             | 1             | 2             |      |      |
| :------------ | :------------ | :------------ | :--- | :--- |
| **A**-只要111 | **B**-只要222 | **C**-只要333 |      |      |
| **A**-不要钱  | **B**-不要钱  | **C**-不要钱  |      |      |

它将会转化为

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

## 6. 如果你需要更强大的模板

比如根据性别称呼先生和女士

首先在数据里添加性别，

```
[
    {
        "name": "张三",
        "link": "http://xxx.com/user/zhang_san",
        "qq_id": "8848"，
        "gender": 0
    },
    {
    	"name": "李四",
        "link": "http://xxx.com/user/li_si",
        "qq_id": "2233",
        "gender": 1
    }
]
```



```
你好{{$name}}{{if $gender == 0}}{{"先生"}}{{else}}{{"女士"}}，恭喜你会员充值成功了！！！更多问题，你现在可以在{{$link}}获取你的专属权益！！！
```

### 6.1. 模板的写法

模板支持所有php语法

```html
{{: $a = 2333}} 执行一个php表达式, 不用以;结尾
{{# $var "子模板"}} 用$var变量去渲染子模板，$var需要是一个map
{{$var}} 输出$var的值
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

**什么是$data变量？**

就是当前用来渲染的那个map等价于\$data，比如你上面在会员通知用的\$name, \$link 也可换成 \$datap["name"], $date["link"]

### 6.2 子模板父模板是干嘛用的

举个栗子

info.tmpl

```
++++++++++++++++++++++++++++++++++++++++++++++++++++++
+ 名字： {{$name}}
+ 性别： {{$name}}{{if $gender == 0}}{{"男"}}{{else}}{{"女"}}
++++++++++++++++++++++++++++++++++++++++++++++++++++++
<a href="{{$link}}">点我查看会员权益</a>
```

这里提供了一个好康的信息卡（确信）

然后其它地方就可以方便的使用它

```
你好，恭喜你会员充值成功了！！！
{{# $data "./info.tmpl"}}
```

比如你还可以将先生与女士对调 （这是神马恶趣味哼）

```
你好，恭喜你会员充值成功了！！！
{{: $newData = $data}}
{{if $gender == 0}}
{{: $newData["gender"] = 1}}
{{else}}
{{: $newData["gender"] = 0}}
{{# $newData "./info.tmpl"}}
```



### 6.3 内置模板举例说明

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

