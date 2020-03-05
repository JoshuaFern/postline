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

<pre>ORPHAN POSTS RECOVERY (0_recover_orphan_posts.php)</pre>

<pre>
TO BE USED WHEN:

- after restoring a backup of the 'forums' folder where files may be
  older than the files from the 'posts' folder, in which case random
  threads may no longer show in forums lists because, at the time of
  the backup of the 'forums' folder, several posts did not yet exist
  in the 'posts' folder; running this script allows to locate orphan
  messages (messages without a thread to be in), and then eventually
  rebuild entries for the lost threads by performing reverse lookups
  on the 'tid' field of the whole archive of posts: this would quite
  obviously be slow, and so exploring small slices of the archive is
  recommended - beware, though, that when this script is launched to
  effectively recover lost threads, the single large range of orphan
  messages has to be precisely determined; for example, assume that,
  by processing short spans of a few thousand message IDs, you found
  that there were orphan posts in a first range 1000-1300 as well as
  in a second range 5000-5600, an attempt to recover all of the lost
  threads will have to be performed in a *SINGLE RUN* that will span
  the range 1000 to 5600; otherwise, recovered threads may be listed
  in forums, but still be missing random posts!

This script will reconstruct lost thread entries basing on any posts
found in the 'posts' directory of which the thread ID indicates some
thread that does not appear inside the 'forums' directory, i.e. that
has been lost. Orphan threads will be grouped into a forum that will
be created for the occasion. This script was created because there's
been such a problem, on anywherebb.com's forums, when PL2005 scripts
were still defective. Eventually operations performed by this script
could be undone by creating a full backup of the 'forums' folder and
a single-file backup of 'stats/counters', before telling this script
to effectively build the recovery forum.
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

$start_at = intval (fget ("start", 1000, ""));
$stop_at = intval (fget ("stop", 1000, ""));

if ($stop_at == 0) {

  $stop_at = intval (get ("stats/counters", "counter>newposts", "count"));

}

