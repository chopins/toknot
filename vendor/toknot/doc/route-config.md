
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