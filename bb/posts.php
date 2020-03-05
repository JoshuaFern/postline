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
require ("suitcase.php");
require ("sessions.php");
require ("template.php");

/*
 *
 * initialize output accumulators
 *
 */

$phtm = "";                             // poll HTML output
$paging = "<a class=alert>1</a>";       // paging links
$forum_name = "";                       // thread's parent forum name
$thread_title = "";                     // messages' parent thread title
$error = false;                         // error flag
$witheld_tracking = false;              // URL tracked unless listing a thread in a hidden forum
$ignored_messages_present = false;      // assume no ignored messages found so far
$list = "";                             // void list
unset ($message);                       // set in loop, checked for permalink in paginated threads

/*
 *
 * set void array of "Messages On Page" for marking posts read, later:
 * it's an array made of message IDs appearing in this page
 *
 */

$mop = array ();

/*
 *
 * get parameters
 *
 */

$t = intval (fget ("t", 10, ""));       // thread ID
$page = fget ("p", 1000, "");           // page number (can be alphabetic in special requests)

/*
 *
 * locate thread, and its parent forum:
 * - $tdb is the generated database file name basing on the thread ID ($t);
 * - thread's record is read and its parent forum Id ($fid) is passed to $parent_forum;
 * - forum's record is read, and finally checked for existence and visibility.
 *
 * note: forum (and its threads) visibility depends both on the existence of the forum,
 * and on the member's account authorization level (the return value of "may_see"), and
 * it's important that both a non-existing forum record and an invisible one will cause
 * exactly the same reaction from such a script. Otherwise, existence of a hidden forum
 * may be involountary revealed by using a different error message.
 *
 */

$tdb = "forums" . intervalOf ($t, $bs_threads);
$thread_record = get ($tdb, "id>$t", "");
$parent_forum = intval (valueOf ($thread_record, "fid"));
$forum_record = get ("forums/index", "id>$parent_forum", "");

if ((empty ($forum_record)) || (may_see ($forum_record) == false)) {

  $error = true;
  $list = "&nbsp;&nbsp;No such thread.<br><br>";

  $parent_forum = 0;    // in case it exists, but hidden, force zero here (a "not found" forum)

}
else {

  $forum_name = valueOf ($forum_record, "name");
  $thread_title = valueOf ($thread_record, "title");
  $witheld_tracking = (valueOf ($forum_record, "istrashed") == "yes") ? true : false;

}

/*
 *
 * check authorizations to provide links to message editing functions
 *
 */

$unlock_st = 0;
$unlock_ar = 0;

$is_locked = false;
$is_closed = false;

if ($is_mod == true) $unlock_st = 1;
if ($is_admin == true) $unlock_st = 2;

if ($error == false) {

  if (valueOf ($thread_record, "islocked") == "yes") {

    /*
      editing in locked threads requires moderation rights
    */

    $unlock_ar = 1;
    $is_locked = true;

  }

  if (valueOf ($forum_record, "islocked") == "yes") {

    /*
      editing in locked forums requires administration rights
    */

    $unlock_ar = 2;
    $is_locked = true;

  }

  if (valueOf ($forum,  "isclosed") == "yes") {

    /*
      this causes a warning message "this forum is closed" to appear:
      yet, it'll be a link to the post-a-thread panel if user is an administrator;
      it's also used as a flag to hide the "split thread" links to moderators.
    */

    $is_closed = true;

  }

}

if ($unlock_st >= $unlock_ar) {

  $showeditlinks = true;

}
else {

  $showeditlinks = false;

}

/*
 *
 * generate list of logged-in users reading this thread
 *
 */

