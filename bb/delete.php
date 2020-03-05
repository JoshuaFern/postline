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
define ("left_frame", true);

require ("settings.php");
require ("suitcase.php");
require ("sessions.php");
require ("template.php");

/*
 *
 * initialize accumulators
 *
 */

$form = "";                     // header and error report
$conf = "";                     // confirmation request
$error = false;                 // error flag
$thread_removed = false;        // becomes true after deleting the only remaining post in a thread
$page_number = 0;               // no. of page holding post in its thread (initially zero)

/*
 *
 * get parameters
 *
 */

$m = fget ("m", 1000, "");              // target message ID
$submit = fget ("submit", 1000, "");    // submission value (void, or some kind of yes/no answer)

/*
 *
 * message deleting operations
 *
 */

if ($login) {

  /*
    double-check message ID for credible validity
  */

  if (($m != "") && (is_numeric ($m))) {

    $m = intval ($m);

    /*
      find message coordinates (thread ID = $t, forum ID = $f), locate and load up records
      for this message ($mdb -> $prec), its thread ($tdb -> $trec), its forum ($frec):
      - a simple check on the final forum record will be the only eventual error output,
        to avoid spoiling the presence of messages and threads in hidden forums; anyway,
        if one of these steps fails, the last step will too, because void ID strings will
        evaluate as zero, and no threads or forums can ever have an ID of zero. The only,
        intentional imprecision is that the error message, for any error conditions,
        will always be the same: "Message record not found.", but that's intentional,
        so Postline will handle hidden messages, and threads, and forums, in the same
        way as if they didn't exist for a user who's not allowed to see them.
    */

    $mdb = "posts" . intervalOf ($m, $bs_posts);
    $prec = get ($mdb, "id>$m", "");
    $t = intval (valueOf ($prec, "tid"));
    $tdb = "forums" . intervalOf ($t, $bs_threads);
    $trec = get ($tdb, "id>$t", "");
    $f = intval (valueOf ($trec, "fid"));
    $frec = get ("forums/index", "id>$f", "");

    if ((empty ($frec)) || (may_see ($frec) == false)) {

      $error = true;
      $form = "Message record not found.//"
            . "The message you were trying to edit does no longer exist in the database, "
            . "most probably because it's been deleted in the meantime. Alternatively, "
            . "there could be a problem with database file \"$mdb\".";

    }
    else {

      /*
        get original post author and creation time
      */

      $author = valueOf ($prec, "author");
      $ctime = valueOf ($prec, "date");

      /*
        if it's a confirmation, go ahead, else show confirmation request
      */

      $code = intval (fget ("code", 1000, ""));

      if (($submit == "yes, delete it") && ($code == getcode ("pst"))) {

        /*
          on submission, obtain exclusive write access, then check once more the coordinates
          to ensure they're still all there, ie. that the message still exists, that it's in
          the same thread and, especially, in the same forum, because the forum record holds
          authorization informations (is it hidden? is it visible?): this, apparently silly,
          repetition, is instead necessary: while the database wasn't locked, anything could
          have happened while this same script was executing those same steps above...
        */

        lockSession ();

        $prec = get ($mdb, "id>$m", "");
        $t = intval (valueOf ($prec, "tid"));
        $tdb = "forums" . intervalOf ($t, $bs_threads);
        $trec = get ($tdb, "id>$t", "");
        $f = intval (valueOf ($trec, "fid"));
        $frec = get ("forums/index", "id>$f", "");

        if ((empty ($frec)) || (may_see ($frec) == false)) {

          $error = true;
          $form = "Message record not found.//"
                . "The message you were trying to edit does no longer exist in the database, "
                . "most probably because it's been deleted in the meantime. Alternatively, "
                . "there could be a problem with database file \"$mdb\".";

        }
        else {

          /*
            can Postline allow deletion of messages posted in this forum,
            considering the user's authorization level? (may_access)
          */

          if (may_access ($frec)) {

            /*
              to enable this user to delete a message in this thread,
              the thread may be not locked, or the user be at least a moderator
            */

            $is_not_locked = (valueOf ($trec, "islocked") == "yes") ? false : true;

            if (($is_not_locked) || ($is_admin) || ($is_mod)) {

              /*
                retrieve an array holding which message IDs are part of this thread ($posts),
                and check if the given message ID ($m) is really part of that list: I couldn't
                see why it shouldn't, but you never know: it's a double check, it won't hurt;
                besides, I need the thread's posts list for later purposes.
              */

              $list = valueOf ($trec, "posts");
              $posts = explode (";", $list);

              if (in_array ($m, $posts)) {

                /*
                  check message's creation time:
                  - there may be, however, a limitation on until when it's possible to
                    delete ($candelete), but it won't affect moderators and administrators.
                */

                $candelete = ((!$posteditdelay) || ($ctime + $posteditdelay >= $epc)) ? true : false;

                if (($candelete) || ($is_admin) || ($is_mod)) {

                  /*
                    check message's author:
                    - it's identified by the nickname, but there could be members who registered
                      with the nickname of someone who has resigned after writing that post: so,
                      nothing would guarantee that this person is the same, if it wasn't for one
                      further check on the user's "join time" ($jtime).
                  */

                  $candelete = (($author == $nick) && ($ctime >= $jtime)) ? true : false;

                  if (($candelete) || ($is_admin) || ($is_mod)) {

                    /*
                      one final condition:
                      - regular members may not delete their posts unless the target message is
                        the very last of this thread, so nobody had time to reply to that post yet
                    */

                    $candelete = ($posts[count ($posts) - 1] == $m) ? true : false;

                    if (($candelete) || ($is_admin) || ($is_mod)) {

                      /*
                        process words un-indexing for search,
                        unless search functions are globally disabled.
                      */

                      if (!$search_disable) {

                        /*
                          Get contents of message to delete, and make its wordlist:
                          all words that were not yet indexed will be un-queued for indexing;
                          all words that were already indexed will be queued for un-indexing.
                          You don't need sorting the list, it will be sorted later anyway...
                        */

                        $message = valueOf ($prec, "message");
                        $wordlist = wordlist_of ("dummy", $message, true, false);

                        /*
                          Get former wordlist position and length within indexing LIFO list:
                          eventually, if a post was made while search indexing was disabled,
                          these fields would be void, and evaluate to zero, which would then
                          leave "$former_wordlist_add" a void array anyway, so who cares?
                        */

                        $idx_lifo_offset = intval (valueOf ($prec, "idx_lifo_offset"));
                        $idx_lifo_length = intval (valueOf ($prec, "idx_lifo_length"));

                        $former_wordlist_add = array ();

                        /*
                          Providing the length of this post's wordlist results non-void
                          (yes, that could happen, for eg. a post made of 2-char words),
                          read LIFO in that point to get what remains of former wordlist.
                          What's certain is that the list begins at $idx_lifo_offset, but
                          its effective length might -no longer- be what it was when the
                          message was posted or last edited, because at least a few words
                          might have been already indexed, and the LIFO file shortened in
                          that point (and eventually before further job records were also
                          appended after shortening it, so it couldn't even count on how
                          many bytes @fread will effectively read): it will need matching
                          exactly all words that correspond to the message ID, and find
                          out the effective length of those remaining words. This value
                          is important because it will be used to delete the remains of
                          the former wordlist.
                        */

                        if ($idx_lifo_length > 0) {

                          $h = @fopen ("words/posts_lifo.txt", "r+");

                          if ($h !== false) {

                            /*
                              Seek to first entry of former wordlist,
                              read $former_text upto indicated length.
                            */

                            @fseek ($h, $idx_lifo_offset);
                            $former_text = @fread ($h, $idx_lifo_length);

                            /*
                              Compute, left-pad and quote base62 encoding of the
                              actual message ID for later pattern-based matchings.
                            */

                            $mid = preg_quote (str_pad (convertToBase62 ($m), 4, "=", STR_PAD_LEFT));

                            /*
                              Get entries that still have to be indexed,
                              by scanning all entries concerning this message ID with a "+" flag.
                              Count all entries to make sure you're deleting all of them anyway.
                            */

                            preg_match_all ("/.{15}$mid/", $former_text, $former_wordlist_match);

                            $entries = 0;

                            foreach ($former_wordlist_match[0] as $w) {

                              if ($w[14] == "+") $former_wordlist_add[] = rtrim (substr ($w, 0, 14), "=");

                              $entries++;

                            }

                            /*
                              Now if some entries were effectively found...
                            */

                            if ($entries > 0) {

                              /*
                                ...erase the stream of outdated job records from the LIFO list:
                                a pattern of slashes will signal pquit()'s post-processor to
                                skip those records.
                              */

                              @fseek ($h, $idx_lifo_offset);
                              @fwrite ($h, str_repeat ("/", 19 * $entries));

                            }

                            @fclose ($h);

                          }

                        }

                        /*
                          Now for the least comprehensible part, which is PATCHING search
                          indices to reflect which words were taken out of the message in
                          consequence of this deletion: now, find out which words already
                          got in the index, since they need to be un-indexed.
                        */

                        $wordlist_tmp = wArrayDiff ($wordlist, $former_wordlist_add);

                        /*
                          Canonicalize the array of words to un-index after this delete:
                          ie. arrange the array to have stricly progressive numeric keys.
                        */

                        $wordlist_clr = array ();

                        foreach ($wordlist_tmp as $w) $wordlist_clr[] = $w;

                        usort ($wordlist_clr, "posts_by_page");

                        /*
                          Append patching wordlist to LIFO list: of course there's no need
                          to remember wordlist position and length, for the message is gone:
                          in facts, there'd no longer be a record to hold such informations.
                        */

                        clear_occurrences_of ("posts", $wordlist_clr, $m);

                      }

                      /*
                        prepare $page_number for use in the link to review the message:
                        $page_number is an index of the page holding this post in its thread,
                        based at zero (zero being the progressive index of thread's first page).
                      */

                      $ppp = (valueOf ($trec, "ispaged") == "yes") ? 1 : $postsperpage;
                      $page_number = (int) (array_search ($m, $posts) / $ppp);

                      /*
                        remove message ID from thread's posts list:
                        if it was the thread's last remaining post, get rid of the thread itself.
                      */

                      $posts = wArrayDiff ($posts, array ($m));

                      if (count ($posts)) {

                        /*
                          thread still exists:
                          - update thread record to reflect patched posts list;
                          - produce a delete notice, tied to the thread's record;
                          - differentiate between normal deletion and moderation intervent.
                        */

                        $trec = fieldSet ($trec, "posts", implode (";", $posts));

                        if ($author == $nick)
                          $note = "$nick deleted a message";
                        else
                          $note = "$nick deleted one of $author's messages";

                        $trec = fieldSet ($trec, "last", $note);
                        $trec = fieldSet ($trec, "laston", strval ($epc));

                        set ($tdb, "id>$t", "", $trec);

                      }
                      else {

                        /*
                          thread has no more reasons to keep existing:
                          - delete its record, set $thread_removed flag.
                        */

                        set ($tdb, "id>$t", "", "");

                        $thread_removed = true;

                        /*
                          delete ghost icons of this thread:
                          - $ghost is the "from" field of the thread's ghost record, signalling
                            that the thread was eventually moved before being deleted: it will
                            be searched for, first in regular threads directory of that forum,
                            which is "th-[forumID]", then, if no ghost was found there, in the
                            corresponding sticky threads directory, which is "st-[forumID]".
                        */

                        $ghost = get ("forums/th-$f", "id>$t", "from");
                        $ghost = (empty ($ghost)) ? get ("forums/st-$f", "id>$t", "from") : $ghost;

                        if (!empty ($ghost)) {

                          set ("forums/th-$ghost", "id>$t", "", "");
                          set ("forums/st-$ghost", "id>$t", "", "");

                        }

                        /*
                          delete the thread also from its forum's directory, no matter if
                          it's a sticky or a regular thread (they are in separate directories)
                        */

                        set ("forums/st-$f", "id>$t", "", "");
                        set ("forums/th-$f", "id>$t", "", "");

                        /*
                          all what remains to do is decreasing the forum's threads count ($tc)
                        */

                        $tc = intval (get ("forums/index", "id>$f", "tc")) - 1;
                        set ("forums/index", "id>$f", "tc", strval ($tc));

                      }

                      /*
                        removing message's recorded hint in recent discussions' archive,
                        providing the message is so recent that its hint is still there:
                        - load recent discussions archive ($rd_o), build an array ($rd_a) out
                          of its newline-delimited records, initialize filtered string ($rd_f)
                          to hold the new version of the archive, scan $rd_a looking for the
                          record where the message ID ($_m) corresponds to this message ID ($m),
                          and finally write back the archive only if record was deleted ($rd_m).
                        - note: recent discussions archive is public for all users, but no
                          record appears there if a message had been posted in a hidden forum.
                      */

                      $rd_a = all ("forums/recdisc", asIs);
                      $rd_f = array ();
                      $rd_m = false;

                      foreach ($rd_a as $r) {

                        list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);

                        if ($_m == $m) {

                          $rd_m = true;

                        }
                        else {

                          $rd_f[] = implode (">", array ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t));

                        }

                      }

                      if ($rd_m) {

                        asm ("forums/recdisc", $rd_f, asIs);

                      }

                      /*
                        remove message record from its database file:
                        - it's done only now because if any of the above operations may
                          fail, the message will leave its "orphan" trace in the database...
                      */

                      set ($mdb, "id>$m", "", "");

                    }
                    else {

                      $error = true;
                      $form = "Not last post of thread.//"
                            . "Your message is no longer the last message of its thread, "
                            . "so sorry, but you can no longer delete it, ideally because "
                            . "someone seems to have replied to your message, and deleting "
                            . "it at this point could make subsequent replies look off-topic. "
                            . "Still, if you really want to delete your message, you could "
                            . "ask a moderator or an administrator to do that for you.";

                    }

                  }
                  else {

                    $error = true;
                    $form = "You can't delete other's posts.//"
                          . "That post doesn't seem to be written by you, but only "
                          . "moderators and administrators may delete other members' "
                          . "posts. Please also consider that if you joined, but then "
                          . "resigned, your new account will not grant you the right "
                          . "to delete your past messages, even if it's under the same "
                          . "nickname, because nothing guarantees to this system that "
                          . "you are in fact the same person.";

                  }

                }
                else {

                  $error = true;
                  $form = "Sorry, you're out of time.//"
                        . "The time span allowed to delete this post has expired. "
                        . "From now on, only moderators may delete this post: this is "
                        . "to keep people from continuously deleting posts, making "
                        . "eventual replies look off-topic.";

                }

              }
              else {

                $error = true;
                $form = "Message not found in thread.//"
                      . "Either the database got corrupted or (most probably) a "
                      . "moderator has moved the thread or deleted that single "
                      . "message while you were trying to delete it.";

              }

            }
            else {

              $error = true;
              $form = "Sorry, this thread is locked.//"
                    . "A moderator or an administrator has locked this thread: "
                    . "this means nobody but moderators and administrators could "
                    . "post, edit, retitle or delete messages in/from this thread.";

            }

          }
          else {

            $error = true;
            $form = "Sorry, this forum is locked.//"
                  . "A community administrator has locked this forum: this means nobody, "
                  . "except members having administration privileges, could post, edit, "
                  . "retitle or delete any messages and threads placed anywhere in this forum.";

          }

        }

      }
      else {

        /*
          no real error - only to show confirmation request
        */

        $error = true;

        /*
          not confirmed, was it cancelled?
        */

        if ($submit == "no, don't delete") {

          /*
            yeah, deletion cancelled
          */

          $list = valueOf ($trec, "posts");
          $posts = explode (";", $list);

          $ppp = (valueOf ($trec, "ispaged") == "yes") ? 1 : $postsperpage;
          $page_number = (int) (array_search ($m, $posts) / $ppp);

          $form = "Delete: operation canceled!//The message still exists."
                . "<hr><a href=postform.php?t=$t>Post in same thread</a>"
                . "<br><a " . link_to_posts ("pan", $t, $page_number, $m, true) . ">Review target message</a>"
                . "<br><a " . link_to_threads ("pan", $f, 0) . ">Index of this forum</a>"
                . "<br><a " . link_to_forums ("pan") . ">Index of all forums</a>";

        }
        else {

          /*
            nope, not even a cancel: it means the user didn't answer yet, so ask confirmation:
            - in the form, method is POST, but $m argument is via GET, so it doesn't get lost
              on reloads and can be recoveded upon showing/hiding the chat frame...
          */

          $form = "Please confirm deletion!//"
                . "If you confirm this operation, the message you requested to delete "
                . "will be removed from its thread and it will NOT be recoverable. Normally, "
                . "moderators might delete messages that for some reason break the community's "
                . "rules, or if a non-moderator member has explicitly requested moderators to "
                . "delete that message. So, are you sure you want to proceed?";

          $conf = "<table width=$pw>"
                .  "<form action=delete.php?m=$m enctype=multipart/form-data method=post>"
                .  "<input type=hidden name=code value=" . setcode ("pst") . ">"
                .  "<tr>"
                .   "<td height=20 class=inv align=center>"
                .    "OK TO DELETE?"
                .   "</td>"
                .  "</tr>"
                .  $bridge
                .  "<tr>"
                .   "<td>"
                .    "<input class=su style=width:{$pw}px type=submit name=submit value=\"yes, delete it\">"
                .   "</td>"
                .  "</tr>"
                .  $bridge
                .  "<tr>"
                .   "<td>"
                .    "<input class=ky style=width:{$pw}px type=submit name=submit value=\"no, don't delete\">"
                .   "</td>"
                .  "</tr>"
                .  $shadow
                .  "</form>"
                . "</table>";

        }

      }

    }

  }
  else {

    $error = true;
    $form = "Missing or invalid arguments.";

  }

}
else {

  $error = true;
  $form = "You are not logged in.//<a href=mstats.php>go to login panel</a>";

}

