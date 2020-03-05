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

<pre>FORUM INDICES SYNCHRONIZATION (0_sync_forum_indices.php)</pre>

<pre>
TO BE USED WHEN:

- after restoring backups, or after malfunctions, indices of both
  sticky and regular threads in the 'forums' folder (e.g. 'th-1')
  may list threads that no longer exist; which may also happen in
  consequence of running the "missing posts removal" script, when
  the said script encounters threads that were entirely voided of
  their content and consequentially deletes them: however it will
  miss the "ghost" entries of deleted threads (the cross-checking
  would take too much time and is better handled separately, with
  the use of this script).

The script will read the individual forum indices for both sticky
and regular threads, e.g. 'forums/st-1', 'forums/th-1', etc; then
it will check which of the listed threads are missing from the DB
and remove them from the indices.
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
    . "<form action=0_sync_forum_indices.php enctype=multipart/form-data method=post>"
    . "<input type=submit name=submit value=\"LAUNCH FORUM INDICES SYNCHRONIZATION\">"
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

lockSession ();
set_time_limit (0);

$n = intval (get ('stats/counters', 'counter>newforums', 'count'));
$f = 0;

for ($i = 1; $i < $n; ++ $i) {

	$thList = all ("forums/th-{$i}", makeProper);
	$newThList = array ();
	$changed = false;

	foreach ($thList as $e) {

		$t = intval (valueOf ($e, 'id'));
		$r = get ('forums' . intervalOf ($t, $bs_threads), "id>{$t}", wholeRecord);
		$x = empty ($r) ? false : true;

		if ($x == true) {

			$newThList[] = $e;

		}

		else {

			$changed = true;

		}

	}

	if ($changed == false) {

		$actual = count ($newThList);

		echo ("<pre>PASSED - REGULAR THREADS INDEX FOR FORUM #{$i} ({$actual} ENTRIES)</pre>");

	}

	else {

		$former = count ($thList);
		$actual = count ($newThList);

		echo ("<pre>PATCHING REGULAR THREADS INDEX FOR FORUM #{$i} (FORMER ENTRIES: {$former}, ACTUAL ENTRIES: {$actual})</pre>");

		asm ("forums/th-{$i}", $newThList, makeProper);

		++ $f;

	}

	$stList = all ("forums/st-{$i}", makeProper);
	$newStList = array ();
	$changed = false;

	foreach ($stList as $e) {

		$t = intval (valueOf ($e, 'id'));
		$r = get ('forums' . intervalOf ($t, $bs_threads), "id>{$t}", wholeRecord);
		$x = empty ($r) ? false : true;

		if ($x == true) {

			$newStList[] = $e;

		}

		else {

			$changed = true;

		}

	}

	if ($changed == false) {

		$actual = count ($newStList);

		echo ("<pre>PASSED - STICKY THREADS INDEX FOR FORUM #{$i} ({$actual} ENTRIES)</pre>");

	}

	else {

		$former = count ($stList);
		$actual = count ($newStList);

		echo ("<pre>PATCHING STICKY THREADS INDEX FOR FORUM #{$i} (FORMER ENTRIES: {$former}, ACTUAL ENTRIES: {$actual})</pre>");

		asm ("forums/st-{$i}", $newStList, makeProper);

		++ $f;

	}

}

if ($f == 0) {

	echo ("<pre>NO MISMATCHES, NO INDICES TO PATCH</pre>");

}

else {

	echo ("<pre>{$f} FILES PATCHED</pre>");

}

/*
 *
 * done, releasing database lock to flush the cache and let the board resume normal operation
 *
 */

unlockSession ();
echo ("<pre>COMPLETED.</pre>");



?>
