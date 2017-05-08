# 实用工具库 

### Xlsx文件操作库 Tokont\Share\SimpleXlsx.php

本库能简单创建与读取xlsx文件，主要用于xlsx相关数据的导入与导出，由于类似读写文件，所有占用较小内存，可以导入导出任何大小文件。
本库必须在PHP安装有ZipArchive扩展的环境下运行。

用法：
```php
//创建SimpleXlsx实例
$xlsx = new SimpleXlsx();

//准备创建一个xlsx文件
$xlsx->createXlsx('/your_file_save_path/test.xlsx');

//为要创建的xlsx文件新建一个名为test的表
$index = $xlsx->newSheet('test');

//向表中添加数据
for ($i = 0; $i < 1000; $i++) {
    $row = range(1, 100);

    //向$index表中添加一行数据
    $xlsx->addRow($row, $index);
}

//保存全部数据
$xlsx->save();

//加载一个xlsx文件
$xls->loadXlsx('your_file_save_path/test.xlsx');

//获取表列表数组，该数组索引为表ID，值为表名
$sheets = $xls->getSheetList();

//读取第一个表，传入索引ID或表名字，$pos将
$id = $xls->readSheet(1, $pos);

//循环读取一行，并将指针移到下一行
while($r = $xls->row($id)) {
     
}
```

### 命令行相关 Toknot\Share\CommandLine
本库主要提高了命令行相关的操作

用法:
```php

$cmd = new CommandLine;

//显示进度
$cmd->progress($percent, $message = '', $speed = '', $color = null)

//显示提示信息，然后读取输入
$cmd->readline($prompt, $color = null)

//输出消息
$cmd->message($msg, $color = null, $newLine = true)

//循环交互式，$callable 将获得输入
$cmd->interactive($callable, $prompt = null)

//循环检测一个输入，直到得到正确值
$cmd->freadline($msg, $mismatch = '', function($input, $cmdObj) {
    //检测值的代码

    //未得到正确值，继续要求输入，调用 CommandLine::cont()
    $cmdObj->cont();
}, $color = '')

//显示错误信息，并退出
$cmd->error($msg, $status = 255)

```

### 进程管理相关 Toknot\Share\Process\Process
进程操作相关库

用法：
```php

$p = new Process;

//设置进程显示名字
$p->setProcessTitle($title)

//手动加载PHP进程管理相关扩展
$p->loadProcessExtension()

//生成一对管道连接
list($sock1,$sock2) = $p->pipe()

//启动一个任务队列处理服务
$p->taskQueue('tcp://127.0.0.1:9111');

//其他进程向任务处理队列添加处理任务
$p1 = new Process;
$p->addTask('tcp://127.0.0.1:9111', $functionName);

//启动无派生关系的进程锁服务
$p->anyLock($port = 9088)

//其他本主机程序
$p2 = new Process;
//获取锁
$p2->aLock($port);
//释放锁
$p2->aUnlock();


//启动具有派生关系的进程锁服务
$pid = $p->bloodLock(3);
if($pid > 0) {
    //主服务退出后的父进程代码
} else {
    //子进程代码

    //获取锁
    $this->lock();
    //释放锁
    $this->unlock();
}

//启动多个进程，直到所有子进程全部退出
$status = $p->multiProcess(10);

//$status大于1为父进程，等于0为子进程
if($status) {
    //主服务退出后的父进程代码
} else {
    //子进程代码
}

//启动多个进程，并永远保持固定数量子进程运行
$status = $p->processPool(10);
//$status大于1为父进程，等于0为子进程
if($status) {
    //主服务退出后的父进程代码
} else {
    //子进程代码
}


//以守护进程方式运行
$p->demon()

//向指定进程发送信号
$p->kill($pid, $sig)

//启动看守式进程服务，本方法启动一个父进程与一个子进程，子进程退出，父进程将会重新启动该子进程，直到父进程调用的callback函数返回退出标志
$pid = $p->guardFork(function() {
    //父进程会在每次子进程退出后调用本函数
    sleep(10);
    return 'exit;
},'exit');

//$pid大于1为父进程后续代码，等于0为子进程
if($pid >0) {
    //退出看守后的父进程代码
} else {
    //子进程
}

```

###人机验证题目相关 Toknot\Share\Security\OpenSSL
本库会生成计算与顺序推导类题目用于人机验证
用法：
```php
//创建一个实例，并设置相关语言
$r = RobotSpot('zh')

//获取支持语言列表
$langList = $r->supportLang();

//计算题目
$askMessage = calculation(&$answer)

//顺序推导题目
$askMessage= findOrder(&$answer)
```

###阿拉伯数字转中文数字 Toknot\Share\ChineseNumber
用法：
```php

$zhNum = new ChineseNumber(12331);
echo $zhNum; //一万二千三百三十一
echo $zhNum->getZhnum(); //一万二千三百三十一
```

###文件操作相关 Toknot\Share\File
用法：
```php
$f = new File($filename, $mode = 'r', $useInclude = false, $context = null)

//从文件中获取字符$start和字符$end之间的内容
$f->findRange($start, $end)

//将文件指针移动到指定字符串后面一位
$f->seekPos($start)

//获取当前指针到字符串$end之间的内容
$f->findNextRange($end)

//获取指定文件指定偏移量到指定长度之间的内容
$f->substr($start, $len)

//搜索指定字符串在文件中首次出现位置
$f->strpos($search)

//获取读取生成器（php 5.5 以上支持）
$f->getReader()
$f->getWriter()

//文件写
$f->write($str)
//文件读
$f->read($len)
```