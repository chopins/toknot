<?php
namespace ToknotUnit;

define('TK_PATH', dirname(dirname(__DIR__)) . '/Toknot');

require_once TK_PATH.'/Boot/Autoloader.php';

\Toknot\Boot\Autoloader::importToknotModule('Exception','BaseException');
\Toknot\Boot\Autoloader::importToknotModule('Boot', 'Object');
\Toknot\Boot\Autoloader::importToknotModule('Boot\Exception');
\Toknot\Boot\Autoloader::importToknotClass('Config\ConfigLoader');
\Toknot\Config\ConfigLoader::singleton();
require_once __DIR__ . '/TestCase.php';