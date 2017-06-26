<?php
include 'sistema.php'; 
$web->logout();
$web->smarty->display('logout.html');
?>