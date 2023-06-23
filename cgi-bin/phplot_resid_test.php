<?php
# PHPlot Example - Bubble Plot

require_once 'phplot.php';

# Custom label formatting function: Return an empty string, unless this is
# the largest value in the row.
function fmt_label($value, $data, $row, $column )
{
    return $data[$row][0];
}

# Plot annotation callback.
# The pass-through argument is the PHPlot object.
function annotate_plot($img, $plot)
{
    # Allocate our own colors, rather than poking into the PHPlot object:
    $red = imagecolorresolve($img, 255, 0, 0);
    $green = imagecolorresolve($img, 0, 216, 0);

    # Get the pixel coordinates of the data points for the best and worst:
    list($best_x, $best_y) = $plot->GetDeviceXY(0., -2.);
    #list($worst_x, $worst_y) = $plot->GetDeviceXY($worst_index, $worst_sales);

    # Place some text above the points:
    $font = '3';
    $fh = imagefontheight($font);
    $fw = imagefontwidth($font);
    imagestring($img, $font, $best_x, $best_y+30, 'Good Job!', $green);
    imagestring($img, $font, $best_x, $best_y+60, 'Good Job!', $green);
    imagestring($img, $font, $best_x, $best_y+90, 'Good Job!', $green);
    imagestring($img, $font, $best_x, $best_y+120, 'Good Job!', $green);

    # We can also use the PHPlot internal function for text.
    # It does the center/bottom alignment calculations for us.
    # Specify the font argument as NULL or '' to use the generic one.
    #$plot->DrawText('', 0, $worst_x, $worst_y-10, $red, 'Bad News!', 'center', 'bottom');
}

$data = array(
    array('DEV',3.5,0.31,3),
    array('DEV',3.5,0.51,1),
    array('MSC',11.4,-0.23,2),
    array('WWC',14.3,-0.28,2),
    array('BLA2',19.6,-0.29,1),
    array('RMR',27.6,-0.23,1),
    array('KNW',31.1,-0.01,1),
    array('B081',31.5,-0.02,1),
    array('PMD',39.1,-0.28,1),
    array('PFO',40.4,-2.33,0),
    array('DNR',44.7,-0.04,0),
    array('JVA',44.9,-0.19,1),
    array('POB2',45.5,-0.08,0),
    array('SND',46.1,-0.03,0),
    array('CRY',47.3,-0.01,0),
    array('LUC2',66.0,0.33,1),
    array('PALA',77.4,0.81,0),
    array('MTG',85.5,0.34,0),
    array('SS2',90.6,-0.39,1),
    array('SS2',90.6,-0.39,1)
);

$data2 =  array(
    array('DEV',3.5,0.11,5),
    array('TST',9.5,2.,0),
    array('MSC',11.4,0.21,5),
    array('WWC',14.3,2.28,3),
    array('BLA2',19.6,-0.08,3),
    array('PMD',39.1,0.21,2),
    array('JVA',44.9,0.89,2),
    array('POB2',45.5,-0.09,3),
    array('BBR',46.8,-0.18,4),
    array('HMT2',49.7,0.16,2)
);

$plot = new PHPlot(900, 400);
$plot->SetPrintImage(False);
$leg = array('P phase');
$evid = isset( $_REQUEST['EVID'] ) ? $_REQUEST['EVID'] : '';
#"37044215";
$catline = shell_exec("/app/aqms/www/review/cgi-bin/catLineWrapper.sh $evid");
$catline .= "P(red) and S(blue) Residuals Scaled By Importance";
$plot->SetTitle($catline); // 
$plot->SetFontGD('title',3,3);
#
#$plot->SetTTFPath('/usr/openwin/lib/X11/fonts/TrueType');
#$plot->SetDefaultTTFont('LiberationSans-Regular.ttf');
#$plot->SetFontTTF('title','LiberationMono-Regular.ttf',12);
#$plot->SetUseTTF(True);
#
#$plot->SetMarginPixels(NULL,NULL,NULL,500);
$plot->SetPlotAreaWorld(0.,-2.,NULL,2.);
$plot->SetDataType('data-data-xyz');
$plot->SetDataValues($data);
##$plot->SetYDataLabelType('custom', 'fmt_label', $data);
##$plot->SetXDataLabelType('custom', 'fmt_label', $data);
##$plot->SetDrawXDataLabelLines(False);
##$plot->SetXDataLabelPos('plotin');
#
$plot->SetPlotType('bubbles');
$plot->SetDataColors(array('red', 'green'));
  $_data = &$data;
$plot->SetCallback('data_color', 'pickcolor', $data);
$plot->SetDrawPlotAreaBackground(True);
#$plot->SetPlotBgColor('yellow');
$plot->SetLightGridColor('gray'); // Change grid color to make it visible
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

##$plot->SetPlotType('bubbles');
$plot->SetDataValues($data2);
$plot->SetDataColors(array('yellow','blue')); // Use same color for all data sets
#  $plot->SetDataColors('blue'); // Use same color for all data sets
  $_data = &$data2;
$plot->SetDrawPlotAreaBackground(False);
#$plot->SetLightGridColor('gray'); // Change grid color to make it visible
#$plot->SetImageBorderType('plain');
#$plot->SetPlotBorderType('full');
#$plot->SetXTickIncrement(20); // For grid line spacing
#$plot->SetYTickIncrement(.5);
#$plot->SetXTickPos('both'); // Tick marks on both sides
#$plot->SetYTickPos('both'); // Tick marks on top and bottom too
#$plot->SetXDataLabelPos('plotin'); // X axis data labels top and bottom
$plot->SetYTickLabelPos('both'); // Y axis labels left and right
$plot->SetXTickLabelPos('none'); // no X axis labels
$plot->SetXTickIncrement(5.);
$plot->SetXTitle('');
#$plot->SetDrawXGrid(true);
#$plot->SetDrawYGrid(False);
$plot->SetCallback('data_color', 'pickcolor', $data2);
$plot->DrawGraph();

$plot->PrintImage();

#############################################################################
function pickcolor( $_img, $_data, $_row, $_col ) {
   if ( $_data[$_row][2] > 2. || $_data[$_row][2] < -2 ) {
     $val = 1;
   }
   else {
     $val = 0;
   }
   return $val;
}
#############################################################################

?>
