<?php
namespace ToknotUnit;

set_include_path('../../Toknot/Boot:../../Toknot/Db');
require_once 'Autoloader.php';

\Toknot\Boot\Autoloader::importToknotModule('Exception','BaseException');
\Toknot\Boot\Autoloader::importToknotModule('Boot', 'Object');
\Toknot\Boot\Autoloader::importToknotModule('Boot\Exception');

require_once __DIR__ . '/TestCase.php';