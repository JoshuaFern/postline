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



$em['admin_rights_required'] = "ADMINISTRATION RIGHTS REQUIRED";
$ex['admin_rights_required'] = "Use of this command is generally restricted to administrators.";

/*
 *
 * this is a command-line script, to be executed from the chat input line:
 *
 * from_cli is defined only when the script is launched because its name has been typed into
 * the chat prompt/command line, and ONLY if the member was acknowledged (is logged in, and
 * has a precise identity given by $nick, and a logical id $id). This is very important.
 *
 */

if (!defined ("from_cli")) {

  exit ("NO STANDALONE EXECUTION");

}

/*
 *
 * this command is generally restricted to administrators
 *
 */

if ($is_admin == false) {

  unlockSession ();
  die (because ('admin_rights_required'));

}

/*
 *
 * get target forum name and arguments past the slash
 *
 */

$name = strFromTo ($speech, "&quot;", "&quot;");
$args = strtolower (strFromTo ($speech . "<", "$name&quot;", "<"));

/*
 *
 * script duties
 *
 */

if (empty ($name)) {

  /*
    if no double-quoted forum name was specified, report intended syntax
  */

  $speech = "/lose ($nick): syntax is: /lose \"forum name\" [/e]";

}
else {

  /*
    set $e as flag signalling that forum entry must not be deleted from the index:
    this kind of behavior is used to empty a trashcan forum without destroying it
  */

  $e = (strpos ($args, "e") === false) ? false : true;

  /*

    physically deleting a trashed forum's database: all posts, all threads, everything about it

    - WARNING: this reduces the size of the database, acting on a forum THAT HAS BEEN TRASHED
      already, but it may be quite a slow operation. It is advised to make a backup of the whole
      database before attempting a "lose", not because this script may make errors, but specially
      because it's so slow. Since PHP can be configured to STOP executing a script after a given
      number of seconds (usually 30 seconds to 1 minute) to avoid hogging server's resources, if
      such a forced break happens while this script is working, the forum could be only deleted
      PARTIALLY, leaving some mess in the database, such as unlinked or void threads... In most
      cases a forced break could be recovered by re-executing "lose" on the same forum, which
      should eventually restart deleting any remaining messages and threads left from the former
      attempt, but you never really know where it stopped so consider *making a full backup* if
      you think your database is quite precious to you and your community's members.

    - This script doesn't use the forums/th-????.php and forums/st-????.php as a directory of
      the target forum, because that might cause the threads' blocks like "forums/???-???.php"
      to not be cached (because the script would keep jumping from a block to another). It does
      instead scan the threads' blocks in sequence. Just willing to explain the fact that this
      things isn't there to make things slower, it being an optimization in reality.

  */

  lockSession ();

  /*
    get forum ID corresponding to $name (case-sensitive check)
  */

  $fid = intval (get ("forums/index", "name>$name", "id"));

  if ($fid > 0) {

    /*
      if there's a forum with that name ($fid not empty or zero),
      check if it's already been trashed (ie. it's a hidden forum)
    */

    if (get ("forums/index", "id>$fid", "istrashed") == "yes") {

      /*
        uhm... give me a couple minutes to complete this long task, unfortunately this won't be
        effective in safe mode, and many providers set safe mode on, but we try anyway, well...
        this would be anyway a very long time (2 minutes), which might often be not really
        needed, it should complete in less than 30 seconds with average sized forums (say 1000
        threads or so), but anyway, if the deletion is interrupted by the server forcing a
        timeout on PHP execution, it may be continued with another run, or in desperate cases
        with several other runs, each run deleting part of the remaining data.
      */

      set_time_limit (120);

      /*
        phase 1: deleting
      */

      $threads_deleted = 0;   // counter reset
      $posts_deleted = 0;     // counter reset
      $words_unindexed = 0;   // counter reset

      $blocks = getintervals ("forums");

      foreach ($blocks as $tdb) {

        list ($lo, $hi) = explode ("-", $tdb);
        $tdb = "forums" . intervalOf (intval ($lo), $bs_threads);

        /*
          parsing threads block:
          get all thread records, setup void array for threads that aren't part of lost forum
        */

        $threads = all ($tdb, makeProper);
        $remaining_threads = array ();

        /*
          clear change flag: block will be written back only if changed,
          ie. only if one or more thread records were effectively deleted from it
        */

        $changed = false;

        /*
          parse all records in threads block looking for threads belonging to forum $fid
        */

        foreach ($threads as $t) {

          /*
            if thread record $t matches forum ID $fid...
          */

          if (valueOf ($t, "fid") == $fid) {

            /*
              explode thread's posts list (semicolon-delimited) creating array $posts:
              each element of that array is a message ID (later $mid)
            */

            $posts = valueOf ($t, "posts");
            $posts = wExplode (";", $posts);

            foreach ($posts as $mid) {

              /*
                identify database file ($mdb) holding the record of this $mid,
                and read that message's record into $post
              */

              $mdb = "posts" . intervalOf (intval ($mid), $bs_posts);
              $post = get ($mdb, "id>$mid", "");

              if (!empty ($post)) {

                /*
                  message record successfully read:
                  if search indexing is enabled, create wordlist of message and unindex words
                */

                if (!$search_disable) {

                  $message = valueOf ($post, "message");
                  $wordlist = wordlist_of ("posts", $message, true, true);

                  clear_occurrences_of ("posts", $wordlist, $mid);

                  $words_unindexed += count ($wordlist);

                }

                /*
                  delete message record
                */

                set ($mdb, "id>$mid", "", "");

                $posts_deleted ++;

              }

            }

            $threads_deleted ++;
            $changed = true;

          }
          else {

            /*
              $fid not matched: add threads to surviving threads' list for this block
            */

            $remaining_threads[] = $t;

          }

        }

        /*
          assemble and save surviving threads' database block:
          surviving threads are those that weren't found to be in lost forum
        */

        if ($changed) {

          asm ($tdb, $remaining_threads, makeProper);

        }

      }

      invalidateCache ();       // force suspended cache writes to be written, clear read cache

      /*
        phase 2: verifying threads' deletion
      */

      $ok = true;

      $blocks = getintervals ("forums");

      foreach ($blocks as $tdb) {

        list ($lo, $hi) = explode ("-", $tdb);
        $tdb = "forums" . intervalOf (intval ($lo), $bs_threads);

        $threads = all ($tdb, makeProper);

        foreach ($threads as $t) {

          /*
            look for any threads' records that might not have survived:
            those that belong to forum ID $fid, the ID of the lost forum
          */

          if (valueOf ($t, "fid") == $fid) {

            $ok = false;
            break 2;

          }

        }

      }

      /*
        phase 3: completing deletion
      */

      if ($ok) {

        /*
          delete lost forum's sticky and normal threads directories from "forums" folder
        */

        writeTo ("forums/st-$fid", voidString);
        writeTo ("forums/th-$fid", voidString);

        if ($e) {

          /*
            forum emptied: confirmation message
          */

          set ("forums/index", "id>$fid", "tc", "");    // zero threads count

          $speech = "/lose ($nick): ok, forum &quot;$name&quot; has been "
                  . "emptied. No errors. Deleted $threads_deleted threads "
                  . "and $posts_deleted posts; words unindexed: $words_unindexed.";

        }
        else {

          /*
            forum deleted: confirmation message
          */

          set ("forums/index", "id>$fid", "", "");      // erase forum entry

          $speech = "/lose ($nick): ok, forum &quot;$name&quot; has been "
                  . "physically deleted. Deleted $threads_deleted threads "
                  . "and $posts_deleted posts; words unindexed: $words_unindexed.";

        }

      }
      else {

        $speech = "/lose ($nick): failed to delete one or more threads. I have no idea why.";

      }

    }
    else {

      $speech = "/lose ($nick): forum must first be trashed with a /delforum.";

    }

  }
  else {

    $speech = "/lose ($nick): forum &quot;$name&quot; not found.";

  }

}



?>
