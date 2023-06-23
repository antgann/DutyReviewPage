<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<HTML>
    <HEAD>
        <title>Depth Input</title>
    </HEAD>

<?php
session_start();
/*
  make the form for editing magnitude parameters

  SYNTAX: depthEditPanel.php?EVID=<evid>

  This script is also the FORM submit target - i.e. script processes itself.
  This is done so the form input can be checked and error messages
  printed and the user given a second chance to get it right before the
  script that actually writes to the dbase is called.

  All form variables have name f_xxxx in the $HTTP_POST_VARS array
  To check that a value was changed compare $xxx to $f_xxx.

*/

include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";
include_once "phpmods/formutils.php";
include_once "phpmods/config.php";
include_once "phpmods/event_actions.php";

extract($_REQUEST);

global $evid, $dbhost;

// from the dbase
global $d_lat, $d_lon, $d_depth, $d_TMTSdepth, $d_bogusflag;

// from the form input
global $f_depth;

global $errorMessage;

// FORM will process itself - 
// only way to repaint form with user entered values if there's an invalid field
$processor = $_SERVER['PHP_SELF'];
$_SESSION['proceedDepthEdit'] = false;

// "$submit" will only have a value when the SUBMIT button posts the form


if ( ! isset($depthSubmit) ) {    // 1st time called
    $_SESSION["confirmDepth"] = NULL;
    $evid   = $_GET["EVID"];
    // lookup current event info
    getDepthData($evid);   // get fresh mag data
    printForm("New depth edit for submittal", false);
} 
else {
    // validate form data
    // if invalid repaint form preserving good user changes

    if ( validateDepth($f_depth) ) {
        if ( !(isset($_SESSION['confirmDepth'])) && isset($depthSubmit) ) {
            $_SESSION["confirmDepth"] = "confirmed";
	    $depthInBounds = checkDepthBounds($f_depth, $editDepthMin, $editDepthMax);
            $confirmMsg = "You are attempting to change the depth to $f_depth, is this correct?  {$depthInBounds}";
            printForm("Confirm depth edit for submittal", true, $confirmMsg);

        }
        else {
            $noChange = depthUnchanged();
            if ($noChange) {
                printSuccess("No changes made in form, dbase not updated.");
            } 
            else {
                echo "<b>Creating new D$f_depth</b><br>evid&nbsp&nbsp: $evid<br>";
                $retv = updateDb();
                if ( $retv ) {
                    printSuccess("Update succeeded.<br> Press 
                      <span style=\"font-size:20;color:white;background-color:DeepPink\">Resend Alarms</span>
                      in button panel<br>to run alarm scripts (e.g. notify NEIC).");
                } 
                else {
                    $errorMsg = "<FONT color=RED>ERROR: Could not update dbase returned: $retv.</FONT>";
                    printForm($errorMsg, false);
                }
                $_SESSION["confirmDepth"] = NULL;
            }
        }
    } 
    else {
        $_SESSION["confirmDepth"] = NULL;
        $msg = "<FONT color=RED>$errorMessage </FONT>";
        printForm($msg, false);
    }
}
// -------------------------
// Check depth for proper format?
function validateDepth($_val) {

  global $errorMessage;
           
  if (is_numeric($_val)) {
    return true;
  }
  $errorMessage .= "<BR>Invalid depth value: $_val<BR>";
  return false;
}

// -------------------------
// Is depth valid?
function checkDepthBounds($_val, $_editDepthMin, $_editDepthMax) {

  global $errorMessage;
  $_message = " ";
      
  // DON'T ERROR IF BEYOND BOUNDS, JUST ALERT USER 
  //          $errorMessage .= "min val: $_editDepthMin<BR>max val: $_editDepthMax<BR>Invalid depth value: $_val<BR>";
  //          return false;     
  if (($_val < $_editDepthMin) || ($_val > $_editDepthMax)) {
    $_message = "YOU ARE ATTEMPTING TO CHANGE OUTSIDE THE NORMAL BOUNDS.  THE NEW DEPTH WILL BE {$_val}, PROCEED WITH CAUTION!";
  }
  return $_message;
}
// --------------------------------
function printSuccess($_message) {
$str = <<< EOF
<HTML>
  <HEAD>
    <TITLE>Success</TITLE>
  </HEAD
<H2>
$_message
</H2>
</HTML>
EOF;

echo $str;
}

// ---------------
function depthUnchanged () {
// returns 1 = true

  // from the dbase
  global $d_depth;

  // from the form input
  global $f_depth;

  return $f_depth == $d_depth;
}

// -----------------------------------------
function getDepthData ($_evid) {
  
// Values read from the dbase

  global $d_lat, $d_lon, $d_depth, $d_bogusflag, $d_TMTSdepth;

  db_connect_glob();   // connect to dbase using info from db_conn.php

// Get the current standard event depth data

   $sql_query="SELECT DISTINCT o.evid \"d_evid\",
                     o.lat \"d_lat\",
                     o.lon \"d_lon\",
                     o.depth \"d_depth\",
                     o.bogusflag \"d_bogusflag\"
               FROM Origin o WHERE
                     o.Evid = $_evid";


   $result = db_query_glob($sql_query);

// fetch result into array: $dbvals['d_evid'], $dbvals['d_lat'], etc.
   $dbvals = array ();
   OCIFetchInto ($result, $dbvals, OCI_ASSOC);

   extract($dbvals);  // convert array variables:e.g. $dbvals['d_mag'] => $d_mag

// Get the current TMTS event depth data if it exists

   $sql_query="SELECT DISTINCT o.depth \"d_TMTSdepth\"
               FROM Origin o, Netmag n WHERE
                     o.evid = $_evid AND
                     n.magalgo = 'TMTS' AND
		     o.orid = n.orid(+)";


   $result = db_query_glob($sql_query);

// fetch result into array: $dbvals['d_TMTSdepth']
   $dbvals = array ();
   OCIFetchInto ($result, $dbvals, OCI_ASSOC);

// You can't pass an array thru POST, so use individual variables
// which will be passed as Hidden variables during the post
   extract($dbvals);  // convert array variables:e.g. $dbvals['d_mag'] => $d_mag

   db_logoff();

}


function updateDb () {
    global $evid, $d_lat, $d_lon, $d_depth, $d_bogusflag;

    // from the form input
    global $f_depth;

    $return = 0;
    $magid = 0;
    $zeroValue = 0;
    $hValue = "H";

    $conn = db_connect_write();
    $sql = "BEGIN :rtn := EPREF.accept_trigger(:evid, :lat, :lon, :depth, :bogusflag); END;";

    $stmt = oci_parse($conn, $sql) or die('error parsing query in updateDb');

    oci_bind_by_name($stmt, ":rtn", $return, 20) or die("ERR: updateDb: bindvars");
    oci_bind_by_name($stmt, ":evid", $evid, 20)
                                         or die("ERR: updateDb: bindvars");
    oci_bind_by_name($stmt, ":lat", $d_lat, 20)
                                         or die("ERR: updateDb: bindvars");
    oci_bind_by_name($stmt, ":lon", $d_lon, 20)
                                         or die("ERR: updateDb: bindvars");
    oci_bind_by_name($stmt, ":depth", $f_depth, 20)
                                         or die("Err: updateDb: bindvars");
    oci_bind_by_name($stmt, ":bogusflag", $d_bogusflag, 20)
                                         or die("Err: updateDb: bindvars");

    oci_execute($stmt) or die("Err: updateDb: execute_1");

    if ($return > 0) {
        // if execute is successful, the return value is greater than zero
        $return = 1;
    }
   
    return $return;
}

// ---------------------------------------------
function printForm ($_message, $_confirm, $_confirmMessage = NULL) {
    global $evid, $dbhost, $processor;
    global $d_lat, $d_lon, $d_depth, $d_TMTSdepth, $d_bogusflag, $f_depth;
    global $networkCode;

$f_auth = $networkCode;
$_submit_form = "";
$_force_click = "";
$fdepth_input = "<INPUT TYPE=\"text\" NAME=\"f_depth\" ID=\"f_depth\" SIZE=6 MAXLENGTH=6 Value=\"$d_depth\" AUTOCOMPLETE=OFF\">";

if($_confirm) {
    $_force_click = "onload=\"document.getElementById('depthSubmit').click()\"";    
    $_submit_form = "onClick=\"if(confirm('$_confirmMessage'))
            return true;
            else { window.close(); return false; }\"";
    $fdepth_input = "";
}


echo <<< EOF1

<BODY $_force_click>

<h3>Edit Depth For Event $evid</h3>

$_message

<H4>Existing db depth=$d_depth </H4>

<H4>Existing TMTS depth=$d_TMTSdepth </H4>

<INPUT TYPE="button" VALUE="Use TMTS" STYLE="background: Orange" onClick="setInputBoxVal();">

<HR style="height:4px" />

<FORM name="mainForm" ACTION="$processor" METHOD="POST" >

<INPUT Type=hidden Name=evid Value="$evid" >
<Input Type=Hidden Name=d_lat value="$d_lat" >
<Input Type=Hidden Name=d_lon value="$d_lon" >
<Input Type=Hidden Name=d_depth value="$d_depth" >
<Input Type=Hidden Name=d_bogusflag value="$d_bogusflag" >
<Input Type=Hidden Name=d_depth value="$d_TMTSdepth" >

<Input Type=Hidden Name=f_depth value="$f_depth" >

<FIELDSET>
   <LEGEND><b>Enter New Depth</b></LEGEND><P>
      <TABLE>
        <TR>
          <TD>
EOF1;


echo <<<EOF3

$fdepth_input
          </TD>
        </TR>
      </TABLE>
</FIELDSET>
<BR>
EOF3;

echo <<< EOF4


<TABLE border="0" width="100%">
  <TR>
  <TD>
      <INPUT ID="depthSubmit" TYPE="SUBMIT" NAME="depthSubmit" VALUE="Commit" STYLE="background: Lime" $_submit_form>      
  </TD>
  <TD>
      <INPUT TYPE="RESET" NAME="reset" VALUE="Reset" STYLE="background: Yellow">
  </TD>
  <TD>
      <INPUT TYPE="button" VALUE="Cancel" STYLE="background: Red" onClick="window.close()">
  </TD>
  </TR>
</TABLE>
</FORM >
<SCRIPT TYPE="text/JavaScript"> 

function setInputBoxVal () {
document.getElementById('f_depth').value = $d_TMTSdepth;
}

</SCRIPT>
</BODY>
</HTML>
EOF4;
}
?>
