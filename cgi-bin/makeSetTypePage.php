<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html>
  <head>
   <title>Set Event Type</title>
    </head>

<?php
/* 
   Make a simple, HTML-only set type page.

   This was done for the sake of the SideKick

  SYNTAX:  makeSetTypePage.php?EVID=<evid>

  Example: makeSetTypePage.php?EVID=1234567

*/
$evid   = $_GET["EVID"];

echo "<head><title>Change Type $evid </title> </head>";
echo "Set Type of ID= $evid<p>";

$typeArray = array ('eq' => 'Earthquake', 'qb' => 'Quarry blast','ex' => 'Explosion', 'sh' => 'Shot', 'sn' => 'Sonic', 'th' => 'Thunder' ); 

// make the html list
while ($curr = each($typeArray)) {
 
  $short = $curr['key'];
  $long  = $curr['value'];
//  echo "<A HREF=\"setEventType.php?EVID=${evid}&TYPE=${short}\">$long</A><br>"; }
  echo "<A HREF=\"doAction.php?EVID=${evid}&ACTION=SET_TYPE&TYPE=${short}\">$long</A><br>"; }
?>


</HTML>

