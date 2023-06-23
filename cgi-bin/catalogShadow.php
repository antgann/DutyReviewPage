<?php
/*
- The catalog creation script of the Duty Review Page
- Reads catalog from the dbase (which is defined in phpmods/db_conn.php)
- holds internal event navigation list holds value of current selected event
*/

// required libraries for dbase access
include_once "phpmods/config.php";
include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";

$lastEvid = -1;
if ( isset($_GET["EVID"]) ) { 
  $lastEvid = $_GET["EVID"];
  // 1 day expiration
  //setCookie("lastEvid", $lastEvid, time() + 84600);
  // hour expiration
  setCookie("lastEvid", $lastEvid, time() + 3600);
}
if ( isset($_COOKIE["lastEvid"]) ) { 
  $lastEvid = $_COOKIE["lastEvid"];
}

// ----- Extract catalog data from the dbase -------
//$hrsBack = 72;  // show events this old, instead  DEFAULT TO config.php $hrsBack value -aww 2012/05/25
$stop = time();                         // now
$start = $stop - ($hrsBack * 60 * 60);  // convert time to epoch secs

// Limit the number of rows in the list so it doesn't get too long during intense sequences
$DefRowLimit = 100;
$nines = 999999;

$maxRows   = $_GET["LIMIT"];
// If no arg or neg. use default value
if ($maxRows <= 0) {$maxRows = $DefRowLimit;};

$minMag = -9;
if ( array_key_exists("MINMAG", $_GET) ) {
  $minMag = $_GET["MINMAG"];
}

$narrow = 0;
if ( isset($_GET["NARROW"]) ) { $narrow = $_GET["NARROW"]; }

echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<link rel="stylesheet" title="catalogstyle" type="text/css" href="catalog.css">
<title>Last $hrsBack hrs of Seismicity $lastEvid</title>
</head>
<body link="#006699" vlink="black" alink="#ffff99">
<a name="top"></a>
<div id="wrapper">
EOT;

# Print line in red if > this mag (to highlight)
$redMag = 2.94;

// Read one extra row as a marker that we truncated (indicates there were more)
$rowFetchCount = $maxRows + 1;

$idList = array();
$rflagList = array();
// connect to dbase using info from db_conn.php
db_connect_glob();

// The dbase query
$sql_query = "SELECT * FROM (
      SELECT /*+ FIRST_ROWS */ 
                e.evid \"evid\", 
                e.prefmec \"pm\", 
                e.version \"vers\", 
                n.magnitude \"mag\", 
                n.magtype \"mt\", 
                n.subsource \"msrc\",
                TrueTime.getString(o.datetime) \"ot\", 
                o.lat \"lat\",
                o.lon \"lon\", o.depth \"z\",
                o.ndef \"ndef\",
                o.wrms \"rms\", o.gap \"gap\",
                e.etype \"type\",
                o.gtype \"gtype\",
                o.rflag \"rflag\",
                o.subsource \"src\",
                WHERES.LOCALE_BY_TYPE(o.lat, o.lon, o.depth, 1, 'town') \"town\"
      FROM Event e
      LEFT OUTER JOIN NetMag n ON
      n.magid = e.prefmag
      JOIN Origin o ON
      o.orid = e.prefor
      WHERE e.selectflag = 0 and e.etype not in ('st','ts') and (o.gtype is null or o.gtype != 't')
      and o.datetime between $start and $stop";
if ( $minMag > -9) {
   $sql_query = $sql_query . " Order by o.datetime desc ) WHERE \"mag\">=$minMag and ROWNUM<=${rowFetchCount}";
}
else {
   $sql_query = $sql_query . " Order by o.datetime desc ) WHERE ROWNUM<=${rowFetchCount}";
}

//echo "$sql_query\n";

// execute the query
$result = db_query_glob($sql_query);

//
if ($result) {
    format_catalog_html($result, getDefDbhost(), $maxRows);
} else {
    print "<b>query did not execute</b>";
}
//

// disconnect
db_logoff();

// //////////////////////////////////////////////////////////////////////

