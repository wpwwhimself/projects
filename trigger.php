$(function(){
<?php
if(isset($_GET['p'])){?>
openProject("<? echo strtoupper($_GET['p']); ?>");
<?php } ?>
});