/*
 *
 * form initialization
 *
 */

if ($error) {

  /*
    no confirmation for delete:
    show error report, cancel message, or ask for confirmation
  */

  list ($frame_title, $frame_contents) = explode ("//", $form);

  $form =

        makeframe (

          $frame_title, false, false

        )

        .

      (($frame_contents)

        ?

        makeframe (

          "information", $frame_contents, true

        )

        : "") . $conf;

}
else {

  /*
    delete confirmed and successfully executed:
    show report, also reporting if parent thread has been ALSO deleted...
  */

  if ($thread_removed)

    $conf = "...and thread ALSO deleted."
          . "<hr><a" . chr (32) . link_to_threads ("pan", $f, 0) . ">Index of this forum</a>"
          . "<br><a" . chr (32) . link_to_forums ("pan") . ">Index of all forums</a>";

  else

    $conf =     "<a" . chr (32) . link_to_posts ("pan", $t, $page_number, $m, true) . ">Let me see if it's gone</a>"
          . "<br><a href=postform.php?t=$t>Post a different message</a>"
          . "<br><a" . chr (32) . link_to_threads ("pan", $f, 0) . ">Index of this forum</a>"
          . "<br><a" . chr (32) . link_to_forums ("pan") . ">Index of all forums</a>";

  $form =

        makeframe (

          "Message deleted...", false, false

        )

        .

        makeframe (

          "links", $conf, true

        );

}

/*
 *
 * template initialization
 *
 */

$postpanel = str_replace

  (

    array

      (

        "[FORM]",
        "[MODCP]",
        "[PERMALINK]"

      ),

    array

      (

        $form,
        "",
        $permalink

      ),

    $postpanel

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page upon showing or
 * hiding the chat frame, and for the online members list links (unless no-spy flag checked).
 *
 */

include ("setlfcookie.php");

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($postpanel));



?>