/* Format the html for the catalog listing.
   Creates the links to update snapshot view and button panel

    ID#      MAG     DATE      TIME   TP SRC F   lat      lon      z    #  rms
  14421464 1.3 Ml 2009/02/15 03:59:13 le Jig F 33.9625 -117.7672 11.7  32 0.19 >   5.7 km SE  of Diamond Bar, CA
  14421456 1.4 Ml 2009/02/15 03:57:01 le Jig F 34.3103 -116.8928 11.7  76 0.17 >   6.2 km NW  of Big Bear City, CA

*/
function format_catalog_html($result, $host, $maxRows) {

    // Added $db_name to global list as test -aww 2012/04/20
    global $networkCode, $havePDL, $DefRowLimit, $nines, $lastEvid, $hrsBack, $redMag, $narrow, $db_name;

    $self=$_SERVER['PHP_SELF']; // Added this -aww 2012/04/20

    // $narrow will be either 'false' or a number (true)
    $break = "";
    //echo "<a id=\"llink\" href=\"#bottom\">(bottom of catalog)</a>"; 

    if ($narrow) {$break = "<br>";};
    echo "\n<PRE>";             // fixed format font, obeys <cr>s

    // Make the header
    if ( $narrow == 0 )
      echo "<div id=\"catHdr\" class=\"catHdrWide\">\n";
    else {
      echo "<div id=\"catHdr\" class=\"catHdrNarrow\">\n";
    }

    //$reload="<a href=\"#\" onclick=\"top.document.location.reload(true);return false;\">refresh</a>";
    $reload="<a href=\"$self?LIMIT=$maxRows&NARROW=$narrow\">refresh</a>";
    $bottom="<a id=\"rlink\" href=\"javascript:pageScroll('bottom')\">bottom</a>";
    $top="<a id=\"rlink\" href=\"javascript:pageScroll('top')\">top</a>";
    $selected="<a id=\"rlink\" href=\"javascript:scrollToSelected('top')\">selected</a>";
    if ($narrow) {
      echo "Catalog: <A HREF=\"$self?LIMIT=$maxRows&NARROW=0\">WIDE</A>|NARROW ";
    } else {
      echo "Catalog: WIDE|<A HREF=\"$self?LIMIT=$maxRows&NARROW=1\">NARROW</A>";
    }
    echo "&nbsp;($reload $bottom $top $selected)<br>";

    echo "<b>";
    // removed prefmec: pm
    //echo "  evid       mag      date      time   et gt$break";
    echo "  evid       mag      date      time   et gt pm$break";
    if ( $havePDL ) {
      echo "  src r  v  p  lat      lon      z    #ph rms$break  > location";
    }
    else {
      echo "  src r  v  lat      lon      z    #ph rms$break  > location";
    }
    echo "</b>";
    // end of catHdr div
    echo "</div>\n";
    if ( $narrow == 0 )
      echo "<div id=\"contentWide\">\n";
    else {
      echo "<div id=\"contentNarrow\">\n";
    }

    $knt = 0;

    if ( $havePDL ) {
        // PDL lookup
        $pdl_user = 'web';
        $pdl_pass = 'readonly';
        $netC = strtolower($networkCode);

        try {
              $dbh = new PDO('mysql:host=localhost;dbname=product_index', $pdl_user, $pdl_pass);
              //$sql = "select updatetime as utime, eventLatitude as lat, eventLongitude as lon, eventDepth as depth,
              //        eventMagnitude as mag, version as vers, status as sts
              //        from productSummary where eventSource = '$netC' and type = 'origin' and eventSourceCode = ?"; 
              $sql = "select max(version) as maxv from productSummary where eventSource ='$netC' and type = 'origin' and eventSourceCode = ?"; 
              $psth = $dbh->prepare($sql);
        }
        catch (Exception $e) {
                echo 'Caught PDO MySQL exception: ', $e->getMessage(), "\n";
        }
    }

    // for each row returned
    while (oci_fetch($result)) {


            // parse the individual attributes by name
            $evid    = oci_result($result, "evid");
            if (oci_field_is_null($result, "pm")) {
              $pm = "0";
            } else {
              $pm = "1";   
            }
            $vers    = oci_result($result, "vers");
            $mag     = oci_result($result, "mag");
            $magtype = oci_result($result, "mt");
            $msrc    = oci_result($result, "msrc");
            $ot      = oci_result($result, "ot");
            $lat     = oci_result($result, "lat");
            $lon     = oci_result($result, "lon");
            $z       = oci_result($result, "z");
            $ndef    = oci_result($result, "ndef");
            $rms     = oci_result($result, "rms");
            $gap     = oci_result($result, "gap");
            $type    = oci_result($result, "type");
            $gtype   = oci_result($result, "gtype");
            $rflag   = oci_result($result, "rflag");
            $src     = oci_result($result, "src");
            $town    = oci_result($result, "town");

            // parse/compose town string
            $km = substr($town, 0,10);
            $az = substr($town, 22, 3);
            $ref= chop(substr($town, 47));  
            $townStr = sprintf ("%5.1f km %3s of %s", $km, $az, $ref);

            // PDL lookup
            $pvers=0;
            if ( $havePDL ) {
                    try {
                      $psth->bindValue(1, $evid);
                      $psth->execute();

                      $result2 = $psth->fetchAll(PDO::FETCH_ASSOC);

                      $output = '';
                      //$ivers=0;
                      foreach ($result2 as $row) {
                         //print_r($row);
                         $output .= implode( ",", array_values($row) ) . "\n";
                         $pvers = $row['maxv'];
                         if ( is_null($pvers) || empty($pvers) ) { $pvers = 0; }

                         //$ivers = $row['vers'];
                         //if ( is_null($ivers) || empty($ivers) ) { $ivers = 0; }
                         //if ( $ivers > $pvers ) { 
                         //  $pvers = $ivers;
                         //}
                      }
                      file_put_contents("/tmp/${evid}_pdl_vers.txt", $output);

                    }
                    catch (Exception $e) {
                        echo 'Caught PDO MySQL exception: ', $e->getMessage(), "\n";
                    }
            }
            $evidstr = sprintf ("%10d", $evid);
            //$sz = sprintf("%1.1f", $z);      // only way to right-justified!
            if ( $z > 99.9 ) { $z = 99.9; }
            //$srms = sprintf("%1.2f", $rms);
            if ( $rms > 9.99 ) { $rms = 9.99; }

            if (is_null($gtype)) {
              $gtype = "-";
            }

            // format an output row 
            $idname = $evid;
            $classn = '';
            if ( $lastEvid == $evid ) {
                $idname = 'sel-' . $evid;
                $classn ="class=\"selEvid\"";
            }

            // NOTE: php rounding math is wrong, so add 0.000001 to $mag to force a round up, but
            // better would be to instead  change default precision=14 to 17 in installed php lib:
            // /usr/local/php/lib/php.ini -aww 20150123
            $mag += .000001;
            //$smag = substr(sprintf("%4.2f",$mag),0,3);

            if ($mag > $redMag) {
              $str  = "<a class=\"bigM\" href=\"#\" onclick=\"selectNewEvidAndScroll($evid, 0); return false;\" id=\"$idname\" $classn>";
              if ( is_null($magtype) ) {
                $str .= sprintf ("%s<b> NL:</b> M%-2s %19s %2s %2s %2s", $evidstr, $magtype, $ot, $type, $gtype, $pm);
              }
              else {
                $str .= sprintf ("%s<b>%4.1f</b> M%-2s %19s %2s %2s %2s", $evidstr, $mag, $magtype, $ot, $type, $gtype, $pm);
              }
              $str .= "</a>$break";
            }
            else {
              $str  = "<a href=\"#\" onclick=\"selectNewEvidAndScroll($evid, 0); return false;\" id=\"$idname\" $classn>";
              if ( is_null($magtype) ) {
                $str .= sprintf ("%s<b> NL:</b> M%-2s %19s %2s %2s %2s", $evidstr, $magtype, $ot, $type, $gtype, $pm);
              }
              else {
                $str .= sprintf ("%s<b>%4.1f</b> M%-2s %19s %2s %2s %2s", $evidstr, $mag, $magtype, $ot, $type, $gtype, $pm);
              }
              $str .= "</a>$break";
            }

            $ssrc="";
            if ( $msrc == $src ) {
              $ssrc = sprintf("<span style=\"font-style:italic\">%4.4s</span>",$src);
            }
            else {
              //$src = $msrc;
              $ssrc = sprintf("<span style=\"color:DarkOrange;font-style:normal;font-weight:bold\">%4.4s</span>",$src);
            }

            $svers = 0;
            if ( $havePDL ) {
                    if ( "$pvers" != "$vers" ) {
                        //$svers = sprintf ("<FONT COLOR=red><b>%2d %2d</b></FONT>", $vers, $pvers);
                        $svers = sprintf ("<span style=\"color:red\">%2d %2d</span>", $vers, $pvers);
                    }
                    else {
                        //$svers = sprintf ("<FONT COLOR=green><b>%2d %2d</b></FONT>", $vers, $pvers);
                        $svers = sprintf ("<span style=\"color:green\">%2d %2d</span>", $vers, $pvers);
                    }
                    //$str .= sprintf ("<i> %4.4s %1s %s %8.4f %9.4f %4.1f %3d %4.2f</i>", $src, $rflag, $svers, $lat, $lon, $z, $ndef, $rms);
                    $str .= sprintf (" %s <i>%1s %s %8.4f %9.4f %4.1f %3d %4.2f</i>", $ssrc, $rflag, $svers, $lat, $lon, $z, $ndef, $rms);
            }
            else {
                    // SPECIAL CASE UNLESS PDL product client mysql can be accessed
                    $svers = sprintf ("<span style=\"color:green\"><b>%2d</b></span>", $vers);
                    //$str .= sprintf ("<i> %4.4s %1s %s %8.4f %9.4f %4.1f %3d %4.2f</i>", $src, $rflag, $svers, $lat, $lon, $z, $ndef, $rms);
                    $str .= sprintf (" %s <i>%1s %s %8.4f %9.4f %4.1f %3d %4.2f</i>", $ssrc, $rflag, $svers, $lat, $lon, $z, $ndef, $rms);
            }


    // Mark if SnapShot file is missing - Added DDG 1/20/06
            if (snapshotExists($evid)) { $str .= " "; }
            else { $str .= "*"; }

            $str .= "$break>$townStr";

    // Add the town info

    // Output the row - make it red if its big
            if ($mag > $redMag) {
                      echo "<span style=\"color:red; background-color:pink\">$str\n</span>";
            } else {
                    echo "$str\n";
            }
            $idList[$knt] = $evid;
            $rflagList[$knt] = $rflag;
            $knt++;

            if ($knt == $maxRows) {break;}  //bail now if we are truncating the list

    } // end of result set parsing while loop

    // REMOVE closeCursor when no PDL query is done
    if ( $havePDL ) {
        $psth->closeCursor();
    }

    if ( $knt > 1 ) {
        echo "<a class=\"left\" href=\"#\" onclick=\"javascript:pageScroll('top');return false;\">(top of catalog)</a>"; 
        echo "<a class=\"right\" href=\"#\" onclick=\"javascript:pageScroll('top');return false;\">(top of catalog)</a>"; 
        echo "<br>"; 
    }
    /* tell user if list is truncated
    if ($knt == $maxRows) {
        echo "<hr><b>** List truncated at $knt events to limit list size $break (full $hrsBack hrs not shown)$break";
        echo "<a href=\"$self?LIMIT=$nines&NARROW=$narrow\">Click to see ALL events in last ${hrsBack} hours.</a>";
    } else {
        echo "<hr>End of list: $knt events in last $hrsBack hrs";
        if ($knt == 0 ) {
            echo "<a href=\"../../index.html\" target=\"_top\"> AQMS Home </A>";
        }
        if ($knt > $DefRowLimit) {
            echo "<br><a href=\"$self?LIMIT=$DefRowLimit&NARROW=$narrow\">View list truncated to $DefRowLimit events.</a>";
        }
    }
    */
    echo "</pre>\n"; 
    echo "<a name=\"bottom\"></a>\n";

    // end of catlog contentXXX  div
    echo "</div>\n";

    echo "<div id=\"catFtr\">";
    if ($knt == $maxRows) {
        echo "<b>*List truncated at $knt events to limit list size $break";
        echo "<a href=\"$self?LIMIT=$nines&NARROW=$narrow\">Click to see ALL events in last ${hrsBack} hours.</a>";
    } else {
            echo "<hr>End of list: $knt events in last $hrsBack hrs";
        if ($knt == 0 ) {
            echo "$narrow<a href=\"../../index.html\" target=\"_top\"> AQMS Home </A>";
        }
        elseif ($knt > $DefRowLimit) {
            echo "<a href=\"$self?LIMIT=$DefRowLimit&NARROW=$narrow\">View list truncated to $DefRowLimit events.</a>";
        }
    }
    // end of catFtr div
    echo "</div>\n";
    // end of wrapper div
    echo "</div>\n";

    // --------------- Begin JavaScript ------------------

    //JavaScript to add evid to array
    echo "\n<script type=\"text/javascript\">\n";
    echo "<!-- Hide if browser can not cope with JavaScript \n";
    echo "var evidList = new Array(); \n";
    echo "var flagList = new Array(); \n";
    // index of currently "selected" evid 
    echo "var idx = 0;\n";
    if ( $lastEvid > 0 ) {
       echo "idx = ". $lastEvid . ";\n"; 
    }
    echo "var dbhost = \"$host\";\n";     // name of the dbase host
    echo "var dbase = \"$db_name\";\n";   // name of the database
    echo "var narrow = \"$narrow\";\n";

    // define the JavaScript evid array
    $i = 0;
    foreach ($idList as $id) {
       //echo "evidList($id);\n";
       echo "evidList[$i] = \"$id\" ;\n";
       $i++;
    }
    $i = 0;
    foreach ($rflagList as $flag) {
       //echo "evidList($id);\n";
       echo "flagList[$i] = \"$flag\" ;\n";
       $i++;
    }

    // Initially, selected evid is the one at the top of the list

}   // end of function format_catalog_html()

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
var lastEvid = getCookie("lastEvid"); 
if (lastEvid > 0) {
  idx = getIndexOf(lastEvid);
}
selectDefaultEvidAndScroll(1);    // Load up wave and button frames with current 1st ID

