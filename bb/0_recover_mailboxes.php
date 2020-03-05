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

<pre>PM MAILBOX RECOVERY (0_recover_mailboxes.php)</pre>

<pre>
TO BE USED WHEN:

- after restoring the 'members' folder from backups, files in 'pms'
  folder are more recent than the files in 'members' folder and may
  contain references to entries that do not appear in the 'members'
  folder because they were not yet existing at the time of the last
  backup of the 'members' folder

This script will read private messages database files (held in 'pms'
folder) and check the recipients of each message: if a certain PM is
missing from either the inbox of its recipients or the outbox of its
sender, it will append the logical ID of that missing message on top
of the corresponding mailbox list, therefore allowing the recipients
and/or the senders to 'recover' it. Eventually, operations performed
by this script could be undone by creating a backup of the 'members'
folder, before effectively launching this script.
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
    . "<form action=0_recover_mailboxes.php enctype=multipart/form-data method=post>"
    . "<input type=submit name=submit value=\"LAUNCH PM MAILBOX RECOVERY\">"
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
 * scan PM archive: for each message, process recipients list and add the message ID
 * to the $outbox array of the member that sent the message, and to the $inbox array
 * of all other recipients; both $inbox and $outbox must be indexed by nickname, and
 * hold second-level arrays as the lists of private messages, ready to be saved...
 *
 */

echo ("<pre>PHASE 1: BUILDING INBOX/OUTBOX TABLES</pre>");

lockSession ();

$blocks = getIntervals ("pms");

$inbox = array ();
$outbox = array ();

foreach ($blocks as $b) {

  list ($lo, $hi) = explode ("-", $b);
  $b = "pms" . intervalOf (intval ($lo), $bs_pms);

  echo ("<pre>\tPARSING: $b</pre>");

  $pm_records = all ($b, makeProper);

  foreach ($pm_records as $r) {

    $pmid = intval (valueOf ($r, "id"));

    if ($pmid > 0) {

      $rcpt = wExplode (";", valueOf ($r, "rcpt"));
      $author = valueOf ($r, "author");

      foreach ($rcpt as $n) {

	if ($n != $author) {

	  $inbox[$n][] = $pmid;

	}
	else {

	  $outbox[$n][] = $pmid;

	}

      }

    }

  }

}

/*
 *
 * patching all intervals of members' archive
 *
 */

echo ("<pre>PHASE 2: PATCHING MEMBERS DATABASE</pre>");

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

    $n = valueOf ($r, "nick");

    $old_inbox = wExplode (";", valueOf ($r, "inbox"));
    $old_outbox = wExplode (";", valueOf ($r, "outbox"));

    if (isset ($inbox[$n])) {

      $inbox[$n] = wArrayUnique ($inbox[$n]);
      $messages_in = count ($inbox[$n]);

    }
    else {

      $messages_in = 0;

    }

    if (isset ($outbox[$n])) {

      $outbox[$n] = wArrayUnique ($outbox[$n]);
      $messages_out = count ($outbox[$n]);

    }
    else {

      $messages_out = 0;

    }

    $former_messages_in = count ($old_inbox);
    $former_messages_out = count ($old_outbox);

    $inbox_difference = $messages_in - $former_messages_in;
    $outbox_difference = $messages_out - $former_messages_out;

    if ($inbox_difference) {

      if ($messages_in == 0) {

	$new_inbox = "";

      }
      else {

	sort ($inbox[$n]);
	$new_inbox = implode (";", $inbox[$n]);

      }

      $r = fieldSet ($r, "inbox", $new_inbox);
      $s = true;

    }

    if ($outbox_difference) {

      if ($messages_out == 0) {

	$new_outbox = "";

      }
      else {

	sort ($outbox[$n]);
	$new_outbox = implode (";", $outbox[$n]);

      }

      $r = fieldSet ($r, "outbox", $new_outbox);
      $s = true;

    }

    $is = ($inbox_difference > 0) ? "+" : "";
    $os = ($outbox_difference > 0) ? "+" : "";

    echo (

      "<pre>\t"
    . "$n / "
    . "INBOX: $is$inbox_difference ENTRIES, "
    . "OUTBOX: $os$outbox_difference ENTRIES"
    . "</pre>"

    );

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
