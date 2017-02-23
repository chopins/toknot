＃控制器数据库Model文档

##控制器父类 Toknot\Share\Controller

###｀Toknot\Share\Controller｀方法列表：

* `model($tableName)`  实例化一个表model类，传入表名或model类名, 数据库表名字中的下划线将会被视为单词分割，然后大写首字母，并去掉下划线。model类名字不需要包括应用的命名空间和视图的命名空间，点会被视为命名空间名字分割线，例如：`user_info`表会获得`AppNs\ModelNs\UserInfo`类的实例。`call.user.info_ext`将会获得`AppNs\ModelNs\Call\User\Info_ext`的实例
* `setTitle($title)`  设置页面的标题
* `setLayout($layout)`  设置布局模版类，`$layout`必须是布局类的全类名，包括绝对的命名空间名字
* `getViewClass($view)`  获取一个缩写的模版名字的真实全类名，也就是　$view 不包括应用的命名空间和视图的命名空间，格式可以类似`user.index`,这个或获取到`AppNs\ViewNs\User\Index`, 应用的命名空间和视图的命名空间是在配置文件中配置的。
* `view($view, $return = false)` 解析一个视图模版,`$view`值见上一个方法，`$return`是否返回视图的HTML内容。方法会默认设置200响应代码，以及将视图HTML内容设置为页面内容
* `setResponse($status, $contents = '')`  设置响应内容,`$status`状态码,响应内容
* `redirect($route, $params = [], $status = 302)`  从定向到指定路由,本方法会终止后续逻辑(Kernel响应仍然会执行)
* `url($route, $params = [])` 生成指定路由的URL地址
* `config($key0 = '', $key1 = '', $key2 = '')`  根据指定key获取应用基本配置项,第一个参数为第一级，第二个为第一个参数指定项目为数组时的key,依次类推，例如获取`first.foo.key　＝　ｖalue`获取方法为`config('first','foo','key')`
* `get($key = '')` 获取请求参数，依次包含了POST,GET以及自定义数据
* `startSession()` 启动session,session管理类是`Toknot\Share\Session`
* `enableCsrf()` 激活CSRF校验,本方法需要配合在视图中调用`$this->enableCsrf($form)`
* `checkCsrf()`  校验CSRF值
* `v($key, $value)`  设置视图内部变量

##Model操作方法

* `getTableInfo()` 获取当前Model指定表的结构信息
* `getLastSql()`  获取最后执行的SQL语句
* `getColumnType($column)`  获取指定字段类型
* `tableName()`  获取当前Model关联表名
* `key()`   获取当前表主键名
* `getKeyValue($keyValue)`  获取指定主键值所在行
* `insert($value)`  插入一条数据,`$value` 值类似

    ```
    ['columnName1'=>'columnValue1',
     'columnName2'=>'columnValue2',
     'columnName3'=>'columnValue3'
    ......]
    ```

    生成`INSERT INTO table (columnName1,columnName2,columnName3...) VALUES('columnValue1','columnValue2','columnValue3'...)`

* `where($param)`  查询where语句，参数可以是字符串和数组,数组格式类似:

    ```
    ['column','value','=']
    ['column','value','>']
    ```
    生成`WHERE column='value'`和`WHERE column>'vaule'`
    
    ```
    ['&&',['column1','value1'],
          ['column2','value2','='],
          ['column3','value3','>']
    ]
    ```
    生成`WHERE column1 = 'value1' && column2 = 'value2' && column3 > 'value3'` 

    ```
    ['&&',['&&',['column1','value1'],
                ['column2','value2','='],
                ['column3','value3','>']
          ],
          ['||',['column4','value4'],
                ['column5','value5','>=']
          ]
    ]
    ```
    生成`WHERE (column1 = 'value1' && column2 = 'value2' && column3 > 'value3') && (column4 = 'value4' || column5 >= 'value5')`

    ```
    ['&&',['&&',['column1','value1'],
                ['column2','value2','='],
          ],
          ['&&' [ '&&',['column3','value3'],
                       ['column4','value4']
                ],
                ['column5','value5']
          ],
          ['||',['column6','value6'],
                ['column7','value7','>=']
          ]
    ]
    ```
    生成`WHERE (column1='value1' && column2=>'value2') && ((column3='value3' && column4= 'value4') && column5 = 'value5') && (column6='value6' || column7 >= 'value7')`

* `update($values, $where = [], $limit = 500, $start = 0)`  更新数据, `$values`值类似:
    ```
    [columnName1 => columnValue1,
     columnName2 => columnValue2,
    ......]
    ```
    生成`SET columnName1 = 'columnValue1', columnName2 = 'columnValue2'......`
    或者类似
    ```[columnName1=> ['+', columnName1, 1],
        columnName2=>[+, column2, column3],
        columnName3=>[SUM, column1, column2]
       ]
    ```
    生成`SET columnName1 = columnName1 + 1,columnName2 = column2 + column3,columnName3 = SUM(column1,column2)`

* `setColumn($column, $alias = '')`  设置查询返回字段和表别名
* `select($where = '')` 执行一条查询，默认返回所有字段,通过上一个方法改变
* `builder()`  实例一个查询构建实例
* `orderBy($sort, $order = null)` 添加排序方法和字段，在buider()调用后可用
* `delete($where, $limit = 500, $start = 0)` 删除语句
* `groupBy($key)`   分组语句
* `having($clause)` having 语句
* `setAlias($alias)` 设置表查询别名
* `geColumnAlias($col)`  获取一个字段，并添加表别名
* `leftJoin($table, $on, $where)` 左联查询,`$table`可以是数组或去他Model实例,数据包括所有Model实例，表别名通过上一个方法在Ｍodel中设置.`$on`格式与`$table`相同并依次对应,每添加一个表，增加一条`['table1.col1','table2.col2','=']`，与where中的条件一样，默认比较方式为等于
* `useNamespace()` 多数据库查询时，需要在查询前调用本方法
* `save($data)` 数据格式同`insert()`方法，本方法在主键重复时，会更新数据