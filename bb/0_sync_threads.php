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

<pre>THREAD POSTSLISTS SYNCHRONIZATION (0_sync_threads.php)</pre>

<pre>
TO BE USED WHEN:

- after restoring backups, or malfunctions, files in the 'forums'
  folder are more recent than the files in 'posts' folder and may
  contain references to entries that do not match in the relevant
  records of the 'posts' folder (specifically in the form of some
  missing posts from threads), since they were not present at the
  time of the last backup of the 'posts' folder or they were lost
  in SQL database malfunctions.

This script will read the files from 'forums' and check each post
for each thread, creating lists of message IDs to store back into
records of the 'forums' folder holding the "directories" of posts
in each thread. Because this script could take a long time to run
if executed on all files together, you may want to split its duty
into multiple runs by selecting individual files to parse in each
run from the list below. For a complete analysis locating any and
all possibly missing mosts, however, you must execute this on all
of the listed ranges.
</pre>

<?php



/*************************************************************************************************

	    HSP Postline

	    PHP community engine
	    version 2010.11.25 (10.11.25)

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

  $blocks = getIntervals ("forums");
  $list = array ();

  foreach ($blocks as $b) {

    list ($lo, $hi) = explode ("-", $b);

    $b = "forums" . intervalOf (intval ($lo), $bs_threads);

    $lo = str_pad ($lo, 6, '0', STR_PAD_LEFT);
    $hi = str_pad ($hi, 6, '0', STR_PAD_LEFT);

    $list[] = "{$lo} <input name=\"{$b}\" type=checkbox>RANGE: {$lo}-{$hi}</input>";

  }

  sort ($list, SORT_NUMERIC);

  die (

      "<pre>"
    . "<form action=0_sync_threads.php enctype=multipart/form-data method=post>"
    . implode ('<br>', $list)
    . "<br>"
    . "<br>"
    . "<input type=submit name=submit value=\"LAUNCH THREAD POSTLISTS SYNCHRONIZATION\">"
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
 * scan forums archive: for each thread, scan the posts list and locate
 * each possibly missing post, to then write the (filtered) list back
 *
 */

lockSession ();
set_time_limit (0);

$blocks = getIntervals ("forums");
$f = 0;
$t = 0;

foreach ($blocks as $b) {

  list ($lo, $hi) = explode ("-", $b);
  $b = "forums" . intervalOf (intval ($lo), $bs_threads);

  if (isset ($_POST[$b])) {

    echo ("<pre>\tPARSING: $b</pre>");

    $old_thread_records = all ($b, makeProper);
    $new_thread_records = array ();
    $changed = false;

    foreach ($old_thread_records as $r) {

      $tid = intval (valueOf ($r, "id"));
      $fid = intval (valueOf ($r, "fid"));

      if (($tid > 0) && ($fid > 0)) {

	$oldPosts = wExplode (';', valueOf ($r, 'posts'));
	$newPosts = array ();

	$n = 0;

	foreach ($oldPosts as $p) {

	  $p = intval ($p);
	  $postEntry = get ('posts' . intervalOf ($p, $bs_posts), "id>{$p}", wholeRecord);

	  if (empty ($postEntry)) {

	    ++ $n;

	  }

	  else {

	    $newPosts[] = $p;

	  }

	}

	if ($n == 0) {

	  $new_thread_records[] = $r;

	}

	else {

	  if (count ($newPosts) == 0) {

		  set ("forums/th-{$fid}", "id>{$tid}", wholeRecord, deleteRecord);
		  set ("forums/st-{$fid}", "id>{$tid}", wholeRecord, deleteRecord);

		  echo ("<pre>THREAD #{$tid} OF {$n} MISSING POSTS WAS ENTIRELY DELETED FROM FORUM #{$fid}</pre>");

	  }

	  else {

		  $new_thread_records[] = fieldSet ($r, 'posts', implode (';', $newPosts));

		  echo ("<pre>THREAD #{$tid} LISTED {$n} MISSING POSTS</pre>");

	  }

	  $t += $n;
	  $changed = true;

	}

      }

    }

    if ($changed == true) {

      asm ($b, $new_thread_records, makeProper);

      ++ $f;

    }

  }

}

/*
 *
 * done, releasing database lock to flush the cache and let the board resume normal operation
 *
 */

unlockSession ();
echo ("<pre>{$f} FILES PATCHED, {$t} MISSING POSTS UNLISTED</pre>");



?>
