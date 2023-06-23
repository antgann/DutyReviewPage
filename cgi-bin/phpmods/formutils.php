<?php

/*
  Utility functions for building and processing forms.

USE:

  include "phpmods/formutils.php";

  DDG 5/05


The datetime form variables are:  $yr, $mo3, $dy, $hr, $mn, $fsec
*/

include_once "phpmods/seismic.php";  // for scsn_DateTime()
include_once "phpmods/config.php";

// -----------------------------------------
function numberChooserListStr ($_start, $_end, $_selected) {
    $str;

    $n = $_start;
    do {
        if ($n == $_selected) {
            $str .= "<option value=\"$n\" SELECTED >$n</option>\n";
        } else {
            $str .= "<option value=\"$n\" >$n</option>\n";
        }
        $n++;
    } while ($n < ($_end + 1));

    return $str;
}


// -----------------------------------------
function secondChooserStr ($_sel,$ievt ) {

    $str  = "<select $ievt name=\"fsec\" size=\"1\" title=\"Select Second\">";
    $str .= numberChooserListStr( 0, 60, $_sel);
    $str .=  "</select>";
    return $str;
}


// -----------------------------------------
function minuteChooserStr ($_sel,$ievt ) {

    $str  =  "<select $ievt name=\"mn\" size=\"1\" title=\"Select Minute\">";
    $str .= numberChooserListStr( 0, 60, $_sel);
    $str .=  "</select>";
    return $str;
}


// -----------------------------------------
function hourChooserStr ($_sel,$ievt ) {

    $str  = "<select $ievt name=\"hr\" size=\"1\" title=\"Select Hour\">";
    $str .= numberChooserListStr( 0, 24, $_sel);
    $str .=  "</select>";
    return $str;
}


// -------------------------------
function dayChooserStr ($_sel, $ievt ) {

    $str  = "<select $ievt name=\"dy\" size=\"1\" title=\"Select Day of Month\">";
    $str .= numberChooserListStr( 1, 31, $_sel);
    $str .=  "</select>";
    return $str;
}


// -------------------------------
function monthChooserStr ($_sel, $ievt ) {

//$month = array ("Jan", "Feb", "Mar", "Apr", "May", "Jun",
//                "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

$dt = new scsn_DateTime(0);
$month = $dt->getMonthArray();

$str = "<select $ievt  name=\"mo3\" size=\"1\" title=\"Select Month\">";
foreach ($month as $m) {
//   if ($m == $_sel) {
   if (strcasecmp( $m, $_sel) == 0) {
     $str .=  "<option value=\"$m\" SELECTED >$m</option>\n";
   } else {
     $str .=  "<option value=\"$m\" >$m</option>\n";
   }

}

$str .=  "</select>";
return $str;
}

// -------------------------------
function yearChooserStr ($_sel,  $ievt ) {
  $end = date("Y");  // current year
  $start   = 1932;
  $str = "<select $ievt  name=\"yr\" size=\"1\" title=\"Select Year\">";
  $str .= numberChooserListStr( $start, $end, $_sel, $ievt );
  $str .="</select>";
  return $str;
}

// -------------------------------
// Return a string
function eventTypeChooserStr ($_sel, $ievt ) {

    global $eventTypes; // from config.php -aww
    $str ="<select $ievt  name=\"etype\" size=\"1\" title=\"Event Type\">";
    foreach ($eventTypes as $type) {
        if ($type == $_sel) {
            $str .= "<Option Selected> $type </Option>"."\n";
        } else {
            $str .=  "<Option> $type </Option>"."\n";
        }
    }

    $str .=  "</select>";

    return $str;
}

function eventGTypeChooserStr ($_sel, $ievt ) {

    global $eventGTypes; // from config.php -aww
    $str ="<select $ievt  name=\"gtype\" size=\"1\" title=\"Event GType\">";
    foreach ($eventGTypes as $gtype) {
        if ($type == $_sel) {
            $str .= "<Option Selected> $gtype </Option>"."\n";
        } else {
            $str .=  "<Option> $gtype </Option>"."\n";
        }
    }

    $str .=  "</select>";

    return $str;
}


// --------------------------------
// Make a chooser for event types
// $_sel is the current event type to be selected by default
function makeTypeOptionList($_sel, $_evid) {
    global $eventTypes;  // from config.php
    $str = "";

  // make the html list
    while ($curr = each($eventTypes)) {
        $short = $curr['key'];
        $long  = $curr['value']." (".$short.")";

        if ($short == $_sel) {
            $str .= "<OPTION Selected Value=\"$short\">$long\n";
        } else {
            $str .= "<OPTION Value=\"$short\">$long\n";
        }
   }
   return $str;
}

