<?php
//
// make the page to view, edit, and send event email
//
// to:   felt@eqinfo.wr.usgs.gov
// from: seismologist@eqinfo.wr.usgs.gov
//
//  The script that actually sends the mail is formmail.pl
//
// Script has hard-coded host network dependencies

include_once "phpmods/format_events.php";

$fromAddr = "seismologist@eqinfo.wr.usgs.gov";

// Extract form parameters
extract ($_POST);         // get SUBMIT value
$evid   = $_GET["EVID"];

if (!$submit) {    // 1st time called ($submit will be undefined)

  // Get the default the email text for the event
  printForm($evid);

} else {   // processes and send 

// check

// send

$to      = $_POST["recipient"];
$subject = 'CIT/USGS Earthquake Message';
$message = $_POST["msg"];
$headers = "From: $fromAddr" . "\r\n";

$status = mail($to, $subject, $message, $headers);

// dump a status message for user
if ($status) {
echo <<< EOF
Mail sent!<br>
status = $status<br>
To: $to<br>
Subject: $subject<br>
$headers<br>
EOF;
} else {
echo "Mail function FAILED.";
}



}

// --------------------------------------------------------------------
function printForm ($evid) {

// define address
$DEFtoAddr   = "felt@eqinfo.wr.usgs.gov";

// form processor
  $self = $_SERVER['PHP_SELF'];

  // Get the default the email text for the event
  $DEFemailtext = format_CubeEmailToString($evid);

echo <<< EOFx
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<html>
  <head>
    <title>Send mail for event $evid</title>
  </head>

  <body>
    <h1>Send mail for event $evid</h1>

<h3> 
Send mail to <b>FELT</b> e-mail list. Edit this form as necessary. Include comments at the bottom.
</h3>

<FORM name="mainForm" ACTION="$self" METHOD="POST" >

      <TABLE>
	<TR> 
	  <TD>To:</TD>
	  <TD> &nbsp </TD>
	  <TD> <INPUT TYPE=text NAME="recipient" SIZE=25 Value="$DEFtoAddr"> 
	  </TD>
	</TR>
</TABLE> 
<TEXTAREA name="msg" Rows=30 Cols=80>
$DEFemailtext
</TEXTAREA>
      <TABLE>
	<TR> 
	  <TD>
		<INPUT TYPE=SUBMIT NAME="submit" Value="Send e-mail">
	  </TD>
	  <TD> &nbsp </TD>
	  <td>
      		<INPUT TYPE="RESET" NAME="reset" VALUE="Reset">
  	 </TD>
	  <TD>
		<INPUT TYPE=BUTTON Value="Cancel" onClick="window.close()">
	  </TD>
	</TR>
      </TABLE>

</FORM>

  </body>
</html>
EOFx;
}

?>
