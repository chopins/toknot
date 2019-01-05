<?php

use Toknot\Boot\Kernel;

function tk_dump($value) {
    TK::dump($value);
}

function main() {
    include __DIR__ . '/Boot/Kernel.php';
    return Kernel::instance()->boot();
}

return main();
