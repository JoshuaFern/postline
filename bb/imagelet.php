<?php



/*************************************************************************************************

            HSP Postline

            PHP community engine
            version 2010.02.10 (10.2.10)

                                Copyright (C) 2003-2010 by Alessandro Ghignola
                                Copyright (C) 2003-2010 Home Sweet Pixel software

            This program is free software; you can redistribute it and/or modify
            it under the terms of the GNU General Public License as published by
            the Free Software Foundation; either version 2 of the License, or
            (at your option) any later version.

            This program is distributed in the hope that it will be useful,
            but WITHOUT ANY WARRANTY; without even the implied warranty of
            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
            GNU General Public License for more details.

            You should have received a copy of the GNU General Public License
            along with this program; if not, write to the Free Software
            Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

*************************************************************************************************/



/*
 *
 * imagelet.php
 *
 * this is a simple script that's used to encapsulate a c-disk image showen by cdimage.php within
 * an iframe that's used to provide scrollers for the image alone, rather than for the layout of
 * the whole page; I know, iframes aren't very polite ways to display contents, even considering
 * that the surrounding frameset is already quite complicated, but... it's just very cute this way
 *
 */

$i = trim (stripslashes ($_GET["i"]));                          // image name with relative path
$l = strlen ($i);                                               // length of above argument string

$a = (substr ($i, 0, 3) == "cd/") ? true : false;               // is path beginning by "cd/"?
$b = (($l > 7) && ($i[$l - 4] == ".")) ? true : false;          // is extension where expected?
$c = (preg_match ("/[^a-zA-Z0-9\/\.]/", $i)) ? false : true;    // are chars. in expected range?
$d = @file_exists ($i);                                         // is file really on server?

if (($a) && ($b) && ($c) && ($d)) {

  /*
    security checks passed
  */

  exit (

    "<html>"
  . "<head>"
  . "<title>IMAGELET</title>"
  . "<style type=text/css>"
  . "<!--"
  . "body{"
  .   "margin:0px;"
  .   "padding:0px;"
  .   "background-color:white;"
  .   "background-image:url(layout/images/trans.gif);"
  .   "scrollbar-face-color:#C4C0C8;"
  .   "scrollbar-shadow-color:#C4C0C8;"
  .   "scrollbar-highlight-color:#C4C0C8;"
  .   "scrollbar-3dlight-color:#000;"
  .   "scrollbar-darkshadow-color:#000;"
  .   "scrollbar-track-color:#000;"
  .   "scrollbar-arrow-color:#646068"
  . "}"
  . "table,td{"
  .   "margin:0px;"
  .   "padding:0px;"
  .   "border:0px solid;"
  .   "border-collapse:collapse;"
  . "}"
  . "-->"
  . "</style>"
  . "</head>"
  . "<body>"
  .  "<table style=width:100%;height:100%>"
  .   "<td align=center>"
  .    "<img src=$i>"
  .   "</td>"
  .  "</table>"
  . "</body>"
  . "</html>"

  );

}
else {

  /*
    security checks not passed, display error
  */

  exit (

    "<html>"
  . "<head>"
  . "<title>IMAGELET</title>"
  . "<style type=text/css>"
  . "<!--"
  . "body,table{"
  .   "margin:0px;"
  .   "padding:0px;"
  .   "border:0px solid;"
  .   "border-collapse:collapse"
  . "}"
  . "td{"
  .   "color:black;"
  .   "background-color:FF8040;"
  .   "font:10px sans-serif"
  . "}"
  . "-->"
  . "</style>"
  . "</head>"
  . "<body>"
  .  "<table style=width:100%;height:100%>"
  .   "<td align=center>"
  .    "IMAGELET: INCONSISTENT U.R.L."
  .   "</td>"
  .  "</table>"
  . "</body>"
  . "</html>"

  );

}



?>
