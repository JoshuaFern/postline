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

$form = "";                     // page output accumulator
$hint = "";                     // hint holding additional messages on successful deliver
$modcp = "";                    // utility links on bottom of page
$error = false;                 // error flag
$emlist = false;                // true when emoticons list is requested to show
$dontrememberthisurl = false;   // discards URL tracker duties to avoid repeating actions

/*
 *
 * manage random balloon color, normalize default balloon color
 *
 */

$def_b = ($def_b == -1) ? mt_rand (0, $no_of_bcols - 1) : $def_b;
$def_b = ($def_b >= $no_of_bcols) ? 0 : $def_b;

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
 * get form submission trigger:
 * showing or hiding emoticons' list does not result in a "real" submission
 *
 */

$submit = isset ($_POST["submit"]);
$emlist_toggle = false;

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
    $emlist_toggle = true;

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
    $emlist_toggle = true;

  }

  /*
    in other cases, it's a real submission, and will be processed as normal...
  */

  $dontrememberthisurl = true;

}

/*
 *
 * get parameters
 *
 */

if (($submit == false) && ($emlist_toggle == false)) {

  /*
    if it's no submission, recipient and subject may be passed as URL arguments encoded as
    base-62 strings: it happens when user clicks the link to reply in "viewpm.php", or the
    links to send private messages to a certain member, placed in posts and profile forms.
  */

  $to = fget ("to", -1000, "");
  $cc = fget ("cc", -2200, "");
  $title = fget ("sj", -1000, "");

}
else {

  /*
    if it's a submit,
    recipient and subject are gathered from posted form arguments...
  */

  $to = ucfirst (fget ("to", 1000, ""));
  $cc = fget ("cc", 2200, "");
  $title = fget ("title", 1000000, "");

}

$anon = (isset ($_POST["anon"])) ? true : false;        // anonymous mode:
$anon = (($is_admin) || ($is_mod)) ? $anon : false;     // only staff members can use it

$message = fget ("message", 1000000, "&;");             // body of message to send
$message_in = str_replace ("&;", "\n", $message);       // when echoed to its textarea

/*
 *
 * check security code on submission of private message form
 *
 */

if ($submit) {

  $code = intval (fget ("code", 1000, ""));
  $submit = ($code == getcode ("pst")) ? true : false;

}

/*
 *
 * private message posting operations
 *
 */

