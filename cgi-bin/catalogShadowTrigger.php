<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html><head>
<link rel="stylesheet" title="buttonpanel" type="text/css" href="catalogTrigger.css">
<body BGCOLOR=#FFFFCC>

<?php

// The workhorse creation script of the Duty Review Page
// - Reads catalog from the dbase (whic is defined in phpmods/db_conn.php)
// - holds internal event navigation list
// - holds value of current selected event

// Invoked with:  "cgi-bin/catalog.php"

// required libraries for dbase access
include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";
include_once "phpmods/config.php";

// Next 3 lines mimic catalog.php setup:
global $narrow, $break;
$narrow = 0;
if ( isset($_GET["NARROW"]) ) { $narrow = $_GET["NARROW"]; }
if ($narrow) {$break = "<br>";};

// ----- Extract catalog data from the dbase -------
//$hrsBack = 72;  // show events this old, instead  DEFAULT TO config.php $hrsBack value -aww 2012/05/25

// Limit the number of rows in the list so it doesn't get too long
// during intense sequences
$rowLimit = 200;

$idList = array();

echo "<title> Last $hrsBack hours of Seismicity </title>";
echo "</head>";
echo '<body link="#006699" vlink="black" alink="#ffff99">';

echo "<a name=\"top\"></a>";

// format the results as html
format_catalog_trigger(getDefDbname(), $rowLimit, $hrsBack);


// //////////////////////////////////////////////////////////////////////

/*
  ID#       MAG        DATE   TIME    ET F SRC   lat    lon   #channels 
  14217036 0.0 M  2006/03/14 19:28:19 st A RT1 0.0000 0.0000  134 
*/
function format_catalog_trigger($dbase, $maxRows, $hrsBack) {
  global $break;

//  header
  echo "<PRE><FONT SIZE=+1>";
  echo "  ID#           DATE   TIME    ET F SRC$break";
  echo "<i>   lat    lon      #channels</i>";

  $knt = 0;

  // connect to dbase using info from db_conn.php
  //db_connect($db_user, $db_password, $db);
  db_connect_glob();

  // ----- Extract catalog data from the dbase -------
  $stop = time();                        // now
  $start = $stop - ($hrsBack * 60 * 60);  // convert time to epoch secs

  //
  // Signature of a subnet trigger: selectflag = 0  and etype = 'st' and bogusflag = 1
  // Origin.rflag = 'A' before its accepted and = 'H' after
  //

  // The dbase query
  $sql_query="SELECT t2.* from (
    SELECT /*+ FIRST_ROWS */ 
            e.evid \"evid\",
            TrueTime.nominal2string(o.datetime) \"ot\",
            o.lat \"lat\", o.lon \"lon\",
            e.etype \"type\", o.rflag \"rflag\", o.subsource \"src\",
            count(*) \"ntrig\"
            FROM Event e
            JOIN Origin o ON
            o.orid=e.prefor
            LEFT OUTER JOIN assocnte t ON
            t.evid=e.evid
            LEFT OUTER JOIN trig_channel c ON
            c.ntid=t.ntid
            WHERE e.selectflag=0 and e.etype='st' and o.orid=e.prefor and
            o.datetime between $start and $stop
            group by e.evid,o.datetime,o.lat,o.lon,e.etype,o.rflag,o.subsource
            ORDER BY o.datetime desc) t2
      WHERE rownum <= $maxRows+1
