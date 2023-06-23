<?php

// display prefor associated phases for an event
// residual plot followed by the phase list <pre> formatted

// required libraries for plot
require_once 'phplot.php';

// required libraries for dbase globals and config
include_once "phpmods/config.php";
include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";

######################################################################################

$evid = isset( $_REQUEST['EVID'] ) ? $_REQUEST['EVID'] : 0;
db_connect_glob(); // connect to dbase using info from db_conn.php
format_page($evid); // format the results as html
db_logoff(); // db disconnect

######################################################################################
function format_page($_evid) {

  // The dbase query
  $sql_query = "Select a.NET,a.STA,a.SEEDCHAN,decode(a.LOCATION,'  ','--',a.LOCATION),
        TrueTime.getStringf(a.DATETIME), 
        o.IPHASE, o.SUBSOURCE, a.FM, a.QUALITY, a.RFLAG, a.QUAL,
        o.IMPORTANCE,o.DELTA,o.SEAZ,o.IN_WGT,o.WGT,TIMERES,o.EMA,o.SCORR,o.SDELAY
        from Arrival a, AssocArO o, Event e where 
        a.ARID = o.ARID and o.ORID=e.PREFOR and e.EVID=";

  $sql_query .= $_evid;

  $sql_query .= "\norder by o.DELTA,a.iphase,a.DATETIME,a.NET,a.STA,a.LOCATION,a.SEEDCHAN";

  #echo "$sql_query\n\n";
  // execute the query
  $result = db_query_glob($sql_query);

  // array to contain list of phase data strings
  $phaselist = array();
  $iphases = 0;
  // start phase list with a title
  $phaselist[$iphases] = "sta   nt chl lc desc date       time            dist resid delay iwgt owgt azm ema imprt src f";
  $iphases++;

  // for each row returned parse the individual attributes by name
  // need to add bogus points to get the bubble sizing range correct
  // set flag 1 when input importance has min 0 or max 1
  $minPI = 1;
  $maxPI = 0;

  // separate plot arrays for P and S data
  $dataP = array ();
  $dataS = array ();

  // array row idx counter
  $ip = 0;
  $is = 0;

  // get phase data
  while (OCIFetch($result)) {
    $icol = 1;
    $net       = OCIResult($result,$icol++);
    $sta       = OCIResult($result,$icol++);
    $chan      = OCIResult($result,$icol++);
    $loc       = OCIResult($result,$icol++);
    $datetime  = OCIResult($result,$icol++);
    $phase     = OCIResult($result,$icol++);
    $source    = OCIResult($result,$icol++);
    $cd        = OCIResult($result,$icol++);
    $quality   = OCIResult($result,$icol++);
    $rflag     = OCIResult($result,$icol++);
    $eiw       = OCIResult($result,$icol++);
    $import    = OCIResult($result,$icol++);
    $delta     = OCIResult($result,$icol++);
    $seaz      = OCIResult($result,$icol++);
    $in_wgt    = OCIResult($result,$icol++);
    $wgt       = OCIResult($result,$icol++);
    $timeres   = OCIResult($result,$icol++);

    // append tag to output string, it shows relative residual size
    $resid_tag = '';
    $resid_tag2 = '';
    $resid = $timeres; 
    $len = abs(round(($resid/0.25),0,PHP_ROUND_HALF_UP));
    if ( $len > 20 ) {
      $len=20;
      $resid_tag2 = '*>';
    }
    if ($resid < -2. ) {
      $resid_tag = '-';
      $resid_tag = str_pad($resid_tag, $len, "-", STR_PAD_RIGHT);
      $resid = -2.01;
    }
    elseif ( $resid > 2.0 ) {
      $resid_tag = '+';
      $resid_tag = str_pad($resid_tag, $len, "+", STR_PAD_RIGHT);
      $resid = 2.02;
    }
    else if ( $resid > 0. ) {
      $resid_tag = '+';
      $resid_tag = str_pad($resid_tag, $len, "+", STR_PAD_RIGHT);
    }
    else if ( $resid < 0. ) {
      $resid_tag = '-';
      $resid_tag = str_pad($resid_tag, $len, "-", STR_PAD_RIGHT);
    }
    else {
      $resid_tag = '0';
    }
    $resid_tag .= $resid_tag2;

    $ema       = OCIResult($result,$icol++);
    $scorr     = OCIResult($result,$icol++);
    $sdelay    = OCIResult($result,$icol++);

    if ( $phase == 'P' ) {
      $dataP[$ip] = array($sta,$delta,$resid,$import);
      $ip++;
      if ($import > $maxPI) {
        $maxPI = $import;
      }
      elseif ($import < $minPI) {
        $minPI = $import;
      }
    }
    else {
      $dataS[$is] = array($sta,$delta,$resid,$import);
      $is++;
    }
    // Added bogus points out of bound to get the S bubble sizing relative to P size
    $dataS[$is]   = array('',9999,0.,$maxPI);
    $dataS[$is+1] = array('',9999,0.,$minPI);

    // do some pre-formatting
    $wt = 4;

    if ($quality >= 1) {
      $wt = 0;
    } elseif ($quality >= 0.75) {
      $wt = 1;
    } elseif ($quality >= 0.50) {
      $wt = 2;
    } elseif ($quality >= 0.25) {
      $wt = 3;
    } 
 
    $fm = " ";
    if ($cd == "d.") {
      $fm = "D";
    } elseif ($cd == "c.") {
      $fm = "U";
    }
    $str = sprintf("%-5s %2s %3s %2s %1s%1s%1s%1d %s", $sta, $net, $chan, $loc, $eiw, $phase, $fm, $wt, $datetime);
    $str .= sprintf(" %6.1f %5.2f %5.2f %4.2f %4.2f %3.0f %3.0f %4.3f %3.3s %1s",
              $delta, $timeres, $sdelay, $in_wgt, $wgt, $seaz, $ema, $import, $source, $rflag);

    $phaselist[$iphases] = "$str $resid_tag";
    $iphases++;

  } // end of while loop
  
  $str = implode("\n",$phaselist); 

  // Now make the plot
  makeResidualPlot($_evid, $dataP, $dataS, $str);

} // end of function

