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
 * initialize output accumulators
 *
 */

$page = 0;              // page number (provided to "posts.php" in the URL to review a message)
$form = "";             // page output accumulator
$modcp = "";            // utility links on bottom of page
$error = false;         // error flag
$emlist = false;        // true when emoticons list is requested to show

$dontrememberthisurl = false;           // discards URL tracker duties to avoid repeating actions

/*
 *
 * manage random balloon color, normalize default balloon color
 *
 */

$bcol = ($bcol == -1) ? mt_rand (0, count ($bcols) - 1) : $bcol;
$bcol = ($bcol < 0) ? 0 : $bcol;
$bcol = ($bcol < count ($bcols)) ? $bcol : count ($bcols) - 1;

/*
 *
 * this warning is presented along with error messages claiming a title or message was too long:
 * it might explain the user why the message looks shorter than the limit, but still doesn't get
 * throught the said checks...
 *
 */

$string_length_warning =

  "<hr>"
. "If you believed it was shorter than the limit, please be aware of the fact that a few "
. "special characters are being 'escaped' while your post is being processed: characters like "
. "the greater/lower than signs (&lt; &gt;), ampersands (&amp;) and doublequotes (&quot;) are "
. "transformed into special tags that are <u>longer</u> than one character. After such filter "
. "has been applied, the resulting text may have grown well beyond the limit. And please note "
. "that it's no mistake: it's just the way this board works. You might either split your post "
. "into multiple messages, or diminish your use of the above special characters.";

/*
 *
 * get parameters
 *
 */

$f = intval (fget ("f", 10, ""));       // target forum ID
$t = intval (fget ("t", 10, ""));       // target thread ID
$m = intval (fget ("m", 10, ""));       // message ID of quoted post (if any)

/*
 *
 * get form submission trigger:
 * showing or hiding emoticons' list does not result in a "real" submission
 *
 */

$submit = isset ($_POST["submit"]);

