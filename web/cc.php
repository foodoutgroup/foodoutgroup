<?php
$out = "";
    exec("php ../app/console cache:clear --env=prod", $out);
var_dump($out);
?>