// Fade to Yellow 
var timeoutMillis = 1000 * 60        // change color each minute
var colorCount = 15;
// start things off
window.setTimeout("ChangeBgColor()", timeoutMillis)

function ChangeBgColor() {

    var c = new Array("00", "11", "22", "33", "44", "55",
        "66", "77", "88", "99", "AA", "BB", "CC", "DD", "EE", "FF");

    //col_val = "#FF"+ c[colorCount]+ c[colorCount];  // fade to PINK/RED
    col_val = "#FFFF"+ c[colorCount];                 // fade to yellow
    document.bgColor=col_val;
    colorCount = colorCount -1;
    // keep changing
    if (colorCount > 0) {
        window.setTimeout("ChangeBgColor()", timeoutMillis)
    }
}

function selectNewEvid(newEvid) {
   selectNewEvidAndScroll(newEvid, 1);
}
function selectNewEvidAndScroll(newEvid, doScroll) {
    //setCookie("lastEvid", newEvid, 1);
    //3rd arg is #millisecs into future e.g. 3600000 = 1 hour
    setCookie("lastEvid", newEvid, 600000);
    setSelectedEvid(newEvid);  // change internal pointer
    top.buttons.location = "loading2.php";
    top.waves.location = "loading.php?SCROLL="+ doScroll;
//    selectDefaultEvidAndScroll(doScroll);       // update frames
}

