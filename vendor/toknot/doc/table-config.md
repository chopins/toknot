# Table config like below:
* the section config column list of table,key is table name:`[user.column]`
* key is column name and type:`uid.type = integer`
* column length:`uid.length = 11`
* column wheter unsigned:`uid.unsigned = true`
* column wheter is autoincrement:`uid.autoincrement = true`
* string type :`letter_code.type= string`
* the column wheter fixed:`letter_code.fixed = true`
* column length:`letter_code.length = 9`

* the section is option of table:`[user.option]`
* table comment:`comment = 用户表`
* table engine:`engine=`
* table collate charset:`collate=`

* the section is index of table:`[user.indexes]`
* feild name is primary of table:`primary = uid`
* table of unique index,key is index name:`login_name.type = unique`
* the index type,key is index name and field name:`login_name.username = default`
* table of index:`org.type = index`
* key is index name and field name:`org.process_org = default`