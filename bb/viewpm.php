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
 * initialize accumulators
 *
 */

$error = false; // error flag
$list = "";     // page output accumulator
$paging = "";   // paging links

/*
 *
 * get parameters (message ID)
 *
 */

$m = intval (fget ("m", 1000, ""));

/*
 *
 * check authorizations (this is a private message, thus it must be in facts kept private)
 *
 */

if ($login == false) {

  $error = true;

  $list = "<em>"
        .  "You must be logged in to access your private messages."
        . "</em>"
        . "<br>"
        . "<br>"
        . "Once you have logged in, by following <a target=pst href=mstats.php>this link</a>, "
        . "click the &quot;pm&quot; button in the status line (at the bottom of the screen) "
        . "to go to your inbox.";

}
else {

  $db = "members" . intervalOf ($id, $bs_members);
  $mr = get ($db, "id>$id", "");

  if (empty ($mr)) {

    $error = true;

    $list = "<em>"
          .  "Error acessing members database file &quot;$db&quot;."
          . "</em>"
          . "<br>"
          . "<br>"
          . "If this is a malfunction, it's very weird and unlikely. If it isn't, it may be "
          . "that your account has just been deleted, possibly by mistake (in which case we "
          . "apologize and will listen to feedback); as a last possibility, this could have "
          . "been a transitory error due to unknown causes, and you may just retry.";

  }
  else {

    /*
      message ID has to appear in either the inbox, or the outbox (which are lists of IDs):
      in the inbox, it could appear as its negative (-$m) to mean that the message is read
    */

    $inbox = wExplode (";", valueOf ($mr, "inbox"));
    $outbox = wExplode (";", valueOf ($mr, "outbox"));

    $found_marked = in_array (-$m, $inbox);
    $found_unread = in_array ($m, $inbox);
    $found_outbox = in_array ($m, $outbox);

    if (($found_marked == false) && ($found_unread == false) && ($found_outbox == false)) {

      $error = true;

      $list = "<em>"
            .  "Private message #$m does not (or no longer) belong to your account."
            . "</em>"
            . "<br>"
            . "<br>"
            . "This may be because you have deleted it, or because there's been a problem "
            . "with members' database file &quot;$db&quot;. If it's the latter, you ought "
            . "report this inconvenience to the community manager. Alternatively, you might "
            . "have reached this page by clicking a link to the page that another member "
            . "was viewing (from the online members' list): in this case, you can't obviously "
            . "be allowed to read the other member's private message.";

    }

  }

}

/*
 *
 * find message record and prepare to show single message
 *
 */

if ($error == false) {

  $pmdb = "pms" . intervalOf ($m, $bs_pms);
  $post = get ($pmdb, "id>$m", "");

  if (empty ($post)) {

    $error = true;

    $list = "<em>"
          .  "Message not found in private messages' database."
          . "</em>"
          . "<br>"
          . "<br>"
          . "Are you sure you didn't formerly delete this message? If you are, there could "
          . "have been a problem concerning database file &quot;$pmdb&quot;, which you may "
          . "then report to the administrators, while for the moment, we apologize for the "
          . "inconvenience.";

  }
  else {

    $recipient = valueOf ($post, "to");

    $er = base62Encode ($recipient);
    $at = intval (valueOf ($post, "date"));

    $paging = "to: <a class=fl target=pst href=profile.php?nick=$er&amp;at=$at>$recipient</a>";

  }

}

/*
 *
 * generate output
 *
 */

