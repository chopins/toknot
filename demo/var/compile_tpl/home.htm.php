<?php 
$this->out_html.=<<<XTHTML

XTHTML;
$this->inc_tpl("header");
$this->out_html.=<<<XTHTML

<div class="b-center" id="b-center">
</div>
<script type="text/javascript">
X.ready(dop.init);
</script>

XTHTML;
$this->inc_tpl("footer");
$this->out_html.=<<<XTHTML


XTHTML;