if ($witheld_tracking == true) {

  $userslist = "staff members are not tracked while browsing hidden forums";

}
else {

  $sessions = all ("stats/sessions", makeProper);
  $c = count ($sessions);
  $userslist = array ();

  if ($c) {

    /*
      start from first session record,
      loop for each session record and upto a given limit ($userslistsize as of "settings.php")
    */

    $n = 0;

    while (($n < $c) && ($n < $userslistsize)) {

      /*
        is this user not the viewer? is this session active? (did not expire yet)
        it checks because there may be expired sessions not yet removed from "stats/sessions"
      */

      $_id = intval (valueOf ($sessions[$n], "id"));
      $_beg = intval (valueOf ($sessions[$n], "beg"));

      if (($_id != $id) && (($_beg + $sessionexpiry) >= $epc)) {

        /*
          retrieve nickname
        */

        $_nick = valueOf ($sessions[$n], "nick");

        /*
          get last tracked URI:
          seeking for "posts.php" where argument "t" corresponds to this thread's ID...
        */

        list ($arg_name, $arg_value) = explode ("=", getlsp ($_id));
        list ($arg_value) = explode ("&", $arg_value);

        if ($arg_name == "{$path_to_postline}/posts.php?t") {

          /*
            this user is reading a thread
          */

          if ($arg_value == $t) {

            /*
              this user is reading THIS thread:
              append entry to accumulator...
            */

            $profile_link = "profile.php?nick=" . base62Encode ($_nick);
            $userslist[] = "<a target=pst href=$profile_link>$_nick</a>";

          }

        }

      }

      $n ++;

    }

  }

  if (count ($userslist) == 0) {

    /*
      there's nobody reading this thread
    */

    $userslist = "no" . chr (32) . (($login == true) ? "other" . chr (32) : "") . "members are reading this thread";

  }
  else {

    /*
      concatenate nicknames in list
    */

    $rest = (($login == true) ? $c - 1 : $c) - $userslistsize;
    $end_list = ($rest > 0) ? ", and another {$rest} member" . (($rest == 1) ? "" : "s") : "";
    $userslist = implode ("," . chr (32), $userslist) . $end_list . ".";

  }

}

/*
 *
 * generate thread posts listing
 *
 */

