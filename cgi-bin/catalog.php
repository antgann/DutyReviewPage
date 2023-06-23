<?php
/**
 * The catalog creation script of the Duty Review Page
 * Reads catalog from the dbase (which is defined in phpmods/db_conn.php)
 * holds internal event navigation list holds value of current selected event
 * @author     Gary Gann
 * @copyright  2012-2020 US Geological Survey
 * @version    3.15
 */

// required libraries for dbase access
include_once "phpmods/config.php";
include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";
include_once "pointInPolygon.php";

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

if( isset($_GET["HRSBACK"]) ) $hrsBack = ( is_numeric($_GET["HRSBACK"]) ? intval($_GET["HRSBACK"]) : 72 );

if( ($_GET["DATESTART"] > 0) ) {
  $dateStart = $_GET["DATESTART"];
  $start = strtotime($dateStart . " 00:00:00 GMT");
  if ($_GET["DATESTOP"] > 0) {
    $dateStop = $_GET["DATESTOP"];
    $stop = strtotime($dateStop . " 23:59:59 GMT");
  }
  else {
    $stop = time(); 
  }

//  if ($start == $stop) {
//    $start = $stop - (24 * 60 * 60);  // go back 24 hours
//  }
}  
else {
  $stop = time();                         // now
  $start = $stop - ($hrsBack * 60 * 60);  // convert time to epoch secs
}

if ($_GET["EVIDSEARCH"] != 0)  {  // Search by event ID is being requested
  $evIDSearch = $_GET["EVIDSEARCH"];
}

// Limit the number of rows in the list so it doesn't get too long during intense sequences
$DefRowLimit = 100;
$nines = 999999;

$maxRows = (isset($_GET["PAGELIMIT"]) ? $_GET["PAGELIMIT"] : $DefRowLimit); 

$minMag = $minMagDefault;
$maxMag = $maxMagDefault;
if ( isset($_GET["MINMAG"]) ) {
  if( is_numeric($_GET["MINMAG"]) ) $minMag = floatval($_GET["MINMAG"]);
}
if ( isset($_GET["MAXMAG"]) ) {
  if( is_numeric($_GET["MAXMAG"]) ) $maxMag = floatval($_GET["MAXMAG"]);
}
$magArray = array($minMag, $maxMag);

$narrow = (isset($_GET["NARROW"]) ? $_GET["NARROW"] : 0); 

echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<link rel="stylesheet" title="catalogstyle" type="text/css" href="catalog.css">

<script src="CalendarPopup.js" type="text/javascript"></script>
<SCRIPT LANGUAGE="JavaScript" ID="js1">
var cal1 = new CalendarPopup();
</SCRIPT>

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


//ORDER BY ARRAY 
$orderByOptions = array(
	"evid" => "e.evid",
	"magnitude" => "n.magnitude",
	"datetime" => "o.datetime",
);

$orderBy = ( isset( $_GET['ORDERBY'] ) ) ? $_GET['ORDERBY'] : 'datetime';

$orderOrientation = ( isset( $_GET['ORDERDESCASC'] ) ) ? $_GET['ORDERDESCASC'] : 'desc';

$dateBoundaryFlag = True;  // add bounds for the date

// The dbase query
$sql_query = "SELECT a.*, rownum rnum FROM (
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
      WHERE e.selectflag = 1 and e.etype not in ('st','ts') and (o.gtype is null or o.gtype != 't')";
      
if(isset($evIDSearch)) {  // Search by event ID is being requested, no date boundaries
   $sql_query = $sql_query . " AND e.evid = " . $evIDSearch; 
   $dateBoundaryFlag = False; 
}
else if ( $minMag > $minMagDefault || $maxMag < $maxMagDefault) {   // If different magnitudes are requested, append the range to the query
   $sql_query = $sql_query . " AND n.magnitude >=" . $minMag . " AND n.magnitude <=" . $maxMag; 
}
// we have boundaries for the date
if($dateBoundaryFlag) {
   $sql_query = $sql_query . " AND o.datetime between $start and $stop";
}
//add sorting 
$sql_query = $sql_query . " Order by " . $orderByOptions[$orderBy] . " " . $orderOrientation . " ) a";


$pageLimit      = ( isset( $_GET['PAGELIMIT'] ) ) ? $_GET['PAGELIMIT'] : 100;
$page       = ( isset( $_GET['PAGE'] ) ) ? $_GET['PAGE'] : 1;


