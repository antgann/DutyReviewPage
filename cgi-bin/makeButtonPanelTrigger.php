<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html>
  <head>
   <title>Buttons</title>
   <link rel="stylesheet" title="buttonpanel" type="text/css" href="buttonpanel.css">
  </head>


<?php
// ==================================================================

include_once "phpmods/db_conn.php";

// PASSED ARGS
$evid   = $_GET["EVID"];
$dbase = getDefDbname();

//$fileroot = "../eventfiles";

// get current time in local and GMT
date_default_timezone_set('UTC');
$datestr = date("M d, Y");
$timeloc = date("G:i e");
// $timez = date("G:i");

function url_exists($url) {
    $status = true;
    if ( strpos($url, "http:") === false ) {
      $status = file_exists($url);
    } else {
      $headers = get_headers($url);
      $str = substr($headers[0],9,3);
      if ( "$str" == "" || "$str" === "404" ) {
        $status = false;
      }
    }
    return $status;
}

$webicorderString = "";
if ( url_exists("$webicorderURL") ) {
  $webicorderString = "<a style=\"background-color: gray\" HREF=\"$webicorderURL\" TARGET=\"_top\">Helicorders</A>";
}

// print a bunch of HTML and JavaScript almost verbatim,
// Doing it in a 'print' allows translation of php variables
print <<<end_text

<!-- set bacground color to pale yellow -->
<body BGCOLOR=#FFFFCC>

<Center>
<h2>TRIGGER</h2>
<b>Last catalog refresh:<br>
$datestr&nbsp;$timeloc<br>
ID = $evid<br>

DB = $db_name</b><br>

<div id="csscontainer">

<div class="button4">

<a style="background-color: green" 
   href="#" onclick="acceptIt(); return false;" name="accBut">Accept</a>
<IMG SRC="../../images/clearpixel.gif" WIDTH="0" HEIGHT="0" HSPACE="1" VSPACE="3"  Border=0>
<a style="background-color: red" 
   href="#" onclick="deleteIt(); return false;" name="delBut">Delete</a>
</div>

<div class="button4">
<table><tr>
<td>
<a style="width: 3.6em; text-align: center; background-color: tan; color: black"
   href="#" onclick="loadEarlier(); return false;" name="downBut" >
   <img src="../../images/trans_arrow_down.gif" alt="Down" 
        title="Jump to earlier event (down list)"  border=0 >
</a>
</td>
<td>
<a style="width: 3.6em; text-align: center; font-weight: bold; background-color: tan; color: black"
   href="#" onclick="loadLater(); return false;" name="upBut">
  <img src="../../images/trans_arrow_up.gif" alt="Up" 
       title="Jump to later event (up list)"  border=0 >
</a>
</td>
</tr></table>
</div>

<hr>

<div class="button4">
<a href="#" onClick="showSetEventType(); return false;">Set EType</a>
</div>

<!-- SETTING GTYPE DOES NOT MAKE SENSE FOR SUBNET TRIGGERS !
<div class="button4">
<a href="#" onClick="showSetEventGType(); return false;">Set GType</a>
</div>
-->

<div class="button4">
<a href="#" onclick="remakeGif(); return false;" name="gifBut">Remake GIF</a>
</div>

<!-- NOT READY YET
 <a href="#" onClick="showEditLoc(); return false;" name="locBut">Edit Loc/Mag</a>
-->

</div>

<div class="button4">

  <a style="background-color: gray" href="#" onclick="showLog(); return false;">Show Log</a>

  <!-- Obsolete checklist replace with cicese equivalent link
 <a style="background-color: gray" href="#" onclick="showChecklist();return false;">Checklist</A> -->
  <!--   HREF="../../duty/checklist.html" TARGET="_new">Checklist</A> -->

  <a style="background-color: gray" HREF="./reviewOptions.html" TARGET="parent">Options</A>

</div>

<div class="button4">

  <a style="background-color: gray" HREF="../index.php" TARGET="_top">Event Page</A>

  <a style="background-color: gray" HREF="../../index.html" TARGET="_top"> AQMS Home </A>

  ${webicorderString}

</div>

<!-- below is ending/closing ccs container div tag -->
</div>

</Center>

<SCRIPT TYPE="text/JavaScript"> 
<!-- Hide if browser can not cope ------------------------------------------------------
// Get necessary parameters from the 'catalog' frame 
var id = top.catalog.getCurrentId();
var dbase = top.catalog.getDbase();
var testWin = null;

// ---------------------
function acceptIt() {

    action = "doAction.php?EVID="+id+"&ACTION=accept_trigger";

    // sends results to status frame in button frame
    top.statusframe.location = action;

    loadEarlier();  // jump to next event
}
// ---------------------
function deleteIt() {

    action = "doAction.php?EVID="+id+"&ACTION=delete_trigger";

    // sends results to status frame in button frame
    top.statusframe.location = action;

    loadEarlier();  // jump to next event

}


function remakeGif() {

  if ( window.confirm ( "Post event " + id + " to MakeGifTrg? \\n" +
                        "NOTE: This will take minutes to process." ) )
  {

      action = "doAction.php?EVID=" + id + "&ACTION=MAKEGIFTRG&DATABASE=" + dbase;
      if (testWin == null) { testWin = window.open ("","logwindow") }
      testWin.location.href= action;

      loadEarlier();  // jump to next event
  }
}

// ---------------------
function showChecklist() {
    newWin = window.open ("","")
    newWin.location.href= "../../duty/checklist.html";
}

//---------------------
// Show the last 50 entries in the delete/accept log file in a new window
function showLog() {

   logwindow = window.open ("logAsHtml.php#bottom", "logwindow"); 
	//"height=350,width=900,toolbar=no,menubar=no,"+
	//"resizable=yes,scrollbars=yes")

}
// Used to do this with a dropdown list but SideKick wouldn't support it
// so opted for consistency between browser and SideKick versions
function showSetEventType() {

   logwindow = window.open ("makeSetTypePage.php?EVID="+id, "logwindow", 
	"height=200,width=200,toolbar=no,menubar=no,"+
	"resizable=yes,scrollbars=yes")

}

//---------------------
function showSetEventGType() {

   logwindow = window.open ("makeSetGTypePage.php?EVID="+id, "logwindow", 
	"height=200,width=200,toolbar=no,menubar=no,"+
	"resizable=yes,scrollbars=yes")

}

//---------------------
function showEditLoc() {

   logwindow = window.open ("locEditPanel.php?EVID="+id, "logwindow", 
	"height=700,width=400,toolbar=no,menubar=no,"+
	"resizable=yes,scrollbars=yes")

}

//---------------------
function loadLater () {
  var id   = top.catalog.decrementIndex();
  top.catalog.selectDefaultEvid(1);
}
//---------------------
function loadEarlier () {
  var id   = top.catalog.incrementIndex();
  top.catalog.selectDefaultEvid(1);
}
// -------------------
function loadCurrent() {
  top.catalog.selectDefaultEvid();
}

// End of hide comment -->
</SCRIPT>

</body>

end_text;

?>

</html>
