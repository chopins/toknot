##View 和 Layout 文档
* 页面视图类必须继承`Toknot\Share\View\View`
* 页面布局类必须继承`Toknot\Share\View\Layout`

####`Toknot\Share\View\View`
需在页面视图类中实现以下方法：
* `page()` 创建页面内容

方法文档
* `setHead($layout)` 将布局中的head标签实例保存到`$this->head`属性中
* `setBody(Layout $display)` 将布局中的body标签实例保存到`$this->body`属性中
* `static setTitle($title)`  设置页面标题
* `static setLayout($layout)` 设置页面布局类
* `layout()` 返回当前页面布局类
* `title()` 返回当前设置的页面标题
* `getLayoutInstance()` 返回布局类实例
* `static html($param = [])` 返回页面HTML文档，`$param` 页面参数
* `route($route, $params = [])` 生成路由URL
* `enableCsrf($form)`   在`$form`表单中激活csrf字段

魔术方法：未定义方法调用将返回同名标签类实例

####`Toknot\Share\View\Layout`
需要在布局类中实现以下方法：
* `head()` 本方法设置页面head头信息
以下方法为可选实现：
* `html()` 本方法返回html标签属性
* `docType` 设置页面doc版本
* `body()` 本方法返回body标签属性

####`Toknot\Share\View\Tag`
本类实现创建所有HTML标签的HTML文档。
以下为静态方法：
* `html($attr = [], $docType = [])` HTML标签，`$attr` HTML标签属性，`$docType`设置文档版本,不返回值
* `head()`   head 标签
* `body($attr = [])` body标签，`$attr` body标签属性
* `title($text)` 设置title标题
* `text($parentTag, $text)` 给标签添加文本
* `textarea($parentTag, $attr = [], $text = '')` 文本区域输入框，`$attr`属性，`$text`文件内容
* `form(TagBulid $parentTag, $attr = [])` 设置`form`标单数据，数据格式类似如下：
     ```php
        $form = ['class' => 'pure-form pure-form-stacked', 
                 'method' => 'post', 
                 'action' => $this->param['login'],
                 'input' =>  [
                               'login-name' => ['value' => '', 
                                                'id' => 'login_name', 
                                                'type' => 'text', 
                                                'placeholder' => '用户名/邮件/手机号', 
                                                'label' => ''],
                               'password' => ['value' => '', 
                                              'type' => 'password',
                                              'id' => 'password', 
                                              'placeholder' => '登陆密码', 
                                              'label' => ''],
                                'selcet' => ['value'=>'',
                                              'type'=>'select'
                                            ],
                                ['type' => 'submit', 
                                'value' => '登陆', 
                                'class' => 'pure-button pure-button-primary'],
                                //标签name属性 => ['type'=>input标签类型,'label'=> 使用label标签包含input ......]
                             ]
                ]
    ```
    数组一维Key为form标签属性。其中`input`key为子输入标签数组，该数组key为标签name,数组为该标签相关数据

* `stylesheet($parentTag, $src)` 样式文件标签,`$src`样式位置
* `script($parentTag, $attr = [])` 脚本标签
* `style($parentTag, $code = '')` 定义样式，`$code`样式内容
* `select($parentTag, $attr)`   select标签，`$attr`属性数据类似如下：
    ```php
        ['name'=>'XXX',
        'option'=>['option1'=>['value'=>'1'],
                   'option2'=>['value=>2,selected=>true],
                   'option3'=>['value=>3]
                  ]
        ]
    ```

其他常规标签举例如下：
`p`标签：`Tag::p($parentTag,$attr)`, `$parentTag`为父标签，`$attr`标签属性数组

以上方法返回`Toknot\Share\View\TagBulid`实例

####`Toknot\Share\View\TagBulid`
方法如下：
`static addSingleTag($tagName)` 添加单边标签，例如`<br />`
`removeClass($class)` 移出标签的一个CSS样式
`removeStyle($key)` 移出标签的style样式定义
`pushText($text)` 向标签添加文本
`push($tag)` 向标签添加子标签
`delTag($tag)`删除子标签
`addAttr($attr, $value)` 给标签添加属性



