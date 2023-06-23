<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html>
<!-- set bacground color to pale yellow -->

<?php
/*
  Make a waveform view for the Sidekick that uses no JavaScript

  SYNTAX:  makeSnapshotFrame.php?EVID=<evid>

  Example: makeSnapshotFrame.php?EVID=1234567

DDG 8/22/05 - based on makeSimpleWaveview.php 
DDG 2/23/06 - Trigger version

*/

include_once "phpmods/config.php";


// PASSED ARGS
$evid   = $_GET["EVID"];

$fileroot = "../eventfiles/gifs";

echo "<head>
  <link rel=\"stylesheet\" title=\"waveframestyle\" type=\"text/css\" href=\"waveframe.css\">
  <meta http-equiv=\"Cache-Control\" content=\"no-cache, no-store, must-revalidate\" />
  <meta http-equiv=\"Pragma\" content=\"no-cache\" />
  <meta http-equiv=\"Expires\" content=\"0\" />
  <title>$networkName Trigger $evid </title>
</head>\n";

echo "<body BGCOLOR=#FFFFCC>";

$time = time(); // add time version to gif url to force a server request instead of using cached img ?
$giffile = "$fileroot/${evid}.gif";
$imgStr = "<img src=\"${giffile}?v=${time}\" ALT=\"No Snapshot for Event $evid\">";

// Put the image at the top
echo "<a name=\"top\"</a>";
echo "$imgStr";

date_default_timezone_set('UTC');

if (@file_exists($giffile)) { 
  echo "<br>Snapshot made: ".date("F d, Y H:i:s T",filemtime("$giffile"));
}

?>

<br><a href="#top" class="left">Top</a><a href="#top" class="right">Top</a>

</body>
</html>