if ($error == false) {

  /*
    eventually processing polls, and the act of voting in...
  */

  $poll = valueOf ($thread_record, "poll");
  $opts = wExplode (";", $poll);

  $closed_poll = (valueOf ($thread_record, "poll_close") == "yes") ? true : false;

  if (count ($opts) > 0) {

    /*
      ok, there's a poll.
      who voted?
      how many people voted so far?
    */

    $voters = valueOf ($thread_record, "voters");

    if (empty ($voters)) {

      $voters = array ();
      $v = "NO";

    }
    else {

      $voters = explode (";", $voters);
      $v = count ($voters);

    }

    /*
      show the form to vote? (if user is logged in and didn't vote yet)
      is this user the thread's starter? (will see results even without voting)
    */

    if ($closed_poll) {

      $showform = false;
      $is_starter = false;

    }
    else {

      if ($login == true) {

        $showform = (in_array ($nick, $voters)) ? false : true;
        $is_starter = (valueOf ($thread_record, "starter") == $nick) ? true : false;

        $thread_started_on = intval (valueOf ($thread_record, "date"));
        $is_starter = ($thread_started_on < $jtime) ? false : $is_starter;

        $showsubmit = true;

      }
      else {

        $showform = true;
        $is_starter = false;

        $showsubmit = false;

      }

    }

    /*
      check security code on voting or closing
    */

    $code_match = intval (fget ("code", 1000, ""));
    $code_match = ($code_match == getcode ("pan")) ? true : false;

    /*
      is there a request to close the poll?
    */

    $doclose = (fget ("submit", 10, "") == "close poll") ? $code_match : false;
    $doclose = ($closed_poll) ? false : $doclose;

    if ($doclose == true) {

      /*
        polls may be closed by owners, moderators, or administrators
      */

      if (($is_starter) || ($is_admin) || ($is_mod)) {

        /*
          if closing request has been made,
          obtain exclusive write access to database
        */

        lockSession ();

        /*
          re-check every condition that led here after locking the session:
          invalidation of cached files ensures what's read below will be up to date
        */

        $thread_record = get ($tdb, "id>$t", "");
        $parent_forum = valueOf ($thread_record, "fid");
        $forum_record = get ("forums/index", "id>$parent_forum", "");

        if ((empty ($forum_record)) || (may_see ($forum_record) == false)) {

          $error = true;
          $list .= makeframe ("error", "unable to close poll: thread has been deleted", false);

        }
        else {

          $poll = valueOf ($thread_record, "poll");
          $opts = wExplode (";", $poll);

          if (count ($opts) == 0) {

            $list .= makeframe ("error", "unable to close poll: poll has been removed", false);

          }
          else {

            /*
              update thread record to reflect the change
            */

            $thread_record = fieldSet ($thread_record, "poll_close", "yes");

            /*
              queue request to save thread record to write cache
            */

            set ($tdb, "id>$t", "", $thread_record);

            /*
              given that the poll has been closed, witheld voting form now
            */

            $showform = false;
            $is_starter = false;
            $closed_poll = true;

            $list .= makeframe ("confirmation", "this poll has been closed", false);

          }

        }

      }
      else {

        $list .= makeframe ("error", "it looks like this poll isn't yours", false);

      }

    }

    /*
      is there a request to vote?
    */

    $dovote = (fget ("submit", 11, "") == "submit vote") ? $code_match : false;
    $dovote = ($closed_poll) ? false : $dovote;

    if ($dovote == true) {

      /*
        there are further checks to be made after locking the session:
        for now, clear the following flag, it will get true again once everything is right
      */

      $dovote = false;

      if ($showform == true) {

        /*
          if voting request has been made,
          obtain exclusive write access to database
        */

        lockSession ();

        /*
          re-check every condition that led here after locking the session:
          invalidation of cached files ensures what's read below will be up to date
        */

        $thread_record = get ($tdb, "id>$t", "");
        $parent_forum = valueOf ($thread_record, "fid");
        $forum_record = get ("forums/index", "id>$parent_forum", "");

        if ((empty ($forum_record)) || (may_see ($forum_record) == false)) {

          $error = true;
          $list .= makeframe ("error", "unable to vote: thread has been deleted", false);

        }
        else {

          $poll = valueOf ($thread_record, "poll");
          $opts = wExplode (";", $poll);

          if (count ($opts) == 0) {

            $list .= makeframe ("error", "unable to vote: poll has been removed", false);

          }
          else {

            $voters = valueOf ($thread_record, "voters");

            if (empty ($voters)) {

              $voters = array ();
              $v = "NO";

            }
            else {

              $voters = explode (";", $voters);
              $v = count ($voters);

            }

            if ($login == true) {

              $showform = (in_array ($nick, $voters)) ? false : true;

              $dovote = $showform;      // this what may effectively grant cast of vote

            }
            else {

              $showform = false;

            }

          }

        }

      }
      else {

        /*
          but not if this account had already voted in this poll:
          show a peaceful warning, the attempt may not be malicious,
          it may be just a failure to load the page upon clicking "submit vote"...
        */

        $list .= makeframe ("error", "it looks like you already voted in this poll", false);

      }

    }

    /*
      if member is (still) allowed to vote, record his/her vote
    */

    if ($dovote == true) {

      /*
        find voted option index in $opts array:
        beware the difference between a option key of zero (first option) and false (not found)
      */

      $vote = fget ("vote", -1000, "");

      $n = 0;
      $opkey = false;

      foreach ($opts as $o) {

        list ($option, $option_voters) = explode ("|", $o);

        if ($vote == $option) {

          $opkey = $n;
          break;

        }

        $n ++;

      }

      /*
        the symbol === means "identical to",
        and can distinguish a boolean value of false from a numeric value of zero
      */

      if ($opkey === false) {

        $list .= makeframe ("error", "select one of the poll's options to cast your vote", false);

      }
      else {

        /*
          update $v_list as the list of voters of this option,
          by adding this member's account nickname as a voter of this option
        */

        $v_list = wExplode (",", $option_voters);
        $v_list[] = $nick;

        $opts[$opkey] = $option . "|" . implode (",", $v_list);

        /*
          update $voters as the overall list of voters for this poll,
          by adding this member's account nickname as a voter in this poll,
          and increase voters counter ($v)
        */

        $voters[] = $nick;
        $v = ($v == "NO") ? 1 : $v + 1;

        /*
          update thread record's "voters" field
        */

        $thread_record = fieldSet ($thread_record, "voters", implode (";", $voters));

        /*
          update thread record's "poll" field
          (to reflect the change in the option this member has voted)
        */

        $thread_record = fieldSet ($thread_record, "poll", implode (";", $opts));

        /*
          queue request to save thread record to write cache
        */

        set ($tdb, "id>$t", "", $thread_record);

        /*
          given that the member has successfully voted, witheld voting form now
        */

        $showform = false;
        $is_starter = false;

        $list .= makeframe ("confirmation", "<em>your vote has been cast</em><br>thanks", false);

      }

    }

    /*
      the above code (to cast votes in polls) may have locked the session: well, it's in most
      cases too early to lock it, and results in having the rest of this script's time being
      spent in a locked session frame that's now useless (because no more modifications to the
      database will be made after the vote is recorded); to avoid slowing down parallel scripts
      from now on (ie. while this script renders the thread, reads all files keeping messages'
      records, updates the VRD field...), force an unlock of the session to promptly grant any
      other scripts read access. This kind of behavior is uncommon to Postline scripts: often,
      they lock the session until execution completes, to avoid being slowed down on themselves
      by the resulting loss of read cache, should the session get locked again later. I'm so
      doing this here only because I know it's rather convenient: voting in polls is a common
      operation, and generating a thread's page output can be relatively slow (0.2-0.3 seconds)
    */

    unlockSession ();

    /*
      assemble poll options' HTML code as $phtm, to be later appended to first post,
      and the corresponding form to vote (if appropriate)
    */

    if ($showform) {

      $b1 = "";
      $b2 = "";
      $b3 = "";

    }
    else {

      $b1 = ";border:1px solid black";
      $b2 = chr (32) . "bgcolor=black";
      $b3 = chr (32) . "bgcolor=black colspan=3";

    }

    $phtm = "<table class=w>"
          .

        (($showform)

          ? "<form action=posts.php enctype=multipart/form-data method=post>"
          . "<input type=hidden name=code value=" . setcode ("pan") . ">"
          . "<input type=hidden name=t value=$t>"

          :  ""

        )

          .  "<tr>"
          .   "<td>"
          .    "<table class=w style=\"margin-top:10px;margin-bottom:10px{$b1}\">";

    /*
      after opening the options' table, add a row for each option:
      $n is used to alternate colored stripes in proportional bars,
      $c to know when $n reachs the last option (and avoid a vertical spacer)
      ---
      ehm... the following code is... so and so... and the HTML is *slightly* messed,
      but don't worry too much, it's mostly a question of output, not functionality.
    */

    $n = 0;
    $c = count ($opts);

    foreach ($opts as $o) {

      list ($option, $option_voters) = explode ("|", $o);

      if (empty ($option_voters)) {

        $option_voters = array ();
        $votes = 0;

      }
      else {

        $option_voters = explode (",", $option_voters);
        $votes = count ($option_voters);

      }

      $vbar = "";

      if ($showform == true) {

        $phtm .= "<tr>"
              .   "<td align=right>"
              .    "<input type=radio name=vote value=" . base62Encode ($option) . ">"
              .   "</td>";

      }
      else {

        if (($login) || ($closed_poll)) {

          $phtm .= "<tr>"
                .   "<td align=right class=alert>"
                .    $votes
                .   "</td>";

          $vpot = ($v == "NO") ? -438 : -438 + intval ((438 * $votes) / $v);

          $vbar = chr (32)
                . "style=\"background-image:url(layout/images/votebar" . ($n % 2) . ".gif);"
                . "background-repeat:repeat-y;background-position:{$vpot}px 0px;"
                . "background-color:white;\"";

        }
        else {

          $phtm .= "<tr>"
                .   "<td align=right>"
                .    "<input type=radio name=vote value=\"\">"
                .   "</td>";

        }

      }

      $phtm .= "<td{$b2}>"
            .   "<img width=1 class=h>"
            .  "</td>"
            .  "<td width=100% class=pl{$vbar}>"
            .   $option;

      if (in_array ($nick, $option_voters)) {

        /*
          a little star marks your preference, cute eh?
        */

        $phtm .= chr (32) . "<img src=layout/images/lilstar.png width=12 height=12>";

      }
      else {

        if ($is_starter == true) {

          $phtm .= chr (32) . "($votes)";

        }

      }

      $phtm .= "</td></tr>";

      $n ++;

      if ($n != $c) {

        $phtm .= "<tr>"
              .   "<td height=1{$b3}>"
              .   "</td>"
              .  "</tr>";

      }

    }

    $phtm .=

        (($showform)

          ?       "<tr>"
          .        "<td height=6>"
          .        "</td>"
          .       "</tr>"
          .

        (($showsubmit)

          ?       "<tr>"
          .        "<td colspan=3>"
          .         "<input class=rf type=submit name=submit value=\"submit vote\" style=\"margin:6px 6px 0px 0px\">"
          .

        ((($is_starter) || ($is_admin) || ($is_mod))

          ?         "<input class=rf type=submit name=submit value=\"close poll\" style=margin-top:6px>"
          :         ""

          )

          .        "</td>"
          .       "</tr>"
          :       ""

          )

          .  "</form>"
          :  ""

          )

          .      "</table>"
          .     "</td>"
          .    "</tr>"
          .  "</table>"
          .

        ((($showform == false) && ($closed_poll == false) && (($is_starter) || ($is_admin) || ($is_mod)))

          ?  "<table>"
          .   "<form action=posts.php enctype=multipart/form-data method=post>"
          .   "<input type=hidden name=code value=" . setcode ("pan") . ">"
          .   "<input type=hidden name=t value=$t>"
          .   "<td>"
          .    "<input class=rf type=submit name=submit value=\"close poll\" style=margin-bottom:6px>"
          .   "</td>"
          .   "</form>"
          .  "</table>"
          :  ""

          );

  }

  /*
    processing rest of thread (well, the messages after such massive management of polls):
    start by setting $postlink, which is a repeated link presented under each post, that
    brings to the post-a-message panel (and for moderators also to moderation controls)
  */

  if (($login == false) || ($showeditlinks == false)) {

    $postlink = "";

  }
  else {

    if (($is_admin == false) && ($is_mod == false)) {

      $postlink = "<a class=j target=pst href=postform.php?t=$t>"
                .  "reply"
                . "</a>";

    }
    else {

      $postlink = "<a class=j target=pst href=postform.php?t=$t>"
                .  "reply, moderate"
                . "</a>";

    }

  }

  /*
    effect of "pagination":
    paginated threads hold only one message per page
  */

  $postsperpage = (valueOf ($thread_record, "ispaged") == "yes") ? 1 : $postsperpage;
  $enable_styling_warning = ($postsperpage == 1) ? false : true;

  /*
    retrieve thread starter, retrieve list of posts in this thread, compute number of pages
  */

  $thread_starter = valueOf ($thread_record, "starter");
  $posts = wExplode (";", valueOf ($thread_record, "posts"));

  $c = count ($posts);
  $pages = ($c == 0) ? 0 : (int) (($c - 1) / $postsperpage);

  /*
    if arguments say to display the last page, whichever it is, set $page to last page
  */

  if ($page == "last") {

    $page = $pages;

  }
  else {

    /*
      else it may even mean the page that contains a certain message ID
    */

    if (substr ($page, 0, 2) == "of") {

      $find = substr ($page, 2);
      $page = (int) (array_search ($find, $posts) / $postsperpage);

    }
    else {

      /*
        and finally, if it's no special request, it's supposed to be a page number:
        if void or negative, it will default to zero (the first page)
      */

      $page = intval ($page);
      $page = ($page < 0) ? 0 : $page;

    }

  }

  /*
    display all posts, accumulating their output on $list:
    - $list is updated by "message.php";
    - $p is the index, in the $posts array, of the first message to display;
    - $n is a simple progressive counter, provided for convenience to "message.php",
      in case there could be a preference for alternating colors or other elements
      between odd and even messages; it also counts for terminating the "while".
  */

  $n = 0;
  $p = $page * $postsperpage;

  /*
    while post index < number of posts in thread
    and number of displayed messages < number of messages in a page,
    ie. while neither the trail of messages of this thread, nor the page, is over...
  */

  while (($p < $c) && ($n < $postsperpage)) {

    /*
      given the message ID, generate database file name holding message record ($mbd);
      then read the record and, if it's valid (it might!), display the message...
    */

    $mid = intval ($posts[$p]);
    $mdb = "posts" . intervalOf ($mid, $bs_posts);

    $post = get ($mdb, "id>$mid", "");

    if (!empty ($post)) {

      /*
        who authored this message?
        is the author in the viewer's ignore list?
        note: array $ilist is globally loaded by "suitcase.php" as an array of nicknames
      */

      $author = valueOf ($post, "author");

      if (in_array ($author, $ilist)) {

        /*
          generally, "profile.php" does not allow ignoring moderators and administrators,
          but what if the member mentioned in the ignore list became ranked as such after
          this viewer sent him/her to the ignore list?
        */

        $a_id = intval (get ("members/bynick", "nick>$author", "id"));
        $a_db = "members" . intervalOf ($a_id, $bs_members);
        $a_auth = get ($a_db, "id>$a_id", "auth");

        $is_no_admin = (in_array ($a_auth, $admin_ranks)) ? false : true;
        $is_no_mod = (in_array ($a_auth, $mod_ranks)) ? false : true;

        if (($is_no_admin) && ($is_no_mod)) {

          $ignored = true;
          $ignored_messages_present = true;

        }

      }
      else {

        /*
          message author isn't in ignore list
        */

        $ignored = false;

      }

      /*
        retrieve message from record,
        and date and speech bubble color as well.
      */

      if (($p > 0) || (empty ($phtm))) {

        $message = valueOf ($post, "message");

      }
      else {

        $message = "&lt;l&gt;$thread_title&lt;/l&gt;&;"
                 .

               (($closed_poll)

                 ? "$v members voted, and this poll is now closed"
                 : "$v members voted in this poll so far"

                 )

                 . "&lt;h&gt;$phtm&lt;/h&gt;"
                 . valueOf ($post, "message");

      }

      $epoc = intval (valueOf ($post, "date"));
      $bcol = (valueOf ($post, "bcol") !== "") ? intval (valueOf ($post, "bcol")) : "";

      /*
        initialize control buttons to void strings
      */

      $quote_button = $edit_button = $retitle_button = $delete_button = $split_button = "";

      if ($login == true) {

        if ($showeditlinks == true) {

          /*
            setup quote button
          */

          $quote_button = "<a class=j target=pst href=postform.php?t=$t&amp;m=$mid>quote</a>";

          /*
            can this member edit this message? yes, if:
            - member is an administrator or a moderator (in any cases);
            - member is the author and post edit delay did not expire, or is disabled.
          */

          $is_author = ($nick == $author) ? true : false;
          $within_delay = (($posteditdelay == 0) || ($epoc + $posteditdelay >= $epc)) ? true : false;

          if (($is_author && $within_delay) || ($is_admin) || ($is_mod)) {

            $edit_button = "<a class=j target=pst href=edit.php?m=$mid>edit</a>";

            /*
              can member retitle this message's parent thread?
              yes, if member is the author of this message and the starter of the thread,
              or obviously if it's a moderator or an administrator
            */

            if ($p == 0) {

              if (($is_admin == true) || ($is_mod == true) || (($is_author == true) && ($nick == $thread_starter))) {

                $retitle_button = "<a class=j target=pst href=retitle.php?t=$t>retitle</a>";

              }

            }

            /*
              for messages past the first one of a thread, can this member split the thread?
              yes, if viewer is an administrator or a moderator, and, in case of a moderator,
              the whole forum isn't closed...
            */

            if ($p > 0) {

              if (($is_admin) || (($is_mod == true) && ($is_closed == false))) {

                $split_button = "<a class=j target=pst href=split.php?m=$mid>split</a>";

              }

            }

            /*
              can member delete this message?
              yes, if it's the last message of the thread (and therefore nobody answered)
              or, if the viewer is an administrator or a moderator (they can always delete)
            */

            if (($p == $c - 1) || ($is_admin) || ($is_mod)) {

              $delete_button = "<a class=j target=pst href=delete.php?m=$mid>delete</a>";

            }

          }

        }

      }

      /*
        assemble "last edit" notice, if appropriate
      */

      $editor = valueOf ($post, "edit");

      if (empty ($editor)) {

        $lastedit = "";

      }
      else {

        $e_epoc = valueOf ($post, "on");
        $e_date = gmdate ("F d, Y", $e_epoc);
        $e_time = gmdate ("H:i", $e_epoc);

        $lastedit = "last changed by <a target=pst href="
                  . "profile.php?nick=" . base62Encode ($editor) . "&amp;"
                  . "at=$e_epoc>$editor</a> on $e_date at $e_time";

      }

      /*
        output message anchor (for having the browser jump there, when the viewer clicks
        a link that leads exactly to this message, while having the others still part of
        the page's HTML output; witheld if the thread is "paginated", one msg. per page)
      */

      if ($postsperpage > 1) {

        $list .= "<a name=$mid></a>";
        $list .= ($p == $c - 1) ? "<a name=lastpost></a>" : "";

      }

      /*
        output message body ("message.php" will be tailing it to $list accumulator)
      */

      include ("message.php");

    }

    /*
      apart from rendering:
      - add message ID to $mop array for later marking all messages on page as read;
      - if the viewer is an administrator or a moderator, and this message was that
        which made the intercom blink, empty the intercom field (and shout the light).
    */

    $mop[] = $mid;

    if ($mid == $intercom) {

      lockSession ();

      $db = "members" . intervalOf ($id, $bs_members);
      $account_record = get ($db, "id>$id", "");

      if (!empty ($account_record)) {

        set ($db, "id>$id", "intercom", "");

      }

    }

    /*
      advance counters,
      proceed with the loop to next message
    */

    $n ++;
    $p ++;

  }

  /*
    the page may be void:
    typically because the thread has not enough messages to reach a given page number;
    by clicking links this can't easily happen, but could by passing arbitrary URLs...
  */

  if ($n == 0) {

    $list = "Error: this page of the thread appears to be void, possibly because "
          . "all messages formerly present on this page have been deleted. If this doesn't "
          . "seem to be the case, then there might be something wrong in database file "
          . "&quot;$tdb&quot; and you might report it to the community manager.";

  }

  /*
    if there's more than one page...
    (remember pages are numbered from page 0 as the first page)
  */

  if ($pages > 0) {

    /*
      ...initialize paging links
    */

    $li = $page - $pp_posts;
    $lj = $page + $pp_posts;

    $number = ($lj > ($pages - 1)) ? $li - ($lj - $pages + 1) : $li;
    $nlimit = ($li < 1) ? $lj - $li + 1 : $lj;

    $number = ($number < 0) ? 0 : $number;
    $nlimit = ($nlimit > $pages) ? $pages : $nlimit;

    $paging =

          (($number == 0)

            ? ""
            : "<a class=fl href=posts.php?t=$t>1</a>"

            )

            .

          (($number <= 1)

            ? ""
            : ".."

            );

    while ($number <= $nlimit) {

      $paging .=

            (($number == $page)

              ? "<span class=alert>" . ($number + 1) . "</span>"
              : "<a class=fl href=posts.php?t=$t&amp;p=$number>" . ($number + 1) . "</a>"

              );

      $number ++;

    }

    $paging .=

          (($nlimit < $pages - 1)

            ? ".."
            : ""

            )

            .

          (($nlimit < $pages)

            ? "<a class=fl href=posts.php?t=$t&amp;p=$pages>" . ($pages + 1) . "</a>"
            : ""

            );

  }

}