if ($submit) {

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
             . "<script language=JavaScript type=text/javascript>\n"
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
 * generate sample code for new thread, if requested: only if no thread argument provided,
 * meaning the form was for composing a new thread; otherwise, retrieve posted fields as normal...
 *
 */

if ((empty ($t)) && (fget ("ex", 4, "") == "show")) {

  $submit = false;      // user pressed "show me an example": not a real submit

  $title = "Hello, how do you do?";

  $message = "CLICK &quot;PREVIEW&quot; TO SEE RESULT"
           . "&;&;"
           . "This is an example thread, change the text here and in the other fields to what "
           . "you effectively want to post in this forum. The title of this thread will be "
           . "&quot;Hello, how do you do?&quot;, and the thread will be associated with a poll "
           . "which answers are given in the field below: notice the various answers are "
           . "given as a list and separated by semicolons ( ; )";

  $poll = "fine, thanks; not bad; so and so; mind your own business";

}
else {

  /*
    retrieve submitted title and message:
    limit is set to a conventional megabyte of text, which might be well below the maximum
    size of a post, to later check if any of these fields turn out to be longer than allowed.
  */

  $title = fget ("title", 1000000, "");
  $message = fget ("message", 1000000, "&;");

  /*
    retrieve submitted poll options:
    a little post-processing, other than the checks made by "fget", is necessary to ensure
    that the pipe, or vertical bar, (|), does not occur among the options, because it will
    be used to save voters' informations. Also any carriage returns here are translated to
    simple blank spaces, as well as any whitespace formatters (eg. tabs); finally, for any
    multiple occurrences of a blank space, a single space is preserved.
  */

  $poll = fget ("poll", 1000000, chr (32));
  $poll = str_replace ("|", chr (32), $poll);
  $poll = preg_replace ("/\s{2,}/", chr (32), $poll);

  /*
   *
   * bugfix [7/6/2006]
   *
   * replace &lt; &gt; &quot; in poll options (semicolon separates options):
   * less than/greater than are replaced with blanks, doublequotes with double single quote
   *
   */

  $poll =

    str_replace (

      array ("&quot;", "&lt;", "&gt;"),
      array (chr (39) . chr (39), chr (32), chr (32)),

      $poll

    );

}

/*
 *
 * replace carriage returns in form input versions of the above fields:
 * yes, there could be existing text even if this is not a final submission,
 * for instance after showing/hiding smileys... or, what if there will be an error?
 * preserving these fields avoids the user to have to retype them if they don't get throught.
 *
 */

$title_in = str_replace ("&;", "", $title);
$message_in = str_replace ("&;", "\n", $message);

/*
 *
 * retrieve flood timeout counter:
 * keeps users from posting messages and threads too quickly (reduces spamming damages)
 *
 */

if (($is_admin == true) || ($is_mod == true)) {

  $enable_flood_limit = false;

}
else {

  $enable_flood_limit = true;

}

$lastpost = get ("stats/flooders", "entry>post", "=");
list ($lpid, $lptime, $lpmd5) = explode ("+", $lastpost);

$lpid = intval ($lpid);
$lptime = intval ($lptime);

/*
 *
 * the given security code will be requested to match,
 * before it will be really performing any actions
 *
 */

$code_match = intval (fget ("code", 1000, ""));
$code_match = ($code_match == getcode ("pst")) ? true : false;

$code = setcode ("pst");

/*
 *
 * message/threads posting operations
 *
 */

if ($login == true) {

  if (($submit == false) || ($code_match == false)) {

    /*
      if it's not a post submission, show either a void form for the user to fill,
      or pre-load the form quoting another message (as indicated by argument $m):
      the error flag is asserted to show the form in place of the confirmation...
    */

    $error = true;

    /*
      retrieve name of forum and title of thread to post within:
      they'll eventually show in the form (indicatively)...
    */

    if (empty ($t)) {

      /*
        new thread submission (no thread ID provided)
      */

      $forum_record = get ("forums/index", "id>$f", "");

      if ((empty ($forum_record)) || (may_see ($forum_record) == false)) {

        /*
          either the target forum ID doesn't exist,
          or the target forum is hidden, and members are not allowed to know it exists...
        */

        $forum_name = "(unknown forum)";

      }
      else {

        $forum_name = valueOf ($forum_record, "name");

      }

      /*
        set forum name as the form's title,
        and eventually add a hint about the flood timeout...
      */

      $form = shorten ($forum_name, 24);

      if (($enable_flood_limit == true) && ($lpid == $id) && ($lptime + $antithrdflood > $epc)) {

        $form .= "//flood timeout: " . (($lptime + $antithrdflood) - $epc) . " sec...";

      }

    }
    else {

      /*
        posting in reply to an existing thread (thread ID provided):
        locate target thread record, and that of its parent forum...
      */

      $tdb = "forums" . intervalOf ($t, $bs_threads);
      $thread_record = get ($tdb, "id>$t", "");
      $parent_forum = intval (valueOf ($thread_record, "fid"));
      $forum_record = get ("forums/index", "id>$parent_forum", "");

      if ((empty ($thread_record)) || (empty ($forum_record)) || (may_see ($forum_record) == false)) {

        /*
          either the thread record doesn't exist, or its parent forum ID doesn't exist,
          or its parent forum is hidden, and members are not allowed to know it exists...
        */

        $forum_name = "(unknown forum)";
        $thread_title = "(unknown thread)";

        /*
          $parent_forum must be initialized because it's passed as an argument inside
          the message preview form, for the case of a reply to an existing thread, or
          seeked to determine if there is a ghost icon to remove in the assembling of
          modcp links; it's initialized to zero, which would always be a non-existing
          forum in case the variable doesn't get changed by later code...
        */

        $parent_forum = 0;

      }
      else {

        $forum_name = valueOf ($forum_record, "name");
        $thread_title = valueOf ($thread_record, "title");

        /*
          alright, thread is in a public forum:
          eventually, you can pre-load quoted text from message ID passed as $m,
          but only if member didn't re-load the post-a-message panel after typing
          some text already (or modifying a quoted message), or changes would be
          obviously discarded... so chech that there's no text in the message box
        */

        if (empty ($message_in)) {

          if (!empty ($m)) {

            /*
              locate database file holding quoted message record,
              read that record to extract author and message's text
            */

            $mdb = "posts" . intervalOf ($m, $bs_posts);
            $quoted_record = get ($mdb, "id>$m", "");

            if (!empty ($quoted_record)) {

              $text = valueOf ($quoted_record, "message");
              $text = str_replace ("&;",  "\n",  $text);

              $message_in = "<q " . valueOf ($quoted_record, "author") . " [$m]>\n$text\n</q>\n\n\n";

            }

          }

        }

      }

      /*
        set thread title as the form's title,
        and eventually add a hint about the flood timeout...
      */

      $form = shorten ($thread_title, 24);

      if (($enable_flood_limit == true) && ($lpid == $id) && ($lptime + $antipostflood > $epc)) {

        $form .= "//flood timeout: " . (($lptime + $antipostflood) - $epc) . " sec...";

      }

    }

  }
  else {

    /*
      Post submission:
      set flag to witheld tracking of this URL (avoid to repeat the submit on reloads).
    */

    $dontrememberthisurl = true;

    /*
      Compute the message's wordlist now: it may be a time-consuming operation:
      you'd better do it before locking the session, so it'll eventually multitask.
    */

    $wordlist = wordlist_of ("posts", $message, true, true);

    /*
      obtain exclusive write access:
      database manipulation ahead...
    */

    lockSession ();

    if (empty ($t)) {

      /*
        thread argument not provided, so storing message as a new thread's starter
      */

      $forum_record = get ("forums/index", "id>$f", "");

      if ((!empty ($forum_record)) && (may_see ($forum_record))) {

        /*
          forum exists AND this member can see that it exists:
          is it locked, by any chance?
        */

        if (may_access ($forum_record)) {

          /*
            forum exists, member can generally be granted write-access to it,
            but is it closed to new threads' submission?
          */

          $closed_forum = (valueOf ($forum_record, "isclosed") == "yes") ? true : false;

          if (($is_admin == true) || ($closed_forum == false)) {

            /*
              all authorization checks passed:
              forum exists AND this member can post a thread in the forum, so do it.
            */

            $pmd5 = md5 ($title . $message);

            if (($lpid == $id) && ($lpmd5 == $pmd5)) {

              $error = true;

              $form = "Double post...//This may not be your fault, but a thread holding "
                    . "the same contents was posted TWICE from your same account ID: it "
                    . "may happen, so don't worry. Your thread was stored, this note is "
                    . "here to inform you that its second copy was already rejected, so "
                    . "you don't have to delete its twin thread manually.";

            }
            else {

              /*
                no double post:
                the above check was made to keep people who may have encountered connection
                problems during the transfer of this page's confirmation message, and who,
                not seeing any confirmation, may try to submit again, involountarily posting
                the same thread twice.
              */

              if (($enable_flood_limit == true) && ($lpid == $id) && ($lptime + $antithrdflood > $epc)) {

                /*
                  flood limit
                */

                $error = true;

                $form = "Too early!//You're submitting multiple new threads too quickly: "
                      . "you have to wait " . intval ($antithrdflood / 60) . " minutes "
                      . "between each thread submission: it's an anti-flood precaution.";

              }
              else {

                /*
                  no flood limit
                */

                if (!empty ($title)) {

                  /*
                    non-void title
                  */

                  if (strlen ($title) <= $maxtitlelen) {

                    /*
                      title fits indicated limit (see "settings.php")
                    */

                    if (!empty ($message)) {

                      /*
                        non-void message
                      */

                      if (strlen ($message) <= $maxmessagelen) {

                        /*
                          message fits indicated limit (see "settings.php")
                        */

                        if (strlen ($poll) <= $maxpolllen) {

                          /*
                            if there are one or more options for a poll, process them:
                            trim whitespaces from options, and remove duplicated options.
                          */

                          if (!empty ($poll)) {

                            $option = explode (";", $poll);
                            $valid_options = array ();

                            foreach ($option as $o) {

                              $o = trim ($o);

                              if ((!empty ($o)) && (!in_array ($o, $valid_options))) {

                                $valid_options[] = $o;

                              }

                            }

                            $poll = implode (";", $valid_options);

                          }

                          /*
                            - get next available thread ID (from progressive counter),
                            - generate file name ($tdb) of database file holding thread record,
                            - continue only if there's no allocated record under that ID...
                            meanwhile, do the same for the message ID, using $mid and $mdb.
                          */

                          $tid = intval (get ("stats/counters", "counter>newthreads", "count")) + 1;
                          $tdb = "forums" . intervalOf ($tid, $bs_threads);

                          $mid = intval (get ("stats/counters", "counter>newposts", "count")) + 1;
                          $mdb = "posts" . intervalOf ($mid, $bs_posts);

                          $existing_thread_record = get ($tdb, "id>$tid", "");
                          $existing_message_record = get ($mdb, "id>$mid", "");

                          if ((empty ($existing_thread_record)) && (empty ($existing_message_record))) {

                            /*
                              write updated counters
                              (before writing records to reduce ID conflict possibilities)
                            */

                            set ("stats/counters", "counter>newthreads", "", "<counter>newthreads<count>$tid");
                            set ("stats/counters", "counter>newposts", "", "<counter>newposts<count>$mid");

                            /*
                              write thread and message records:
                              message record is obviously that of the first post of this thread,
                              and the thread record reflects this in its initial <posts> list...
                            */

                            set ($mdb, "", "", "<id>$mid<tid>$tid<author>$nick<date>$epc<message>$message<bcol>$bcol");
                            set ($tdb, "", "", "<id>$tid<fid>$f<title>$title<posts>$mid<starter>$nick<date>$epc<last>$nick started the thread<laston>$epc");

                            /*
                              save poll options in thread records, if options given
                            */

                            if (!empty ($poll)) {

                              set ($tdb, "id>$tid", "poll", $poll);

                            }

                            /*
                              add thread ID on top of parent forum's directory,
                              otherwise it wouldn't be listed among its forum's threads:
                              this is the operation that makes the thread visible and clickable,
                              and it's done at this point because now all informations about the
                              thread have been saved (not considering the write cache, though).
                            */

                            set ("forums/th-$f", "", "id", strval ($tid));

                            /*
                              updating threads' count of parent forum in forums' index
                            */

                            $tc = intval (get ("forums/index", "id>$f", "tc")) + 1;
                            set ("forums/index", "id>$f", "tc", strval ($tc));

                            /*
                              save anti-flood trace
                            */

                            set ("stats/flooders", "entry>post", "", "<entry>post<=>$id+$epc+$pmd5");

                            /*
                              locate database file holding thread author's record,
                              update posted threads and messages count for that member,
                              and, providing the target forum is not hidden,
                              add this thread's ID to list of threads posted by this member
                            */

                            $forum_is_hidden = (valueOf ($forum_record, "istrashed") == "yes") ? true : false;

                            $db = "members" . intervalOf ($id, $bs_members);
                            $mr = get ($db, "id>$id", "");

                            $tc = intval (valueOf ($mr, "threads")) + 1;
                            $mr = fieldSet ($mr, "threads", strval ($tc));
                            $pc = intval (valueOf ($mr, "posts")) + 1;
                            $mr = fieldSet ($mr, "posts", strval ($pc));

                            if ($forum_is_hidden == false) {

                              $tl = wExplode (";", valueOf ($mr, "threads_list"));
                              $tl[] = $tid;

                              $mr = fieldSet ($mr, "threads_list", implode (";", $tl));

                            }

                            set ($db, "id>$id", "", $mr);

                            /*
                              check if this post caused the member to reach "major age"
                            */

                            majoragepromotioncheck ($id);

                            /*
                              all that remains to do is indexing the thread's first message
                              for keyword-based search, and adding an entry in the recent
                              discussions archive: but only if the parent forum is public
                            */

                            if ($forum_is_hidden == true) {

                              /*
                                if a thread is posted in a hidden forum,
                                tell all moderators via an intercom call...
                              */

                              dispatch_intercom_call ($mid);

                            }
                            else {

                              /*
                                if a thread is posted in a public forum,
                                add recent discussion entry
                              */

                              $forum_name = valueOf ($forum_record, "name");
                              $msg_hint = cutcuts (substr (trim ($message), 0, $hintlength));

                              addrecdisc ($forum_name, $title, $mid, $nick, $msg_hint, $f, $tid);

                              /*
                                now, mark the above "recent discussion entry" as read,
                                at LEAST for the author of this message...
                              */

                              $vrd = getvrd ($id);
                              $vrd[] = $mid;

                              rsort ($vrd, SORT_NUMERIC);
                              setvrd ($id, $vrd);

                              /*
                                index the words in the thread's first message
                                (unless search indexing is globally disabled)
                              */

                              if ($search_disable == false) {

                                /*
                                  remember wordlist starting position in LIFO list
                                */

                                $idx_lifo_marker = wFilesize ("words/posts_lifo.txt");
                                set ($mdb, "id>$mid", "idx_lifo_offset", strval ($idx_lifo_marker));

                                /*
                                  append wordlist to LIFO list
                                */

                                set_occurrences_of ("posts", $wordlist, $mid);

                                /*
                                  remember wordlist size (in bytes) in LIFO list:
                                  when this value is non-zero, it means at least one word has to be
                                  indexed for the search database: coupled with the corresponding
                                  starting position, this value will allow quick deletion of the
                                  abovely queued wordlist from the indexing LIFO list. Deletion of
                                  queued wordlists becomes absolutely necessary on editing the post.
                                */

                                $idx_lifo_marker = wFilesize ("words/posts_lifo.txt") - $idx_lifo_marker;
                                set ($mdb, "id>$mid", "idx_lifo_length", strval ($idx_lifo_marker));

                              }

                            }

                            /*
                              set variables to locate the new thread in confirmation links
                            */

                            $t = $tid;
                            $m = $mid;

                          }
                          else {

                            $error = true;

                            $form = "Error: ID conflicts.//"
                                  . "Possible problem with counters, please report this.";

                          }

                        }
                        else {

                          $error = true;

                          $form = "Poll options too long.//"
                                . "The limit is $maxpolllen characters. {$string_length_warning}";

                        }

                      }
                      else {

                        $error = true;

                        $form = "Message too long.//"
                              . "The limit is $maxmessagelen characters. {$string_length_warning}";

                      }

                    }
                    else {

                      $error = true;

                      $form = "Void message...//"
                            . "The message associated to a new thread might hold visible text "
                            . "to show in the speech bubble. Write your text in the second, "
                            . "tall text input area below (the large white rectangle).";

                    }

                  }
                  else {

                    $error = true;

                    $form = "Title too long.//"
                          . "The limit is $maxtitlelen characters. {$string_length_warning}";

                  }

                }
                else {

                  $error = true;

                  $form = "Void title...//"
                        . "Threads might have a title to be presented in their parent forum's "
                        . "directory. Such titles might be formed by visible text (no blanks). "
                        . "Write your title in the first, small field below...";

                }

              }

            }

          }
          else {

            $error = true;

            $form = "Whoa! Closed forum.//This forum is closed to new threads submissions, "
                  . "because an admin said so. In closed forums, existing threads may be "
                  . "extended with further posts, and posts edited or deleted as normal, "
                  . "but it isn't possible, except for admins, to start new threads.";

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
      else {

        $error = true;

        $form = "No such forum.//Maybe it has been deleted in the meantime. "
              . "In the worst case, there may be troubles with the forums' index.";

      }

    }
    else {

      /*
        thread argument provided, so storing message as a reply:
        locate target thread record, and that of its parent forum...
      */

      $tdb = "forums" . intervalOf ($t, $bs_threads);
      $thread_record = get ($tdb, "id>$t", "");
      $parent_forum = intval (valueOf ($thread_record, "fid"));
      $forum_record = get ("forums/index", "id>$parent_forum", "");

      if ((!empty ($forum_record)) && (!empty ($thread_record)) && (may_see ($forum_record))) {

        /*
          thread exists AND this member can see that it exists:
          are the thread, or its parent forum, locked, by any chance?
        */

        $forum_access = may_access ($forum_record);
        $thread_access = (($is_admin) || ($is_mod) || (valueOf ($thread_record, "islocked") != "yes")) ? true : false;

        if (($forum_access == true) && ($thread_access == true)) {

          /*
            all authorization checks passed:
            forum and thread exist AND this member can post a reply in this thread, so do it.
          */

          $pmd5 = md5 ($message);

          if (($lpid == $id) && ($lpmd5 == $pmd5)) {

            $error = true;

            $form = "Double post...//This may not be your fault, but a post of the "
                  . "same message was submitted TWICE, from your same accound ID: it "
                  . "may happen, so don't worry. Your message was stored, this note is "
                  . "here to inform you that its second copy was already rejected, so "
                  . "you don't have to delete the twin post manually.";

          }
          else {

            /*
              no double post:
              the above check was made to keep people who may have encountered connection
              problems during the transfer of this page's confirmation message, and who,
              not seeing any confirmation, may try to submit again, involountarily posting
              the same message twice.
            */

            if (($enable_flood_limit == true) && ($lpid == $id) && ($lptime + $antipostflood > $epc)) {

              /*
                flood limit
              */

              $error = true;

              $form = "Too early!//You're submitting multiple new posts too quickly: "
                    . "you have to wait " . intval ($antipostflood / 60) . " minutes "
                    . "between each message submission: it's an anti-flood precaution.";

            }
            else {

              /*
                no flood limit
              */

              if (!empty ($message)) {

                /*
                  non-void message
                */

                if (strlen ($message) <= $maxmessagelen) {

                  /*
                    message fits indicated limit (see "settings.php"):
                    - get next available message ID (from progressive counter),
                    - generate file name ($mdb) of database file holding message record,
                    - continue only if there's no allocated record under that ID...
                  */

                  $mid = intval (get ("stats/counters", "counter>newposts", "count")) + 1;
                  $mdb = "posts" . intervalOf ($mid, $bs_posts);

                  $existing_message_record = get ($mdb, "id>$mid", "");

                  if (empty ($existing_message_record)) {

                    /*
                      write updated counter
                      (before writing record to reduce ID conflict possibilities)
                    */

                    set ("stats/counters", "counter>newposts", "", "<counter>newposts<count>$mid");

                    /*
                      write message record
                    */

                    set ($mdb, "", "", "<id>$mid<tid>$t<author>$nick<date>$epc<message>$message<bcol>$bcol");

                    /*
                      add message ID to parent thread's posts list,
                      and indicate the action (of posting) in target thread's record
                    */

                    $posts = valueOf ($thread_record, "posts");
                    $posts = wExplode (";", $posts);

                    $posts[] = $mid;

                    $thread_record = fieldSet ($thread_record, "posts", implode (";", $posts));

                    $thread_record = fieldSet ($thread_record, "last", "$nick posted here");
                    $thread_record = fieldSet ($thread_record, "laston", strval ($epc));

                    set ($tdb, "id>$t", "", $thread_record);

                    /*
                      locate database file holding message author's record,
                      update posted messages count for that member...
                    */

                    $db = "members" . intervalOf ($id, $bs_members);

                    $pc = intval (get ($db, "id>$id", "posts")) + 1;
                    set ($db, "id>$id", "posts", strval ($pc));

                    /*
                      check if this post caused the member to reach "major age"
                    */

                    majoragepromotioncheck ($id);

                    /*
                      this may seem a strange operation, but it's really simple:
                      it's "bumping" the thread to the top of the forum's directory
                      (held within "forums/<prefix>-<forum-id>"), where <prefix>
                      is "th" for normal threads, and "st" for sticky threads; keeping
                      stickies separated from normal threads greatly simplifies the
                      management and sorting of such threads.
                    */

                    $sticky_thread_entry = get ("forums/st-$parent_forum", "id>$t", "");

                    if (empty ($sticky_thread_entry)) {

                      /*
                        this thread has no entry in sticky threads directory
                        (its ID doesn't appear there): so it must be a normal thread
                      */

                      $prefix = "th";

                    }
                    else {

                      /*
                        this thread has an entry in sticky threads directory
                        (its ID appears there): so it must be a sticky thread
                      */

                      $prefix = "st";

                    }

                    /*
                      so, now "bump" the thread in the directory determined by above code,
                      by deleting its entry (its ID) from the directory in the place it is
                      now, and then re-adding it again on top of the directory: in effects
                      Postline's database engine defined within "suitcase.php" always adds
                      records at the beginning of a file, not at its end.
                    */

                    $bump = get ("forums/$prefix-$parent_forum", "id>$t", "");

                    if (!empty ($bump)) {

                      set ("forums/$prefix-$parent_forum", "id>$t", "", "");        // delete
                      set ("forums/$prefix-$parent_forum", "", "", $bump);          // insert

                    }

                    /*
                      save anti-flood trace
                    */

                    set ("stats/flooders", "entry>post", "", "<entry>post<=>$id+$epc+$pmd5");

                    /*
                      is the post in a public forum or in a hidden forum?
                    */

                    $forum_is_hidden = (valueOf ($forum_record, "istrashed") == "yes") ? true : false;

                    if ($forum_is_hidden == true) {

                      /*
                        if a message is posted in a hidden forum,
                        tell all moderators via an intercom call...
                      */

                      dispatch_intercom_call ($mid);

                    }
                    else {

                      /*
                        if a message is posted in a public forum,
                        add recent discussion entry
                      */

                      $forum_name = valueOf ($forum_record, "name");
                      $thread_title = valueOf ($thread_record, "title");
                      $msg_hint = cutcuts (substr (trim ($message), 0, $hintlength));

                      addrecdisc ($forum_name, $thread_title, $mid, $nick, $msg_hint, $parent_forum, $t);

                      /*
                        now, mark the above "recent discussion entry" as read,
                        at LEAST for the author of this message...
                      */

                      $vrd = getvrd ($id);
                      $vrd[] = $mid;

                      rsort ($vrd, SORT_NUMERIC);
                      setvrd ($id, $vrd);

                      /*
                        index the words in the thread's first message
                        (unless search indexing is globally disabled)
                      */

                      if ($search_disable == false) {

                        /*
                          remember wordlist starting position in LIFO list
                        */

                        $idx_lifo_marker = wFilesize ("words/posts_lifo.txt");
                        set ($mdb, "id>$mid", "idx_lifo_offset", strval ($idx_lifo_marker));

                        /*
                          append wordlist to LIFO list
                        */

                        set_occurrences_of ("posts", $wordlist, $mid);

                        /*
                          remember wordlist size (in bytes) in LIFO list:
                          when this value is non-zero, it means at least one word has to be
                          indexed for the search database: coupled with the corresponding
                          starting position, this value will allow quick deletion of the
                          abovely queued wordlist from the indexing LIFO list. Deletion of
                          queued wordlists becomes absolutely necessary on editing the post.
                        */

                        $idx_lifo_marker = wFilesize ("words/posts_lifo.txt") - $idx_lifo_marker;
                        set ($mdb, "id>$mid", "idx_lifo_length", strval ($idx_lifo_marker));

                      }

                    }

                    /*
                      set variables to locate the new post in confirmation links
                    */

                    $ppp = (valueOf ($thread_record, "ispaged") == "yes") ? 1 : $postsperpage;

                    $f = $parent_forum;
                    $m = $mid;

                    $page = count ($posts) - 1;
                    $page = (int) ($page / $ppp);

                  }
                  else {

                    $error = true;

                    $form = "Error: message ID conflict.//"
                          . "Possible problem with counters, please report this.";

                  }

                }
                else {

                  $error = true;

                  $form = "Message too long.//"
                        . "The limit is $maxmessagelen characters. {$string_length_warning}";

                }

              }
              else {

                $error = true;

                $form = "Void message...//"
                      . "Posted messages might hold visible text "
                      . "to show in the speech bubble. Write your text in the "
                      . "tall text input area below (the large white rectangle).";

              }

            }

          }

        }
        else {

          $error = true;

          $form = "Sorry, access denied.//"
                . "Either this thread, or its parent forum, has been locked. Because of this, "
                . "at the moment only moderators and administrators are allowed to post there.";

        }

      }
      else {

        $error = true;

        $form = "Can't find the forum...//...this thread belongs to. Maybe the parent forum "
              . "has been deleted in the meantime. In the worst case, there may be troubles "
              . "with the forums' index.";

      }

    }

  }

}
else {

  $error = true;

  $form = "You are not logged in.//<a href=mstats.php>go to login panel</a>";

}

/*
 *
 * moderation controls: preloading forum/thread records to check their actual state
 *
 */

if (($is_admin == true) || ($is_mod == true)) {

  if (empty ($t)) {

    /*
      if no thread ID provided as an argument, moderation controls will be for a whole forum,
      but in this case it's however better to ensure filename $tdb and the thread's record to
      be emptied (may they have been loaded before?)
    */

    unset ($tdb);
    unset ($thread_record);

    $forum_record = get ("forums/index", "id>$f", "");

  }
  else {

    /*
      if thread ID provided, controls will be for a thread,
      and it shall locate the thread in its actual forum...
    */

    $tdb = "forums" . intervalOf ($t, $bs_threads);
    $thread_record = get ($tdb, "id>$t", "");
    $parent_forum = intval (valueOf ($thread_record, "fid"));
    $forum_record = get ("forums/index", "id>$parent_forum", "");

  }

}

/*
 *
 * forum locking and unlocking:
 * requires administration access rights
 *
 */

if ($is_admin == true) {

  /*
    if not moderating a thread,
    and the forum record exists...
  */

  if ((empty ($t)) && (!empty ($forum_record))) {

    /*
      get "lock" argument
    */

    $lockstate = ($code_match) ? fget ("lock", 1, "") : "";

    if ($lockstate == "n") {

      /*
        grant exclusive database access,
        check if forum record wasn't deleted while in shared access
      */

      lockSession ();

      $forum_record = get ("forums/index", "id>$f", "");

      if (!empty ($forum_record)) {

        /*
          unlock forum,
          reload $forum_record to keep it up-to-date
        */

        set ("forums/index", "id>$f", "islocked", "");
        $forum_record = get ("forums/index", "id>$f", "");

        /*
          log event in persistent chat logs
        */

        $lw_forum_name = valueOf ($forum_record, "name");
        logwr ("Service communication: &quot;{$lw_forum_name}&quot; has been unlocked.", lw_persistent);

      }

    }

    if ($lockstate == "y") {

      /*
        grant exclusive database access,
        check if forum record wasn't deleted while in shared access
      */

      lockSession ();

      $forum_record = get ("forums/index", "id>$f", "");

      if (!empty ($forum_record)) {

        /*
          lock forum,
          reload $forum_record to keep it up-to-date
        */

        set ("forums/index", "id>$f", "islocked", "yes");
        $forum_record = get ("forums/index", "id>$f", "");

        /*
          log event in persistent chat logs
        */

        $lw_forum_name = valueOf ($forum_record, "name");
        logwr ("Service communication: &quot;{$lw_forum_name}&quot; has been locked.", lw_persistent);

      }

    }

    /*
      depending on the actual value of the "islocked" field,
      tail the appropriate "switch" to moderation controls:
    */

    if (valueOf ($forum_record, "islocked") == "yes") {

      $modcp .= "<br>"
             .  "<a class=ll target=pst href=postform.php?f=$f&amp;code=$code&amp;lock=n>"
             .   "Unlock forum (allow changes)"
             .  "</a>";

    }
    else {

      $modcp .= "<br>"
             .  "<a class=ll target=pst href=postform.php?f=$f&amp;code=$code&amp;lock=y>"
             .   "Lock forum (forbid changes)"
             .  "</a>";

    }

  }

}

/*
 *
 * forum opening and closing:
 * requires administration access rights
 *
 */

if ($is_admin == true) {

  /*
    if not moderating a thread,
    and the forum record exists...
  */

  if ((empty ($t)) && (!empty ($forum_record))) {

    /*
      get "tper" (thread-posting permission) argument
    */

    $tperstate = ($code_match) ? fget ("tper", 1, "") : "";

    if ($tperstate == "n") {

      /*
        grant exclusive database access,
        check if forum record wasn't deleted while in shared access
      */

      lockSession ();

      $forum_record = get ("forums/index", "id>$f", "");

      if (!empty ($forum_record)) {

        /*
          open forum,
          reload $forum_record to keep it up-to-date
        */

        set ("forums/index", "id>$f", "isclosed", "");
        $forum_record = get ("forums/index", "id>$f", "");

        /*
          log event in persistent chat logs
        */

        $lw_forum_name = valueOf ($forum_record, "name");
        logwr ("Service communication: &quot;{$lw_forum_name}&quot; is now open (new threads allowed).", lw_persistent);

      }

    }

    if ($tperstate == "y") {

      /*
        grant exclusive database access,
        check if forum record wasn't deleted while in shared access
      */

      lockSession ();

      $forum_record = get ("forums/index", "id>$f", "");

      if (!empty ($forum_record)) {

        /*
          close forum,
          reload $forum_record to keep it up-to-date
        */

        set ("forums/index", "id>$f", "isclosed", "yes");
        $forum_record = get ("forums/index", "id>$f", "");

        /*
          log event in persistent chat logs
        */

        $lw_forum_name = valueOf ($forum_record, "name");
        logwr ("Service communication: &quot;{$lw_forum_name}&quot; is now closed (new threads forbidden).", lw_persistent);

      }

    }

    /*
      depending on the actual value of the "islocked" field,
      tail the appropriate "switch" to moderation controls:
    */

    if (valueOf ($forum_record, "isclosed") == "yes") {

      $modcp .= "<br>"
             .  "<a class=ll target=pst href=postform.php?f=$f&amp;code=$code&amp;tper=n>"
             .   "Open forum (allow new threads)"
             .  "</a>";

    }
    else {

      $modcp .= "<br>"
             .  "<a class=ll target=pst href=postform.php?f=$f&amp;code=$code&amp;tper=y>"
             .   "Close forum (forbid new threads)"
             .  "</a>";

    }

  }

}

/*
 *
 * the thread's starter, or staff members, may change the behavior of links to the thread
 * in their forum's listing, whereas you may have the thread open the first or last page
 *
 */

$is_starter = (($login == true) && ($nick == valueOf ($thread_record, "starter"))) ? true : false;

if (($is_admin == true) || ($is_mod == true) || ($is_starter == true)) {

  /*
    if moderating a thread,
    and the thread record exists...
  */

  if ((!empty ($t)) && (!empty ($thread_record))) {

    /*
      if thread's parent forum exists, and it's accessible to the member...
    */

    if ((!empty ($forum_record)) && (may_access ($forum_record))) {

      /*
        react to requests to revert the thread to the normal behavior (opens to first post)
      */

      $nlstate = ($code_match) ? fget ("nl", 1, "") : "";

      if ($nlstate == "n") {

        lockSession ();

        /*
          after obtaining exclusive write access by a "lock_session ()",
          the above conditions for thread access are re-checked to ensure
          that the permissions are still valid
          (in theory, an admin may have locked the forum in the meantime)
        */

        $thread_record = get ($tdb, "id>$t", "");
        $parent_forum = intval (valueOf ($thread_record, "fid"));
        $forum_record = get ("forums/index", "id>$parent_forum", "");

        if ((!empty ($forum_record)) && (may_access ($forum_record))) {

          set ($tdb, "id>$t", "nl", "");
          $thread_record = get ($tdb, "id>$t", "");

        }

      }

      /*
        react to requests to make thread "news-like", i.e. opens to the last post
      */

      if ($nlstate == "y") {

        lockSession ();

        /*
          after obtaining exclusive write access by a "lock_session ()",
          the above conditions for thread access are re-checked to ensure
          that the permissions are still valid
          (in theory, an admin may have locked the forum in the meantime)
        */

        $thread_record = get ($tdb, "id>$t", "");
        $parent_forum = intval (valueOf ($thread_record, "fid"));
        $forum_record = get ("forums/index", "id>$parent_forum", "");

        if ((!empty ($forum_record)) && (may_access ($forum_record))) {

          set ($tdb, "id>$t", "nl", "yes");
          $thread_record = get ($tdb, "id>$t", "");

        }

      }

      /*
        depending on the actual value of the "nl" field,
        tail the appropriate "switch" to moderation controls:
      */

      if (valueOf ($thread_record, "nl") == "yes") {

        $modcp .= "<br>"
               .  "<a class=ll href=postform.php?t=$t&amp;code=$code&amp;nl=n>"
               .   "Link brings up last page"
               .  "</a>";

      }
      else {

        $modcp .= "<br>"
               .  "<a class=ll href=postform.php?t=$t&amp;code=$code&amp;nl=y>"
               .   "Link brings up first page"
               .  "</a>";

      }

    }

  }

}

/*
 *
 * both administrators and moderators may lock/unlock/stick/unstick/move threads,
 * except that moderators can't:
 *
 * - move threads OUT of locked or closed forums,
 * - move threads IN to closed forums.
 *
 */

if (($is_admin == true) || ($is_mod == true)) {

  /*
    if moderating a thread,
    and the thread record exists...
  */

  if ((!empty ($t)) && (!empty ($thread_record))) {

    /*
      if thread's parent forum exists, and it's accessible to the member...
      (in fact moderators can't access locked forums)
    */

    if ((!empty ($forum_record)) && (may_access ($forum_record))) {

      /*
        react to thread depaginating request
      */

      $pagstate = ($code_match) ? fget ("paginate", 1, "") : "";

      if ($pagstate == "n") {

        lockSession ();

        /*
          after obtaining exclusive write access by a "lock_session ()",
          the above conditions for thread access are re-checked to ensure
          that the permissions are still valid
          (in theory, an admin may have locked the forum in the meantime)
        */

        $thread_record = get ($tdb, "id>$t", "");
        $parent_forum = intval (valueOf ($thread_record, "fid"));
        $forum_record = get ("forums/index", "id>$parent_forum", "");

        if ((!empty ($forum_record)) && (may_access ($forum_record))) {

          set ($tdb, "id>$t", "ispaged", "");
          $thread_record = get ($tdb, "id>$t", "");

        }

      }

      /*
        react to thread paginating request
      */

      if ($pagstate == "y") {

        lockSession ();

        /*
          after obtaining exclusive write access by a "lock_session ()",
          the above conditions for thread access are re-checked to ensure
          that the permissions are still valid
          (in theory, an admin may have locked the forum in the meantime)
        */

        $thread_record = get ($tdb, "id>$t", "");
        $parent_forum = intval (valueOf ($thread_record, "fid"));
        $forum_record = get ("forums/index", "id>$parent_forum", "");

        if ((!empty ($forum_record)) && (may_access ($forum_record))) {

          set ($tdb, "id>$t", "ispaged", "yes");
          $thread_record = get ($tdb, "id>$t", "");

        }

      }

      /*
        depending on the actual value of the "ispaged" field,
        tail the appropriate "switch" to moderation controls:
      */

      if (valueOf ($thread_record, "ispaged") == "yes") {

        $modcp .= "<br>"
               .  "<a class=ll href=postform.php?t=$t&amp;code=$code&amp;paginate=n>"
               .   "Depaginate thread"
               .  "</a>";

      }
      else {

        $modcp .= "<br>"
               .  "<a class=ll href=postform.php?t=$t&amp;code=$code&amp;paginate=y>"
               .   "Paginate thread"
               .  "</a>";

      }

      /*
        react to thread unlocking request
      */

      $lockstate = ($code_match) ? fget ("lock", 1, "") : "";

      if ($lockstate == "n") {

        lockSession ();

        /*
          after obtaining exclusive write access by a "lock_session ()",
          the above conditions for thread access are re-checked to ensure
          that the permissions are still valid
          (in theory, an admin may have locked the forum in the meantime)
        */

        $thread_record = get ($tdb, "id>$t", "");
        $parent_forum = intval (valueOf ($thread_record, "fid"));
        $forum_record = get ("forums/index", "id>$parent_forum", "");

        if ((!empty ($forum_record)) && (may_access ($forum_record))) {

          set ($tdb, "id>$t", "islocked", "");
          $thread_record = get ($tdb, "id>$t", "");

        }

      }

      /*
        react to thread locking request
      */

      if ($lockstate == "y") {

        lockSession ();

        /*
          after obtaining exclusive write access by a "lock_session ()",
          the above conditions for thread access are re-checked to ensure
          that the permissions are still valid
          (in theory, an admin may have locked the forum in the meantime)
        */

        $thread_record = get ($tdb, "id>$t", "");
        $parent_forum = intval (valueOf ($thread_record, "fid"));
        $forum_record = get ("forums/index", "id>$parent_forum", "");

        if ((!empty ($forum_record)) && (may_access ($forum_record))) {

          set ($tdb, "id>$t", "islocked", "yes");
          $thread_record = get ($tdb, "id>$t", "");

        }

      }

      /*
        depending on the actual value of the "islocked" field,
        tail the appropriate "switch" to moderation controls:
      */

      if (valueOf ($thread_record, "islocked") == "yes") {

        $modcp .= "<br>"
               .  "<a class=ll href=postform.php?t=$t&amp;code=$code&amp;lock=n>"
               .   "Unlock thread"
               .  "</a>";

      }
      else {

        $modcp .= "<br>"
               .  "<a class=ll href=postform.php?t=$t&amp;code=$code&amp;lock=y>"
               .   "Lock thread"
               .  "</a>";

      }

      /*
        react to thread unsticking/unpinning request
      */

      $stickstate = ($code_match) ? fget ("stick", 1, "") : "";

      if ($stickstate == "n") {

        lockSession ();

        /*
          after obtaining exclusive write access by a "lock_session ()",
          the above conditions for thread access are re-checked to ensure
          that the permissions are still valid
          (in theory, an admin may have locked the forum in the meantime,
          or moved the thread to a locked forum...)
        */

        $thread_record = get ($tdb, "id>$t", "");
        $parent_forum = intval (valueOf ($thread_record, "fid"));
        $forum_record = get ("forums/index", "id>$parent_forum", "");

        if ((!empty ($forum_record)) && (may_access ($forum_record))) {

          $sticky_thread_entry = get ("forums/st-$parent_forum", "id>$t", "");
          $is_sticky_thread = (empty ($sticky_thread_entry)) ? false : true;

          if ($is_sticky_thread) {

            /*
              if there was an entry for the thread in its forum's sticky threads directory:
              - insert thread entry into normal threads directory;
              - remove thread entry from sticky threads directory.
            */

            set ("forums/th-$parent_forum", "id>$t", "", $sticky_thread_entry);
            set ("forums/st-$parent_forum", "id>$t", "", "");

          }

        }

      }

      /*
        react to thread sticking/pinning request
      */

      if ($stickstate == "y") {

        lockSession ();

        /*
          after obtaining exclusive write access by a "lock_session ()",
          the above conditions for thread access are re-checked to ensure
          that the permissions are still valid
          (in theory, an admin may have locked the forum in the meantime,
          or moved the thread to a locked forum...)
        */

        $thread_record = get ($tdb, "id>$t", "");
        $parent_forum = intval (valueOf ($thread_record, "fid"));
        $forum_record = get ("forums/index", "id>$parent_forum", "");

        if ((!empty ($forum_record)) && (may_access ($forum_record))) {

          $normal_thread_entry = get ("forums/th-$parent_forum", "id>$t", "");
          $is_normal_thread = (empty ($normal_thread_entry)) ? false : true;

          if ($is_normal_thread) {

            /*
              if there was an entry for the thread in its forum's normal threads directory:
              - insert thread entry into sticky threads directory;
              - remove thread entry from normal threads directory.
            */

            set ("forums/st-$parent_forum", "id>$t", "", $normal_thread_entry);
            set ("forums/th-$parent_forum", "id>$t", "", "");

          }

        }

      }

      /*
        depending on which directory holds the thread's entry,
        tail the appropriate "switch" to moderation controls:
      */

      $sticky_thread_entry = get ("forums/st-$parent_forum", "id>$t", "");

      if (empty ($sticky_thread_entry)) {

        $modcp .= "<br>"
               .  "<a class=ll href=postform.php?t=$t&amp;code=$code&amp;stick=y>"
               .   "Pin thread"
               .  "</a>";

      }
      else {

        $modcp .= "<br>"
               .  "<a class=ll href=postform.php?t=$t&amp;code=$code&amp;stick=n>"
               .   "Unpin thread"
               .  "</a>";

      }

      /*
        the code below controls the act of moving a thread into another forum:
        it starts by checking if the actual forum the thread belongs to is accessible,
        which means it's neither locked nor closed, or that the member's an administrator
      */

      $locked_forum = (valueOf ($forum_record, "islocked") == "yes") ? true : false;
      $closed_forum = (valueOf ($forum_record, "isclosed") == "yes") ? true : false;

      if (($is_admin == true) || (($locked_forum == false) && ($closed_forum == false))) {

        /*
          the "move" argument can be:
          - void, which means the user did nothing to try and move this thread, and the script
            has nothing to do except showing a link that brings to the next step ("to");
          - "to", which means the user wants to move the thread, but still couldn't decide where,
            because no links were provided as a list of possible destinations (so provide list);
          - "do", which means the user selected the destination from the list, and there's another
            argument to indicate it ("toforum"), and it's time to effectively move the thread.
        */

        $move = ($code_match) ? fget ("move", 2, "") : "";

        if (empty ($move)) {

          $modcp .= "<br>"
                 .  "<a class=ll title=\"(open destinations list)\" href=postform.php?t=$t&amp;code=$code&amp;move=to>"
                 .   "Move thread to..."
                 .  "</a>";

        }

        if ($move == "to") {

          $modcp .= "<br>"
                 .  "&lt;&middot;&middot; <a class=ll title=\"(close destinations' list)\" href=postform.php?t=$t>"
                 .   "Move thread to:"
                 .  "</a>";

          /*
            build destinations list:
            list all forums that are not closed (where new threads can't be posted or moved),
            avoid inclusion of the forum the thread is ALREADY within ($fid == $parent_forum)
          */

          $forum = all ("forums/index", makeProper);
          $ready = false;

          foreach ($forum as $f) {

            $fid = valueOf ($f, "id");
            $name = valueOf ($f, "name");

            if (($fid != $parent_forum) && (valueOf ($f, "isclosed") != "yes")) {

              $ready = true;

              $modcp .= "<br>"
                     .  "&nbsp;&nbsp;&gt; <a class=ll href=postform.php?t=$t&amp;code=$code&amp;move=do&amp;toforum=$fid>"
                     .   $name
                     .  "</a>";

            }

          }

          if ($ready == false) {        // self-explanatory

            $form = "No forums to move thread to!//"
                  . "There seem to be no forums other than the one you were browsing, "
                  . "or something's gone really wrong with the forums' index stored "
                  . "in file &quot;forums/index.php&quot;.";

          }

        }

        if ($move == "do") {

          $toforum = intval (fget ("toforum", 10, ""));

          if (!empty ($toforum)) {

            lockSession ();

            /*
              after obtaining exclusive write access by a "lock_session ()",
              all conditions for thread move access are re-checked to ensure
              that the permissions are still valid:
              in theory, an admin may have locked the forum in the meantime,
              or moved the thread to a locked or closed forum...
            */

            $thread_record = get ($tdb, "id>$t", "");
            $parent_forum = intval (valueOf ($thread_record, "fid"));
            $forum_record = get ("forums/index", "id>$parent_forum", "");

            $locked_forum = (valueOf ($forum_record, "islocked") == "yes") ? true : false;
            $closed_forum = (valueOf ($forum_record, "isclosed") == "yes") ? true : false;

            $movable_thread = (($is_admin == true) || (($locked_forum == false) && ($closed_forum == false))) ? true : false;

            if ((!empty ($forum_record)) && ($movable_thread == true)) {

              /*
                locate target forum record, read it, and check if it's a closed forum:
                it evidently wasn't before, when the user selected it as the thread's
                destination, but it may have been closed in the meantime.
                ps. admins may of course still move threads into closed forums.
              */

              $toforum_record = get ("forums/index", "id>$toforum", "");
              $target_is_closed = (valueOf ($toforum_record, "isclosed") == "yes") ? true : false;

              if ((!empty ($toforum_record)) && (($target_is_closed == false) || ($is_admin == true))) {

                /*
                  determine in which directory this thread is being kept (normal, or sticky?)
                */

                $prefix = "th";
                $entry = get ("forums/th-$parent_forum", "id>$t", "");

                if (empty ($entry)) {

                  $prefix = "st";
                  $entry = get ("forums/st-$parent_forum", "id>$t", "");

                }

                if (empty ($entry)) {

                  /*
                    hrm... looks like the thread entry didn't appear in any of the directories of
                    the parent forum: that's weird, and it might not happen in theory.
                  */

                  $form = "Error moving thread!//"
                        . "No thread entry found for this thread (#$t) into its expected "
                        . "forum (#$f), neither in normal nor in sticky threads directory. "
                        . "Looks like the thread's entry disappeared from both of those "
                        . "directories: that's either an error in the database, or some "
                        . "other unsafe intervent has removed that entry.";

                }
                else {

                  /*
                    remove an eventual former ghost on multiple moves of the same thread:
                    the "from" field of the actual entry may hold its former parent forum ID,
                    unless thread history was cleared (see later code about clearing that)
                  */

                  $entry_from = valueOf ($entry, "from");

                  if (!empty ($entry_from)) {

                    set ("forums/$prefix-$entry_from", "id>$t", "", "");

                  }

                  /*
                    update actual thread entry and its ghost entry to account for the move:
                    this is what constitutes part of the thread's "history" that could be
                    erased via some code that comes later in this script...
                  */

                  $old_entry = get ("forums/$prefix-$toforum", "id>$t", "");
                  $tc_increase = (empty ($old_entry)) ? 1 : 0;

                  $ghost_entry = fieldSet ($entry, "from", "");
                  $ghost_entry = fieldSet ($ghost_entry, "to", strval ($toforum));
                  set ("forums/$prefix-$parent_forum", "id>$t", "", $ghost_entry);

                  $new_entry = fieldSet ($entry, "from", strval ($parent_forum));
                  $new_entry = fieldSet ($new_entry, "to", "");
                  set ("forums/$prefix-$toforum", "id>$t", "", $new_entry);

                  /*
                    update thread counters for source and destination forums:
                    ghosts don't count, so subtract 1 from the source forum's counter
                  */

                  $fromforumtc = intval (get ("forums/index", "id>$parent_forum", "tc")) - 1;
                  $toforumtc = intval (get ("forums/index", "id>$toforum", "tc")) + $tc_increase;

                  set ("forums/index", "id>$parent_forum", "tc", strval ($fromforumtc));
                  set ("forums/index", "id>$toforum", "tc", strval ($toforumtc));

                  /*
                    update thread record to reflect its new parent forum ID
                  */

                  set ($tdb, "id>$t", "fid", strval ($toforum));

                  /*
                    update thread record to account for move ("moveby", "movefrom", "moveto")
                  */

                  $fromforumname = get ("forums/index", "id>$parent_forum", "name");
                  $toforumname = get ("forums/index", "id>$toforum", "name");

                  set ($tdb, "id>$t", "moveby", $nick);
                  set ($tdb, "id>$t", "movefrom", $fromforumname);
                  set ($tdb, "id>$t", "moveto", $toforumname);

                  /*
                    update recent discussions archive to reflect the move:
                    what this does depends on which kind of forum (public or hidden)
                    the thread is being moved to...
                  */

                  $target_is_hidden = (valueOf ($toforum_record, "istrashed") == "yes") ? true : false;

                  if ($target_is_hidden == true) {

                    /*
                      if destination forum is hidden, remove recent discussions
                      - load recent discussions archive ($rd_o), build an array ($rd_a) out
                        of its newline-delimited records, initialize filtered string ($rd_f)
                        to hold the new version of the archive, scan $rd_a looking for all
                        records where the thread ID ($_t) corresponds to this thread ($t),
                        remove those entries, and write back the archive only if at least a
                        record was changed ($rd_m).
                    */

                    $rd_a = all ("forums/recdisc", asIs);
                    $rd_f = array ();
                    $rd_m = false;

                    foreach ($rd_a as $r) {

                      list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);

                      if ($_t == $t) {

                        $rd_m = true;

                      }
                      else {

                        $rd_f[] = implode (">", array ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t));

                      }

                    }

                    if ($rd_m) {

                      asm ("forums/recdisc", $rd_f, asIs);

                    }

                  }
                  else {

                    /*
                      if destination forum is public, point recent discussions there:
                      - load recent discussions archive ($rd_o), build an array ($rd_a) out
                        of its newline-delimited records, initialize filtered string ($rd_f)
                        to hold the new version of the archive, scan $rd_a looking for all
                        records where the thread ID ($_t) corresponds to this thread ($t),
                        replace parent forum name ($_F) and ID ($_f) with those of the new
                        forum ($toforumname, $toforum), and finally write back the archive
                        only if at least a record was changed ($rd_m).
                    */

                    $rd_a = all ("forums/recdisc", asIs);
                    $rd_f = array ();
                    $rd_m = false;

                    foreach ($rd_a as $r) {

                      list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);

                      if ($_t == $t) {

                        $rd_m = true;

                        $_F = $toforumname;
                        $_f = $toforum;

                      }

                      $rd_f[] = implode (">", array ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t));

                    }

                    if ($rd_m) {

                      asm ("forums/recdisc", $rd_f, asIs);

                    }

                  }

                  /*
                    confirm that everything seems to be well
                  */

                  $form = "Thread moved successfully.//Note: "
                        . "whenever you move a thread, a ghost icon remains in the source forum, "
                        . "with a link pointing members to the destination forum: you may use "
                        . "the link at the bottom of this panel to remove the ghost. If, instead, "
                        . "you want to completely remove all hints about the fact that you have "
                        . "moved this thread, use the link to clear its history.";

                  /*
                    update thread record and parent forum ID in memory
                  */

                  $thread_record        = get ($tdb, "id>$t", "");
                  $parent_forum         = $toforum;

                }

              }
              else {

                $form = "Invalid destination forum!//"
                      . "It may no longer be in the forums' index (forums/index.php) "
                      . "but perhaps an admin has deleted or closed the destination forum "
                      . "while you were selecting it as this thread's destination; either "
                      . "this, or the forums' index has been corrupted.";

              }

            }
            else {

              $form = "Can't access source forum!//"
                    . "It may have been locked or closed while you were selecting this thread's "
                    . "destination forum. Either this, or the forums' index has been corrupted. "
                    . "In effect, if a forum has been closed or locked, it takes administration "
                    . "access rights to move a thread out of that forum.";

            }

          }

        }

      }

    }

  }

}

