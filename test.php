<?php
//the server response time
list($mcsec, $sec) = explode(' ', microtime());
$msec = $sec . substr($mcsec, 2,3);
?>
<script>
functiin gTime() {
    return (new Date()).getTime();
}
document.body.onload = function() {
    var loadtime = gTime();
}
window.onmousedown = function() {
    downtime = gTime();
}
window.onmouseup = function() {
    downtime = gTime();
}
window.onkeydown = function() {
    downtime = gTime();
}
window.onkeyup = function() {
    downtime = gTime();
}
//the request time
TK.Ajax.get(function() {
    restime = gTime();
});
</script>