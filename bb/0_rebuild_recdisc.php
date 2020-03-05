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

<pre>RECENT DISCUSSIONS BUILDER (0_rebuild_recdisc.php)</pre>

<pre>
TO BE USED WHEN:

- if you changed the value of $recdiscrecords, in 'settings.php', by
  launching this script you can resize the actual archive to reflect
  the change without waiting for posts to accumulate (extending), or
  being removed from the tail of that archive (shrinking);

The script collects upto $recdiscrecords (defined in 'settings.php')
messages and builds a new recent discussions archive out of them. It
will then save it as "forums/recdisc". It will also delete the 'VRD'
table, causing members to lose informations about read/unread posts:
this is necessary as the said table may no longer be up to date with
respect to the recent discussions archive, and cause malfunctions in
the form of *persistently* unread messages being signalled to users.
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
    . "<form action=0_rebuild_recdisc.php enctype=multipart/form-data method=post>"
    . "<input type=submit name=submit value=\"LAUNCH RECENT POSTS COLLECTOR\">"
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
 * collect most recent posts until $recdiscrecords are filled:
 * be careful not listing any posts in hidden forums, though...
 *
 */

lockSession ();

$mid = intval (get ("stats/counters", "counter>newposts", "count"));
$records = array ();
$no_of_records = 0;

while (($mid >= 1) && ($no_of_records < $recdiscrecords)) {

  $db = "posts" . intervalOf ($mid, $bs_posts);
  $pr = get ($db, "id>$mid", "");

  if (!empty ($pr)) {

    $tid = intval (valueOf ($pr, "tid"));

    $db = "forums" . intervalOf ($tid, $bs_threads);
    $tr = get ($db, "id>$tid", "");

    if (!empty ($tr)) {

      $fid = intval (valueOf ($tr, "fid"));
      $fr = get ("forums/index", "id>$fid", "");

      if (!empty ($fr)) {

	$public_forum = (valueOf ($fr, "istrashed") != "yes") ? true : false;

	if ($public_forum) {

	  $f = valueOf ($fr, "name");
	  $t = valueOf ($tr, "title");
	  $author = valueOf ($pr, "author");
	  $date = intval (valueOf ($pr, "date"));
	  $hint = cutcuts (substr (valueOf ($pr, "message"), 0, $hintlength));
	  $hint = str_replace ("&;", chr (32), $hint);

	  $records[] = "$f>$t>$mid>$author>$date>$hint>$fid>$tid";
	  $no_of_records ++;

	}

      }

    }

  }

  $mid --;

}

asm ("forums/recdisc", array_reverse ($records), asIs);

/*
 *
 * removing random-access table VRD:
 *
 * this is a random-access table indexed by member ID, and if the value of $vrdtablerecords
 * changed, their records would be completely scrambled now, they didn't hold any important
 * informations, just a list of viewed recent discussions and the last seen page record for
 * each member...
 *
 */

echo ("<pre>REMOVING OUTDATED RANDOM-ACCESS TABLES</pre>");
echo ("<pre>\tDELETING: tables/vrd</pre>");

@unlink ("tables/vrd");

/*
 *
 * done, releasing database lock to flush the cache and let the board resume normal operation
 *
 */

unlockSession ();
echo ("<pre>$no_of_records RECORDS SAVED TO 'forums/recdisc'</pre>");



?>
