<?php
storeInitDb();
$dataDir = ROOT.'ext/store/data/';
if(!is_dir($dataDir)) mkdir($dataDir, 0777, true);
?>
