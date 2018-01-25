# 自动创建路由配置方法
在命令行中执行: `php app/tool/index.php route -a your_app_path  -o your_route_file.ini` 将会自动扫描控制器下所有可用路由。
可用路由是指，方法注释中含有标签: `route`,`console`,`post`,`get`。构造函数的路由信息将表示路由到该类，而不是方法
# The route config document
* name of router is section name:`[console-doc-import-letter]`
* your path prefix:`prefix.path=doc`
* your contoller of prefix:`prefix.controller=`
* your option of prefix:`prefix.option = ''`
* `prefix.method=CLI`
* path of router:`path = importletter`
* request method:`method=CLI`
* route controller name:`controller=Console.ScanWord::importChar`
* `require.id = [0-9]{9-12}`
* `require.subdomain = www`
* invoke class before router:`before=TheAuth`
* invoke class after router:`after=TheView`