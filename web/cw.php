<?php
$out = "";
exec("php ../app/console cache:warmup --env=prod", $out);
var_dump($out);
?>