if ($login) {

  if ($submit) {

    if (empty ($to)) {

      $error = true;

      $form = "Missing recipient!//"
            . "Please type the nickname of the member to send this message to, "
            . "in the 'to' field below...";

    }
    else {

      /*
        obtaining exclusive write access to database,
        which includes exclusive read access to make sure what's read below will be up-to-date
      */

      lockSession ();

      /*
        get ID of recipient, check if recipient account exists
      */

      $rcpt = intval (get ("members/bynick", "nick>$to", "id"));

      if (empty ($rcpt)) {

        $error = true;

        $form = "Can't find '$to'...//"
              . "It seems there's no such nickname in members database. Please check spelling "
              . "in the 'to' field, or use the 'folks' button in the status line to browse the "
              . "members' list and eventually find out who you wished to send this message to.";
      }
      else {

        /*
          check that a non-void subject line is provided:
          it won't accept to send a private message with no subject
        */

        if (empty ($title)) {

          $error = true;

          $form = "Invalid or void subject.//"
                . "Private messages need a non-void subject line, which is pratically "
                . "a title, or brief description of the message's topic. Please be short "
                . "there, though: you only have $maxtitlelen characters for the subject.";

        }
        else {

          /*
            if the subject isn't void, check if it fits the length limit
          */

          if (strlen ($title) > $maxtitlelen) {

            $error = true;

            $form = "Subject too long.//"
                  . "There is a $maxtitlelen characters limit "
                  . "for the subject line." . $string_length_warning;

          }
          else {

            /*
              check that message isn't void: it's nonsensical to send void messages,
              and furthermore it would be a very silly form of spam...
            */

            if (empty ($message)) {

              $error = true;
              $form = "Invalid or void message text.";

            }
            else {

              if (strlen ($message) > $maxmessagelen) {

                $error = true;

                $form = "Message text too long.//"
                      . "There is a $maxmessagelen characters limit "
                      . "for the message body." . $string_length_warning;
              }
              else {

                /*
                  get recipient's record, verify that it exists:
                  due to possible inconsistences in the members' database (due to malfunctions)
                  there could theoretically be registered nicknames without a matching account.
                */

                $db = "members" . intervalOf ($rcpt, $bs_members);
                $mr = get ($db, "id>$rcpt", "");

                if (empty ($mr)) {

                  $error = true;

                  $form = "No record in members database.//"
                        . "Member '$to' appears in the members' list but there seems to be no "
                        . "corresponding record in member's database. This could be an actual "
                        . "read error that could be solved if you retry, otherwise you're "
                        . "pleased to inform the community manager about this problem.";

                }
                else {

                  /*
                    read private messages ID counter, check if next ID is free:
                    if it's not, it's obviously a problem concerning the counter...
                  */

                  $pmid = intval (get ("stats/counters", "counter>newpms", "count")) + 1;
                  $pmdb = "pms" . intervalOf ($pmid, $bs_pms);
                  $pmch = get ($pmdb, "id>$pmid", "");

                  if (empty ($pmch)) {

                    /*
                      create array of recipients:
                      it will hold nicknames, initially not checked for existence and validity;
                      it starts as an array holding only the nickname of the main recipient,
                      then proceeds by exploding the list from the posted $cc field, and finally
                      proceeds to add administrators and moderators from the corresponding lists,
                      if it's been requested by the sender...
                    */

                    $rl = array ($to);
                    $rl = array_merge ($rl, wExplode (";", $cc));

                    if (isset ($_POST["ccadm"])) {

                      $manager = get ("members" . intervalOf (1, $bs_members), "id>1", "nick");
                      $administrators = all ("members/adm_list", asIs);

                      $rl[] = $manager;
                      $rl = array_merge ($rl, $administrators);

                    }

                    if (isset ($_POST["ccmod"])) {

                      $moderators = all ("members/mod_list", asIs);
                      $rl = array_merge ($rl, $moderators);

                    }

                    /*
                      exclude sender from recipients (if present):
                      it's useless to encumber the sender's inbox with a copy of
                      a message that the sender will already have in the outbox...
                    */

                    $rl = wArrayDiff ($rl, array ($nick));

                    /*
                      exclude duplicates from recipients:
                      messages will be only sent in one copy to all recipients, that is,
                      it isn't possible to send the message in two or more copies to the
                      same recipient...
                    */

                    $rl = wArrayUnique ($rl);

                    /*
                      deliver message to all recipients:
                      - the $ri array will hold the nicks of all recipients to which the
                        message can be successfully delivered;
                      - the $fl array will hold the list of those that couldn't be reached,
                        because their nickname entry or their account record don't exist.
                    */

                    $ri = array ();
                    $fl = array ();

                    foreach ($rl as $n) {

                      /*
                        retrieve recipient's ID ($ci): non-zero if nickname exists;
                        locate database file holding corresponding account record ($db);
                        retrieve recipient's record ($mr): non-empty if record exists.
                      */

                      $ci = intval (get ("members/bynick", "nick>$n", "id"));
                      $db = "members" . intervalOf ($ci, $bs_members);
                      $mr = get ($db, "id>$ci", "");

                      if (empty ($mr)) {

                        $fl[] = $n;

                      }
                      else {

                        /*
                          check recipient's ignore list:
                          if recipient ignores this sender, say nothing about it,
                          ie. don't deliver, but don't add recipient to failed recipients...
                        */

                        $rcpt_ilist = wExplode (";", valueOf ($mr, "ignorelist"));
                        $ok_to_deliver = (in_array ($nick, $rcpt_ilist)) ? false : true;

                        if ($ok_to_deliver) {

                          /*
                            add the ID of this PM to recipient's inbox and set
                            the recipient's "you have mail" flag ("newpm" = "yes")
                          */

                          $ri[] = $n;

                          $rcpt_inbox = wExplode (";", valueOf ($mr, "inbox"));
                          $rcpt_inbox[] = $pmid;

                          $mr = fieldSet ($mr, "inbox", implode (";", $rcpt_inbox));
                          $mr = fieldSet ($mr, "newpm", "yes");

                          set ($db, "id>$ci", "", $mr);

                        }

                      }

                    }

                    /*
                      update general PMs counter, write message record:
                      - unless $ri is still void, meaning that a message failed to reach all of
                        its recipients... in such a case, the whole message record has no reason
                        to exist, and nothing is done, in practice (failed recipients will be
                        notified to sender, though, unless they weren't included because they
                        were ignoring the sender).
                    */

                    if (count ($ri) > 0) {

                      $ri[] = $nick;    // PMs are also listed in sender's outbox, so there

                      $record = "<id>$pmid"
                              . "<to>"
                              .

                            (($anon)

                              ? "hidden recipients"
                              . "<hr>" . implode (";", $rl)     // hidden recipients list
                              . "<os>" . $nick                  // hidden original sender
                              : $to

                              )

                              . "<author>"
                              .

                            (($anon)

                              ? $servername
                              : $nick

                              )

                              . "<date>$epc"
                              . "<title>$title"
                              . "<message>$message"
                              . "<rcpt>" . implode (";", $ri);

                      set ("stats/counters", "counter>newpms", "", "<counter>newpms<count>$pmid");
                      set ($pmdb, "", "", $record);

                      /*
                        update private messages and carbon copies counter in sender's account:
                        providing the account still exists, because that wasn't checked after
                        locking the session, but it could in fact happen that an admin deleted
                        the sender's account before this script executed "lock_session", and in
                        this case, this (last) message gets throught, but the sender's record is
                        no longer there and cannot be re-written to update its counters...
                      */

                      $db = "members" . intervalOf ($id, $bs_members);
                      $mr = get ($db, "id>$id", "");

                      if (!empty ($mr)) {

                        $pms = intval (valueOf ($mr, "pms")) + 1;
                        $ccs = intval (valueOf ($mr, "ccs")) + count ($ri) - 2;

                        $sender_outbox = wExplode (";", valueOf ($mr, "outbox"));
                        $sender_outbox[] = $pmid;

                        $mr = fieldSet ($mr, "pms", strval ($pms));
                        $mr = fieldSet ($mr, "ccs", strval ($ccs));
                        $mr = fieldSet ($mr, "outbox", implode (";", $sender_outbox));

                        set ($db, "id>$id", "", $mr);

                      }

                    }

                    /*
                      report of any troublesome recipients
                    */

                    if (count ($fl) > 0) {

                      $hint = "...but the following recipients could not be processed "
                            . "(check spelling and compare to the actual members' list): "
                            . "<ul><li>" . implode ("</li><li>", $fl) . "</li></ul>";

                    }

                  }
                  else {

                    $error = true;

                    $form = "Private message ID conflict.//"
                          . "Possible problem with counters, please report this.";

                  }

                }

              }

            }

          }

        }

      }

    }

  }
  else {

    // this is just because it's not a submit, so it will show a void form...

    $error = true;
    $form = "Send private message...";

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

  if ($login) {

    $fw = $iw - 35;

    $form .=

          makeframe (

            "messaging",
            "<a href=inbox.php>go to your inbox</a><br>"
          . "<a href=outbox.php>go to your outbox</a>", true

          )

          .  "<table width=$pw>"
          .  "<form name=postform action=sendpm.php enctype=multipart/form-data method=post>"
          .  "<input type=hidden name=code value=" . setcode ("pst") . ">"
          .

        ((isset ($_GET["anon"]))

          ?  "<input type=hidden name=anon value=1>"
          :  ""

          )

          .   "<td height=20 class=inv align=center>"
          .    "recipients:"
          .   "</td>"
          .  "</table>"
          .  $inset_bridge
          .  $opcart
          .   $fspace
          .   "<tr>"
          .    "<td width=35 class=in align=right>"
          .     "to:&nbsp;&nbsp;"
          .    "</td>"
          .    "<td class=in>"
          .     "<input class=mf style=width:{$fw}px type=text name=to value=\"$to\" maxlength=20>"
          .    "</td>"
          .   "</tr>"
          .   "<tr>"
          .    "<td width=35 class=in align=right>"
          .     "CC:&nbsp;&nbsp;"
          .    "</td>"
          .    "<td class=in>"
          .     "<input class=mf style=width:{$fw}px type=text name=cc value=\"$cc\" maxlength=2200>"
          .    "</td>"
          .   "</tr>"
          .   $fspace
          .  $clcart
          .  $inset_bridge
          .  $opcart
          .   "<td class=in style=padding:2px>"
          .    "<input type=checkbox name=ccadm"
          .    ((isset ($_GET["ccadm"])) ? chr (32) ."checked" : "")
          .    ">&nbsp;CC to all administrators"
          .    "<br>"
          .    "<input type=checkbox name=ccmod"
          .    ((isset ($_GET["ccmod"])) ? chr (32) ."checked" : "")
          .    ">&nbsp;CC to all moderators"
          .   "</td>"
          .  $clcart
          .  $inset_shadow
          .  "<table width=$pw>"
          .   "<td height=20 class=inv align=center>"
          .    "subject line:"
          .   "</td>"
          .  "</table>"
          .  $inset_bridge
          .  $opcart
          .   "<td class=in>"
          .    "<input class=sf onChange=document.prevform.title.value=document.postform.title.value style=width:{$iw}px type=text name=title value=\"$title\" maxlength=$maxtitlelen>"
          .   "</td>"
          .  $clcart
          .  $inset_shadow
          .  "<table width=$pw>"
          .   "<td height=20 class=inv align=center>"
          .    "message text:"
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
          .     "<input class=su style=width:{$pw}px type=submit name=submit value=\"send message\">"
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
          .  "<input type=hidden name=pm value=1>"
          .

        ((isset ($_GET["anon"]))

          ?  "<input type=hidden name=anon value=1>"
          :  ""

          )

          .  "<input type=hidden name=author value=\"$nick\">"
          .  "<input type=hidden name=title value=\"$title\">"
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
             "Fill the recipient's nickname in the 'to' field on top, write a subject in the "
          .  "second field (upto $maxtitlelen characters are allowed there), and the message's "
          .  "text (which can be upto $maxmessagelen characters long) in the large text area. "
          .  "It is not possible to write more than one recipient in the 'to' field, but to "
          .  "broadcast the same message to other members, you can use the Carbon Copy (CC) "
          .  "field, separating your message's additional recipients in CC with semicolons.", true

          )

          .  "\n"
          .  "<script language=Javascript type=text/javascript>\n"
          .  "<!--\n"
          .    "document.postform.to.focus();\n"
          .  "//-->\n"
          .  "</script>\n";

  }

}
else {

  $form =

        makeframe (

          "message sent", false, false

        )

        .

        makeframe (

          "messaging",
          "<a href=sendpm.php>send another message</a><br>"
        . "<a href=inbox.php>go to your inbox</a><br>"
        . "<a href=outbox.php>go to your outbox</a>", true

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
