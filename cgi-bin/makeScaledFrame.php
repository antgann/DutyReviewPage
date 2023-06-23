<html>
<?php
/*
  Make a waveform view for the Sidekick that uses no JavaScript
  SYNTAX:  makeScaledFrame.php?EVID=<evid>
  Example: makeScaledFrame.php?EVID=1234567

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
  <title>$networkName Scaled Event $evid </title>
</head>\n";


// predefine the action links
$backStr = "simpleCatalog.php#$evid";    // jump back to this evid

$mailStr = "makeEmailPanel.php?EVID=$evid";

$time = time(); // add time version to gif url to force a server request instead of using cached img ?
$giffile = "$fileroot/${evid}_scaled.gif";
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

// Only create these links in if the target URL exist
$synopsGIF = "${fileroot}/${evid}_synops.gif";
$synopsURL = "makeSynopsisFrame.php?EVID=$evid";
if (@file_exists($synopsGIF)) { 
 echo "<A HREF=\"$synopsURL\" >Synopsis View of $evid</A>&nbsp&nbsp(fixed list of selected stations)<BR>\n";
} 

format_productLinks($evid);

?>

<br><a href="#top" class="left">Top</a><a href="#top" class="right">Top</a>

</html>
