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
$modcp = "";                    // utility links on bottom of page
$error = false;                 // error flag (for this script, rather a "no-confirmation" flag)
$emlist = false;                // true when emoticons list is requested to show
$refill = true;                 // true if script is supposed to load up message's text
$page_number = 0;               // no. of page holding post in its thread (initially zero)

$frec = "";                     // upon collecting arguments for preview form, empty on error
$trec = "";                     // upon collecting arguments for preview form, empty on error

/*
 *
 * get parameters
 *
 */

$m = fget ("m", 1000, "");      // target message ID

/*
 *
 * is this a submission?
 *
 */

$submit = isset ($_POST["submit"]);

if ($submit) {

  /*
    if yes, use posted message version instead of loading the existing (stored) version
  */

  $refill = false;

  /*
    and now, which kind of submission?
  */

  $submit_what = fget ("submit", 1000, "");

  if ($submit_what == "show emoticons list") {

    /*
      user clicked "show emoticons list":
      - enable show of emoticons' list;
      - don't consider this a real submission, it's only the act of showing the emoticons' list.
    */

    $emlist = true;
    $submit = false;

    /*
      build emoticons' list ($emtable):
      - following javascript allows clicking on an emoticon to insert its keyword where the
        cursor is actually located in the message's text area. Tested on MSIE, Firefox, Opera.
    */

    $emtable = "\n"
             . "<script type=text/javascript>\n"
             . "<!--\n"
             . "function insert(f,t){"
             .   "f.focus();"
             .   "t=' '+t+' ';"
             .   "if(f.createTextRange){"
             .     "document.selection.createRange().text+=t;"
             .   "}"
             .   "else if(f.setSelectionRange){"
             .     "var l=f.selectionEnd,m=t.length;"
             .     "f.value=f.value.substr(0,l)+t+f.value.substr(l);"
             .     "f.setSelectionRange(l+m,l+m);"
             .   "}"
             .   "else{"
             .     "f.value+=t;"
             .   "}"
             . "}"
             . "function emo(key){"
             .   "insert(document.postform.message,key);"
             .   "document.prevform.message.value=document.postform.message.value;"
             . "}\n"
             . "//-->\n"
             . "</script>\n"

             . "<center>"
             . "<table align=center>"
             .  "<tr>";

    /*
      here begins the emoticons' list HTML code:
      - retrieve all smiley records.
    */

    $smileys = all ("cd/smileys", makeProper);

    if (count ($smileys) == 0) {

      /*
        no smileys, yet
      */

      $emtable .= "<td class=ls align=center>"
               .   "(no emoticons available, yet)"
               .  "</td>";

    }
    else {

      /*
        reverse the array: smiley records, like any other records, are stored in the database
        in reverse chronological order, because of how function "set" in suitcase.php works;
        while this is normally useful for many optimizations, in this case it's confusing to
        see the last added smileys appear on top of the list, so there...
      */

      $smileys = array_reverse ($smileys);

      /*
        calculate (as $r) how many icons might be held by a single row of the table:
        - start from $iw (inner width of a cartridge, a cartridge being a table within the left
          frame which has a border of exactly 1 pixel on every side), because the smileys' table
          lays inside a cartridge (see form code at end of script), minus 6 pixels to allow some
          3-px margin around the inner table, because I'm holding this table inside a "ls" class
          where padding is 3 px to the left and 3 px to the right (see template.php).
      */

      $r = intval (($iw - 6) / $smileypixelsize);

      /*
        append every icon as a table cell, using a counter ($x) as a reference for when it's
        appropriate to close the row (</tr>) and begin the next row; use $x and $y to count in
        which column and row you're adding the cell, to determine wether it's necessary or not to
        specify the width and/or height property of the cell (and save bandwidth otherwise)...
      */

      $x = 0;
      $y = 0;

      $w = chr (32) . "width=$smileypixelsize";
      $h = chr (32) . "height=$smileypixelsize";

      foreach ($smileys as $s) {

        $smiley = valueOf ($s, "image");
        $keyword = valueOf ($s, "keyword");

        $emtable .= "<td align=center" . (($y == 0) ? $w : "") . (($x == 0) ? $h : "") . ">"
                 .   "<a title=$keyword href=javascript:emo('" . preg_quote ($keyword) . "')>"
                 .     "<img src=$smiley border=0>"
                 .   "</a>"
                 .  "</td>";

        $x ++;

        if (($x % $r) == 0) {

          $emtable .= "</tr><tr>";
          $x = 0;
          $y ++;

        }

      }

      /*
        remove last opening <tr> in case one last row was opened for no reason,
        ie. because the $x counter said so, but no further icons came after that.
      */

      $emtable = (substr ($emtable, -4, 4) == "<tr>") ? substr ($emtable, 0, -4) : $emtable;

    }

    $emtable .=  "</tr>"
             .  "</table>"
             .  "</center>";

  }

  if ($submit_what == "hide emoticons list") {

    /*
      user clicked "hide emoticons list":
      - disable show of emoticons' list;
      - don't consider this a real submission, it's only the act of hiding the emoticons' list.
    */

    $emlist = false;
    $submit = false;

  }

  /*
    in other cases, it's a real submission, and will be processed as normal...
  */

}