/*
 *
 * if hidden messages are present on the page, prepend to the list a short javascript
 * to toggle visibility of each hidden message (hidden are those ignored by viewer)
 *
 */

if ($ignored_messages_present) {

  $list = "\n"
        . "<script language=Javascript type=text/javascript>\n"
        . "<!--\n"
        .   "function ts(s){"                                           // called by "tl"
        .     "if(s=='visible'){"
        .       "return('hidden');"
        .     "}"
        .     "else{"
        .       "return('visible');"
        .     "}"
        .   "}"
        .   "function tl(l){"
        .     "var x,s;"
        .     "if(document.layers){"                                    // Netscape/Firefox
        .       "document.layers[l].visibility="
        .       "ts(document.layers[l].visibility);"
        .     "}"
        .     "else{"
        .       "if(document.all){"                                     // MSIE
        .         "s=ts(eval('document.all.'+l+'.style.visibility'));"
        .         "eval('document.all.'+l+'.style.visibility='+'s');"
        .       "}"
        .       "else{"
        .         "if(document.getElementById){"                        // anything else
        .           "x=document.getElementById(l);"
        .           "s=x.style.visibility;"
        .           "x.style.visibility=ts(s);"
        .         "}"
        .       "}"
        .     "}"
        .   "}\n"
        . "//-->\n"
        . "</script>\n"
        . $list;

}

