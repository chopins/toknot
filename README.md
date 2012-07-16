#介绍
本项目是一个轻量级PHP WEB开发框架，专注于页面逻辑处理与展示，让项目代码更加简单  
框架支持ReST或普通HTML压缩输出  
##以下功能已经完成
  * 内置一个基于libevent的多进程WEB服务器，让项目代码常驻内存，减少PHP每次解析代码的消耗
  * 内置一个基于线性查询的K/V文本数据库
  * 一个类smarty模板引擎
  * 友好的面向对象异常处理类
  * URL构成与程序文件关联性下降
  * 程序业务逻辑模式限定
  * 数据组件或通用逻辑松散化，减少组件之间的关联性
  * session扩展, supperglobal变量在被PHP配置文件禁用状态下仍然能使用

##将要实现以下功能
  * 数据接口封装，主要封装数据处理程序访问的接口，例如中间件访问，数据模型调用等
  * 基于映射查询的K/V数据库
  * 完善计划任务功能，目前模型已经实现
  * 数据缓存到内存

##将要进行的功能改进
  * web server 进程通信机制修改, 目前在高并发下有问题，见下面的性能测试
  * url 可配置
  * 模板支持wiki标签
  * 整合x.js库中UI组件数据支持

##性能测试数据
测试url.txt 文件中的三个URL
    http://127.0.0.1:8080/  
    http://127.0.0.1:8080/x.js  
    http://127.0.0.1:8080/style.css  


`$_CFG->web->min_worker_num = 1;$_CFG->web->max_worker_num = 20;`

>   siege -c 500 -r 10 -f url.txt  
>    Transactions:               4844 hits  
>   Availability:              96.88 %  
>   Elapsed time:              38.50 secs  
>   Data transferred:           1.06 MB  
>   Response time:              0.75 secs  
>   Transaction rate:         125.82 trans/sec  
>    Throughput:             0.03 MB/sec  
>    Concurrency:               94.33  
>   Successful transactions:        1710  
>   Failed transactions:             156  
>    Longest transaction:           18.59  
>   Shortest transaction:           0.00  
  
  
`   $_CFG->web->min_worker_num = 15;$_CFG->web->max_worker_num = 20;` 
>   Transactions:               4787 hits  
>   Availability:              95.74 %  
>   Elapsed time:              39.11 secs  
>   Data transferred:           1.08 MB  
>   Response time:              0.56 secs   
>   Transaction rate:         122.40 trans/sec   
>   Throughput:             0.03 MB/sec   
>   Concurrency:               68.07  
>   Successful transactions:        1733  
>   Failed transactions:             213   
>   Longest transaction:           21.59   
>   Shortest transaction:           0.00  