#############################################################################
// Callback to switch bubble color to alternate when the residual is out of plot y-axis bounds
function pickcolor( $_img, $_data, $_row, $_col ) {
     if  ( $_data[$_row][3] == 0. ) {
       return 2; 
     }
     return (( $_data[$_row][2] > 2. || $_data[$_row][2] < -2. ) ? 1 : 0); 
}
#############################################################################

function makeResidualPlot( $_evid, $_dataP, $_dataS, $str ) {

  $plot = new PHPlot_truecolor(800, 300);
  $plot->SetPrintImage(False);

  $catline = makeCatLine($_evid);
  $catline2 = "\nP(green) and S(red) Residuals Scaled By Importance (aqua,purple when imprt=0)";
  $plot->SetTitle($catline . $catline2); // 
  $plot->SetFontGD('title',3,3);
  #
  #$plot->SetTTFPath('/usr/openwin/lib/X11/fonts/TrueType');
  #$plot->SetDefaultTTFont('LiberationSans-Regular.ttf');
  #$plot->SetFontTTF('title','LiberationMono-Regular.ttf',12);
  #$plot->SetUseTTF(True);
  #
  $plot->SetPlotAreaWorld(0.,-2.,NULL,2.);
  $plot->SetPlotType('bubbles');
  $plot->SetDataType('data-data-xyz');
  #
  $plot->SetDataValues($_dataP);
  ##$plot->SetYDataLabelType('custom', 'fmt_label', $_dataP);
  ##$plot->SetXDataLabelType('custom', 'fmt_label', $_dataP);
  ##$plot->SetDrawXDataLabelLines(False);
  ##$plot->SetXDataLabelPos('plotin');
  #
  #$plot->SetDataColors(array('green','DarkGreen', 'aquamarine1'), NULL, 0);
  #00FF00 array(0,255,0,0) Green
  #1E7219 array(30,114,25,0) Dk Green
  #05FCB6 array(5,252,182,0) Aqua
  $plot->SetDataColors(array(array(0,255,0,0), array(30,114,25,0), array(5,252,182,0)));

  $plot->SetCallback('data_color', 'pickcolor', $_dataP);
  $plot->SetDrawPlotAreaBackground(True);
  #$plot->SetPlotBgColor('yellow');
  $plot->SetLightGridColor('gray'); // Change grid color
  $plot->SetImageBorderType('plain');
  $plot->SetPlotBorderType('full');
  #
  $plot->SetXTickIncrement(20); // For grid line spacing
  $plot->SetYTickIncrement(.5);
  $plot->SetXTickPos('both'); // Tick marks on both sides
  $plot->SetYTickPos('both'); // Tick marks on top and bottom too
  #
  $plot->SetYTickLabelPos('both'); // Y axis labels left and right
  $plot->SetXTickLabelPos('plotdown');
  #
  $plot->SetXTitle('Distance (KM)');
  $plot->SetYTitle('Seconds');
  $plot->SetDrawXGrid(True);
  $plot->DrawGraph();
  $plot->RemoveCallback('data_color');
  # 
  $plot->SetDataValues($_dataS);
  # Enable transparency for S colors (e.g. 60 of 127) value:
  #$plot->SetDataColors(array('red','maroon','purple'), NULL, 60); // Use same color for all data sets
  #FF0000 array(255,0,0,60) red
  #B61C5A array(182,28,90,60) maroon
  #985FB6 array(152,95,182,60) purple
  $plot->SetDataColors(array(array(255,0,0,60), array(182,28,90,60), array(152,95,182,60)));
  $plot->SetCallback('data_color', 'pickcolor', $_dataS);
  $plot->SetDrawPlotAreaBackground(False);
  $plot->SetYTickLabelPos('both'); // Y axis labels left and right
  $plot->SetXTickLabelPos('none'); // no X axis labels
  $plot->SetXTickIncrement(5.);
  $plot->SetXTitle('');
  $plot->DrawGraph();
  $plot->RemoveCallback('data_color');
  
  ##$plot->PrintImage();

  // Output html text
  echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML//EN\">";
  echo "<html>";
  echo "<head>\n";
  echo "<title>PhaseList</title>\n";
  echo "</head>\n";
  echo "<body>";
  echo "<img src=\"" . $plot->EncodeImage() . "\" alt=\"No residual plot for event $_evid\">";
  echo "<hr><p><h3>Phase Listing for $_evid</h3>";
  echo "<pre>";
  echo "$catline\n";
  echo "$str";
  echo"</pre>";
  echo "</body>";
  echo "</html>";

}