/*
 *
 * if logged in, update the record holding the discussions this member has viewed (field "vrd")
 *
 */

if (($error == false) && ($login == true)) {

  /*
    building array of message IDs representing all recent posts:
    - load recent discussions archive building an array ($rd_a) out of its
      newline-delimited records, initialize filtered array $is_recent with flags
      that are "true" for all message IDs appearing among recent dicussions: the
      filtered array will have message IDs as its keys. It looks like this works
      faster than scanning with "in_array" (not sure, but I've made a few tries).
  */

  $rd_a = all ("forums/recdisc", asIs);
  $is_recent = array ();

  foreach ($rd_a as $r) {

    list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);
    $is_recent[$_m] = true;

  }

  /*
    doing the same with viewed recent discussions (VRD),
    which is an array of message IDs as well, returned by "getvrd" and then
    transformed by the following brief loop into a keyed array of flags...
  */

  $vrd = getvrd ($id);
  $is_viewed = array ();

  foreach ($vrd as $_m) {

    $is_viewed[$_m] = true;

  }

  /*
    now to update "vrd":
    considering $mop as the array holding all message ids of this page...
    - if message id $mop is part of global recent discussions ($in_actualrecdisc),
      and if same message is NOT part of viewed recent discussions ($in_viewedrecdisc),
      then append the message id to the array of viewed recent discussions
  */

  $save_vrd = false;

  foreach ($mop as $_m) {

    if (($is_recent[$_m] == true) && ($is_viewed[$_m] == false)) {

        $vrd[] = $_m;
        $save_vrd = true;

    }

  }

  if ($save_vrd == true) {

    /*
      note the VRD array is always sorted in reverse numeric order, so that when the elements
      of the array past $vrdtablerecords (see "settings.php" and "setvrd" in "suitcase.php"),
      will be sliced and lost, they will certainly represent oldest message IDs, which have a
      maximum chance of having been lost from the recent discussions archive anyway: in fact,
      the number of records reserved to $vrdtablerecords is greater than $recdiscrecords, and
      accounts for possible deletions (when a message gets deleted, it is removed from recent
      discussions database, "recdisc.txt", but NOT from the VRD field of each member, because
      the process of scanning the members database for this kind of change would be too slow.
    */

    rsort ($vrd, SORT_NUMERIC);
    setvrd ($id, $vrd);

  }

}

