<?php 
include_once('sippwebchk.php');
$sippchk = new sippwebchk();
echo json_encode($sippchk->checknow("http://sipp.pn-bengkulu.go.id/"));
?>