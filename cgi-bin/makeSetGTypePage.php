<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html>
  <head>
   <title>Set Origin GType</title>
    </head>

<?php
/* 
   Make a simple, HTML-only set type page.

   This was done for the sake of the SideKick

  SYNTAX:  makeSetGTypePage.php?EVID=<evid>

  Example: makeSetGTypePage.php?EVID=1234567

*/
$evid   = $_GET["EVID"];

echo "<head><title>Change GType $evid </title> </head>";
echo "Set Origin GType of ID= $evid<p>";

$typeArray = array ('l' => 'local', 'r' => 'regional', 't' => 'teleseism');

// make the html list
while ($curr = each($typeArray)) {
 
  $short = $curr['key'];
  $long  = $curr['value'];
//  echo "<A HREF=\"setEventGType.php?EVID=${evid}&GTYPE=${short}\">$long</A><br>"; }
  echo "<A HREF=\"doAction.php?EVID=${evid}&ACTION=SET_GTYPE&GTYPE=${short}\">$long</A><br>"; }
?>


</HTML>

