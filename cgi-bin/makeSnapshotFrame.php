<html>
<?php
/*
  Make a waveform view for the Sidekick that uses no JavaScript

  SYNTAX:  makeSnapshotFrame.php?EVID=<evid>

  Example: makeSnapshotFrame.php?EVID=1234567

*/

// required libraries for dbase access
include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";
include_once "phpmods/format_events.php";
include_once "phpmods/db_utils.php";
include_once "phpmods/config.php";

date_default_timezone_set('UTC');

// PASSED ARGS
$evid = $_GET["EVID"];
//$evid = $_REQUEST["EVID"];

function currentPageScript() {
   $start = strrpos($_SERVER["SCRIPT_NAME"],"/");
   if ( $start === false ) $start = 0;
   else $start++;
   return substr($_SERVER["SCRIPT_NAME"], $start);
}

function getAnchorForScript( $anchorname ) {
   global $evid;
   return '"' . currentPageScript() . "?EVID=". $evid. "&mytime=". date('U') . "#" . $anchorname . '"' ;
}

$dbhost = getDefDbhost();
$dbname = getDefDbname();
//$dbname = $dbhost."db";
//$dbname = $db_name;

$fileroot = "../eventfiles/gifs";

echo "<head>
  <link rel=\"stylesheet\" title=\"waveframestyle\" type=\"text/css\" href=\"waveframe.css\">
  <meta http-equiv=\"Cache-Control\" content=\"no-cache, no-store, must-revalidate\" />
  <meta http-equiv=\"Pragma\" content=\"no-cache\" />
  <meta http-equiv=\"Expires\" content=\"0\" />
  <title>$networkName Event $evid </title>
</head>\n";

// predefine the action links
$backStr = "simpleCatalog.php#$evid";    // jump back to this evid

$mailStr = "makeEmailPanel.php?EVID=$evid";
$time = time(); // add time version to gif url to force a server request instead of using cached img ?
$giffile = "$fileroot/${evid}.gif";
$imgStr = "<img src=\"${giffile}?v=${time}\" ALT=\"No Snapshot for Event $evid\">";

echo "<body>";
echo "<a name=\"top\"</a>";
echo "<div class=\"left\">";
echo "<a href=\"#bottom\">Bottom</a> or ";
echo "<a href=" . getAnchorForScript('top') . ">Refresh</a>";
echo "</div>";
echo "<div class=\"right\">";
echo "<a href=" . getAnchorForScript('top') . ">Refresh</a> or ";
echo "<a href=\"#bottom\">Bottom</a>";
echo "</div>";

// Put the image at the top
echo "$imgStr";