// Update all the frames to show the current ID
// Note: my intent was to make the list scroll when the buttonpanel arrows
//       are used to insure the event is visible in the catalog list.
//       But I haven't been able to get it to work without making
//       clicks on the list jump annoyingly also.
function selectDefaultEvid() {
    selectDefaultEvidAndScroll(1);
}
function selectDefaultEvidAndScroll(doScroll) {

    // <waves>  -- build path to event's snapshot html file
    var evid = getCurrentId();

    // <buttons>
    action= "makeButtonPanel.php?EVID="+ evid;
    top.buttons.location = action;

    eventfile = "makeSnapshotFrame.php?EVID="+ evid;
    top.waves.location = eventfile;
    if ( doScroll ) {
      // Note below scrolls item to top which is hidden below fixed cat header 
      var item = document.getElementById("sel-" + evid);
      item.scrollIntoView();
      // TODO: Fix kludge below that moves id element to below the catalog header at top of scroll window
      var item2 = document.getElementById("catHdr");
      //alert("item " + item.offsetTop + " " + item.offsetHeight + " " + item.scrollHeight + " " + item.scrollTop);
      if (item.offsetTop < (item.offsetParent.scrollHeight-item.offsetParent.offsetHeight)+item2.offsetHeight) {
          window.scrollBy(0, item.scrollHeight- 2*item2.scrollHeight);
      }
    }
}