if ($error) {

  $postlink = "";
  $thread = "error";

  $list = makeframe ("error", $list, false);

}
else {

  /*
    initialize rendering arguments for "message.php" (see)
  */

  $author = valueOf ($post, "author");
  $epoc = intval (valueOf ($post, "date"));
  $thread = shorten (valueOf ($post, "title"), $tt_hint_length);
  $message = valueOf ($post, "message");

  $a_id = intval (get ("members/bynick", "nick>$author", "id"));
  $bcol = get ("members" . intervalOf ($a_id, $bs_members), "id>$a_id", "bcol");

  $bcol = ($bcol === "") ? "" : intval ($bcol);
  $bcol = ($bcol == -1) ? mt_rand (0, count ($bcols) - 2) : $bcol;

  $a = base62Encode ($author);
  $s = (substr ($thread, 0, 3) == "Re:") ? $thread : "Re:" . chr (32) . $thread;
  $s = base62Encode (shorten ($s, $maxtitlelen));

  $mid = "";
  $ignored = false;

  $postlink = "<a class=j href=sendpm.php?to=$a&amp;sj=$s target=pst>send reply</a>";
  $pm_button = "";
  $quote_button = "";
  $retitle_button = "";
  $edit_button = "";
  $delete_button = "";
  $lastedit = "";
  $enable_styling_warning = false;

  /*
    output message body
  */

  include ("message.php");

  /*
    output recipients in CC,
    or mod-level hidden CC informations
  */

  if ($author != $servername) {

    /*
      public mode
    */

    $rcpt = wExplode (";", valueOf ($post, "rcpt"));
    $rclist = array ();

    foreach ($rcpt as $r) {

      $r .= ($r == $author) ? chr (32). "(sender's copy)" : "";
      $rclist[] = $r;

    }

    $rcpt =

      (count ($rclist) == 0)

        ? "<br>(error retrieving recipients list)"
        : "<ol><li>" . implode ("</li><li>", $rclist) . "</li></ol>";

    $list .= makeframe ("actual recipients of this message", $rcpt, false);

  }
  else {

    /*
      anonymous mode
    */

    if (($is_admin) || ($is_mod)) {

      $hr = wExplode (";", valueOf ($post, "hr"));

      if (count ($hr) == 0) {

        $hr = "<br><br>N/A<br><br>";

      }
      else {

        $hr[0] = "<em>" . $hr[0] . "</em>";
        $hr[1] = "<em>" . $hr[1] . "</em>";
        $hr = "<ol><li>" . implode ("</li><li>" . chr (32), $hr) . "</li></ol>";

      }

      $list .=

        makeframe (

          "hidden recipients at delivery time",

          $hr
        . "<em>anonymous message sender: " . valueOf ($post, "os") . "</em><br>"
        . "<em>note: above informations are confidential, only shown to staff members</em>", false

        );

    }

  }

  /*
    if necessary, mark message as read:
    lock session (foreseeing a write to the database), check if member account record
    still exists, locate message ID within inbox (there can't be unread messages into
    the outbox list) and change its entry from positive to negative ($m to -$m): this
    will mark the message as read once it's listed by "inbox.php"...
  */

  if ($found_unread) {

    lockSession ();

    $db = "members" . intervalOf ($id, $bs_members);
    $mr = get ($db, "id>$id", "");

    $record_exists = (empty ($mr)) ? false : true;

    if ($record_exists) {

      /*
        note that it runs a search-and-replace loop over the whole inbox list:
        that's because the message ID may even appear multiple times into the
        inbox, due to possible carbon copies sent to the same main recipient.
      */

      $old_inbox = wExplode (";", valueOf ($mr, "inbox"));
      $new_inbox = array ();

      foreach ($old_inbox as $i) {

        if ($i != $m) {

          $new_inbox[] = $i;

        }
        else {

          $new_inbox[] = -$m;

        }

      }

      set ($db, "id>$id", "inbox", implode (";", $new_inbox));

    }

  }

}

/*
 *
 * template initialization
 *
 */

$forum_posts =
  str_replace (

    array (

      "[FORUM]",
      "[THREAD]",
      "[PAGING]",
      "[USERSLIST]",
      "[LIST]",
      "[PERMALINK]"

    ),

    array (

      "<a class=fl target=pst href=inbox.php>" . (($login == true) ? $nick : "Someone") . "'s Private Messages</a>",
      "<a class=fl href=viewpm.php?m=$m>$thread</a>",
      $paging,
      "(this is a private message)",
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

if (($login == true) && ($nspy != "yes")) {

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
