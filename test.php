<?php

//this is test file
include_once dirname(__FILE__).'/Toknot/Control/Application.php';
$appPath = dirname(__FILE__).'/test';

$app = new \Toknot\Control\Application();

//$app->setUserRouter('\Test\TestRouter');

$app->addAppPath($appPath);
$app->run('\Test');

//$cgi = $app->runCGIServer('testRouter');
//$cgi->registerApplicationRouter('testRouter');
//$cgi->setWorkOnCurrentUser();
//$cgi->startServer();
/**
 * 
 * @param mixed $expression
 */
function debugPrint($expression) {
    $arr = debug_backtrace(TRUE);
    $filename = basename($arr[0]['file']);
    $expression = print_r($expression, true);
    $pid = posix_getpid();
    print("$pid:$filename:{$arr[0]['line']}:{$expression}\n");
}
namespace Test;
use Toknot\Control\RouterInterface;

//http://sitename/test
class Test {
    public function GET() {
        
    }
    public function POST() {
        
    }
}

class TestRouter implements RouterInterface {
    public function routerRule() {
        
    }
    public function invoke() {
        
    }
    public function routerSpace($appspace) {
        
    }
    public function runtimeArgs() {
        
    }
}

namespace Test\Index;
//GET query http://sitename/?r=index.text
//PATH    http://sitename/index/text
class Text {
    public function GET() {
        
    }
}