/*
 *
 * both admins and mods may clear a threads history notices
 * (this also removes corresponding ghost)
 *
 */

if (($is_admin == true) || ($is_mod == true)) {

  if ((!empty ($t)) && (!empty ($thread_record))) {

    $ch = ($code_match) ? fget ("ch", 1, "") : "";

    if ($ch == "y") {

      lockSession ();

      $thread_record = get ($tdb, "id>$t", "");
      $parent_forum = intval (valueOf ($thread_record, "fid"));
      $forum_record = get ("forums/index", "id>$parent_forum", "");

      if (!empty ($forum_record)) {

        $thread_record = fieldSet ($thread_record, "moveby", "");
        $thread_record = fieldSet ($thread_record, "movefrom", "");
        $thread_record = fieldSet ($thread_record, "moveto", "");
        $thread_record = fieldSet ($thread_record, "retitleby", "");
        $thread_record = fieldSet ($thread_record, "retitlefrom", "");

        set ($tdb, "id>$t", "", $thread_record);

        $ghost_parent = get ("forums/th-$parent_forum", "id>$t", "from");

        if (empty ($ghost_parent)) {

          $ghost_parent = get ("forums/st-$parent_forum", "id>$t", "from");

        }

        if (!empty ($ghost_parent)) {

          set ("forums/th-$parent_forum", "id>$t", "from", "");
          set ("forums/st-$parent_forum", "id>$t", "from", "");
          set ("forums/th-$ghost_parent", "id>$t", "", "");
          set ("forums/st-$ghost_parent", "id>$t", "", "");

        }

      }

    }
    else {

      $moveby = valueOf ($thread_record, "moveby");
      $retitleby = valueOf ($thread_record, "retitleby");

      $was_moved = (empty ($moveby)) ? false : true;
      $was_retitled = (empty ($retitleby)) ? false : true;

      if (($was_moved == true) || ($was_retitled == true)) {

        $modcp .= "<br>"
               .  "<a class=ll href=postform.php?t=$t&amp;code=$code&amp;ch=y>"
               .   "Clear all thread's history"
               .  "</a>";

      }

    }

  }

}

