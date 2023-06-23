<html>
<?php
/*
  Confirm DELETE/CANCEL of an event from the Duty Review Page

  SYNTAX:  confirmAction.php?EVID=<evid>&ACTION=<action>

  Example: acceptEvent.php?EVID=1234567&ACTION=delete

DDG 6/25/05

*/

// required libraries for dbase access

// PASSED ARGS
$evid   = $_GET["EVID"];
// $dbhost = $_GET["HOST"];
$action = $_GET["ACTION"];

$actionStr = "doAction.php?EVID=$evid&ACTION=$action";
$backStr = "makeSimpleWaveview.php?EVID=$evid";    // jump back to this evid

echo "Are you sure you want to <b>$action</b> event $evid?";
?>

<TABLE cellspacing="12">
<TR border="1">
<TD bgcolor=00ff00><A HREF="<?php echo $actionStr ?>"> YES </A>
</TD>
<TD bgcolor=ff0000 ><A HREF="<?php echo $backStr ?>">NO</A>
</TD>
</TR>

</html>