/*
 *
 * message editing operations
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

      $frec = "";       // upon collecting arguments for preview form, empty on error
      $trec = "";       // upon collecting arguments for preview form, empty on error

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
        fill form fields from database, if necessary, else get them as arguments
      */

      if ($refill) {

        $new_message = valueOf ($prec, "message");
        $new_bcol = (valueOf ($prec, "bcol") === "") ? $legacy_bcol : intval (valueOf ($prec, "bcol"));

      }
      else {

        $new_message = fget ("message", $maxmessagelen, "&;");
        $new_bcol = intval (fget ("bcol", 1000, ""));

      }

      /*
        replace carriage returns in form input version of message's text,
        normalize speech balloon color to existing color entries
      */

      $new_message_in = str_replace ("&;", "\n", $new_message);

      $new_bcol = ($new_bcol < 0) ? 0 : $new_bcol;
      $new_bcol = ($new_bcol < count ($bcols) - 1) ? $new_bcol : count ($bcols) - 2;

      /*
        if it's a submission of the new version of this message, go ahead,
        else it's most likely to be a simple load of the edit form's frame...
      */

      if ($submit) {

        $code = intval (fget ("code", 1000, ""));
        $submit = ($code == getcode ("pst")) ? true : false;

      }

      if ($submit) {

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

          $frec = "";   // upon collecting arguments for preview form, empty on error
          $trec = "";   // upon collecting arguments for preview form, empty on error

          $form = "Message record not found.//"
                . "The message you were trying to edit does no longer exist in the database, "
                . "most probably because it's been deleted in the meantime. Alternatively, "
                . "there could be a problem with database file \"$mdb\".";

        }
        else {

          /*
            can Postline allow changes to messages posted in this forum,
            considering the user's authorization level? (may_access)
          */

          if (may_access ($frec)) {

            /*
              to enable this user to touch a message in this thread,
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
                  prepare $page_number for use in the link to review the message:
                  $page_number is an index of the page holding this post in its thread,
                  based at zero (zero being the progressive index of thread's first page).
                */

                $ppp = (valueOf ($trec, "ispaged") == "yes") ? 1 : $postsperpage;
                $page_number = (int) (array_search ($m, $posts) / $ppp);

                /*
                  check message's creation time:
                  - there may be, however, a limitation on until when it's possible to
                    edit ($canedit), but it won't affect moderators and administrators.
                */

                $canedit = ((!$posteditdelay) || ($ctime + $posteditdelay >= $epc)) ? true : false;

                if (($canedit) || ($is_admin) || ($is_mod)) {

                  /*
                    check message's author:
                    - it's identified by the nickname, but there could be members who registered
                      with the nickname of someone who has resigned after writing that post: so,
                      nothing would guarantee that this person is the same, if it wasn't for one
                      further check on the user's "join time" ($jtime).
                  */

                  $canedit = (($author == $nick) && ($ctime >= $jtime)) ? true : false;

                  if (($canedit) || ($is_admin) || ($is_mod)) {

                    /*
                      Postline won't accept void messages: possible in theory, but useless in practice,
                      and it will keep the existing version in case there were problems with the submission.
                    */

                    if ($new_message != "") {

                      /*
                        initialize a change state flag (will be true if message has to be saved),
                        get old (existing) version of message from the database, and its speech
                        bubble color, for then seeing if the user has effectively changed them.
                      */

                      $changed = false;

                      $old_bcol = intval (valueOf ($prec, "bcol"));
                      $old_message = valueOf ($prec, "message");

                      if ($new_bcol != $old_bcol) {

                        /*
                          A simple change of speech bubble color won't produce an edit notice,
                          but will set the change state flag to save the record back...
                        */

                        $changed = true;
                        $prec = fieldSet ($prec, "bcol", strval ($new_bcol));

                      }

                      if ($new_message != $old_message) {

                        /*
                          message text has effectively changed:
                          - set change state flag, update $prec to reflect new message.
                        */

                        $changed = true;
                        $prec = fieldSet ($prec, "message", $new_message);

                        /*
                          produce an edit notice, providing:
                          - the one who's editing isn't the message's original poster;
                          - even if it is, too much time ($mercifuledit) has passed, and
                            it's unlikely that this edit is for correcting simple typos;
                          - always, if there is already a "last changed by" notice.
                        */

                        $existing_notice = valueOf ($prec, "edit");
                        $already_changed = (empty ($existing_notice)) ? false : true;

                        if (($author != $nick) || ($ctime + $mercifuledit < $epc) || ($already_changed)) {

                          $prec = fieldSet ($prec, "edit", $nick);
                          $prec = fieldSet ($prec, "on", strval ($epc));

                          $trec = fieldSet ($trec, "last", "$nick edited a message here");
                          $trec = fieldSet ($trec, "laston", strval ($epc));

                          set ($tdb, "id>$t", "", $trec);

                        }

                        /*
                          process words indexing for search,
                          unless search functions are globally disabled...
                        */

                        if (!$search_disable) {

                          /*
                            ...ah, yes, and unless this forum is actually hidden (trashed):
                            search results would, otherwise, eventually reveal its existence.
                          */

                          $is_trashed = (get ("forums/index", "id>$f", "istrashed") == "yes") ? true : false;

                          if (!$is_trashed) {

                            /*
                              Create two wordlists:
                                $wordlist_new contains words of actual version, to be indexed;
                                $wordlist_old contains words of past version, to be un-indexed.
                              At least, ideally, ie. providing no parts of this message have
                              been effectively indexed yet, but this ideal situation would be
                              pratically impossible: because it's a LIFO list, the last posted
                              message will at least partly get indexed as soon as it's posted,
                              before its rest will be suspended for later processing (in fact,
                              even "postform.php" DOES terminate with a call to pquit(), so it
                              will naturally cause pquit() to at least partly index its text).
                              You don't need sorting the list, it will be sorted later anyway...
                            */

                            $wordlist_new = wordlist_of ("dummy", $new_message, true, false);
                            $wordlist_old = wordlist_of ("dummy", $old_message, true, false);

                            /*
                              Determine if at least one word was added to/removed from the post:
                              if the two wordlists are equivalent, there's nothing left to do.
                            */

                            $i = 0;
                            $v = true;

                            while ($v) {

                              if (empty ($wordlist_new[$i])) {

                                $v = (empty ($wordlist_old[$i])) ? true : false;
                                break;

                              }

                              if (empty ($wordlist_old[$i])) {

                                $v = (empty ($wordlist_new[$i])) ? true : false;
                                break;

                              }

                              $v = ($wordlist_new[$i] == $wordlist_old[$i]) ? true : false;
                              $i ++;

                            }

                            if (!$v) {

                              /*
                                Get former wordlist position and length within indexing LIFO list:
                                eventually, if a post was made while search indexing was disabled,
                                these fields would be void, and evaluate to zero, which would then
                                leave "$former_wordlist_add/clr" void arrays anyway, so who cares?
                              */

                              $idx_lifo_offset = intval (valueOf ($prec, "idx_lifo_offset"));
                              $idx_lifo_length = intval (valueOf ($prec, "idx_lifo_length"));

                              $former_wordlist_add = array ();
                              $former_wordlist_clr = array ();

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
                                the former wordlist, since there will be a NEW wordlist in the
                                LIFO after this edit operation.
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
                                    Separate remaining entries to be indexed...
                                  */

                                  preg_match_all ("/.{14}\+$mid/", $former_text, $former_wordlist_match);

                                  foreach ($former_wordlist_match[0] as $w)

                                    $former_wordlist_add[] = rtrim (substr ($w, 0, 14), "=");

                                  /*
                                    ...from remaining entries to be un-indexed.
                                  */

                                  preg_match_all ("/.{14}\-$mid/", $former_text, $former_wordlist_match);

                                  foreach ($former_wordlist_match[0] as $w)

                                    $former_wordlist_clr[] = rtrim (substr ($w, 0, 14), "=");

                                  /*
                                    Now if some entries were effectively found...
                                  */

                                  $entries = count ($former_wordlist_add) + count ($former_wordlist_clr);

                                  if ($entries > 0) {

                                    /*
                                      ...erase the stream of outdated job records from the LIFO list,
                                      since now we have it in memory anyway: a pattern of slashes
                                      will signal pquit()'s post-processor to skip those records.
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
                                consequence of this editing operation: specifically, what might
                                be un-indexed is given by a few differences between arrays, and
                                below here follows the first difference...
                                1) wordlist of the old version of the message
                                 - wordlist holding what remained to index of the old message
                                 = old message words that were already indexed before editing
                              */

                              $wordlist_tmp = wArrayDiff ($wordlist_old, $former_wordlist_add);

                              /*
                                All words that were already indexed before this edit, but that
                                effectively still belong in the new version of the message,
                                will not be re-indexed (they'd just be messy duplicates):
                                2) wordlist of the new version of the message
                                 - old message words that were already indexed before editing
                                 = new message words that will effectively have to be indexed
                              */

                              $wordlist_aux = wArrayDiff ($wordlist_new, $wordlist_tmp);

                              /*
                                Canonicalize the array of words to index after this edit:
                                ie. arrange the array to have stricly progressive numeric keys.
                              */

                              $wordlist_add = array ();

                              foreach ($wordlist_aux as $w) $wordlist_add[] = $w;

                              usort ($wordlist_add, "posts_by_page");

                              /*
                                Still, it has to be considered the (rather frequent, after all)
                                possibility that ANOTHER recent edit of this same message left
                                some words to be un-indexed: BUT, some of the words that the
                                previous edit wanted to un-index may NOW have been re-inserted
                                in the actual message body (with THIS edit operation). So...
                                3) words that were going to be un-indexed before this edit
                                 + old message words that were already indexed before editing
                                 - words appearing in new message, no longer to be un-indexed
                                 = old message words that will REALLY have to be un-indexed now.
                              */

                              $wordlist_tmp = array_merge ($former_wordlist_clr, $wordlist_tmp);
                              $wordlist_tmp = wArrayDiff ($wordlist_tmp, $wordlist_new);

                              /*
                                Canonicalize the array of words to un-index after this edit:
                                ie. arrange the array to have stricly progressive numeric keys.
                              */

                              $wordlist_clr = array ();

                              foreach ($wordlist_tmp as $w) $wordlist_clr[] = $w;

                              usort ($wordlist_clr, "posts_by_page");

                              /*
                                As in postform.php: remember wordlist starting position in LIFO list.
                              */

                              $idx_lifo_marker = wFilesize ("words/posts_lifo.txt");
                              $prec = fieldSet ($prec, "idx_lifo_offset", strval ($idx_lifo_marker));

                              /*
                                Append new wordlist to LIFO list,
                                append patching wordlist to LIFO list.
                              */

                              set_occurrences_of ("posts", $wordlist_add, $m);
                              clear_occurrences_of ("posts", $wordlist_clr, $m);

                              /*
                                As in postform.php: remember wordlist length in LIFO list.
                              */

                              $idx_lifo_marker = wFilesize ("words/posts_lifo.txt") - $idx_lifo_marker;
                              $prec = fieldSet ($prec, "idx_lifo_length", strval ($idx_lifo_marker));

                            }

                          }

                        }

                        /*
                          build message text hint:
                          - start from the actual message text, cut it to typical hint length;
                          - remove any "relics" of chopped HTML entities from resulting string;
                          - replace carriage returns (Postline internal code "&;") with blanks;
                          - trim blanks (and so the above CRs) from both ends of the string.
                        */

                        $hint = substr ($new_message, 0, $hintlength);
                        $hint = cutcuts ($hint);
                        $hint = str_replace ("&;", chr (32), $hint);
                        $hint = trim ($hint);

                        /*
                          correcting message's recorded hint in recent discussions' archive,
                          providing the message is so recent that its hint is still there:
                          - load recent discussions archive ($rd_o), build an array ($rd_a) out
                            of its newline-delimited records, initialize filtered string ($rd_f)
                            to hold the new version of the archive, scan $rd_a looking for the
                            record where the message ID ($_m) corresponds to this message ID ($m),
                            and finally write back the archive only if record was changed ($rd_m).
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
                            $_h = $hint;

                          }

                          $rd_f[] = implode (">", array ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t));

                        }

                        if ($rd_m) {

                          asm ("forums/recdisc", $rd_f, asIs);

                        }

                      }

                      /*
                        if the message's record was changed, write it back to the database
                      */

                      if ($changed) {

                        set ($mdb, "id>$m", "", $prec);

                      }

                    }
                    else {

                      $error = true;
                      $form = "Invalid or void message.";

                    }

                  }
                  else {

                    $error = true;
                    $form = "You can't edit other's posts.//"
                          . "That post doesn't seem to be written by you, but only "
                          . "moderators and administrators may edit other members' "
                          . "posts. Please also consider that if you joined, but then "
                          . "resigned, your new account will not grant you the right "
                          . "to edit your past messages, even if it's under the same "
                          . "nickname, because nothing guarantees to this system that "
                          . "you are in fact the same person.";

                  }

                }
                else {

                  $error = true;
                  $form = "Sorry, you're out of time.//"
                        . "The time span allowed for changes to this post has expired. "
                        . "From now on, only moderators may edit this post: this is "
                        . "to keep people from continuously editing posts, making "
                        . "eventual replies look off-topic.";

                }

              }
              else {

                $error = true;
                $form = "Message not found in thread.//"
                      . "Either the database got corrupted or (most probably) a "
                      . "moderator has moved the thread or deleted that single "
                      . "message while you were editing its text.";

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
          this is no error, but it's no confirmation either: it needs the error flag set for the
          purpose of showing the form to edit the message, before waiting for submission...
        */

        $error = true;
        $form = "Editing message #$m...";

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
    collect rendering arguments for preview form
  */

  $forum = (empty ($frec))

    ? "unknown forum"
    : valueOf ($frec, "title");

  $fid = (empty ($frec))

    ? 0
    : intval (valueOf ($frec, "id"));

  $title = (empty ($trec))

    ? "unknown thread"
    : shorten (valueOf ($trec, "title"), $tt_hint_length);

  $tid = (empty ($trec))

    ? 0
    : intval (valueOf ($trec, "id"));

  /*
    create speech balloon color selection table
  */

  $bcolselect   = "<table width=$iw style=margin:1px>";

  for ($x = 0; $x < count ($bcols) - 1; ++ $x)

    $bcolselect .= "<tr>"
                .   "<td style=padding:2px width=1 bgcolor=" . $bcols[$x]["COLOR"] . ">"
                .    "<input onClick=document.prevform.bcol.value=$x type=radio name=bcol value=$x" . (($x == $new_bcol) ? chr (32) . "checked" : "") . ">"
                .   "</td>"
                .   "<td style=padding:2px;color:black width=100% height=20 bgcolor=" . $bcols[$x]["COLOR"] . ">"
                .    "&nbsp;" . $bcols[$x]["LABEL"]
                .   "</td>"
                .  "</tr>";

  $bcolselect  .= "</table>";

  /*
    method, in the following form, is "post", but for the sake of at least getting the
    stored version of a message when the frameset gets reloaded (for eg. by showing or
    hiding the chat frame), the message ID ($m) is given in the action string, as the
    only "get" argument. Still, any changes would be lost, but I couldn't pass the
    whole message text in URLencoded form (the "get" method has a limit, of 1024 bytes
    if I'm not remembering it wrong, but it has a quite tight limit anyway).
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

          "error details", $frame_contents, true

        )

        : "")

        . "<table width=$pw>"
        . "<form name=postform action=edit.php?m=$m enctype=multipart/form-data method=post>"
        . "<input type=hidden name=code value=" . setcode ("pst") . ">"
        .  "<td height=20 class=inv align=center>"
        .   "message text:"
        .  "</td>"
        . "</table>"

        . $inset_bridge

        . $opcart
        .  "<td>"
        .   "<textarea name=message style=width:{$iw}px;height:240px onChange=document.prevform.message.value=document.postform.message.value>"
        .    $new_message_in
        .   "</textarea>"
        .  "</td>"
        . $clcart

        . $inset_shadow

        . "<table width=$pw>"
        .  "<tr>"
        .   "<td height=20 class=inv align=center>"
        .    "speech balloon color:"
        .   "</td>"
        .  "</tr>"
        .  $bridge
        .  "<tr>"
        .   "<td class=tb>"
        .    $bcolselect
        .   "</td>"
        .  "</tr>"
        .  $shadow
        .  "<tr>"
        .   "<td height=20 class=inv align=center>"
        .    "operations:"
        .   "</td>"
        .  "</tr>"
        .  $bridge
        .  "<tr>"
        .   "<td>"
        .    "<input class=su style=width:{$pw}px type=submit name=submit value=\"submit changes\">"
        .   "</td>"
        .  "</tr>"
        .  $bridge
        .

      (($emlist)

        ?  "<tr>"
        .   "<td>"
        .    "<input class=ky style=width:{$pw}px type=submit name=submit value=\"hide emoticons list\">"
        .   "</td>"
        .  "</tr>"

        . "</table>"

        . $inset_bridge

        . $opcart
        .  "<td class=ls>"
        .   $emtable
        .  "</td>"
        . $clcart

        . "<table width=$pw>"

        :  "<tr>"
        .   "<td>"
        .    "<input class=ky style=width:{$pw}px type=submit name=submit value=\"show emoticons list\">"
        .   "</td>"
        .  "</tr>"

        )

        .  $bridge
        . "</form>"
        . "<form name=prevform action=preview.php target=pan enctype=multipart/form-data method=post>"
        . "<input type=hidden name=editing value=1>"
        . "<input type=hidden name=forum value=\"$forum\">"
        . "<input type=hidden name=fid value=$fid>"
        . "<input type=hidden name=title value=\"$title\">"
        . "<input type=hidden name=tid value=$tid>"
        . "<input type=hidden name=author value=\"$author\">"
        . "<input type=hidden name=message value=\"$new_message_in\">"
        . "<input type=hidden name=ctime value=$ctime>"
        . "<input type=hidden name=bcol value=$new_bcol>"

        .  "<tr>"
        .   "<td>"
        .    "<input class=ky style=width:{$pw}px type=submit name=submit value=preview>"
        .   "</td>"
        .  "</tr>"

        . "</form>"
        . "</table>"

        . $inset_shadow
        .

        makeframe (

          "instructions",

          "Make your changes in free will, but consider that the message's text cannot exceed "
        . "$maxmessagelen characters, and that it will be truncated otherwise.", true

        );

  $modcp = "<table width=$pw>"
         .  "<td height=20 class=inv align=center>"
         .   "utilities:"
         .  "</td>"
         . "</table>"

         . $inset_bridge

         . $opcart
         .  "<td class=ls>"
         .       "<a class=ll target=pan href=posts.php?t=$t>Review thread's first page.</a>"
         .   "<br><a class=ll target=pan href=posts.php?t=$t&amp;p=last#lastpost>Review thread's last page.</a>"
         .  "</td>"
         . $clcart

         . $inset_shadow;

}
else {

  $form =

        makeframe (

          "Changes acquired...", false, false

        )

        .

        makeframe (

          "links",

              "<a" . chr (32) . link_to_posts ("pan", $t, $page_number, $m, true) . ">Review message</a>"
        . "<br><a href=edit.php?m=$m>Make further changes</a>", false

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
        $modcp,
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