";
//echo "$sql_query\n"; 
  // execute the query
  $result = db_query_glob($sql_query);

  // returns the results in table form
  $ncols = OCINumCols($result);

  // for each row returned
  while (OCIFetch($result))
  {
          if ($knt > $maxRows) { break; }; // hit the end

          // parse the individual attributes by name
          $evid    = OCIResult($result, "evid");
          $ot      = OCIResult($result, "ot");
          $lat     = OCIResult($result, "lat");
          $lon     = OCIResult($result, "lon");
          $type    = OCIResult($result, "type");
          $rflag   = OCIResult($result, "rflag");
          $src     = OCIResult($result, "src");
          $ntrig   = OCIResult($result, "ntrig");

          // format the html 

          $anchor = "<a href=\"#$evid\" onClick=\"selectNewEvid($evid); return false;\" ID=\"ev$evid\">";

          //$anchor = "<a href=\"${targ}?EVID=$evid\" target=\"zoom\" ID=\"$evid\">";

          $evidstr = sprintf ("%10d", $evid);

          // format an output row 
          $str1 = sprintf ("%s %19s %s %s %-4.4s" , $evidstr, $ot, $type, $rflag, $src);
          $str2 = sprintf ("<i> %7.4f %8.4f %4d</i>", $lat, $lon, $ntrig);

          // Highlight ML > 3.0
          // Sidekick does NOT support FONT COLORS, use <hr>
          echo "\n$anchor";
          echo "$str1";
          echo "</a>$break";
          echo "$str2";

          $idList[$knt++] = $evid;
  }  // end of while loop

  //if ( $knt > 1 ) {
    //echo "<a class=\"left\" href=\"#top\">(top of catalog)</a>"; 
    //echo "<a class=\"right\" href=\"#top\">(top of catalog)</a>"; 
  //}
  if ($knt > $maxRows) {
    echo "<br><b>Max rows limit (=$maxRows) exceeded. Old triggers not shown.</b>\n";
    echo "<br>Delete non-event triggers and refresh list to see older triggers.<br>";
  }
  else {
    echo "<hr> End of list: $knt events in last $hrsBack hrs";
  }
  if ( $knt == 0 ) {
    echo "<A HREF=\"../../index.html\" TARGET=\"_top\"> AQMS Home </A><BR>";
  }
  echo "&nbsp;<a href=\"../triggerShadow.php\" target=\"_top\">(refresh catalog)</a>"; 
  echo "<BR><BR><A HREF=\"../indexShadow.php\" TARGET=\"_top\">Shadow Event Review Page</A><BR>";

  // disconnect
  db_logoff();

  echo "<a name=\"bottom\"></a>\n";
  echo "</font></pre>\n"; 

  // --------------- Write JavaScript Header stuff ------------------
  // Need JavaScript to add evid to array

  //echo "\n<SCRIPT LANGUAGE=\"JAVASCRIPT\"> \n";
  echo "\n<SCRIPT type=\"text/JAVASCRIPT\"> \n";
  echo "<!-- Hide if browser can not cope with JavaScript \n";
  echo "var evidList = new Array(); \n";
  echo "var idx = 0;\n";       // index of currently "selected" evid 
  echo "var dbase = \"$dbase\";\n";     // name of the database

  // define the JavaScript evid array
  $i = 0;
  foreach ($idList as $id) {
  //   echo "addEvid($id);\n";
     echo "evidList[$i] = \"$id\" ;\n";
     $i++;
  }

  return $knt;

}   // end of function format_catalog_html()
// //////////////////////////////////////////////////////////////////////

// Return 'true' if a snapshot .gif file exists for the event.
function snapshotExists ($_evid) {
  // Where the gifs are
   $gifFile = "../eventfiles/gifs/" . $_evid . ".gif";
   return @file_exists($gifFile);
}

// end of PHP  ===================================================
?>

//debug// document.writeln ("evid = "+ getCurrentId());

// Pure html/JavaScript begins  -------------------------------------------

selectDefaultEvid();    // Load up wave and button frames with current 1st ID

function selectNewEvid(newEvid) {
    setSelectedEvid(newEvid);  // change internal pointer
    selectDefaultEvid();       // update frames
}

// Update all the frames to show the current ID
// Note: my intent was to make the list scroll when the buttonpanel arrows
//       are used to insure the event is visible in the catalog list.
//       But I haven't been able to get it to work without making
//       clicks on the list jump annoyingly also.
function selectDefaultEvid(scrollList) {

  // <waves>  -- build path to event's snapshot html file
  eventfile = "makeSnapshotFrameTrigger.php?EVID="+getCurrentId();
  top.waves.location = eventfile;

  // <buttons>
  action= "makeButtonPanelTriggerShadow.php?EVID="+getCurrentId();
  top.buttons.location = action;

  //<catalog> - puts selected event line at top of frame (annoying)
  //top.catalog.location = "#"+currentId;
  //if (scrollList == 1) top.catalog.location = currentId;
}

// Set the internal pointer that keeps track
function setSelectedEvid(newId) {
  newIdx = getIndexOf(newId);
  idx = newIdx;
}

function setDbase (name) {
  dbase = name;
}

function getDbase () {
  return dbase;
}

function getCurrentId() {
  return getId(idx); 
}

function getCurrentIndex () {
  return idx;
}
function getId(index) {
  return evidList[index]; 
}

function getIndexOf (evid) {
  for (i = 0; i<evidList.length; ++i) {
    if (evidList[i] == evid) return i ;
  }
  return 0;
}

// move to next event UP the list
function incrementIndex () {
  idx++;
  if (idx > evidList.length-1) {idx = 0};  // wrap
  return getCurrentId();
}

// move to next event DOWN the list
function decrementIndex () {
  idx--;
  if (idx < 0) {idx = evidList.length - 1};  // wrap
  return getCurrentId();
}

function getEvidCount () {
  return evidList.length;
}

</script>
</html>
