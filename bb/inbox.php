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

$form = "";                             // template output
$list = "";                             // private messages list
$error = false;                         // error flag
$dontrememberthisurl = false;           // discards URL tracker duties to avoid repeating actions

/*
 *
 * process private messages' inbox:
 * - if user is not logged in, inbox is not available at all.
 *
 */

if ($login) {

  /*
    toggle read/unread mark (integer message ID)
  */

  $mk = intval (fget ("mk", 1000, ""));

  if ($mk != 0) {

    $dontrememberthisurl = true;

  }

  /*
    security code mismatch will invalidate any actions performed
  */

  $code = intval (fget ("code", 1000, ""));
  $code_match = ($code == getcode ("pst")) ? true : false;

  /*
    process delete requests:
    - initialize array of private message IDs to delete, clear valid delete request flag
    - examine submission trigger ("submit") to determine what's exactly to do
  */

  $del = array ();
  $delete = false;

  $submit = ($code_match) ? fget ("submit", 1000, "") : "";

  switch ($submit) {

    case "remove marked messages":

      /*
        collecting marked messages' IDs:
        - set delete request flag, lock to avoid interferences
      */

      $delete = true;
      lockSession ();

      /*
        read member's account record, extract textual inbox list, explode list to array ($ib),
        compile $del array with any IDs from the inbox list that appear marked for deletion
      */

      $mr = get ("members" . intervalOf ($id, $bs_members), "id>$id", "");

      $ib = valueOf ($mr, "inbox");
      $ib = ($ib != "") ? $ib = explode (";", $ib) : array ();

      foreach ($ib as $m) {

        $pmid = abs ($m);
        if (isset ($_POST["d$pmid"])) $del[] = $pmid;

      }

      break;

    case "remove ALL below marked":

      /*
        collecting marked messages' IDs:
        - set delete request flag, lock to avoid interferences
      */

      $delete = true;
      lockSession ();

      /*
        read member's account record, extract textual inbox list, explode list to array ($ib),
        compute $del array by cutting a slice of the inbox listings, from first marked message:
        - will use $n to determine the index of the first marked message, basing at zero.
      */

      $mr = get ("members" . intervalOf ($id, $bs_members), "id>$id", "");

      $ib = valueOf ($mr, "inbox");
      $ib = ($ib != "") ? $ib = explode (";", $ib) : array ();

      $n = 0;
      $is = array_reverse ($ib);

      foreach ($is as $m) {

        $pmid = abs ($m);

        if (isset ($_POST["d$pmid"])) {

          $del = array_slice ($is, $n);
          break;

        }

        $n ++;

      }

  }

  /*
    now, if a valid delete request was detected...
  */

  if ($delete) {

    /*
      ...check if at least one message did effectively appear in the member's inbox,
      otherwise it could mean that marked messages were already deleted, or that the
      user managed to select IDs of messages that don't belong to his/her account,
      yeah, for some kind of malfunction, or for a simple-minded hacking attempt...
    */

    if (count ($del)) {

      /*
        we know for sure that the request was valid and that there was a consistent
        member record, that's been loaded inside $mr by the above code, so just also
        extract the outbox list (when a message is deleted, Postline makes it so that
        the message disappears both from the inbox and the outbox, in case the member
        chosen to mail a carbon-copy to him/herself, which is perfectly possible).
      */

      $ob = valueOf ($mr, "outbox");
      $ob = ($ob != "") ? $ob = explode (";", $ob) : array ();

      /*
        uhm... a duplicates checking array: it's even possible to mail more than one
        carbon copy to yourself, although it's admittedly silly, but that's how it works
        and I don't see any particular danger with this (the message always exists in a
        single exemplary in the database, so further copies don't really take up space):
        however, delete_pm won't succeed deleting a message more than once, hence this
        necessary handling of the boolean $dc array to try only once on every message ID.
      */

      $dc = array ();   // duplicates check
      $fi = array ();   // failed IDs

      foreach ($del as $d) {

        $pmid = abs ($d);

        if (!isset ($dc[$pmid])) {

          /*
            delete PM from this account: "delete_pm" will effectively remove physical traces
            of the message from the database only if no other recipients are still holding
            this message in their inbox/outbox lists; otherwise, it will just remove this
            member as a recipient of that message
          */

          if (delete_pm ($nick, $pmid)) {

            /*
              on successful "logical" deletion, also remove the message ID from both the inbox
              and the outbox, considering that the ID could be expressed in its negative form,
              within the inbox (a message that's marked as read), but never in the outbox.
            */

            $ib = wArrayDiff ($ib, array ($pmid, -$pmid));
            $ob = wArrayDiff ($ob, array ($pmid));

          }
          else {

            /*
              on failure, report the problem but keep trying with the rest of the messages:
              you'll finally report a list of private messages IDs that failed to be deleted.
            */

            $error = true;
            $fi[] = $pmid;

          }

          $dc[$pmid] = true;

        }

      }

      if ($error)

        $form = "Error deleting message(s)://"
              . "Please check that you didn't already delete one or more "
              . "of the selected messages. Otherwise, it could be a database problem "
              . "for the community manager to (possibly) solve. Failed deletions concern "
              . "the following message IDs: " . implode (", ", $fi) . ".";

      /*
        update memory image of member's record
      */

      $mr = fieldSet ($mr, "inbox", implode (";", $ib));
      $mr = fieldSet ($mr, "outbox", implode (";", $ob));

      /*
        save changed member's record to database
      */

      set ("members" . intervalOf ($id, $bs_members), "id>$id", "", $mr);

    }
    else {

      /*
        nothing-done error message
      */

      $error = true;
      $form = "Please mark PMs to delete...//"
            . "To delete private messages from your mailboxes, mark them by clicking "
            . "over the checkbox you see to the left of the message's title. You can "
            . "choose to either delete single messages (delete marked), or to delete "
            . "from the first marked message to the end of the list.";

    }

  }

  /*
    generate inbox private messages list:
    - meanwhile, use $n to alter the array for marking read/unread state, which is obtained
      by negating the ID of a message within the inbox list (it's simple, but effective...)
    - if there's a request to mark a message as read/unread, given that you'll be reading
      the member's record below, ensure nothing can happen to that record while you're
      processing it...
  */

  $list = "<table width=$pw>"
        . "<form action=inbox.php enctype=multipart/form-data method=post>"
        . "<input type=hidden name=code value=" . setcode ("pst") . ">"
        .  "<td height=20 class=inv align=center>"
        .   "RECEIVED MESSAGES:"
        .  "</td>"
        . "</table>";

  if ($mk > 0) {

    lockSession ();

  }

  $mr = get ("members" . intervalOf ($id, $bs_members), "id>$id", "");

  $ib = valueOf ($mr, "inbox");
  $ib = ($ib != "") ? $ib = array_reverse (explode (";", $ib)) : array ();

  $n = 0;       // index to locate entry in $ib array for marking
  $f = false;   // flag to indicate when a message was effectively marked read/unread

  foreach ($ib as $m) {

    /*
      mark specified message as read/unread (toggle message ID sign), update $m alias in loop
    */

    if ($mk == abs ($m)) {

      $ib[$n] = -$ib[$n];
      $m = $ib[$n];
      $f = true;

    }

    /*
      determine appearence (read or unread, basing on current sign),
      set $pmid to absolute value of $m
    */

    $pmid = intval ($m);

    if ($pmid < 0) {

      $pmid = -$pmid;           // obtain absolute value
      $st = "mark as unread";   // marker tool tip
      $un = "read";             // marker button

    }
    else {

      $st = "mark as read";     // marker tool tip
      $un = "star";             // marker button

    }

    /*
      read PM record, add entry to list
    */

    $pm = get ("pms" . intervalOf ($pmid, $bs_pms), "id>$pmid", "");

    $list .= $inset_bridge
          .  $opcart
          .  "<tr>"
          .   "<td width=21 valign=top class=ls>"
          .    "<input type=checkbox name=d$pmid>"
          .   "</td>"
          .   "<td width=" . ($pw - 21) . " class=ls>"
          .    "<a class=ll target=pan href=viewpm.php?m=$pmid>"
          .     valueOf ($pm, "title")
          .    "</a><br>"
          .    "from: " . valueOf ($pm, "author")
          .    "<a title=\"$st\" href=inbox.php?mk=$pmid>"
          .     "<img align=right src=layout/images/$un.png width=24 height=24 border=0>"
          .    "</a>"
          .   "</td>"
          .  "</tr>"
          .  $clcart;

    $n ++;

  }

  /*
    if there's something to list, also present buttons to delete one or more messages,
    else say the inbox is void...
  */

  if ($n == 0)

    $list .= $inset_bridge
          .  $opcart
          .   "<td class=ls align=center>"
          .    "no messages"
          .   "</td>"
          .  "</form>"
          .  $clcart;

  else

    $list .= $inset_shadow
          .  "<table width=$pw>"
          .   "<tr>"
          .    "<td height=20 class=inv align=center>"
          .     "DELETE FROM INBOX:"
          .    "</td>"
          .   "</tr>"
          .   $bridge
          .   "<tr>"
          .    "<td>"
          .     "<input class=su type=submit name=submit value=\"remove marked messages\" style=width:{$pw}px>"
          .    "</td>"
          .   "</tr>"
          .   $bridge
          .   "<tr>"
          .    "<td>"
          .     "<input class=su type=submit name=submit value=\"remove ALL below marked\" style=width:{$pw}px>"
          .    "</td>"
          .   "</tr>"
          .  "</form>"
          .  "</table>";

  $list .= $inset_shadow;

  /*
    the member's record needs to be updated when a message was marked as read/unread,
    or when the "newpm" flag is found on: removing the flag when the inbox is visited
    will turn off the "you've got mail" indication in the status line...
  */

  if (($f) || ($newpm == "yes")) {

    lockSession ();

    $mr = get ("members" . intervalOf ($id, $bs_members), "id>$id", "");
    $check = empty ($mr) ? false : true;

    if ($check) {

      if ($f) {

        $ib = array_reverse ($ib);
        $mr = fieldSet ($mr, "inbox", implode (";", $ib));

      }

      $mr = fieldSet ($mr, "newpm", "");
      set ("members" . intervalOf ($id, $bs_members), "id>$id", "", $mr);

    }

  }

}
else {

  $error = true;
  $form = "You are not logged in.//<a href=mstats.php>go to login panel</a>";

}

/*
 *
 * form initialization:
 * - report errors, or make an innocent title...
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

        "error details", $frame_contents, true

      )

      : "");

}
else {

  $form =

        makeframe (

          "Private messages inbox", false, false

        );

}

/*
 *
 * provide "related links" to reach the outbox and a way to send a private message,
 * then tail the inbox listing ($list) to the template output ($form)...
 *
 */

$form .=

      makeframe (

        "messaging",
        "<a href=outbox.php>go to your outbox</a><br>"
      . "<a href=sendpm.php>send a private message</a>", true

      )

      .  $list;

/*
 *
 * template initialization
 *
 */

$pmpanel = str_replace

  (

    array (

      "[FORM]",
      "[PERMALINK]"

    ),

    array (

      $form,
      $permalink

    ),

    $pmpanel

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

echo (pquit ($pmpanel));



?>