/*
 *
 * both administrators and moderators may remove a thread's ghost entry
 * (left in source forum upon moving the thread)
 *
 */

if (($is_admin == true) || ($is_mod == true)) {

  if ((!empty ($t)) && (!empty ($thread_record))) {

    $ghost_parent = get ("forums/th-$parent_forum", "id>$t", "from");

    if (empty ($ghost_parent)) {

      $ghost_parent = get ("forums/st-$parent_forum", "id>$t", "from");

    }

    if (!empty ($ghost_parent)) {

      $rg = ($code_match) ? fget ("rg", 1, "") : "";

      if ($rg == "y") {

        lockSession ();

        $thread_record = get ($tdb, "id>$t", "");
        $parent_forum = intval (valueOf ($thread_record, "fid"));
        $forum_record = get ("forums/index", "id>$parent_forum", "");
        $ghost_remove = empty ($forum_record) ? false : true;

        if ($ghost_remove) {

          $ghost_parent = get ("forums/th-$parent_forum", "id>$t", "from");
          $ghost_parent = empty ($ghost_parent) ? get ("forums/st-$parent_forum", "id>$t", "from") : $ghost_parent;
          $ghost_remove = empty ($ghost_parent) ? false : true;

          if ($ghost_remove) {

            set ("forums/th-$parent_forum", "id>$t", "from", "");
            set ("forums/st-$parent_forum", "id>$t", "from", "");
            set ("forums/th-$ghost_parent", "id>$t", "", "");
            set ("forums/st-$ghost_parent", "id>$t", "", "");

          }

        }

      }
      else {

        $modcp .= "<br>"
               .  "<a class=ll href=postform.php?t=$t&amp;code=$code&amp;rg=y>"
               .   "Remove ghost icon only"
               .  "</a>";

      }

    }

  }

}