// --------------------------------
// Make a chooser for event types
// $_sel is the current event type to be selected by default
function makeGTypeOptionList($_sel, $_evid) {
    global $eventGTypes;  // from config.php
    $str = "";

  // make the html list
    while ($curr = each($eventGTypes)) {
        $short = $curr['key'];
        $long  = $curr['value']." (".$short.")";

        if ($short == $_sel) {
            $str .= "<OPTION Selected Value=\"$short\">$long\n";
        } else {
            $str .= "<OPTION Value=\"$short\">$long\n";
        }
   }
   return $str;
}

// -------------------------------
// Return a string w/ HTML for an event subsource chooser
function eventSrcChooserStr ($_sel, $rtn_var, $ievt ) {
    $list = array ('HAND', 'Jiggle', 'MENLO', 'MUNG', 'NEIC', 'RT1', 'RT2', 'UNR', 'unknown');

    $str ="<select $ievt name=$rtn_var size=\"1\" title=\"Select Source\">";
    foreach ($list as $type) {
        if ($type == $_sel) {
            $str .= "<Option Selected> $type </Option>"."\n";
        } else {
            $str .=  "<Option> $type </Option>"."\n";
        }
    }
    $str .=  "</select>";

    return $str;
}


// -------------------------------
function getDateChooser($yr, $mo3, $dy, $ievt) {
    return "<TD>".monthChooserStr($mo3, $ievt )."</TD><TD>".
              dayChooserStr($dy, $ievt)."</TD><TD>".
              yearChooserStr($yr, $ievt)."</TD>";
}


// -------------------------------
function getDateTimeChooser($yr, $mo3, $dy, $hr, $mn, $fsec, $ievt) {
    $dateChooser = getDateChooser($yr, $mo3, $dy, $ievt);

$str = <<<EOT
<TR>
$dateChooser
</TR>

 <TR>
  <TD> Hour </TD> 
  <TD> Min </TD> 
  <TD> Sec  </TD>
 </TR>
<TR>
 <TD>
    <INPUT TYPE=text NAME=hr SIZE=1  MaxLength=2 $ievt Value="$hr">	
 </TD>
 <TD>
    <INPUT TYPE=text NAME=mn SIZE=1 MaxLength=2 $ievt  Value="$mn" >
 </TD>
 <TD>
    <INPUT TYPE=text NAME=fsec SIZE=5 $ievt Value="$fsec" >
 </TD>
 <TD>
 </TR>
EOT;

return $str;
}
// -------------------------------
// Decimal lat/lon/z
function getLatLonDecChooser ($lat, $lon, $depth, $ievt) {

return <<<EOT
<TR>
 <TD>
    <INPUT TYPE=text NAME=lat ID=lat SIZE=9 $ievt Value="$lat" >
 </TD>
 <TD>
    <INPUT TYPE=text NAME=lon ID=lon SIZE=10 $ievt Value="$lon">
 </TD>
 <TD>
    <INPUT TYPE=text NAME=depth ID=depth SIZE=4 $ievt Value="$depth" >
 </TD>
 </TR>
EOT;
}
// -------------------------------
// Deg/min lat/lon
// $ievt is an intrinsic event like "onClick="someFunction(this)"
function getLatLonDMChooserlong ($latd, $latm, $lond, $lonm, $depth, $ievt) {

// format things
$latm4 = round ($latm, 2);   // 2 decimal places
$lonm4 = round ($lonm, 2);   // 2 decimal places
$fmt = "%2.2f";
$depthStr = sprintf($fmt, $depth);

// Set no MaxLength on the degrees part to allow including decimal part.

return <<<EOT
<TR>
 <TD>
    <INPUT TYPE=text NAME=latd ID=latd SIZE=3 $ievt Value="$latd" >
    <INPUT TYPE=text NAME=latm ID=latm SIZE=5 $ievt Value="$latm4" >
 </TD>
 <TD>
    <INPUT TYPE=text NAME=lond ID=lond SIZE=3 $ievt Value="$lond" >
    <INPUT TYPE=text NAME=lonm ID=lonm SIZE=5 $ievt Value="$lonm4" >
 </TD>
 <TD>
    <INPUT TYPE=text NAME=depth ID=depth SIZE=4 $ievt Value="$depthStr" >
 </TD>
 </TR>
EOT;
}
// -------------------------------
// Deg/min lat/lon
// $ievt is an intrinsic event like "onClick="someFunction(this)"
function getLatLonDMChooser ($latd, $latm, $lond, $lonm, $depth, $ievt) {

// format things
$fmt = "%2.2f";
$latmStr = sprintf($fmt, $latm);
$lonmStr = sprintf($fmt, $lonm);

return <<<EOT
<TR>
 <TD>
    <INPUT TYPE=text NAME=latd ID=latd SIZE=1 $ievt Value="$latd">
    <INPUT TYPE=text NAME=latm ID=latm SIZE=4 $ievt Value="$latmStr">
 </TD>
 <TD>
    <INPUT TYPE=text NAME=lond ID=lond SIZE=2 $ievt Value="$lond">
    <INPUT TYPE=text NAME=lonm ID=lonm SIZE=4 $ievt Value="$lonmStr">
 </TD>

 </TR>
EOT;
}
// -------------------------------
// both types - dec & deg/min
function getLatLonComboChooser ($lat, $lon, $depth, $ievt ) {

	$latd = (int) $lat;    // int part
	$latm = abs($lat - $latd)*60.0;

	$lond = (int) $lon;
	$lonm = abs($lon - $lond)*60.0;

  // header
  $str  = "<TR> <TD> Lat </TD>  <TD> Lon </TD>  <TD> Depth  </TD> </TR>";
  $str .= getLatLonDecChooser ($lat, $lon, $depth, $ievt);
  $str .= getLatLonDMChooser ($latd, $latm, $lond, $lonm, $depth, $ievt);
  return $str;
}// -------------------------------
// only  deg/min
function getLatLonComboChooser2 ($lat, $lon, $depth, $ievt) {

	$latd = (int) $lat;    // int part
	$latm = abs($lat - $latd)*60.0;

	$lond = (int) $lon;
	$lonm = abs($lon - $lond)*60.0;

  // header
  $str  = "<TR> <TD> Latitude </TD>  <TD> Longitude </TD>  <TD> Depth (km) </TD> </TR>";
  $str .= getLatLonDMChooserLong ($latd, $latm, $lond, $lonm, $depth, $ievt);
  return $str;
}
// -------------------------------
// validation
// -------------------------------
// For epoch time only check is if its in the future
function epochtimeIsValid ($epochtime) {

  // in future?
  $now = gmmktime() ;  // returns INT epoch secs (good enough)

  return ($now < $epochtime);
 
}
// -------------------------------
// returns FLOAT epoch seconds
function gmmktime_float ($hr, $mn, $fsec, $mo, $dy, $yr) {
  $isec = (int)$fsec;
  $frac = $fsec - $isec;

  return (gmmktime($hr. $mn, $isec, $mo, $dy, $yr) + $frac);
}
// -------------------------------
// assumes GMT in
function gmmktime_float3 ($hr, $mn, $fsec, $mo3, $dy, $yr) {
  $isec = (int)$fsec;
  $frac = $fsec - $isec;

  $offset = strtotime("1970-01-01 00:00:00"); // strtotime() will give LOCAL so must correct

  $timestr = simpleTimeStr ($hr, $mn, $isec, $mo3, $dy, $yr);

 // NOTE: strtotime($timestr) can't handle fractional seconds (returns -1)
  return (strtotime($timestr) + $frac );
}