/*
 *
 * locate exact page of parent forum's threads list in which this thread appears:
 * it's useful to resume navigation of a forum's directory while reading pages of
 * the directory other than the first page.
 *
 */

$p = 0;

if ($error == false) {

  $thread =

        array_merge (
          all ("forums/st-$parent_forum", makeProper),
          all ("forums/th-$parent_forum", makeProper)
        );

  $i = 0;

  foreach ($thread as $h) {

    if (valueOf ($h, "id") == $t) {

      $p = intval ($i / $threadsperpage);
      break;

    }

    $i ++;

  }

}

/*
 *
 * determine permalink presence (present if no arguments given, other than thread ID)
 *
 */

unset ($_GET["t"]);
unset ($_POST["t"]);

$args_not_given = ((count ($_GET)) || (count ($_POST))) ? false : true;

/*
 *
 * permalink initialization
 *
 */

$permalink = (($enable_permalinks) && ($args_not_given) && ($error == false))

  ? str_replace

      (

        "[PERMALINK]", $perma_url . "/" . $posts_keyword . "/" . $t, $permalink_model

      )

  : "";

if (isset ($message)) {

  $permalink = (($enable_permalinks) && ($postsperpage == 1) && ($page > 0))

    ? str_replace

        (

          "[PERMALINK]", $perma_url . "/" . $message_keyword . "/" . $mid, $permalink_model

        )

    : $permalink;

}