#############################################################################
function makeCatLine($_evid) {

    $sql_query = "Select Event.evid, NetMag.magnitude, NetMag.magtype, 
        TrueTime.getString(Origin.datetime), 
        Origin.lat,Origin.lon, Origin.depth,Origin.ndef,
        Origin.wrms, Origin.gap, Origin.nbfm, Event.etype, Origin.gtype, Origin.rflag, Origin.subsource,
        Event.version
        from Event, Origin, NetMag WHERE
        (Event.prefor = Origin.orid(+)) and (Event.prefmag = NetMag.magid(+))
        and Event.evid = $_evid";

    //DEBUG echo "$sql_query\n\n";
    // execute the query
    $result = db_query_glob($sql_query);

    $str = "ID         MAG      DATE       TIME(UTC)     LAT        LON       Z  PHS   RMS GAP NFM ET GT RF  SRC  V\n";
    while (OCIFetch($result)) {
       $icol = 1;
       $id      = OCIResult($result,$icol++);
       $mag     = OCIResult($result,$icol++);
       $magtype = OCIResult($result,$icol++);
       $ot      = OCIResult($result,$icol++);
       $lat     = OCIResult($result,$icol++);
       $lon     = OCIResult($result,$icol++);
       $z       = OCIResult($result,$icol++);
       $nph     = OCIResult($result,$icol++);
       $rms     = OCIResult($result,$icol++);
       $gap     = OCIResult($result,$icol++);
       $nfm     = OCIResult($result,$icol++);
       $etype   = OCIResult($result,$icol++);
       $gtype   = OCIResult($result,$icol++);
       $rflag   = OCIResult($result,$icol++);
       $src     = OCIResult($result,$icol++);
       $vers    = OCIResult($result,$icol++);
            // NOTE: php rounding math is wrong, so add 0.000001 to $mag to force a round up, but
            // better would be to instead  change default precision=14 to 17 in installed php lib:
            // /usr/local/php/lib/php.ini -aww 20150123
            $mag += .000001;

       $str .= sprintf("%-10.10d %3.2f M%s %21s %8.5f %9.5f %6.1f %4d %5.2f %3d %3d %2s %2s %2s %4.4s %02d\n",
        $id,$mag,$magtype,$ot,$lat,$lon,$z,$nph,$rms,$gap,$nfm,$etype,$gtype,$rflag,$src,$vers);
    }

    return $str;
}

#############################################################################
# Custom label formatting function
function fmt_label($value, $data, $row, $column ) {
    return $data[$row][0];
}

?>