if (@file_exists($giffile)) { 
  echo "<br>Snapshot made: ".date("F d, Y H:i:s T",@filemtime("$giffile"))."\n";
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

<?php

// links to outside files, only if they exist

// Only create these links in if the target URL exist
$synopsGIF = "${fileroot}/${evid}_synops.gif";
$synopsURL = "makeSynopsisFrame.php?EVID=$evid";
if (@file_exists($synopsGIF)) { 
 echo "<A HREF=\"$synopsURL\" >Synopsis View of $evid</A>&nbsp&nbsp(fixed list of selected stations)<BR>\n";
} 

$scaledGIF = "${fileroot}/${evid}_scaled.gif";
$scaledURL = "makeScaledFrame.php?EVID=$evid";
if (@file_exists($scaledGIF)) { 
 echo "<A HREF=\"$scaledURL\" >Scaled View of $evid</A>&nbsp&nbsp(stations with picks rescaled to show background noise)<BR>\n";
} 
$odate = new DateTime(getODate($evid));  
#$archivedGIFs = "${GIFarchive}?dir=" . date_format($odate,'Y') . "-" . date_format($odate,'m') . "&eventid=${evid}";   
$archivedGIFs = "${GIFarchive}?eventid=${evid}";   

echo "<A HREF=\"$archivedGIFs\" >Archived GIF files for event $evid</A>&nbsp&nbsp<BR />\n";

echo "<a name=\"product-links\"</a>";
echo "<a href=\"javascript:toggleHide(document.getElementById('productLinks'));\">Toggle products</a>&nbsp;&nbsp";
//echo "<a href=" . getAnchorForScript('product-links') . ">Refresh</a>";
echo "<div id=\"productLinks\" class=\"hidden\">\n";
format_productLinks($evid);
echo "<A HREF=\"dumpamps.php?EVID=$evid\" Target=\"_new\">List preferred ML magnitude amps for $evid</A><br>\n";
echo "<A HREF=\"dumpsmamps.php?EVID=$evid\" Target=\"_new\">List strong ground motion amps for $evid (if any from ampgen or imported)</A><br>\n";
#echo "<A HREF=\"dumpphases.php?EVID=$evid\" Target=\"_new\">List phases for $evid</A><br>\n";
echo "<A HREF=\"phaseresids.php?EVID=$evid\" Target=\"_new\">List phases for $evid</A><br>\n";
echo "<a href=\"dumphypo.php?EVID=$evid\" Target=\"_new\">Run hypoinverse location using database phase picks</a><br>\n";

echo "</div>\n";

echo "<hr>";
echo "<a name=\"event-hist\"</a>\n";
echo "<div class=\"left\">";
echo "<a href=" . getAnchorForScript('event-hist') .  "<strong>Event History:</strong></a>\n";
//echo "<a href=\"#top\">Waveforms</a>&nbsp;<a href=\"#alarm-hist\">Alarm History</a>&nbsp;<a href=\"#bottom\">Bottom</a>";
//echo "</div>";
//echo "<div class=\"right\">";
//echo "<a href=\"#bottom\">Bottom</a>&nbsp;<a href=\"#alarm-hist\">Alarm History</a>&nbsp;<a href=\"#top\">Waveforms</a>";
echo "</div>";
echo "<br>";
echo "<pre>";

 // dump the event history
format_event_history($evid); 

?>	

</pre>

<a href="javascript:toggleHide(document.getElementById('eHistLegend'));">Toggle legend</a>
<table border="1" id="eHistLegend" class="hidden">
<tr>
<th colspan="2">LEGEND</th>
</tr>
<tr>
 <th>&gt;</th>
 <td>before evid, data of current event preferred origin (prefor)</td>
</tr>
<tr>
 <th>=</th> 
 <td>before evid, data of current event preferred magnitude (prefmag)</td>
</tr>
<tr>
 <th>evid</th>
 <td>the event ID (Event.evid)</td>
</tr>
<tr>
 <th>mag</th>
 <td>the magnitude and magtype of the event (NetMag.magnitude and NetMag.magtype)</td>
</tr>
<tr>
 <th>pri</th>
 <td>the magnitude priority (magpref.getMagPriority) </td>
</tr>
 <th>#st</th>
 <td>number of stations contributing to the magnitude (NetMag.nsta)</td>
</tr>
<tr>
 <th><b>magalgo</b></th>
 <td>the magnitude algorithm (NetMag.magalgo)</td>
</tr>
<tr>
 <th><b>src</b></th>
 <td>the source system (Origin.subsource)</td>
</tr>
<tr>
 <th><b>origin-datetime</b></th>
 <td>the date/time of the earthquake (Origin.datetime)</td>
</tr>
<tr>
 <th><b>lat</b></th>
 <td>the latitude of the earthquake (Origin.lat)</td>
</tr>
<tr>
 <th><b>lon</b></th>
 <td>the longitude of the earthquake (Origin.lon)</td>
</tr>
<tr>
 <th><b>z</b></th>
 <td>the depth of the earthquake (Origin.depth)</td>
</tr>
<tr>
 <th><b>#ph</b></th>
 <td>number of phases contributing to origin (Origin.ndef)</td>
</tr>
<tr>
 <th><b>rms</b></th>
 <td>origin uncertainty (root mean square misfit) (Origin.wrms)</td>
</tr>
<tr>
 <th><b>gap</b></th>
 <td>the azimuthal gap for origin (Origin.gap) </td>
</tr>
<tr>
 <th><b>et</b></th>
 <td>event type e.g. eq=earthquake, qb=quarry</td>
</tr>
<tr>
 <th><b>gt</b></th>
 <td>origin gtype e.g. l=local, r=regional, t=teleseism</td>
</tr>
<tr>
 <th><b>r</b></th>
 <td>status of processing (rflag) A=auto, H=reviewed, C=cancelled, I=interim, F=finalized</td>
</tr>
<tr>
 <th><b>lddate of magnitude</b></th>
 <td>The load/date of the earthquake's magnitude (Netmag.lddate)</td>
</tr>
<tr>
 <th><b>orid/magid</b></th>
 <td>the origin ID and mag ID (Origin.orid, NetMag.magid)</td>
</tr>
</table>

<script type="text/javascript">
<!--
   function toggleHide(which) {
     if (which.style.display == 'none' || which.style.display == '' ) {
         which.style.display = 'block';
         scrollBy(0,100);
     }
     else {
       which.style.display = 'none';
       scrollBy(0,-100);
     }
   }

</script>

<hr>
<a name="alarm-hist"</a>
<div class="left">
<?php
echo "<a href=" . getAnchorForScript('alarm-hist') . "<strong>Alarm History</strong></a>";
?>
<!--
<a href="#top">Waveforms</a>&nbsp;<a href="#event-hist">Event History</a>&nbsp;<a href="#bottom">Bottom</a>
</div>
<div class="right">
<a href="#bottom">Bottom</a>&nbsp;<a href="#event-hist">Event History</a>&nbsp;<a href="#top">Waveforms</a>
-->
</div>
<br>
<pre>
<?php 
 // Dump the alarm histories for both the RT and DC systems
 format_rt_alarms($evid); 
 echo "<br>\n";
 format_dc_alarms($evid); 
 echo "<br>\n";
 formatSecondaryAlarms($evid); 
?>
</pre>

<a href="javascript:toggleHide(document.getElementById('aHistLegend'));" id="aLegend">Toggle legend</a>
<div id="aHistLegend" class="hidden">
<ul>
If no alarm actions are listed above:
<li>Alarm criteria rejected this event (the usual case).</li>
<li>Alarming failed, check your alarming system.</li>
</ul>
<table border="1">
<tr>
<th colspan="2">LEGEND</th>
</tr>
<tr>
 <th>Action</th>
 <td>name of alarm action as configured in alarmdec_actions.cfg</td>
</tr>
<tr>
 <th>State</th>
 <td>COMPLETE, OVERRULED (i.e. db not primary system) or ERROR (check logs)</td>
</tr>
<tr>
 <th>Cnt</th>
 <td>mod count, number of times this alarm action was executed</td>
</tr>
<tr>
 <th>Time</th> 
 <td>time of alarm execution logged in database</td>
</tr>
</table>
</div>
<a name="bottom"</a>

<hr>
<div class="left">
<a href="javascript:scrollTo(0,0);">Top</a> or
<?php
echo "<a href=" . getAnchorForScript('bottom') . ">Refresh</a>";
?>
</div>
<div class="right">
<?php
echo "<a href=" . getAnchorForScript('bottom') . ">Refresh</a> or ";
?>
<a href="javascript:scrollTo(0,0);">Top</a>
</div>

</body>
</html>
