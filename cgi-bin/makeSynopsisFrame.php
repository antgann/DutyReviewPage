<html>
<?php
/*
  Make a waveform view for the Sidekick that uses no JavaScript

  SYNTAX:  makeSynopsisFrame.php?EVID=<evid>

  Example: makeSynopsisFrame.php?EVID=1234567

DDG 8/22/05 - based on makeSimpleWaveview.php

*/

// required libraries for dbase access
include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";
include_once "phpmods/format_events.php";
include_once "phpmods/db_utils.php";

// PASSED ARGS
$evid   = $_GET["EVID"];
$dbhost = getDefDbhost();
$dbname = getDefDbname();
// $dbname = $dbhost."db";

$fileroot = "../eventfiles/gifs";

echo "<head>
  <link rel=\"stylesheet\" title=\"waveframestyle\" type=\"text/css\" href=\"waveframe.css\">
  <meta http-equiv=\"Cache-Control\" content=\"no-cache, no-store, must-revalidate\" />
  <meta http-equiv=\"Pragma\" content=\"no-cache\" />
  <meta http-equiv=\"Expires\" content=\"0\" />
  <title>$networkName Synopsis Event $evid </title>
</head>\n";

// predefine the action links
$backStr = "simpleCatalog.php#$evid";    // jump back to this evid

$mailStr = "makeEmailPanel.php?EVID=$evid";

$time = time(); // add time version to gif url to force a server request instead of using cached img ?
$giffile = "$fileroot/${evid}_synops.gif";
$imgStr = "<img src=\"${giffile}?v=${time}\" ALT=\"No Snapshot for Event $evid\">";

// Put the image at the top
echo "<a name=\"top\"</a>";
echo "$imgStr";

date_default_timezone_set('UTC');

if (@file_exists($giffile)) { 
  echo "<br>Snapshot made: ".date("F d, Y H:i:s T",filemtime("$giffile"));
}

?>

<TABLE cellspacing="12">

<TR>
<TD>E-Mail Message>> </TD>
<TD><A HREF="<?php echo "cubeEmail.php?EVID=$evid" ?>" Target="_new">>View Text</A>
</TD>
<TD><A HREF="<?php echo $mailStr ?>" Target="_new">>Edit & Send</A>
</TD>

</TR>
</TABLE>

<HR>

<?php

$normalURL = "makeSnapshotFrame.php?EVID=${evid}";

// Create links to outside products, only if they exist
echo "<A HREF=\"$normalURL\" ><b>Event Trigger View</b> of $evid</A><BR>";

$scaledGIF = "${fileroot}/${evid}_scaled.gif";
$scaledURL = "makeScaledFrame.php?EVID=${evid}";
if (@file_exists($scaledGIF)) { 
 echo "<A HREF=\"$scaledURL\" >Scaled View of $evid</A>&nbsp&nbsp(stations with picks rescaled to show background noise)<BR>\n";
} 

format_productLinks($evid);

?>

<br><a href="#top" class="left">Top</a><a href="#top" class="right">Top</a>

</html>
