<html>
<?php
/*
  Make a waveform view for the Sidekick that uses no JavaScript

  SYNTAX:  makeSimpleWaveview.php?EVID=<evid>

  Example: makeSimpleWaveview.php?EVID=1234567

DDG 6/7/05

*/

// Script has hard-coded host network dependencies

// required libraries for dbase access
include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";
include_once "phpmods/config.php";

// PASSED ARGS
$evid   = $_GET["EVID"];
$dbhost = getDefDbhost();
$dbname = getDefDbname();
// $dbname = $dbhost."db";

$fileroot = "../eventfiles/gifs";

// Environmental & server values
$remoteUser = $_SERVER["REMOTE_USER"];  
$httpHost   = $_SERVER["SERVER_NAME"];
$remoteAddr = $_SERVER["REMOTE_ADDR"];
$browserInfo = strtolower($_SERVER['HTTP_USER_AGENT']);

echo "<head>
<link rel=\"stylesheet\" title=\"waveframestyle\" type=\"text/css\" href=\"waveframe.css\">
<title>$networkName Event $evid </title>
</head>\n
";

// predefine the action links
$backStr = "simpleCatalog.php#$evid";    // jump back to this evid

$time = time(); // add time version to gif url to force a server request instead of using cached img ?
$giffile = "$fileroot/${evid}.gif";
$imgStr = "<img src=\"${giffile}?v=${time}\" ALT=\"No Snapshot for event $evid\">";
$imgStr2 = "<A HREF=\"${giffile}?v=${time}\">GIF</A>";

//$delStr = "deleteEvent.php?EVID=$evid&HOST=$dbhost"; 
//$appStr = "acceptEvent.php?EVID=$evid&HOST=$dbhost"; 
//$canStr = "cancelEvent.php?EVID=$evid&HOST=$dbhost"; 

// confirmAction.php use in place of popup confirmation
// because SideKick can't do JavaScript popups
$delStr = "confirmAction.php?EVID=$evid&ACTION=delete"; 
$appStr = "confirmAction.php?EVID=$evid&ACTION=accept"; 
$canStr = "confirmAction.php?EVID=$evid&ACTION=cancel"; 

//$mailStr = "makeEmailPanel.pl?$evid+$dbhost";
$mailStr = "makeEmailPanel2.php?EVID=$evid";
$typeStr = "makeSetTypePage.php?EVID=$evid";
$gtypeStr = "makeSetGTypePage.php?EVID=$evid";
$logStr  = "logAsHtml.php#bottom";

$netC = strtolower($networkCode);
//$reqStr = "http://earthquake.usgs.gov/recenteqsus/Quakes/${netC}${evid}.html";
//$reqStr = "http://earthquake.usgs.gov/earthquakes/eventpage/${netC}$evid#technical";
$reqStr = "${recEqRootURL}/eventpage/${netC}$evid#summary";

?>

<?php echo $evid ?> &nbsp&nbsp&nbsp&nbsp 

<A HREF="#bottom">Jump to bottom</A>&nbsp<?php echo $imgStr2 ?><br>

<?php 

// Put the image at the top
echo "<a name=\"top\"</a>";
echo "$imgStr";
date_default_timezone_set('UTC');
if (@file_exists($giffile)) { 
  echo "<br>Snapshot made: ".date("F d, Y H:i:s T",filemtime("$giffile"));
}
?>
<br>

<TABLE cellspacing="12"><TR border="1">
<TD><A name="bottom"></A><A HREF="<?php echo $backStr ?>"> BACK </A>
</TD>
<TD bgcolor=00ff00 ><A HREF="<?php echo $appStr ?>">APPROVE</A>
</TD>
<TD bgcolor=yellow><A HREF="<?php echo $canStr ?>">CANCEL</A>
</TD>
<TD bgcolor=red ><A HREF="<?php echo $delStr ?>">DELETE</A>
</TD>
</TR>

<TR>
<TD><A HREF="<?php echo "$typeStr" ?>">Set Event Type</A></TD>
<TD><A HREF="<?php echo "$gtypeStr" ?>">Set Event GType</A></TD>
<TD><A HREF="<?php echo "$fileroot/$evid.email" ?>">See EMAIL</A></TD>
<TD><A HREF="<?php echo $mailStr ?>">Edit EMAIL</A></TD>
<TD><A HREF="<?php echo $logStr ?>">See Log</A></TD>
</TR>
</TABLE>
<hr>
<A HREF="<?php echo $reqStr ?>"><b>Recent EQs</b></A><br>
<?php

// 

// Only create these links in if the target URL exist

$synopsGIF = "${fileroot}/${evid}_synops.gif";
$synopsURL = "makeSynopsisFrame.php?EVID=${evid}";
if (@file_exists($synopsGIF)) { 
 echo "<A HREF=\"$synopsURL\" >Synopsis View</A><BR>";
} 

// scaled gif view added 2013/02/28 -aww
$scaledGIF = "${fileroot}/${evid}_scaled.gif";
$scaledURL = "makeScaledFrame.php?EVID=${evid}";
if (@file_exists($scaledGIF)) { 
 echo "<A HREF=\"$scaledURL\" >Scaled View</A><BR>";
} 

// Only put links in if they exist
// For example: $SmapStr = "http://earthquake.usgs.gov/shakemap/sc/shake/$evid/intensity.html";
foreach ($shakeMapURLs as $SmapStr) {
  if (@file_exists($SmapStr)) { 
      echo " <A HREF=\"$SmapStr\">ShakeMap</A><br> ";
  }
}
 
$dyfiStr = "${dyfiURL}$evid/ciim_display.html";
if (@file_exists($dyfiStr)) { 
 echo "<A HREF=\"$dyfiStr\">DYFI</A> <br>";
}
 echo "<A HREF=\"dumpamps.php?EVID=$evid\">Preferred ML Magnitude Amplitudes for $evid</A>";
 echo "<A HREF=\"dumpphases.php?EVID=$evid\">Phases for $evid</A>";
?>

<br><a href="#top" class="left">Top</a><a href="#top" class="right">Top</a>

</html>
