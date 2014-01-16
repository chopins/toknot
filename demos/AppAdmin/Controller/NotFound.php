<?php

namespace AppAdmin\Controller;
class NotFound implements \Toknot\Control\ControllerInterface\GET {
    public function __construct(\Toknot\Control\FMAI $FMAI) {
        ;
    }
    public function GET() {
        echo 'error not found';
    }
}

?>
