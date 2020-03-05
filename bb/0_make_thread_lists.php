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

<pre>THREAD LISTS GENERATOR (0_make_thread_lists.php)</pre>

<pre>
TO BE USED WHEN:

- after restoring backups of the 'members' folder, files in 'forums'
  folder are more recent than the files in 'members' folder, and may
  contain references to entries that do not appear in records of the
  'members' folder (specifically in the form of thread IDs occurring
  in the threads_list field of each member account), since they were
  not present at the time of the last backup of the 'members' folder

This script will read the files from 'forums' and check the starter
of each thread, creating lists of thread IDs indexed by nickname of
the thread starter. Such lists will then be saved into each record,
within files held into the 'members' folder. Eventually, operations
performed by this script can be undone by creating a full backup of
the 'members' folder, before effectively launching this script.
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
    . "<form action=0_make_thread_lists.php enctype=multipart/form-data method=post>"
    . "<input type=submit name=submit value=\"LAUNCH THREAD LISTS GENERATOR\">"
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
 * scan forums archive: for each thread, add ID of thread to corresponding starter
 * array in $threads_list, indexing second-level arrays in $threads_list by nickname
 *
 */

echo ("<pre>PHASE 1: BUILDING THREADS LISTS</pre>");

lockSession ();

$forums = all ("forums/index", makeProper);
$hidden = array ();

foreach ($forums as $f) {

  $fid = intval (valueOf ($f, "id"));
  $hidden[$fid] = (valueOf ($f, "istrashed") == "yes") ? true : false;

}

$blocks = getIntervals ("forums");
$threads_list = array ();

foreach ($blocks as $b) {

  list ($lo, $hi) = explode ("-", $b);
  $b = "forums" . intervalOf (intval ($lo), $bs_threads);

  echo ("<pre>\tPARSING: $b</pre>");

  $thread_records = all ($b, makeProper);

  foreach ($thread_records as $r) {

    $tid = intval (valueOf ($r, "id"));

    if ($tid > 0) {

      $fid = intval (valueOf ($r, "fid"));

      if ($hidden[$fid] == false) {

	$starter = valueOf ($r, "starter");
	$threads_list[$starter][] = $tid;

      }

    }

  }

}

/*
 *
 * patching all intervals of members' archive
 *
 */

echo ("<pre>PHASE 2: CONDITIONALLY PATCHING MEMBERS DATABASE</pre>");

$blocks = getIntervals ("members");
$f = 0;

foreach ($blocks as $b) {

  list ($lo, $hi) = explode ("-", $b);
  $b = "members" . intervalOf (intval ($lo), $bs_members);

  $old_records = all ($b, makeProper);
  $new_records = array ();

  foreach ($old_records as $r) {

    $n = valueOf ($r, "nick");
    $l = $threads_list[$n];

    if (count ($l) == 0) {

      $r = fieldSet ($r, "threads_list", "");

    }
    else {

      sort ($l);
      $r = fieldSet ($r, "threads_list", implode (";", $l));

    }

    $new_records[] = $r;

  }

  $difference = count (wArrayDiff ($new_records, $old_records));
  $must_patch = ($difference > 0) ? true : false;

  if ($must_patch == false) {

    echo ("<pre>\tPASSED: $b</pre>");

  }
  else {

    echo ("<pre>\tPATCHING: $b ($difference RECORDS CHANGED)</pre>");
    asm ($b, $new_records, makeProper);
    $f ++;

  }

}

/*
 *
 * done, releasing database lock to flush the cache and let the board resume normal operation
 *
 */

unlockSession ();
echo ("<pre>$f FILES PATCHED</pre>");



?>
