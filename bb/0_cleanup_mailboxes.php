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

<pre>PM MAILBOX CLEAN-UP (0_cleanup_mailboxes.php)</pre>

<pre>
TO BE USED WHEN:

- after restoring the 'pms' folder from backups, files in 'members'
  folder are more recent than files in 'pms' folder, and could hold
  references to entries of the 'pms' folder that may have been lost
  in the restore, because they did not yet exist at the time of the
  last backup of the 'pms' folder

This script will clean up inbox and outbox (for private messages) of
every member account (such data is held in the 'members' folder). It
will, in particular, look for non-existing PM records which, despite
having been deleted, may still appear in some mailbox in the form of
a weird blank entry. Eventually, operations performed by this script
could be undone by creating a backup of the 'members' folder, before
effectively launching this script.
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
    . "<form action=0_cleanup_mailboxes.php enctype=multipart/form-data method=post>"
    . "<input type=submit name=submit value=\"LAUNCH PM MAILBOX CLEAN-UP\">"
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
 * building lookup table holding all existing PM records, indexed by private message ID
 *
 */

lockSession ();

$blocks = getIntervals ("pms");
$pm = array ();

foreach ($blocks as $b) {

  list ($lo, $hi) = explode ("-", $b);
  $b = "pms" . intervalOf (intval ($lo), $bs_pms);

  $pm_records = all ($b, makeProper);

  foreach ($pm_records as $r) {

    $pmid = intval (valueOf ($r, "id"));

    if ($pmid > 0) {

      $pm[$pmid] = true;

    }

  }

}

/*
 *
 * patching all intervals of members' archive
 *
 */

$blocks = getIntervals ("members");
$f = 0;

foreach ($blocks as $b) {

  list ($lo, $hi) = explode ("-", $b);
  $b = "members" . intervalOf (intval ($lo), $bs_members);

  echo ("<pre>PARSING: $b</pre>");

  $s = false;

  $old_records = all ($b, makeProper);
  $new_records = array ();

  foreach ($old_records as $r) {

    $c = 0;

    $old_inbox = wExplode (";", valueOf ($r, "inbox"));
    $new_inbox = array ();

    foreach ($old_inbox as $i) {

      $i = abs (intval ($i));

      if (isset ($pm[$i])) {

	$new_inbox[] = $i;

      }
      else {

	$c ++;

      }

    }

    if ($c > 0) {

      $r = fieldSet ($r, "inbox", implode (";", $new_inbox));
      $n = valueOf ($r, "nick");
      $s = true;

      echo ("<pre>\t$c BLANK INBOX ENTRIES REMOVED FROM '$n'</pre>");

    }

    $c = 0;

    $old_outbox = wExplode (";", valueOf ($r, "outbox"));
    $new_outbox = array ();

    foreach ($old_outbox as $i) {

      $i = intval ($i);

      if (isset ($pm[$i])) {

	$new_outbox[] = $i;

      }
      else {

	$c ++;

      }

    }

    if ($c > 0) {

      $r = fieldSet ($r, "outbox", implode (";", $new_outbox));
      $n = valueOf ($r, "nick");
      $s = true;

      echo ("<pre>\t$c BLANK OUTBOX ENTRIES REMOVED FROM '$n'</pre>");

    }

    $new_records[] = $r;

  }

  if ($s) {

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