/*
 *
 * form initialization
 *
 */

if ($error == false) {

  /*
    confirmation message
  */

  $form =

      makeframe (

          "Message stored...",

              "<a " . link_to_posts ("pan", $t, $page, $m, true) . ">Review message</a>"
        . "<br><a href=edit.php?m=$m>Make changes</a>"
        . "<br><a " . link_to_threads ("pan", $f, "") . ">Index of this forum</a>"
        . "<br><a " . link_to_forums ("pan") . ">Index of all forums</a>", true

      );

}
else {

  /*
    build post-a-message form
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

      ) : "");

  /*
    if not logged in, all the rest is witheld:
    it will only output an error message ("you are not logged in")
  */

  if ($login == true) {

    if (empty ($t)) {

      /*
        form to post a new thread
      */

      $form .= "<table width=$pw>"
            .  "<form name=postform action=postform.php?f=$f enctype=multipart/form-data method=post>"
            .  "<input type=hidden name=code value=$code>"
            .   "<td height=20 class=inv align=center>"
            .    "new thread title, topic:"
            .   "</td>"
            .  "</table>"

            .  $inset_bridge

            .  $opcart
            .   "<td class=in>"
            .    "<input class=sf onChange=document.prevform.title.value=document.postform.title.value style=width:{$iw}px type=text name=title value=\"$title_in\" maxlength=$maxtitlelen>"
            .   "</td>"
            .  $clcart

            .  $inset_shadow

            .  "<table width=$pw>"
            .   "<td height=20 class=inv align=center>"
            .    "starter message text:"
            .   "</td>"
            .  "</table>"

            .  $inset_bridge

            .  $opcart
            .   "<td>"
            .    "<textarea onChange=document.prevform.message.value=document.postform.message.value style=width:{$iw}px;height:320px name=message>"
            .     $message_in
            .    "</textarea>"
            .   "</td>"
            .  $clcart

            .  $inset_shadow

            .  "<table width=$pw>"
            .   "<td height=20 class=inv align=center>"
            .    "poll options, if any:"
            .   "</td>"
            .  "</table>"

            .  $inset_bridge

            .  $opcart
            .   "<td>"
            .    "<textarea onChange=document.prevform.poll.value=document.postform.poll.value style=width:{$iw}px;height:80px name=poll>"
            .     $poll
            .    "</textarea>"
            .   "</td>"
            .  $clcart

            .  $inset_shadow

            .  "<table width=$pw>"
            .   "<tr>"
            .    "<td height=20 class=inv align=center>"
            .     "operations:"
            .    "</td>"
            .   "</tr>"
            .   $bridge
            .   "<tr>"
            .    "<td>"
            .     "<input class=su style=width:{$pw}px type=submit name=submit value=\"submit new thread\">"
            .    "</td>"
            .   "</tr>"
            .   $bridge
            .

          (($emlist)

            ?   "<tr>"
            .    "<td>"
            .     "<input class=ky style=width:{$pw}px type=submit name=submit value=\"hide emoticons list\">"
            .    "</td>"
            .   "</tr>"

            .  "</table>"

            .  $inset_bridge

            .  $opcart
            .   "<td class=ls>"
            .    $emtable
            .   "</td>"
            .  $clcart

            .  "<table width=$pw>"

            :   "<tr>"
            .    "<td>"
            .     "<input class=ky style=width:{$pw}px type=submit name=submit value=\"show emoticons list\">"
            .    "</td>"
            .   "</tr>"

          )

            .   $bridge
            .  "</form>"
            .  "<form name=prevform action=preview.php target=pan enctype=multipart/form-data method=post>"
            .  "<input type=hidden name=thread value=1>"
            .  "<input type=hidden name=forum value=\"$forum_name\">"
            .  "<input type=hidden name=fid value=$f>"
            .  "<input type=hidden name=title value=\"$title_in\">"
            .  "<input type=hidden name=author value=\"$nick\">"
            .  "<input type=hidden name=message value=\"$message_in\">"
            .  "<input type=hidden name=poll value=\"$poll\">"
            .   "<tr>"
            .    "<td>"
            .     "<input class=ky style=width:{$pw}px type=submit name=submit value=preview>"
            .    "</td>"
            .   "</tr>"
            .  "</form>"
            .   $bridge
            .  "<form action=postform.php?f=$f&ex=show enctype=multipart/form-data method=post>"
            .   "<tr>"
            .    "<td>"
            .     "<input class=ky style=width:{$pw}px type=submit name=submit value=\"fill with example thread\">"
            .    "</td>"
            .   "</tr>"
            .  "</form>"
            .  "</table>"

            .  $inset_shadow
            .

          makeframe (

               "Instructions:",

               "Above is a form to start a thread. "
            .  "The field on top holds the title of the thread, the first text area below is used "
            .  "to write the message's text (limit: $maxmessagelen characters). The second, smaller "
            .  "text area, could be filled with a semicolon - delimited list of options: those will "
            .  "define a poll to be attached to that thread. If the second area is left void, the "
            .  "thread will have no poll. When you're done, click 'submit new thread'.", true

          )

            .  "\n"
            .  "<script language=Javascript type=text/javascript>\n"
            .  "<!--\n"
            .    "document.postform.title.focus();\n"
            .  "//-->\n"
            .  "</script>\n";

    }
    else {

      /*
        form to post a message in reply to an existing thread
      */

      $form .= "<table width=$pw>"
            .  "<form name=postform action=postform.php?t=$t enctype=multipart/form-data method=post>"
            .  "<input type=hidden name=code value=$code>"
            .   "<td height=20 class=inv align=center>"
            .    "type your reply:"
            .   "</td>"
            .  "</table>"

            .  $inset_bridge

            .  $opcart
            .   "<td>"
            .    "<textarea onChange=document.prevform.message.value=document.postform.message.value style=width:{$iw}px;height:240px name=message>"
            .     $message_in
            .    "</textarea>"
            .   "</td>"
            .  $clcart

            .  $inset_shadow

            .  "<table width=$pw>"
            .   "<tr>"
            .    "<td height=20 class=inv align=center>"
            .     "operations:"
            .    "</td>"
            .   "</tr>"
            .   $bridge
            .   "<tr>"
            .    "<td>"
            .     "<input class=su style=width:{$pw}px type=submit name=submit value=\"post reply\">"
            .    "</td>"
            .   "</tr>"
            .   $bridge
            .

          (($emlist)

            ?   "<tr>"
            .    "<td>"
            .     "<input class=ky style=width:{$pw}px type=submit name=submit value=\"hide emoticons list\">"
            .    "</td>"
            .   "</tr>"

            .  "</table>"

            .  $inset_bridge

            .  $opcart
            .   "<td class=ls>"
            .    $emtable
            .   "</td>"
            .  $clcart

            .  "<table width=$pw>"

            :   "<tr>"
            .    "<td>"
            .     "<input class=ky style=width:{$pw}px type=submit name=submit value=\"show emoticons list\">"
            .    "</td>"
            .   "</tr>"

          )

            .   $bridge
            .  "</form>"
            .  "<form name=prevform action=preview.php target=pan enctype=multipart/form-data method=post>"
            .  "<input type=hidden name=forum value=\"$forum_name\">"
            .  "<input type=hidden name=fid value=$parent_forum>"
            .  "<input type=hidden name=title value=\"$thread_title\">"
            .  "<input type=hidden name=tid value=$t>"
            .  "<input type=hidden name=author value=\"$nick\">"
            .  "<input type=hidden name=message value=\"$message_in\">"
            .   "<tr>"
            .    "<td>"
            .     "<input class=ky style=width:{$pw}px type=submit name=submit value=preview>"
            .    "</td>"
            .   "</tr>"
            .  "</form>"
            .  "</table>"

            .  $inset_shadow
            .

          makeframe (

               "Instructions:",
               "Write your reply in the above form (limit: $maxmessagelen characters). ", true

          )

            .  "\n"
            .  "<script language=Javascript type=text/javascript>\n"
            .  "<!--\n"
            .    "document.postform.message.focus();\n"
            .  "//-->\n"
            .  "</script>\n";

      /*
        append a couple links in the utilities box ($modcp, stands for "moderation control panel")
        to reload either the first or the last page of the thread the user's posting a reply into:
        they may be useful, the first page to see what was the topic about in the beginning, while
        the last page holds the latest posts in chronological order.
      */

      $modcp .= "<br>"
             .  "<a class=ll target=pan title=\"What was the discussion about...\" href=posts.php?t=$t>"
             .   "Reload thread's first page"
             .  "</a>"
             .  "<br>"
             .  "<a class=ll target=pan title=\"Review this thread's most recent posts...\" href=posts.php?t=$t&amp;p=last#lastpost>"
             .   "Reload thread's last page"
             .  "</a>";

    }

  }

}

/*
 *
 * appending moderation controls and utilities,
 * building the utility box only if at least one link is provided:
 * the call to "substr" cuts first 4 characters of $modcp (the first <br> tag)
 *
 */

if (!empty ($modcp)) {

  $modcp = "<table width=$pw>"
         .  "<td height=20 class=inv align=center>"
         .   "utilities:"
         .  "</td>"
         . "</table>"

         . $inset_bridge

         . $opcart
         .  "<td class=ls>"
         .   substr ($modcp, 4)
         .  "</td>"
         . $clcart

         . $inset_shadow;

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

if ($dontrememberthisurl == false) {

  include ("setlfcookie.php");

}

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($postpanel));



?>
