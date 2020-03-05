<html>
<head>

<style type="text/css">
<!--

  body {

    margin: 5px;
    background-color: black;
    color: lightgrey;
    scrollbar-face-color: black;
    scrollbar-shadow-color: black;
    scrollbar-highlight-color: black;
    scrollbar-3dlight-color: black;
    scrollbar-darkshadow-color: black;
    scrollbar-track-color: #e4e0e8;
    scrollbar-arrow-color: #e4e0e8;

  }

  pre {

    font: 14px 'courier new', 'bitstream vera sans mono', monospace;
    letter-spacing: -1px;

  }

//-->
</style>

</head>
<body>

<pre>CHAT LOG STYLE PATCHER (0_patch_logs.php)</pre>

<pre>
TO BE USED WHEN:

- after updating the Postline package over an existing board which
  already has several chat logs stored in the 'logs' folder in the
  past, the chat frame and chat log template formerly used will be
  most probably different from that used by the new version and so
  every formerly stored chat logs will need to be patched

- after changing the log template file (layout/html/chatlog.html),
  appearence of chat logs does no longer reflect the appearence of
  the actual template

- after changing either $sitename or $plversion in 'settings.php',
  the corresponding occurences of them must be updated in existing
  log files

This script will patch each and every chat log saved into the yearly
folders so that it might match a new template given by template file
'layout/html/chatlog.html'. Because there could often be hundreds of
daily logs to patch, the script will eventually stop after a certain
amount of time (about 25 sec.) has passed, and leave the rest of the
logs for the next run of this same script; consequentially, you will
keep running this script until all logs are patched.
</pre>

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



define ("going", true);

require ("settings.php");
require ("suitcase.php");
require ("sessions.php");

/*
 *
 * each run will be forced to take a maximum of <$timeout> seconds
 *
 */

$timeout = 25;

/*
 *
 * utils
 *
 */

function stopwatch_split () {

  global $startTime;

  list ($usec, $sec) = explode (chr (32), microtime());
  return (float) $sec + (float) $usec - $startTime;

}

/*
 *
 * ask confirmation
 *
 */

if (isset ($_POST["submit"])) {

  $year_folder = fget ("year", 4, "");
  $year_folder = (is_numeric ($year_folder)) ? $year_folder : "bogus";

  echo ("<pre>PATCHING: logs/$year_folder/.</pre>");

}
else {

  unlockSession ();

  die (

      "<pre>"
    . "<form action=0_patch_logs.php enctype=multipart/form-data method=post>"
    . "PATCH LOGS FROM YEAR: <input type=text name=year value=2003><br>"
    . "<br>"
    . "<input type=submit name=submit value=\"LAUNCH CHAT LOGS STYLE PATCHER\">"
    . "</form>"
    . "</pre>"

  );

}

/*
 *
 * checking overall board locker
 *
 */

if (@file_exists ("widgets/sync/system_locker") == false) {

  unlockSession ();
  die ("<pre>BOARD IS NOT LOCKED, SO QUITTING</pre>");

}

/*
 *
 * this script is generally restricted to administrators
 *
 */

if ($is_admin == false) {

  unlockSession ();
  die ("<pre>ADMINISTRATION RIGHTS REQUIRED</pre>");

}

/*
 *
 * patching all chat logs to apply a different template
 *
 */

$log_head = @file_get_contents ("layout/html/chat_log_header.html");

if (empty ($log_head)) {

  die ("<pre>ERROR READING layout/html/chat_log_header.html</pre>");

}

lockSession ();

$out = "COMPLETED";
$c = 0;
$g = 0;
$v = 0;

$dh = @opendir ("./logs/$year_folder/");

while (($file = @readdir ($dh)) !== false) {

  if (stopwatch_split () >= $timeout) {

    $out = "SERVER LOAD TIMEOUT AFTER " . stopwatch_split () . " SECONDS";
    $g ++;

  }
  else {

    if (substr ($file, -5, 5) == ".html") {
    if ($file != "index.html") {

      $file = "logs/$year_folder/$file";
      $data = @file_get_contents ($file);

      echo ("<pre>PATCHING: $file ... ");

      if (empty ($data)) {

	echo ("NO DATA (?)</pre>");

      }
      else {

	/*
	  extract date from file name
	*/

	list ($y, $m, $d) = explode ("_", $file);

	$y = $year_folder;
	$d = substr ($d, 0, -5);

	switch ($m) {

	  case	1: $m = "January";	break;
	  case	2: $m = "February";	break;
	  case	3: $m = "March";	break;
	  case	4: $m = "April";	break;
	  case	5: $m = "May";		break;
	  case	6: $m = "June"; 	break;
	  case	7: $m = "July"; 	break;
	  case	8: $m = "August";	break;
	  case	9: $m = "September";	break;
	  case 10: $m = "October";	break;
	  case 11: $m = "November";	break;
	  case 12: $m = "December";	break;

	}

	/*
	  initialize date in template
	*/

	$thead = str_replace

	  (

	    array

	      (

		"[DATE]",
		"[YEAR]",
		"[SITENAME]",
		"[PLVERSION]"

	      ),

	    array

	      (

		"$m $d, $y",
		intval ($y) - 1,
		$sitename,
		$plversion

	      ),

	    $log_head

	  );

	/*
	  insulate current header and body of log
	*/

	list ($fhead, $body) = explode ("<hr>", $data);

	/*
	  check if this log is up to date
	*/

	$h1 = strReplaceFromTo ($fhead, "(Postl" , ")", "postline_version") . "<hr>";
	$h2 = strReplaceFromTo ($thead, "(Postl" , ")", "postline_version");
	$l1 = strlen ($h1);

	if (substr ($h1, 0, $l1) == substr ($h2, 0, $l1)) {

	  echo ("ALREADY PATCHED</pre>");

	}
	else {

	  /*
	    if changed, write log back
	  */

	  $hFile = @fopen ($file, 'wb');
	  $error = ($hFile === false) ? true : false;

	  if ($error == false) {

		  set_file_buffer ($hFile, 0);
		  fwrite ($hFile, $thead . $body);
		  fclose ($hFile);

	  }

	  /*
	    update stats and proceed to next log
	  */

	  $v += strlen ($body);
	  $c ++;

	  echo ("OK</pre>");

	}

      }


    }
    }

  }

}

closedir ($dh);

/*
 *
 * writing statistics for this run
 *
 */

$c = ($c > 0) ? $c : "none";
$g = ($g > 0) ? $g : "none";

echo (

     "<pre>$out"
  .  "\nLOGS PATCHED THIS RUN: $c"
  .  "\nVOLUME PROCESSED: $v BYTES"
  .  "\nREMAINING LOGS TO GO: $g"
  .  "\n"
  .  "\nRUN THIS AGAIN TO PROCESS NEXT SPAN, UNLESS NO FILES REMAIN.</pre>"

);

/*
 *
 * done, releasing database lock to flush the cache and let the board resume normal operation
 *
 */

unlockSession ();



?>
