<?php
session_start();
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Huntsville_data.xls"');
header('Cache-Control: max-age=0');

echo $_SESSION['table'];
?>
