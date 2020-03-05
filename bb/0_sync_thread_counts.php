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

    font: 15px 'courier new', 'bitstream vera sans mono', monospace;
    letter-spacing: -1px;

  }

//-->
</style>

</head>
<body>

<pre>THREAD COUNTERS SYNCHRONIZATION (0_sync_thread_counts.php)</pre>

<pre>
TO BE USED WHEN:

- after restoring backups or malfunctions, 'forums/index' reports
  wrong thread counts (to the right of each forum, in the index).

The script will read the individual forum indices for both sticky
and regular threads, e.g. 'forums/st-1', 'forums/th-1', etc; then
it will count entries in those indices and finally reflect counts
in corrisponding quick-reference 'tc' fields of 'forums/index'.
</pre>

<?php



/*************************************************************************************************

	    HSP Postline

	    PHP community engine
	    version 2010.11.28 (10.11.28)

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
require ("sessions.php");
require ("suitcase.php");

/*
 *
 * ask confirmation
 *
 */

if (isset ($_POST["submit"])) {

  echo ("<pre>RUNNING...</pre>");

}

else {

  unlockSession ();

  die (

      "<pre>"
    . "<form action=0_sync_thread_counts.php enctype=multipart/form-data method=post>"
    . "<input type=submit name=submit value=\"LAUNCH THREAD COUNTERS SYNCHRONIZATION\">"
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
 * scan forums archive: for each index, count entries,
 * then correct the field in the reference counters of the main 'forums/index'
 *
 */

echo ("<pre>PHASE 1: ANALYZING THREAD COUNTERS</pre>");

lockSession ();
set_time_limit (0);

$n = intval (get ('stats/counters', 'counter>newforums', 'count'));
$tc = array ();

$changed = array ();
$overall = false;

for ($i = 1; $i < $n; ++ $i) {

	$stList = all ("forums/st-{$i}", makeProper);
	$thList = all ("forums/th-{$i}", makeProper);

	$oldTc = intval (get ('forums/index', "id>{$i}", 'tc'));
	$newTc = count ($stList) + count ($thList);

	echo ("<pre>FORUM #{$i} COMPRISES {$newTc} THREADS (LISTS {$oldTc} THREADS)</pre>");

	$tc[$i] = $newTc;

	$changed[$i] = ($oldTc == $newTc) ? false : true;
	$overall = ($changed[$i] == true) ? true : $overall;

}

echo ("<pre>PHASE 2: PATCHING THREAD COUNTERS</pre>");

if ($overall == false) {

	echo ("<pre>NO MISMATCHES, NO COUNTERS TO PATCH</pre>");

}

else {

	$n = intval (get ('stats/counters', 'counter>newforums', 'count'));

	for ($i = 1; $i < $n; ++ $i) {

		if ($changed[$i] == true) {

			echo ("<pre>PATCHING COUNTER OF FORUM #{$i} TO {$tc[$i]} THREADS</pre>");
			set ('forums/index', "id>{$i}", 'tc', strval ($tc[$i]));

		}

	}

}

/*
 *
 * done, releasing database lock to flush the cache and let the board resume normal operation
 *
 */

unlockSession ();
echo ("<pre>COMPLETED.</pre>");



?>