if ((isset ($thread_permas[$t])) && ($page == 0)) {

  $permalink = (($enable_permalinks) && ($error == false))

    ? str_replace

        (

          "[PERMALINK]", $perma_url . "/" . $thread_permas[$t], $permalink_model

        )

    : "";

}

/*
 *
 * template initialization
 *
 */

$_forum = "<a class=fl " . link_to_threads ("", $parent_forum, $p) . ">..</a>";
$_thread = "<a class=fl href=posts.php?t=$t>" . shorten ($thread_title, $tt_hint_length) . "</a>";

$forum_posts =

  str_replace (

    array

      (

        "[FORUM]",
        "[THREAD]",
        "[PAGING]",
        "[USERSLIST]",
        "[LIST]",
        "[PERMALINK]"

      ),

    array

      (

        $_forum,
        $_thread,
        $paging,
        $userslist,
        $list,
        $permalink

      ),

    $forum_posts

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page
 * upon showing or hiding the chat frame (via cookie: this also works for guests)
 *
 */

include ("setrfcookie.php");

/*
 *
 * saving navigation tracking informations for the online members list links
 * (unless no-spy flag checked)
 *
 */

if (($login == true) && ($nspy != "yes") && ($witheld_tracking == false)) {

  $this_uri = substr ($_SERVER["REQUEST_URI"], 0, 100);

  if (getlsp ($id) != $this_uri) {

    setlsp ($id, $this_uri);

  }

}

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($forum_posts));



?>