// Set the internal pointer that keeps track
function setSelectedEvid(newId) {
    updateSelected(newId);
    newIdx = getIndexOf(newId);
    idx = newIdx;
}

function setDbHost(name) {
    dbhost = name;
}

function getDbase() {
    return dbase;
}

function getDbHost() {
    return dbhost;
}

function getCurrentId() {
    return getId(idx); 
}

function getCurrentIndex() {
    return idx;
}

function getCurrentFlag() {
    return getFlag(idx); 
}

function getFlag(index) {
    return flagList[index]; 
}

function getId(index) {
    return evidList[index]; 
}

function updateSelected(id) {
    var oldid = getId(getCurrentIndex())
    var str = "sel-" + oldid;
    var item = document.getElementById(str);
    if ( item != null ) {
      item.id = oldid;
      item.className = null;
    }
    item = document.getElementById(id);
    item.id = "sel-" + id;
    item.className = 'selEvid';
}

function getIndexOf(evid) {
    for (i = 0; i<evidList.length; ++i) {
        if (evidList[i] == evid) return i;
    }
    return 0;
}

// next idx DOWN list
function nextIndexDown() {
   var lid = idx + 1
   if (lid > evidList.length-1) {lid = 0};  // wrap
   return lid;
}
// move to next event DOWN list
function incrementIndex() {
   idx++;
   if (idx > evidList.length-1) {idx = 0};  // wrap
   return getCurrentId();
}

