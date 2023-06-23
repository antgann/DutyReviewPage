<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd"> 
<!-- This defines the frames for the Shadow Duty Review Page
     The waves & buttons frames are event specific and created by catalogShadow.php  -->

<html>
  <head>
<?php
    include_once "cgi-bin/phpmods/config.php";
    print "<title>SHADOW $networkCode Event Review</title>"
?>
  </head>

  <frameset rows="75%,*" frameborder="YES" FRAMESPACING="2" BORDER="2" BORDERCOLOR="blue">
    <frameset cols="80%,*" frameborder="NO" >
      <frame src="cgi-bin/blankPage.html" name="waves" scrolling="yes">
      <frame src="cgi-bin/blankPage.html" name="buttons" scrolling="yes">
    </frameset>
    <frameset rows="12%,*" FRAMEBORDER="NO" >
      <frame src="cgi-bin/cataloghdr.php?LIMIT=0&NARROW=0" name="cataloghdr" scrolling="no" marginheight=0; marginwidth=0;>
      <frame src="cgi-bin/catalogShadow.php?LIMIT=0&NARROW=0" name="catalog" scrolling="yes" marginheight=0; marginwidth=0;>
    </frameset>
  </frameset>

</html>

<!--
+---------------------------------+-------------+
| [waves]                         | [buttons]   |
|                                 |             |
|                                 |             |
|                                 |             |
|                                 |             |
|                                 |             |
|                                 |             |
+---------------------------------+-------------+
| [catalog]                                     |
|                                               |
|                                               |
+-----------------------------------------------+
-->
