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

-->
</style>

</head>
<body>

<pre>IGNORERS LIST CREATION (0_create_ignorerslist.php)</pre>

<pre>
TO BE USED WHEN:

- the list of members that ignore at least one member is not up
  to date, possibly because it did not exist (as of PL2006 and
  earlier versions) or because the members database has been
  restored from a backup; the list in question is 'stats/ignorers'

This script will scan the members database, check which members
have a non-void ignorelist, and add their nicknames to the said
list.
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
    . "<form action=0_create_ignorerslist.php enctype=multipart/form-data method=post>"
    . "<input type=submit name=submit value=\"LAUNCH IGNORERS LIST GENERATOR\">"
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
 * lock access
 *
 */

lockSession ();

/*
 *
 * do what you have to
 *
 */

$blocks = getIntervals ("members");
$ignorers = array ();

foreach ($blocks as $mdb_file) {

  list ($lo, $hi) = explode ("-", $mdb_file);
  $mdb_file = "members" . intervalOf (intval ($lo), $bs_members);

  $mdb_record = all ($mdb_file, makeProper);
  $mdb_records = array ();

  foreach ($mdb_record as $r) {

    $il = valueOf ($r, "ignorelist");
    $add_this_member = (empty ($il)) ? false : true;

    if ($add_this_member) {

      $ignorers[] = "<nick>" . valueOf ($r, "nick");

    }

  }

}

asm ("stats/ignorers", $ignorers, makeProper);

/*
 *
 * done, releasing database lock to flush the cache and let the board resume normal operation
 *
 */

unlockSession ();
echo ("<pre>DONE</pre>");



?>