if (($start_at >= $stop_at) || (!isset ($_POST["submit"]))) {

  unlockSession ();

  die (

      "<pre>"
    . "PLEASE SPECIFY RANGE TO INSPECT AND PRESS 'ENTER':\n\n"
    . "<form action=0_recover_orphan_posts.php enctype=multipart/form-data method=post>"
    . "STARTING MESSAGE ID: <input type=text name=start value=$start_at>\n\n"
    . "ENDING MESSAGE ID:   <input type=text name=stop value=$stop_at>\n\n"
    . "OK TO RECOVER NOW?   <input type=text name=ok value=NO>"
    . "&nbsp;<input type=submit name=submit value=RUN>\n\n"
    . "CHOOSE 'NO' TO EXPLORE GIVEN RANGE IN SEARCH OF ORPHANS,\n"
    . "CHOOSE 'YES' AFTER THE RANGE INCLUDING ALL ORPHANS HAS BEEN FOUND\n\n"
    . "RANGE DEFAULTS TO WHOLE RANGE OF POSTED MESSAGES IDs:\n"
    . "BEWARE THAT THIS COULD BE TOO MUCH TO COMPLETE IN TIME."
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
 * read every block from the "forums" folder, checking the existence of every thread ID,
 * creating a list of existing thread IDs ($tids) where each ID is marked by a true flag
 *
 */

echo ("<pre>RUNNING...</pre>");

$block = array_merge (getFiles ("forums"), getIntervals ("forums"));
$block = wArrayUnique ($block);

$tids = array ();
$threads_in = array ();

foreach ($block as $b) {

  list ($lo, $hi) = explode ("-", $b);

  if ((is_numeric ($lo)) && (is_numeric ($hi))) {

    $thread = all ("forums" . intervalOf (intval ($lo), $bs_threads), makeProper);

    foreach ($thread as $t) {

      $tid = intval (valueOf ($t, "id"));
      $tids[$tid] = true;

    }

  }
  elseif (($lo == "st") || ($lo == "th")) {

    $fid = intval ($hi);
    $threads_in[$hi][$lo] = all ("forums/$b", makeProper);

  }

}

/*
 *
 * read every block from the "posts" folder, checking the existence of every post's thread,
 * creating a list of thread IDs ($tids) where to store every thread ID that doesn't match
 * a record in the "forums" folder; meanwhile, create a 2-dimensional array ($tlists) where
 * to store the list of posts of every thread ID appearing in $tids.
 *
 */

$block = getIntervals ("posts");

$tlists = array ();
$oids = array ();
$orphans_list = array ();

foreach ($block as $b) {

  list ($lo, $hi) = explode ("-", $b);

  if ((is_numeric ($lo)) && (is_numeric ($hi))) {
  if (($lo >= $start_at) && ($hi <= $stop_at)) {

    $post = all ("posts" . intervalOf (intval ($lo), $bs_posts), makeProper);

    foreach ($post as $p) {

      $tid = intval (valueOf ($p, "tid"));

      if (!isset ($tids[$tid])) {

        $mid = intval (valueOf ($p, "id"));
        $tlists[$tid][] = $mid;

        if (!isset ($oids[$tid])) {

          $oids[$tid] = true;
          $orphans_list[] = $tid;

        }

      }

    }

  }
  }

}

/*
 *
 * printing list of thread IDs that could be recovered
 *
 */

if (count ($oids) == 0) {

  echo ("<pre>NO ORPHAN POSTS LOCATED IN GIVEN RANGE ($start_at-$stop_at)</pre>");

}
else {

  $threads = count ($oids);
  $all_orphans = array ();

  echo ("<pre>$threads THREADS TO RECOVER:</pre>");

  if (strtolower (fget ("ok", 1000, "")) != "yes") {

    foreach ($orphans_list as $tid) {

      sort ($tlists[$tid], SORT_NUMERIC);
      $all_orphans = array_merge ($all_orphans, $tlists[$tid]);

      echo (

          "<pre>"
        . "THREAD #$tid\n"
        . "POSTS: " . implode (";", $tlists[$tid])
        . "</pre>"

      );

    }

    $start_at = min ($all_orphans);
    $stop_at = max ($all_orphans);

    $start_at -= ($start_at % $bs_posts);
    $stop_at = $stop_at - ($stop_at % $bs_posts) + $bs_posts;

    echo (

        "<pre>"
      . "TO RECOVER ALL ABOVE THREADS, RUN THIS SCRIPT AGAIN\n"
      . "WITH THE RANGE $start_at-$stop_at, AND 'YES' TO RECOVER"
      . "</pre>"

    );

  }
  else {

    lockSession ();

    $recovery_forum_record = get ("forums/index", "name>Recovery forum", "");

    if (empty ($recovery_forum_record)) {

      $rfid = intval (get ("stats/counters", "counter>newforums", "count")) + 1;
      $rfdir = array ();

      $forums = all ("forums/index", makeProper);

      $forums[] = "<id>$rfid"
                . "<name>Recovery forum"
                . "<desc>lists threads that were lost due to system malfunctions"
                . "<islocked>yes"
                . "<istrashed>yes"
                . "<tc>$threads";

      asm ("forums/index", $forums, makeProper);

    }
    else {

      $rfid = intval (valueOf ($recovery_forum_record, "id"));
      $rfdir = all ("forums/th-$rfid", makeProper);
      $threads += count ($rfdir);

      set ("forums/index", "id>$rfid", "tc", strval ($threads));
      set ("forums/index", "id>$rfid", "islocked", "yes");
      set ("forums/index", "id>$rfid", "istrashed", "yes");

    }

    foreach ($orphans_list as $tid) {

      $rfdir[] = "<id>$tid";

    }

    asm ("forums/th-$rfid", array_reverse ($rfdir), makeProper);

    $n = 1;

    foreach ($orphans_list as $tid) {

      echo ("<pre>$n. RECOVERING THREAD #$tid</pre>");

      sort ($tlists[$tid], SORT_NUMERIC);

      $first_mid = $tlists[$tid][0];
      $fpost_db = "posts" . intervalOf ($first_mid, $bs_posts);
      $first_post = get ($fpost_db, "id>$first_mid", "");

      if (empty ($first_post)) {

        echo ("<pre>\tERROR: CAN'T LOCATE FIRST MESSAGE RECORD (MID=$first_mid)</pre>");

      }
      else {

        $title = "[LOST]" . chr (32) . cutcuts (substr (valueOf ($first_post, "message"), 0, 40));
        $title = str_replace ("&;", chr (32), $title);
        $title = (empty ($title)) ? "NO TITLE" : $title;
        $posts = implode (";", $tlists[$tid]);
        $starter = valueOf ($first_post, "author");
        $starter = (empty ($starter)) ? "UNKNOWN" : $starter;
        $date = intval (valueOf ($first_post, "date"));

        $pfid = false;

        foreach ($threads_in as $fid => $d) {

          $th = (isset ($d["th"])) ? $d["th"] : array ();
          $st = (isset ($d["st"])) ? $d["st"] : array ();
          $id = "<id>$tid";

          if ((in_array ($id, $th)) || (in_array ($id, $st))) {

            $pfid = $fid;
            break;

          }

        }

        if ($pfid === false) {

          $last = "CANNOT LOCATE PARENT FORUM -- ASSUMING FIRST FORUM";
          $laston = $epc;

          $pfid = 1;

        }
        else {

          $pfname = get ("forums/index", "id>$pfid", "name");

          $last = "ORIGINAL LOCATION: $pfname";
          $laston = $epc;

        }

        echo (

            "<pre>"
          . "\tTITLE: $title\n"
          . "\tSTARTER: $starter\n"
          . "\tDATE:" . gmdate ("M d, Y H:i", $date) . "\n"
          . "\t$last"
          . "</pre>"

        );

        set (

            "forums" . intervalOf ($tid, $bs_threads),

            "id>$tid",
            "",

            "<id>$tid"
          . "<fid>$pfid"
          . "<title>$title"
          . "<posts>$posts"
          . "<starter>$starter"
          . "<date>$date"
          . "<last>$last"
          . "<laston>$laston"

        );

      }

      $n ++;

    }

  }

}

/*
 *
 * done, releasing database lock to flush the cache and let the board resume normal operation
 *
 */

unlockSession ();
echo ("<pre>DONE</pre>");



?>
