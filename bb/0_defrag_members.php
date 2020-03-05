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

<pre>MEMBERS DATABASE DEFRAGMENTATOR (0_defrag_members.php)</pre>

<pre>
TO BE - OPTIONALLY - USED WHEN:

- periodically, to keep the board more efficient, this script would
  claim defragmentation is 'recommended': when it says so, it might
  be a good idea to launch it (by engaging the maintenance lock and
  then clicking its link); before doing so, however, READ CAREFULLY
  AND IN FULL THE FOLLOWING NOTES

This script will defragment the members' directory, optimizing it so
that it will take less disk space on the server, and will indirectly
affect the size of random-access tables found in the 'tables' folder
under the file names of 'VRD' and 'LSP'. Defragmentation may be done
because unused ID entries are left in the said database when someone
resigns by his own will, is forced to resign for misbehaving or gets
the account pruned for inactivity. Unused entries will take space in
the given random-access tables ('VRD' is used to track unread posts,
'LSP' tracks the last seen URL for the online members' list) and can
cause the 'members' directory to hold more files than needed, which,
in turn, can be annoying when you come to backup that directory. And
in general, because larger or more numerous files can make the board
slower as time goes on and the 'members' folder of the database gets
more and more fragmented.

Defragmentation should be done by following these steps, considering
that this entire sequence must be executed while the board is locked
to avoid any possible interferences; you may want to print this page
to consult it as you proceed through this delicate process.

  1. using the Database Explorer, create a full backup of every file
     in the 'members' directory (follow instructions in the database
     explorer help, and remember to disclaim all eventual backups to
     make sure your backup packets will hold all the files);

  2. backup the single file called 'counters' in the 'stats' folder:
     if anything goes wrong while defragmenting, your will also need
     to restore that file, along with the files backed up in step 1;

  3. activate the 'system maintenance lock' switch, at the bottom of
     the CDP form, then run this script again: this time, the script
     will effectively begin defragmenting the members' database, and
     the operation may take some time; note that if after doing this
     something goes wrong, you will need to restore from backups, as
     this script may delete some high-numbered files from 'members';

  4. when this script has finished executing (and the 'DONE' message
     appears at the bottom of this page) go to Database Explorer and
     locate the 'defrag' folder (it will have been created there, if
     it wasn't already existing): once there, save backup packets of
     that folder on your computer, until you get all the files;

  5. before continuing you might want to briefly inspect the results
     of the defragmentation by checking if the files in the 'defrag'
     folder appear to contain the expected informations (the records
     corresponding to each actual member account);

  6. once you're reasonably sure that the defragmented archive looks
     alright, from the Database Explorer select the 'members' folder
     and proceed to restore there all of your backup packets made in
     step number 4: this will properly overwrite affected files with
     their defragmented versions (which is what you want).

This completes the defragmentation process, after which you may make
sure that everything appears to be working, for instance by browsing
the members list, checking that known members seem to be still there
and that their profiles are reachable and hold correct informations.

Should anything look wrong, you could undo the whole defragmentation
by restoring all files in the 'members' folder using backups created
in step 1, also remembering to restore the single 'counters' file in
the 'stats' folder, which you saved in step 2. Doing this will bring
the 'members' folder to its former exact state, although VRD and LSP
still couldn't be recovered (recent discussions will appear unread).

If everything went well, at least from what you can tell, deactivate
the system maintenance lock switch and life will continue as before:
only, the community might be faster and less memory-consuming. Well,
not by much, and in fact, if you cannot really stand doing all this,
you may even choose to skip this kind of defragmentation completely.
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
 * these are legacy fields from the far (or less far) past:
 * they have no use in the member's accounts records anymore, at all, and they will be removed
 *
 */

$fields_to_remove =
  array (

    "pomelized" // used by old versions of the pomelizer only (newer touch the session only)

  );

/*
 *
 * give an estimation of the members' database fragmentation state
 *
 */

$s = intval (get ("stats/counters", "counter>signups", "count"));
$r = intval (get ("stats/counters", "counter>resigns", "count"));
$l = (int) ((100 * $r) / $s);

echo ("<pre>ESTIMATED FRAGMENTATION LEVEL: $l%</pre>");

if ($l > 33) {

  echo ("<pre>\tVERDICT: DEFRAGMENTATION RECOMMENDED</pre>");

}
elseif ($l > 25) {

  echo ("<pre>\tVERDICT: DATABASE MAY BE DEFRAGMENTED</pre>");

}
else {

  echo ("<pre>\tVERDICT: DEFRAGMENTATION NOT NECESSARY</pre>");

}

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
    . "<form action=0_defrag_members.php enctype=multipart/form-data method=post>"
    . "<input type=submit name=submit value=\"LAUNCH MEMBERS DATABASE DEFRAGMENTATION\">"
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
 * lock access, attempt to run for an undefined amount of time
 *
 */

lockSession ();
set_time_limit (0);

/*
 *
 * remove everything from the "defrag" folder (that possible former runs may have left there)
 *
 */

echo ("<pre>PHASE 1: CLEANING UP FORMER RUNS...</pre>");

$file = array_merge (getfiles ("defrag"), getintervals ("defrag"));
$file = wArrayUnique ($file);

foreach ($file as $f) {

  list ($lo, $hi) = explode ("-", $f);

  if ((is_numeric ($lo)) && (is_numeric ($hi))) {

    $f = "defrag" . intervalOf (intval ($lo), $bs_members);

  }
  else {

    $f = "defrag/$f";

  }

  echo ("<pre>\tDELETING: $f</pre>");

  writeTo ($f, voidString);

}

/*
 *
 * defragmenting members list (dropping unused IDs):
 *
 * note that it runs until current signups count, but signups count is altered at the end
 * of this script to discard all unused IDs that were found, and that's what makes this
 * script inappropriate if launched another time without completing the operations given
 * in the instructions... the second time it would create a smaller database, chopping the
 * members' database where the altered signups count ends.
 *
 */

echo ("<pre>PHASE 2: DEFRAGMENTING MEMBERS DATABASE...</pre>");

$s = (int) ($s / $bs_members) * $bs_members;	// round $s to interval of last signup
$s += $bs_members;				// add one interval

$c = 0; // set low limit of initial interval to zero
$i = 1; // set first used ID in defragmented database to one

while ($c <= $s) {

  /*
    explode records from block indicated by $c, and reverse the array to obtain the
    original (chronological) order of insertion: "set" will then re-create the inverse
    order while re-building the block indicated by $i, adding records to "defrag" archives
  */

  $member_record = array_reverse (all ("members" . intervalOf ($c, $bs_members), makeProper));

  if (count ($member_record) > 0) {

    foreach ($member_record as $r) {

      /*
	retrieve former ID $x and nickname $n from account record
      */

      $x = valueOf ($r, "id");
      $n = valueOf ($r, "nick");

      /*
	retrieve former entry $e from "bynick" list, change "id" field in entry to new ID ($i),
	force the nickname in the new entry to make sure the entry is made of at least the two
	fields "id" and "nick" (solving small eventual inconsistences due to damaged accounts)
      */

      $e = get ("members/bynick", "id>$x", "");
      $e = fieldSet ($e, "nick", $n);
      $e = fieldSet ($e, "id", strval ($i));

      /*
	change "id" field in account record to reflect the new ID ($i),
	patch the record by removing obsolete fields (if any, from past versions of Postline)
      */

      $r = fieldSet ($r, "id", strval ($i));

      foreach ($fields_to_remove as $f) {

	$r = fieldSet ($r, $f, "");

      }

      /*
	patch ignorelists (old format to new format: they differ in which lists use ";"
	as the separator between nicknames, semicolon being disallowed as a nickname's
	character, in place of the older, and silly, "->-" sequence...)
      */

      $l = valueOf ($r, "ignorelist");

      if (strpos ($l, "->-") !== false) {

	$l = explode ("->-", $l);
	$l = implode (";", $l);

	$r = fieldSet ($r, "ignorelist", $l);

      }

      /*
	insert new entry into defragmented "bynick" list,
	insert new record into defragmented block of members
      */

      set ("defrag/bynick", "", "", $e);
      set ("defrag" . intervalOf ($i, $bs_members), "", "", $r);

      /*
	explain what happened,
	increase $i to the next free ID of defragmented database
      */

      echo ("<pre>\tADDING: &quot;$n&quot; AS #$i (FORMER #$x)</pre>");

      $i ++;

    }

  }

  /*
    add one interval to $c, proceed to next block:
    as this loop goes on, note that block may not exist (because all of the records of
    an interval may have been deleted, pruned for inactivity, damaged by malfunctions)
    and that's why it can't simply get a list of files from the members' directory...
  */

  $c += $bs_members;

}

/*
 *
 * updating signup and resign counters
 *
 */

echo ("<pre>UPDATING SIGNUP/RESIGN COUNTERS:</pre>");
echo ("<pre>\tNEXT ACCOUNT ID WILL BE: $i</pre>");
echo ("<pre>\tRESIGNS COUNTER HAS BEEN ZEROED</pre>");

set ("stats/counters", "counter>signups", "count", strval ($i - 1));
set ("stats/counters", "counter>resigns", "count", "0");

/*
 *
 * removing excess of files from "members" folder:
 *
 * now that used IDs might be less than before, files holding higher IDs must no longer exist,
 * otherwise next signups would conflict with existing records from former fragmented archive;
 * this series of deletes is really unavoidable, and is what makes a complete backup of the
 * members' archive something that's very recommended before attempting to defrag the archive.
 *
 */

echo ("<pre>REMOVING FILES IN EXCESS:</pre>");

$i = (int) ($i / $bs_members) * $bs_members;	// round $i to interval of last signup
$i += $bs_members;				// add one interval

while ($i < $s) {

  $file = "members" . intervalOf ($i, $bs_members);
  writeTo ($file, voidString);

  echo ("<pre>\tDELETING: $file</pre>");
  $i += $bs_members;

}

/*
 *
 * removing random-access tables VRD, LSP and SCT (these are real files):
 *
 * these were indexed by member ID, their indexs were so completely scrambled by the act of
 * defragmenting the members' archive; however, they didn't hold any important informations,
 * just a list of viewed recent discussions and the last seen page record for each member...
 *
 *    - the security code table for CDP access, which is in the "pst" frame section, is kept
 *	backed up in memory during this process, so that sct_pst can be restored after being
 *	deleted, at least for what concerns its first record: that record holds the security
 *	code that the CDP (defense.php) script may be waiting for on next submission; if it
 *	wasn't restored, the CDP would claim a security code mismatch, unless re-loaded, and
 *	produce a scary "orange screen of death", which is nothing serious but... scary :)
 *
 */

$cdp_code_backup = getcode ("pst");

echo ("<pre>REMOVING OUTDATED RANDOM-ACCESS TABLES</pre>");
echo ("<pre>\tDELETING: tables/vrd</pre>");
echo ("<pre>\tDELETING: tables/lsp</pre>");
echo ("<pre>\tDELETING: tables/sct_cli</pre>");
echo ("<pre>\tDELETING: tables/sct_pan</pre>");
echo ("<pre>\tDELETING: tables/sct_pst</pre>");

@unlink ("tables/vrd");
@unlink ("tables/lsp");
@unlink ("tables/sct_cli");
@unlink ("tables/sct_pan");
@unlink ("tables/sct_pst");

if ($cdp_code_backup) {

	$hFile = @fopen ('tables/sct_pst', 'wb');
	$error = ($hFile === false) ? true : false;

	if ($error == false) {

		set_file_buffer ($hFile, 0);
		fwrite ($hFile, $cdp_code_backup);
		fclose ($hFile);

	} // no error re-creating sct_pst to restore manager's code to submit the CDP

} // there was an effective security code to backup from tables/sct_pst

/*
 *
 * done, releasing database lock to flush the cache and let the board resume normal operation
 *
 */

unlockSession ();
echo ("<pre>DONE</pre>");



?>