$_total = db_numrows($sql_query);
$result = getData($pageLimit, $page, $sql_query, $_total);

if ($result) {
    format_catalog_html($result, getDefDbhost(), $maxRows, $inRegionBoundaryPolygon);
} else {
    print "<b>query did not execute</b>";
}

// disconnect
db_logoff();

// //////////////////////////////////////////////////////////////////////

/* Format the html for the catalog listing.
   Creates the links to update snapshot view and button panel

    ID#      MAG     DATE      TIME   TP SRC F   lat      lon      z    #  rms
  14421464 1.3 Ml 2009/02/15 03:59:13 le Jig F 33.9625 -117.7672 11.7  32 0.19 >   5.7 km SE  of Diamond Bar, CA
  14421456 1.4 Ml 2009/02/15 03:57:01 le Jig F 34.3103 -116.8928 11.7  76 0.17 >   6.2 km NW  of Big Bear City, CA

*/
function format_catalog_html($resultClass, $host, $maxRows, $inRegionBoundaryPolygon) {

    // Added $db_name to global list as test -aww 2012/04/20
    global $networkCode, $havePDL, $DefRowLimit, $nines, $lastEvid, $hrsBack, $dateStop, $dateStart, $redMag, $narrow, $db_name, $orderBy, $orderOrientation, $magArray;

    $havePDL = False;  // FOR DEVELOPMENT ENVIRONMENT - GG 06/01/2020
    $self=$_SERVER['PHP_SELF']; // Added this -aww 2012/04/20

    $resultData = $resultClass->data;

    $links      = ( isset( $_GET['links'] ) ) ? $_GET['links'] : 7;

    // $narrow will be either 'false' or a number (true)
    $break = "";

    if ($narrow) {$break = "<br />";};
    echo "\n<PRE>";             // fixed format font, obeys <cr>s

    // Make the header
    if ( $narrow == 0 )
      echo "<div id=\"catHdr\" class=\"catHdrWide\">\n";
    else {
      echo "<div id=\"catHdr\" class=\"catHdrNarrow\">\n";
    }


// Removing limit variable settings below - GG 3/2/2016
    $currentLimit = $resultClass->limit;
    $reload="<a href=\"$self?NARROW=$narrow&PAGELIMIT=$currentLimit\">refresh</a>";
    $bottom="<a id=\"rlink\" href=\"javascript:pageScroll('bottom')\">bottom</a>";
    $top="<a id=\"rlink\" href=\"javascript:pageScroll('top')\">top</a>";
    $selected="<a id=\"rlink\" href=\"javascript:scrollToSelected('top')\">selected</a>";
    if ($narrow) {
      echo "Catalog: <A HREF=\"$self?NARROW=0&PAGELIMIT=$currentLimit\">WIDE</A>|NARROW ";
    } else {
      echo "Catalog: WIDE|<A HREF=\"$self?NARROW=1&PAGELIMIT=$currentLimit\">NARROW</A>";
    }
    echo "&nbsp;($reload $bottom $top $selected)<br>";

    echo "<b>";

   
    $orderByLink = "<A HREF=\"$self?NARROW=$narrow&PAGELIMIT=$currentLimit&DATESTART=$dateStart&DATESTOP=$dateStop&HRSBACK=$hrsBack";
	
    if(!($magArray === NULL)) $orderByLink .= '&MINMAG=' . $magArray[0] . '&MAXMAG=' . $magArray[1];

    $orderDescOrAsc = 'desc';

    if($orderBy === "magnitude") {
	if($orderOrientation === "desc") $orderDescOrAsc='asc';
    }
    	$orderByMag = "$orderByLink&ORDERBY=magnitude&ORDERDESCASC=$orderDescOrAsc\">mag</A>";

    $orderDescOrAsc = 'desc';

    if($orderBy === "evid") {
	if($orderOrientation === "desc") $orderDescOrAsc='asc';
    }
    	$orderByEvid = "$orderByLink&ORDERBY=evid&ORDERDESCASC=$orderDescOrAsc\">evid</A>";

    $orderDescOrAsc = 'desc';

    if($orderBy === "datetime") {
	if($orderOrientation === "desc") $orderDescOrAsc='asc';
    }
    $orderByDatetime = "$orderByLink&ORDERBY=datetime&ORDERDESCASC=$orderDescOrAsc\">time</A>";

    // Configured these columns to sort the catalog upon clicking.  Others can be added if necessary
    echo "  $orderByEvid       $orderByMag      date      $orderByDatetime   et gt pm ir$break";
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
    while (oci_fetch($resultData)) {


            // parse the individual attributes by name
            $evid    = oci_result($resultData, "evid");
            if (oci_field_is_null($resultData, "pm")) {
              $pm = "0";
            } else {
              $pm = "1";   
            }
            $vers    = oci_result($resultData, "vers");
            $mag     = oci_result($resultData, "mag");
            $magtype = oci_result($resultData, "mt");
            $msrc    = oci_result($resultData, "msrc");
            $ot      = oci_result($resultData, "ot");
            $lat     = oci_result($resultData, "lat");
            $lon     = oci_result($resultData, "lon");
            $z       = oci_result($resultData, "z");
            $ndef    = oci_result($resultData, "ndef");
            $rms     = oci_result($resultData, "rms");
            $gap     = oci_result($resultData, "gap");
            $type    = oci_result($resultData, "type");
            $gtype   = oci_result($resultData, "gtype");
            $rflag   = oci_result($resultData, "rflag");
            $src     = oci_result($resultData, "src");
            $town    = oci_result($resultData, "town");

	    // determine if event is in our region
	    
	    $pointLocation = new pointLocation();
	    $outsideRegion = $pointLocation->pointInPolygon("$lat $lon", $inRegionBoundaryPolygon);    

	    if ($outsideRegion === "outside") {
		$inRegion = "n";
	    } else {
	    	$inRegion = "y";
	    }


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

                      foreach ($result2 as $row) {
                         $output .= implode( ",", array_values($row) ) . "\n";
                         $pvers = $row['maxv'];
                         if ( is_null($pvers) || empty($pvers) ) { $pvers = 0; }
   
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

            if ($mag > $redMag) {
              $str  = "<a class=\"bigM\" href=\"#\" onclick=\"selectNewEvidAndScroll($evid, 0); return false;\" id=\"$idname\" $classn>";
              if ( is_null($magtype) ) {
                $str .= sprintf ("%s<b> NL:</b> M%-2s %19s %2s %2s %2s %2s", $evidstr, $magtype, $ot, $type, $gtype, $pm, $inRegion);
              }
              else {
                $str .= sprintf ("%s<b>%4.1f</b> M%-2s %19s %2s %2s %2s %2s", $evidstr, $mag, $magtype, $ot, $type, $gtype, $pm, $inRegion);
              }
//              $str .= "</a>$break";
            }
            else {
              $str  = "<a href=\"#\" onclick=\"selectNewEvidAndScroll($evid, 0); return false;\" id=\"$idname\" $classn>";
              if ( is_null($magtype) ) {
                $str .= sprintf ("%s<b> NL:</b> M%-2s %19s %2s %2s %2s %2s", $evidstr, $magtype, $ot, $type, $gtype, $pm, $inRegion);
              }
              else {
                $str .= sprintf ("%s<b>%4.1f</b> M%-2s %19s %2s %2s %2s %2s", $evidstr, $mag, $magtype, $ot, $type, $gtype, $pm, $inRegion);
              }
//              $str .= "</a>$break";
            }

            $ssrc="";
            if ( $msrc == $src ) {
              $ssrc = sprintf("<i>%4.4s</i>",$src);
//              $ssrc = sprintf("<span style=\"font-style:italic\">%4.4s</span>",$src);
            }
            else {
              $ssrc = sprintf("<font color=\"DarkOrange\"><b>%4.4s</b></font>",$src);
//              $ssrc = sprintf("<span style=\"color:DarkOrange;font-style:normal;font-weight:bold\">%4.4s</span>",$src);
            }

            $svers = 0;
            if ( $havePDL ) {
                    if ( "$pvers" != "$vers" ) {
//                        $svers = sprintf ("<span style=\"color:red\">%2d %2d</span>", $vers, $pvers);
                        $svers = sprintf ("<font color=\"red\">%2d %2d</font>", $vers, $pvers);
                    }
                    else {
//                        $svers = sprintf ("<span style=\"color:green\">%2d %2d</span>", $vers, $pvers);
                        $svers = sprintf ("<font color=\"green\">%2d %2d</font>", $vers, $pvers);
                    }
                    $str .= sprintf (" %s <i>%1s %s %8.4f %9.4f %4.1f %3d %4.2f</i>", $ssrc, $rflag, $svers, $lat, $lon, $z, $ndef, $rms);
            }
            else {
                    // SPECIAL CASE UNLESS PDL product client mysql can be accessed
//                    $svers = sprintf ("<span style=\"color:green\"><b>%2d</b></span>", $vers);
                    $svers = sprintf ("<font color=\"green\"><b>%2d</b></font>", $vers);
                    $str .= sprintf (" %s <i>%1s %s %8.4f %9.4f %4.1f %3d %4.2f</i>", $ssrc, $rflag, $svers, $lat, $lon, $z, $ndef, $rms);
            }


    // Mark if SnapShot file is missing - Added DDG 1/20/06
            if (snapshotExists($evid)) { $str .= " "; }
            else { $str .= "*"; }

            $str .= "$break>$townStr";

            $str .= "</a>$break";

    // ADD A GOOGLE MAP LINK - GG 08/03/2020
            $lat_for_map=sprintf("%7.4f", $lat);
            $lon_for_map=sprintf("%9.4f", $lon);
            $gMap="<a href=\"http://maps.google.com/maps?q=loc:$lat_for_map,+$lon_for_map+(M$magtype=$mag+$ot
            +UTC+Evid+$evid+Gap=$gap+RMS=$rms)&z=10&t=h&output=embed\" Target=\"_new\">Map</a>";
            $str .= "&nbsp;&nbsp;$gMap";

    // ADD A USGS TIMELINE LINK - GG 08/10/2020
            $timelineLink="<a href=\"https://jmfee-usgs.github.io/comcat-timeline/timeline.html#ci$evid\" Target=\"_new\">Timeline</a>";
            $str .= "&nbsp;&nbsp;$timelineLink";

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
   
    echo "</pre>\n"; 
    echo "<a name=\"bottom\"></a>\n";

    // end of catlog contentXXX  div
    echo "</div>\n";

    echo "<div id=\"catFtr\">";

    // The original footer has been replaced by the pagination links and other extensible features
    echo createFooter( $links, 'pagination pagination-sm', $resultClass->limit, $resultClass->page, $resultClass->total, $narrow, $orderBy, $orderOrientation, $magArray, $hrsBack, $dateStart, $dateStop);


    // end of catFtr div
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

// getData handles the query processing, and builds a Class that will hold the data returned along with informative variables for the pagination 
function getData( $limit = 100, $page = 1, $_query, $_total ) {

    if ( $limit == 'all' ) {
	$query = $_query;
    } else {
        $query = "SELECT * FROM (" . $_query . " WHERE ROWNUM <= " . ($page * $limit) . ") WHERE rnum >= " . ( ( $page - 1 ) * $limit );
    }

    $results = db_query_glob($query);

    $queryResult         = new stdClass();
    $queryResult->page   = $page;
    $queryResult->limit  = $limit;
    $queryResult->total  = $_total;
    $queryResult->data   = $results;
 
    return $queryResult;
}

// createFooter handles creating the pagination links as well as the other tools in the footer
function createFooter( $links, $list_class, $_limit, $_page, $_total, $narrow, $orderVar, $orderOrient, $magArray = NULL, $hrsBack = NULL, $dateStart = NULL, $dateStop = NULL ) {
    if ( $_limit == 'all' ) {
        return '';
    }

    $params = array("NARROW=$narrow",
    "PAGELIMIT=$_limit",
    "ORDERBY=$orderVar",
    "ORDERDESCASC=$orderOrient", 
    (isset($magArray) ? 'MINMAG=' . $magArray[0] : NULL),
    (isset($magArray) ? 'MAXMAG=' . $magArray[1] : NULL),
    (isset($hrsBack) ? 'HRSBACK=' . $hrsBack : NULL),
    (isset($dateStart) ? 'DATESTART=' . $dateStart : NULL),
    (isset($dateStop) ? 'DATESTOP=' . $dateStop : NULL)
    );


    $last       = ceil( $_total / $_limit );
 
    $start      = ( ( $_page - $links ) > 0 ) ? $_page - $links : 1;
    $end        = ( ( $_page + $links ) < $last ) ? $_page + $links : $last;
 
    $html       = '<form action="catalog.php" method="get"><ul class="' . $list_class . '">';
 
    $class      = ( $_page == 1 ) ? "disabled" : "";

    $html       .= '<li class="' . $class . '"><a onclick="javascript: window.location = \'';
    for( $a = 0; $a < count($params); $a++) {
	$html .= ($a == 0) ? '?' : '&';
	$html .= $params[$a]; 	
    }
    $html .= '&PAGE=' . ( $_page - 1 ) . '\';">&laquo;</a></li>';
 
    if ( $start > 1 ) {
        $html   .= '<li><a href="';
    	for( $a = 0; $a < count($params); $a++) {
		$html .= ($a == 0) ? '?' : '&';
		$html .= $params[$a]; 	
    	}
    	$html   .= '&PAGE=1">1</a></li>';
        $html   .= '<li class="disabled"><span>...</span></li>';
    }
 
    for ( $i = $start ; $i <= $end; $i++ ) {
        $class  = ( $_page == $i ) ? "active" : "";
        $html   .= '<li class="' . $class . '"><a href="';
    	for( $a = 0; $a < count($params); $a++) {
		$html .= ($a == 0) ? '?' : '&';
		$html .= $params[$a]; 	
    	}
	$html .= '&PAGE=' . $i . '">' . $i . '</a></li>';
    }
 
    if ( $end < $last ) {
        $html   .= '<li class="disabled"><span>...</span></li>';
        $html   .= '<li><a href="';
    	for( $a = 0; $a < count($params); $a++) {
		$html .= ($a == 0) ? '?' : '&';
		$html .= $params[$a]; 	
    	}
	$html .= '&PAGE=' . $last . '">' . $last . '</a></li>';
    }
 
    $class      = ( $_page == $last ) ? "disabled" : "";
    $html       .= '<li class="' . $class . '"><a href="';
    for( $a = 0; $a < count($params); $a++) {
	$html .= ($a == 0) ? '?' : '&';
	$html .= $params[$a]; 	
    }
    $html .= '&PAGE=' . ( $_page + 1 ) . '">&raquo;</a></li>';

// ----CUSTOM FEATURES START----

    $html	.= '&nbsp;Page result limit';

    for( $b = 1; $b <= 4; $b++ ) {
	switch ($b) {
	   case 1:
	      $limitSelect = 100;
	      break;
	   case 2:
	      $limitSelect = 200;
	      break;
	   case 3:
	      $limitSelect = 300;
	      break;
	   case 4:
	      $limitSelect = 500;
	      break;
	}
	$html .= '&nbsp;<a href="?NARROW=' . $narrow . '&PAGELIMIT=' . $limitSelect;
    for( $a = 2; $a < count($params); $a++) {
	$html .= '&' . $params[$a]; 	
    }
	$html .= '&PAGE=1">' . $limitSelect . '</a>';
    }
	
    $html	.= '&nbsp;&nbsp;&nbsp;&nbsp;Min Mag: <input type="text" name="MINMAG" maxlength="5" value="' . $magArray[0] . '" size ="5">';
    $html	.= '&nbsp;&nbsp;Max Mag: <input type="text" name="MAXMAG" maxlength="5" value="' . $magArray[1] . '" size ="5">&nbsp;&nbsp;';
    $html	.= '&nbsp;&nbsp;&nbsp;&nbsp;<A HREF="#" onClick="cal1.select(document.forms[0].DATESTART,\'anchor1\',\'MM/dd/yyyy\'); return false;" TITLE="cal1.select(document.forms[0].DATESTART,\'anchor1\',\'MM/dd/yyyy\'); 
		   return false;" NAME="anchor1" ID="anchor1">Start Date:</A> <input name="DATESTART" type="text" value="' . $dateStart . '"size ="10" />&nbsp;&nbsp;';
    $html	.= '&nbsp;&nbsp;&nbsp;&nbsp;<A HREF="#" onClick="cal1.select(document.forms[0].DATESTOP,\'anchor2\',\'MM/dd/yyyy\'); return false;" TITLE="cal1.select(document.forms[0].DATESTOP,\'anchor2\',\'MM/dd/yyyy\'); 
		   return false;" NAME="anchor2" ID="anchor2">End Date:</A> <input name="DATESTOP" type="text" value="' . $dateStop . '"size ="10" />&nbsp;&nbsp;';
    $html	.= '&nbsp;&nbsp;Search by Event ID: <input type="text" name="EVIDSEARCH" maxlength="8" value="0" size="8">&nbsp;&nbsp;';

//    TAKING OUT "HOURS BACK" FEATURE AND REPLACE WITH DATE PICKER
//    $html	.= '&nbsp;&nbsp;&nbsp;&nbsp;Hours Back: <input type="text" name="HRSBACK" maxlength="4" value="' . $hrsBack . '" size ="4">&nbsp;&nbsp;';
    $html	.= '<input type="submit" value="Submit"';

    $html       .= '</ul></form>';

    return $html;
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