// last index UP list
function nextIndexUp() {
   var lid = idx - 1;
   if (lid < 0) {lid = evidList.length - 1};  // wrap
   return lid;
}
// move to next event UP list
function decrementIndex() {
   idx--;
   if (idx < 0) {idx = evidList.length - 1};  // wrap
   return getCurrentId();
}

function getEvidCount() {
    return evidList.length;
}

function pageScroll(position) {
    //focus();
    if ( position == 'top' ) {
        scrollTo(0, 0);
    }
    else {
        scrollTo(0, document.body.scrollHeight);
    }
}

function scrollToSelected(position) {
    // Note below scrolls item to top which is hidden below fixed cat header
    var oldid = getId(getCurrentIndex())
    var str = "sel-" + oldid;
    var item = document.getElementById(str);
    item.scrollIntoView();
    // TODO: Fix kludge below that moves id element to below the catalog header at top of scroll window
    var item2 = document.getElementById("catHdr");
    //alert("item " + item.offsetTop + " " + item.offsetHeight + " " + item.scrollHeight + " " + item.scrollTop);
    if (item.offsetTop < (item.offsetParent.scrollHeight-item.offsetParent.offsetHeight)+item2.offsetHeight) {
        window.scrollBy(0, item.scrollHeight- 2*item2.scrollHeight);
    }
}

function setCookie(c_name, value, exmillis) {
    var exdate=new Date();
    //exdate.setDate(exdate.getDate() + exmillis);
    exdate.setTime(exdate.getTime() + exmillis);

    var c_value=escape(value) + ((exmillis==null) ? "" : "; expires="+exdate.toUTCString());
    document.cookie=c_name + "=" + c_value;
}

function getCookie(c_name) {
    var c_value = document.cookie;
    var c_start = c_value.indexOf(" " + c_name + "=");
    if (c_start == -1) {
       c_start = c_value.indexOf(c_name + "=");
    }
    if (c_start == -1) {
        c_value = null;
    }
    else {
       c_start = c_value.indexOf("=", c_start) + 1;
       var c_end = c_value.indexOf(";", c_start);
       if (c_end == -1) {
           c_end = c_value.length;
       }
       c_value = unescape(c_value.substring(c_start,c_end));
    }
    return c_value;
}
//-->
</script>
</body>
</html>