// -------------------------------
// int secs only!
function simpleTimeStr ($hr, $mn, $fsec, $mo3, $dy, $yr) {
   return $mo3." ".$dy." ".$yr." ".$hr.":".$mn.":".(int)$fsec;
}
// -------------------------------
// return current float epoch secs
function microtime_float() {
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}
// ----------------------------
// Dump an associative array - for debugging
function dumpArray ($_array) {

    $keys = array_keys($_array);
    for($index=0;$index<count($keys);$index++){

    $temp_key=$keys[$index];

    $temp=$_array[$temp_key];

    echo "name = [$temp_key] value = \"$temp\" <br>" ;

    }
}

// Mag related stuff

// NOTE: this is just the chooser list and must be wrapped in <Select></Select> tags.
// It's done this way so caller has control of the name of the form variable.
function magTypeChooserList($_sel) {

$list = array ('w','b','c','e','h','l','r','s','d','?');
return genericChooserList($list, $_sel);

}

// NOTE: this is just the chooser list and must be wrapped in <Select></Select> tags.
// It's done this way so caller has control of the name of the form variable.
function magAlgoChooserList($_sel) {

$list = array ('HAND', 'CMT', 'SoCalMl', 'SoCalMd', 'MENLO', 'CodaAmplitude', 'MUNG', 'NEIC', 'sammag', 'trimag', 'TMTS', 'UNR', 'unknown');
return genericChooserList($list, $_sel);

}

// Generic choose list creator - Marks $_sel as selected
function genericChooserList ($list, $_sel) {

$str = "";
foreach ($list as $type) {
  if ($type == $_sel) {
    $str .= "<Option Selected> $type </Option>"."\n";
  } else {
    $str .= "<Option> $type </Option>"."\n";
  }
}
  return $str;
}

?>
