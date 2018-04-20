;
; Toknot (http://toknot.com)
;
; @copyright  Copyright (c) 2011 - 2017 Toknot.com
; @license    http://toknot.com/LICENSE.txt New BSD License
; @link       https://github.com/chopins/toknot
;

[test-rooter]
prefix.path = '/p'
prefix.controller = 
path = /foo
controller = MyController::test
method = GET
require.id = [0-9]{9-12}
require.subdomain = www
options = 
schemes = 
host = 