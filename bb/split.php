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

$em['moderation_rights_required'] = "MODERATION RIGHTS REQUIRED";
$ex['moderation_rights_required'] =

        "The link you have been following leads to a section of this system which is restricted "
      . "to members which have been authenticated as moderators or administrators. If you might "
      . "not be seeing this message, please make sure that your login cookie is intact and that "
      . "you are accessing this site from the same domain you used to perform the login.";

/*
 *
 * this script is generally restricted to administrators and moderators
 *
 */

if (($is_admin == false) && ($is_mod == false)) {

  unlockSession ();
  die (because ('moderation_rights_required'));

}

/*
 *
 * initialize accumulators
 *
 */

$form = "";
$conf = "";
$error = false;
$dontrememberthisurl = isset ($_POST["submit"]);

/*
 *
 * get parameters
 *
 */

$m = intval (fget ("m", 1000, ""));     // message ID
$submit = fget ("submit", 1000, "");    // submission trigger

/*
 *
 * check security code on submission of splitter's form
 *
 */

if ($submit) {

  $code = intval (fget ("code", 1000, ""));
  $submit = ($code == getcode ("pst")) ? $submit : false;

}

/*
 *
 * thread splitting operations
 *
 */

if ($submit == "yes, split") {

  /*
    split confirmed:
    get exclusive access to database, locate thread to split and check if everything is alright,
    but remember to properly hide unaccessible forums... it may sound useless because typically,
    moderators are never forbidden to see forums, but the may_see function could, in the future,
    eventually manage access verification to admin-only forums...
  */

  lockSession ();

  $mdb = "posts" . intervalOf ($m, $bs_posts);
  $message_record = get ($mdb, "id>$m", "");
  $t = intval (valueOf ($message_record, "tid"));
  $tdb = "forums" . intervalOf ($t, $bs_threads);
  $thread_record = get ($tdb, "id>$t", "");
  $parent_forum = intval (valueOf ($thread_record, "fid"));
  $forum_record = get ("forums/index", "id>$parent_forum", "");

  if ((empty ($forum_record)) || (may_see ($forum_record) == false)) {

    $error = true;
    $form = "Can't locate parent forum.";

  }
  else {

    /*
      user is allowed to see that the forum exists, and in fact the forum exists:
      - can user make changes to threads in this forum? (if locked, only admins can)
      - is the forum closed to new threads? (if it is, threads can only be split by admins)
    */

    if ($is_admin) {

      $is_not_closed = true;
      $is_not_locked = true;

    }
    else {

      $is_not_closed = (valueOf ($forum_record, "isclosed") != "yes") ? true : false;
      $is_not_locked = (valueOf ($forum_record, "islocked") != "yes") ? true : false;

    }

    if ((may_access ($forum_record)) && ($is_not_closed) && ($is_not_locked)) {

      $posts = wExplode (";", valueOf ($thread_record, "posts"));

      /*
        splitting is allowed only beginning from the second post onwards:
        check for this by comparing the message ID with that of the first
        post of the thread's list (index 0 of the $posts array)...
      */

      if ($posts[0] == $m) {

        $error = true;

        $form = "Can't split from first post!//"
              . "It seems that the message indicated as an argument to the "
              . "split function is actually the VERY FIRST message of its "
              . "thread. It is impossible to split a thread from its first "
              . "message (the starter), because otherwise the parent thread "
              . "would be devoided of all its posts, becoming an unmanageable "
              . "paradox in the database. This could have appened, for example, "
              . "if you were splitting the thread from its second posts, but in "
              . "the meantime some moderator deleted the first post, causing the "
              . "message indicated by you to become a thread's starter.";

      }
      else {

        /*
          split the posts list in two, as expected
        */

        $pi = array_search ($m, $posts);
        $s1 = array_slice ($posts, 0, $pi);
        $s2 = array_slice ($posts, $pi);

        if (count ($s2) <= $maxpostsinchild) {

          /*
            allocate a new thread ID for the split part, check for conflicts
          */

          $tid = intval (get ("stats/counters", "counter>newthreads", "count")) + 1;
          $udb = "forums" . intervalOf ($tid, $bs_threads);

          $existing_record = get ($udb, "id>$tid", "");

          if (empty ($existing_record)) {

            /*
              update overall threads counter
            */

            set ("stats/counters", "counter>newthreads", "", "<counter>newthreads<count>$tid");

            /*
              save thread entry in parent forum, update parent forum's threads count ($tc)
            */

            $tc = intval (get ("forums/index", "id>$parent_forum", "tc")) + 1;

            set ("forums/th-$parent_forum", "", "id", strval ($tid));
            set ("forums/index", "id>$parent_forum", "tc", strval ($tc));

            /*
              generate title of split part (the "child thread")
            */

            $title = "Split of:" . chr (32) . valueOf ($thread_record, "title");
            $title = cutcuts (substr ($title, 0, $maxtitlelen));

            /*
              retrieve new starter of split part (= author of first message in child thread):
              on error (but why?) let's say the starter will be the moderator who splitted it...
            */

            $first_message_in_child = intval ($s2[0]);

            $mdb = "posts" . intervalOf ($first_message_in_child, $bs_posts);
            $author_of_first_message = get ($mdb, "id>$first_message_in_child", "author");

            $starter =

                   (empty ($author_of_first_message))

                     ? $nick
                     : $author_of_first_message;

            /*
              save child thread record
            */

            set (

              $udb, "", "",

              "<id>$tid<fid>$parent_forum<title>$title<posts>" // (note: no posts so far)
            . "<starter>$starter<date>$epc<last>created by $nick<laston>$epc"

            );

            /*
              update threads count of starter of child thread,
              and list thread as part of his own threads...
            */

            $sid = intval (get ("members/bynick", "nick>$starter", "id"));
            $sdb = "members" . intervalOf ($sid, $bs_members);

            $sr = get ($sdb, "id>$sid", "");
            $starter_exists = (empty ($sr)) ? false : true;

            if ($starter_exists) {

              $tc = intval (valueOf ($sr, "threads")) + 1;
              $sr = fieldSet ($sr, "threads", strval ($tc));

              $tl = wExplode (";", valueOf ($sr, "threads_list"));
              $tl[] = $tid;
              $tl = implode (";", $tl);
              $sr = fieldSet ($sr, "threads_list", $tl);

              set ($sdb, "id>$sid", "", $sr);

            }

            /*
              update all message records of the child thread so they reflect their new location,
              but hmm... now that I think to it, this may take quite a crapload of time if the
              original thread has been split from very early posts, and consequentially the child
              thread now has a lot of messages: well, I think I can't help in such a situation,
              but I *suppose* it might be a rare occurrence... I mean, how probable is that
              someone noticed a thread needs splitting only after many posts went off-topic?
              --
              know what? the consequences of a partial split that terminates here due to time out
              from the PHP parser would be rather paradoxical so I'm gonna add a check concerning
              the length of the split part: if it's longer than allowed, it won't be split... and
              any late moderators how tried to will have to be at peace with that...
            */

            foreach ($s2 as $mid) {

              $mid = intval ($mid);
              $mdb = "posts" . intervalOf ($mid, $bs_posts);
              $post_record_exists = (get ($mdb, "id>$mid", "") == "") ? false : true;

              if ($post_record_exists) {

                set ($mdb, "id>$mid", "tid", "$tid");

              }

            }

            /*
              update posts list in both parent and child thread
            */

            set ($tdb, "id>$t", "posts", implode (";", $s1));
            set ($udb, "id>$tid", "posts", implode (";", $s2));

            /*
              eeh, it'll also have to update the recent discussions archive to account
              for the migration of selected messages from the parent to the child thread...
            */

            $old_recent_posts = all ("forums/recdisc", asIs);
            $new_recent_posts = array ();
            $archive_changed = false;

            foreach ($old_recent_posts as $r) {

              list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);

              if (in_array ($_m, $s2)) {

                $archive_changed = true;

                $_T = $title;
                $_t = $tid;

              }

              $new_recent_posts[] = implode (">", array ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t));

            }

            if ($archive_changed) {

              asm ("forums/recdisc", $new_recent_posts, asIs);

            }

          }
          else {

            $error = true;

            $form = "Error: thread ID conflict.//"
                  . "Possible problem with counters, please report this.";

          }

        }
        else {

          $error = true;

          $form = "Child thread is too long.//"
                . "Sorry but you're a bit too late splitting this thread: it accumulated too "
                . "many replies after the message you tried to split the thread from, and it "
                . "is technically not prudent to try updating all of those messages' records "
                . "in the same run, making it technically impossible to split the thread. "
                . "You have been hitting a safety limit of the forum system to avoid overload "
                . "on the server: there is nothing to do and this is not a real error. Only, "
                . "next time, please try to spot off-topicism earlier...";

        }

      }

    }
    else {

      $error = true;

      $form = "Parent forum is protected!//"
            . "Only admininstrators may split threads in locked or closed forums.";

    }

  }

}
else {

  /*
    no real error - only to show confirmation request
  */

  $error = true;

  /*
    locate parent forum and check access rights anyway,
    because the thread ID isn't provided as an argument (it would be useless since it can be
    deduced by looking up the message record) but still $t is expected to be initialized for
    visualization of utility links (which are not supposed to work if user can't see forum).
  */

  $mdb = "posts" . intervalOf ($m, $bs_posts);
  $message_record = get ($mdb, "id>$m", "");
  $t = intval (valueOf ($message_record, "tid"));
  $tdb = "forums" . intervalOf ($t, $bs_threads);
  $thread_record = get ($tdb, "id>$t", "");
  $parent_forum = intval (valueOf ($thread_record, "fid"));
  $forum_record = get ("forums/index", "id>$parent_forum", "");

  if (may_see ($forum_record) == false) {

    $t = 0;

  }

  /*
    not confirmed, was it cancelled?
  */

  if ($submit == "no, don't split") {

    /*
      yeah, split cancelled
    */

    $form  = "Split: operation cancelled!//Thread has not been split."
           . "<hr><a " . link_to_posts ("pan", $t, "", "", false) . ">Return to target thread</a>"
           . "<br><a " . link_to_forums ("pan") . ">Index of all forums</a>";

  }
  else {

    /*
      nope, not even a cancel: it means the user didn't answer yet, so ask confirmation:
      - in the form, method is POST, but $t/$m arguments are via GET, so they doen't get
        lost on reloads and can be recoveded upon showing/hiding the chat frame...
    */

    $form = "Please confirm split!//"
          . "Operating the split button of a message in a thread will cause the thread to be "
          . "split in two: the &quot;parent&quot; thread will contain all messages before the "
          . "one of which you clicked the split button, the &quot;child&quot; thread will be "
          . "holding the messages from (and including) the message of which you clicked the "
          . "split button. Splitting is recommended if, with that message, the thread went too "
          . "much off-topic and could be therefore considered as a different discussion. "
          . "So, are you sure you want to split the thread?";

    $conf = "<table width=$pw>"
          .  "<form action=split.php?t=$t&amp;m=$m enctype=multipart/form-data method=post>"
          .  "<input type=hidden name=code value=" . setcode ("pst") . ">"
          .  "<tr>"
          .   "<td height=20 class=inv align=center>"
          .    "OK TO SPLIT?"
          .   "</td>"
          .  "</tr>"
          .  $bridge
          .  "<tr>"
          .   "<td>"
          .    "<input class=su style=width:{$pw}px type=submit name=submit value=\"yes, split\">"
          .   "</td>"
          .  "</tr>"
          .  $bridge
          .  "<tr>"
          .   "<td>"
          .    "<input class=ky style=width:{$pw}px type=submit name=submit value=\"no, don't split\">"
          .   "</td>"
          .  "</tr>"
          .  $shadow
          .  "</form>"
          . "</table>";

  }

}

/*
 *
 * form initialization
 *
 */

if ($error) {

  list ($frame_title, $frame_contents) = explode ("//", $form);

  $form =

        makeframe (

          $frame_title, false, false

        )

        .

      (($frame_contents)

        ?

        makeframe (

          "details", $frame_contents, true

        )

        : "") . $conf;

}
else {

  $form =

        makeframe (

          "Thread has been split!",

              "<a " . link_to_posts ("pan", $tid, "", "", false) . ">Display the split part</a>"
        . "<br><a href=retitle.php?t=$tid>Retitle the split part</a>"
        . "<br><a href=postform.php?t=$t>Post in parent thread</a>"
        . "<br><a href=postform.php?t=$tid>Post in split thread</a>"
        . "<br><a " . link_to_threads ("pan", $parent_forum, "") . ">Index of this forum</a>"
        . "<br><a " . link_to_forums ("pan") . ">Index of all forums</a>", true

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
