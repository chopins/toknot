<?php
$s = '<img onerror="console.log(1);" src="x" img_id="166076"><br>&nbsp;dgfgfregrewg<br>&lt;img  onerror=&gt;&lt;<br>';
echo preg_replace_callback('/<img([^\>]*)>/im', function($m) {
    return preg_replace('/on([^=^\s]*)=/i', '_on$1=', $m[1]);
}, $s);
