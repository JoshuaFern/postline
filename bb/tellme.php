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

$em['send_only_once'] = "PLEASE SEND ONLY ONCE";
$ex['send_only_once'] =

        "To limit spam submissions, this system allows each user to send only one message "
      . "every " . ($clear_fb_iplist / 3600) . " hours.";

/*
 *
 * utility function to get back dotquadded IP addresses from hex-encoded ones
 *
 */

function dotquad_ip ($h) {

  $a = base_convert (substr ($h, 0, 2), 16, 10);
  $b = base_convert (substr ($h, 2, 2), 16, 10);
  $c = base_convert (substr ($h, 4, 2), 16, 10);
  $d = base_convert (substr ($h, 6, 2), 16, 10);

  return "$a.$b.$c.$d";

}

/*
 *
 * initialize accumulators
 *
 */

$attachment_file = "dummy";     // dummy (for now) filename of attached (uploaded) file

/*
 *
 * feedback delivery operations
 *
 */

if (isset ($_POST["submit"])) {

  /*
    initialize accumulators
  */

  $errors = false;      // clear error flag
  $attach = false;      // clear attachment presence flag

  /*
    compute used space in feedback folder
  */

  $m = 0;
  $f = 0;
  $u = 0;

  if ($dh = @opendir ("./$feedbackfolder/")) {

    while (($file = @readdir ($dh)) !== false) {

      $s = $m + $f;

      $m += (substr ($file, -6, 6) == ".m.txt") ? 1 : 0;
      $f += (substr ($file, -6, 6) == ".a.txt") ? 1 : 0;

      if ($m + $f > $s) {

        $fsize = wFilesize ("./{$feedbackfolder}/{$file}");
        $fsize = (intval ($fsize / 65536) + 1) * 65536; // considers generous cluster size

        $u += $fsize;

      }

    }

    closedir ($dh);

  }

  if ($u > $feedback_space) {

    /*
      out of reserved feedback space
    */

    $errors = true;

    $status = "Sorry, our feedback folder is full!<br>"
            . "$m messages, $f files, " . ((int) ($u / 1024)) . " Kbytes in hold.<br>"
            . "<br>"
            . "<em>"
            .  "if you are the manager, open an FTP session to<br>"
            .  "retrieve and delete files from the feedback folder"
            . "</em><br>"
            . "<br>"
            . "<em>"
            .  "if you know how to contact the board's manager,<br>"
            .  "please warn the manager about this problem..."
            . "</em>";

  }

  if ($errors == false) {

    $code_typed = fget ("conf", 5, "");
    $code_expected = get ("stats/codes_feedback", "ip>$ip", "code");

    if ($code_typed !== $code_expected) {

      $errors = true;
      $status = "Seems you have typed the wrong number...";

    }

  }

  if ($errors == false) {

    $sender = fget ("name", 60, "");
    $sender = (empty ($sender)) ? "anonymous" : $sender;

    $message = fget ("message", 1000000, "\r\n");

    $attachment_size = (!empty ($_FILES["attachment"]["size"])) ? $_FILES["attachment"]["size"] : 0;
    $attachment_type = (!empty ($_FILES["attachment"]["type"])) ? $_FILES["attachment"]["type"] : 0;
    $attachment_file = ($_FILES["attachment"]["tmp_name"] != "none") ? $_FILES["attachment"]["tmp_name"] : "";

    if ((empty ($message)) && (empty ($attachment_file))) {

      $errors = true;
      $status = "No fields filled - nothing to send.";

    }
    else {

      $message = "Sender: $sender\r\n\r\n" . $message;

    }

  }

  if (($errors == false) && (!empty ($attachment_file))) {

    /*
      check for eventual valid attachment:
      - file must be neither void, nor reside on the server (possible security hole)
    */

    $valid_attachment = ($attachment_size > 0) ? true : false;
    $valid_attachment = ($attachment_size <= $feedbackmaxfile) ? $valid_attachment : false;
    $valid_attachment = (is_uploaded_file ($attachment_file)) ? $valid_attachment : false;

    if ($valid_attachment) {

      $attach = true;
      $status = "Attached file received and stored:<br>please send feedback forms only once.";

      $attachment_filename = gmdate ("Ymd.His", $epc) . $ip . ".a.txt";

      @unlink ($feedbackfolder . '/' . $attachment_filename);
      @copy ($attachment_file, $feedbackfolder . '/' . $attachment_filename);

    }
    else {

      $error = true;

      if ($attachment_size > $feedbackmaxfile) {

        $status = "Attached files cannot exceed " . intval ($feedbackmaxfile / 1024) . " Kb.";

      }
      else {

        $status = "Invalid file specification for attachment.";

      }

    }

  }

  if (($errors == false) && (!empty ($message))) {

    /*
      check message length against allowed maximum:
      on error, message won't be delivered, and already-processed attachment will also be deleted
    */

    if (strlen ($message) <= $feedbackmaxmsg) {

      $status = "Alright, message received and stored:<br>please send feedback forms only once.";
      $message_filename = gmdate ("Ymd.His", time()) . $ip . ".m.txt";

    }
    else {

      $errors = true;
      $status = "Sorry, message text length can't exceed " . intval ($feedbackmaxmsg / 1024) . " Kb.";

      if ($attach) {

        @unlink ($feedbackfolder . '/' . $attachment_filename);

      }

    }

  }

  if ($errors == false) {

    /*
      check flood on feedbacks:
      only one delivery is allowed per IP, but the "blacklist" of IP addresses will be
      periodically discarded to allow an acceptable flow (typically 1 message every 24 hours)
    */

    $date = intval (get ("stats/feedback", "counter>date", "value"));

    if ($date == 0) {

      /*
        zero means it's been evaluated as integer basing on an empty string:
        it indirectly means the list of blacklisted IP addresses hasn't been saved
        yet, since last clanup (in fact, cleanups delete "stats/feedback" entirely)
      */

      lockSession ();
      set ("stats/feedback", "counter>date", "", "<counter>date<value>$epc");

    }

    elseif ($date + $clear_fb_iplist < $epc) {

      /*
        this means enough time has passed ($clear_fb_iplist defined by "settings.php")
        that the blacklist can be erased: a null write to the file will erase the file
      */

      lockSession ();
      writeTo ("stats/feedback", voidString);
      writeTo ("stats/codes_feedback", voidString);

    }

    /*
      apart from clearing the blacklist when the time comes, mantain it by adding this user's
      IP address to the list, unless it's already there (in which case delivery is denied):
      begin by retrieving current list and checking for presence of the sender's IP...
    */

    $list = wExplode (";", get ("stats/feedback", "counter>feedback", "list"));
    $is_flooding = in_array ($ip, $list);

    if ($is_flooding) {

      /*
        deny delivery:
        this user evidently already sent a feedback form after last cleanup of the blacklist;
        session is unlocked because of eventual, former lockings OUTSIDE this script: you may
        notice that this script puts efforts into NOT locking the session when the user tries
        to flood, which is intentional, and precisely done to avoid repeated requests to only
        simulate some sort of weak DoS performed by submitting "dummy" forms many times...
      */

      unlockSession ();

      if ($attach) {

        @unlink ($feedbackfolder . '/' . $attachment_filename);

      }

      if (@file_exists ($attachment_file)) {

        @unlink ($attachment_file);

      }

      die (because ('send_only_once'));

    }
    else {

      /*
        allow delivery:
        but deny any subsequent retries by adding the IP to the blacklist
      */

      $list[] = $ip;
      $list = implode (";", $list);

      lockSession ();
      set ("stats/feedback", "counter>feedback", "", "<counter>feedback<list>$list");

      /*
        log a "volatile" warning to the public chat frame, so mods/admins may know
      */

      $to = array (

        1 => "manager",
        2 => "admins (except manager)",
        3 => "admins",
        4 => "moderators",
        5 => "manager (and all moderators)",
        6 => "admins and moderators",
        7 => "staff people"

      );

      $rcpt = ((isset ($_POST["man"])) ? 1 : 0)
            + ((isset ($_POST["adm"])) ? 2 : 0)
            + ((isset ($_POST["mod"])) ? 4 : 0);

      if ($rcpt > 0) {

        logwr ("Hey {$to[$rcpt]}, you have mail!", lw_persistent);

      }

    }

    /*
      feedback message delivery operations:
      setup email messages subject and body
    */

    $subject = ucfirst ($sitename) . ": feedback from $sender...";
    $sent_to = array ();

    if (isset ($_POST["man"])) $sent_to[] = "manager";
    if (isset ($_POST["adm"])) $sent_to[] = "administrators";
    if (isset ($_POST["mod"])) $sent_to[] = "moderators";

    $message .= "\r\n\r\nSent to: " . implode (", ", $sent_to)
             .  "\r\nSender IP: " . dotquad_ip ($ip)
             .

            (($login)

             ?  "\r\nSender account identity: $nick"
             :  "\r\nMessage was not sent by a registered member."

             )

             .

           (($attach)

             ?  "\r\n\r\nAttached file: {$sitedomain}{$feedbackfolder}/{$attachment_filename}"
             .  "\r\nAttached file type: $attachment_type"
             :  ""

             );

    if ($login) {

      $db = "members" . intervalOf ($id, $bs_members);
      $ra = get ($db, "id>$id", "email");

      $c1 = (empty ($ra)) ? true : false;
      $c2 = (strpos ($ra, "@") === false) ? true : false;
      $c3 = (strpos ($ra, ".") === false) ? true : false;
      $c4 = (strlen ($ra) < 5) ? true : false;

      if (($c1) || ($c2) || ($c3) || ($c4)) {

        $from = "From: $nick (no return address) <do_not_reply@{$sitemailer}>\r\n";

      }

      else {

        $from = "From: $nick <$ra>\r\n";

      }

    }

    else {

      $from = "From: Unregistered {$sitename} user <do_not_reply@{$sitemailer}>\r\n";

    }

    /*
      save message file to server's feedback folder, as plain text:
      a message is always present even if the text area was left void, in which case the message
      only holds informations about attached file (if no attachment as well, errors come before)
    */

    $fh = @fopen ($feedbackfolder . '/' . $message_filename, "w");

    if ($fh !== false) {

      @fwrite ($fh, $message);
      @fclose ($fh);

    }

    /*
      if requested, mail a copy to the manager
    */

    if (isset ($_POST["man"])) {

      $email = get ("members" . intervalOf (1, $bs_members), "id>1", "email");
      $valid = ((strtolower ($email) != "none") && (strpos ($email, "@"))) ? true : false;

      if ($valid) {

        @mail ($email, $subject, $message, $from);

      }

    }

    /*
      if requested, mail a copy to all admins
    */

    if (isset ($_POST["adm"])) {

      $admins = all ("members/adm_list", asIs);

      foreach ($admins as $n) {

        $i = intval (get ("members/bynick", "nick>$n", "id"));
        $email = get ("members" . intervalOf ($i, $bs_members), "id>$i", "email");
        $valid = ((strtolower ($email) != "none") && (strpos ($email, "@"))) ? true : false;

        if ($valid) {

          @mail ($email, $subject, $message, $from);

        }

      }

    }

    /*
      if requested, mail a copy to all mods
    */

    if (isset ($_POST["mod"])) {

      $mods = all ("members/mod_list", asIs);

      foreach ($mods as $n) {

        $i = intval (get ("members/bynick", "nick>$n", "id"));
        $email = get ("members" . intervalOf ($i, $bs_members), "id>$i", "email");
        $valid = ((strtolower ($email) != "none") && (strpos ($email, "@"))) ? true : false;

        if ($valid) {

          @mail ($email, $subject, $message, $from);

        }

      }

    }

  }

  if ($errors) {

    $status .= "<hr><a href=javascript:history.go(-1)>&gt;&gt;&gt; try again...</a>";

  }

  $status = "<table style=width:100%;height:100%>"
          .  "<td align=center>"
          .   "<table align=center class=ot><td>"
          .    "<table align=center class=it>"
          .     "<td width=300>"
          .      $status
          .     "</td>"
          .    "</table>"
          .   "</td></table>"
          .  "</td>"
          . "</table>";

}
else {

  lockSession ();

  $cnf_code = mt_rand (10000, 99999);
  set ("stats/codes_feedback", "ip>$ip", "", "<ip>$ip<code>$cnf_code");

  $digits = array (

    0,1,1,1,0,        // digit "0"
    1,0,0,0,1,
    1,0,0,0,1,
    1,0,0,0,1,
    1,0,0,0,1,
    1,0,0,0,1,
    0,1,1,1,0,

    0,0,1,0,0,        // digit "1"
    0,1,1,0,0,
    0,0,1,0,0,
    0,0,1,0,0,
    0,0,1,0,0,
    0,0,1,0,0,
    0,1,1,1,0,

    0,1,1,1,0,        // digit "2"
    1,0,0,0,1,
    0,0,0,0,1,
    0,0,0,1,0,
    0,0,1,0,0,
    0,1,0,0,0,
    1,1,1,1,1,

    0,1,1,1,0,        // digit "3"
    1,0,0,0,1,
    0,0,0,0,1,
    0,0,1,1,0,
    0,0,0,0,1,
    1,0,0,0,1,
    0,1,1,1,0,

    0,0,0,1,0,        // digit "4"
    0,0,1,1,0,
    0,1,0,1,0,
    1,0,0,1,0,
    1,1,1,1,1,
    0,0,0,1,0,
    0,0,0,1,0,

    1,1,1,1,1,        // digit "5"
    1,0,0,0,0,
    1,0,0,0,0,
    1,1,1,1,0,
    0,0,0,0,1,
    1,0,0,0,1,
    0,1,1,1,0,

    0,1,1,1,0,        // digit "6"
    1,0,0,0,0,
    1,0,0,0,0,
    1,1,1,1,0,
    1,0,0,0,1,
    1,0,0,0,1,
    0,1,1,1,0,

    1,1,1,1,1,        // digit "7"
    0,0,0,0,1,
    0,0,0,0,1,
    0,0,0,1,0,
    0,0,0,1,0,
    0,0,1,0,0,
    0,0,1,0,0,

    0,1,1,1,0,        // digit "8"
    1,0,0,0,1,
    1,0,0,0,1,
    0,1,1,1,0,
    1,0,0,0,1,
    1,0,0,0,1,
    0,1,1,1,0,

    0,1,1,1,0,        // digit "9"
    1,0,0,0,1,
    1,0,0,0,1,
    0,1,1,1,1,
    0,0,0,0,1,
    1,0,0,0,1,
    0,1,1,1,0

  );

  $captcha_code =

          $opcart
        .   "<td class=ls align=center>"
        .    "<table align=center>";

  $cnf_code = strval ($cnf_code);

  for ($r = 0; $r < 7; $r ++) {

    $captcha_code .= "<tr>";

    for ($n = 0; $n < 5; $n ++) {

      $p = (5 * 7 * intval ($cnf_code[$n])) + (5 * $r);

      for ($c = 0; $c < 5; $c ++, $p ++) {

        for ($x = 0; $x < 3; $x ++) {

          $captcha_code .= ($r) ? "<td" : "<td width=1";
          $captcha_code .= ($n + $c) ? "" : " height=3";
          $captcha_code .= ($digits[$p] && mt_rand (1, 15) != 7) ? " bgcolor=black></td>" : "></td>";

        }

      }

      $captcha_code .= ($r) ? "<td></td>" : "<td width=2></td>";

    }

    $captcha_code .= "</tr>";

  }

  $captcha_code .=

             "</table>"
        .   "</td>"
        .  $clcart;

  $status = "<table style=width:100%;height:100%>"
          . "<form action=tellme.php enctype=multipart/form-data method=post>"
          . "<input type=hidden name=MAX_FILE_SIZE value=$feedbackmaxfile>"
          .  "<td align=center>"
          .   "<table align=center class=ot><td>"
          .    "<table align=center class=it>"
          .     "<tr>"
          .      "<td width=300>"
          .       "<table width=300>"
          .        "<tr>"
          .         "<td height=18 align=center>"
          .          "type your name, or your account's nickname<br>"
          .          "<em>leave following field void to be anonymous</em>"
          .         "</td>"
          .        "</tr>"
          .       "</table>"
          .       "<table width=300 class=tb align=center>"
          .        "<tr>"
          .         "<td>"
          .          "<table width=298 style=margin:1px>"
          .           "<tr>"
          .            "<td class=in>"
          .             "<input class=sf type=text name=name value=\"\" style=width:298px maxlength=60>"
          .            "</td>"
          .           "</tr>"
          .          "</table>"
          .         "</td>"
          .        "</tr>"
          .       "</table>"
          .       "<table width=300>"
          .        "<tr><td height=12></td></tr>"
          .        "<tr>"
          .         "<td height=36 align=center>"
          .          "type your message - max $feedbackmaxmsg characters<br>"
          .          "<em>include YOUR EMAIL if you need a reply</em>"
          .         "</td>"
          .        "</tr>"
          .       "</table>"
          .       "<table width=300 class=tb align=center>"
          .        "<tr>"
          .         "<td>"
          .          "<table width=298 style=margin:1px>"
          .           "<tr>"
          .            "<td>"
          .             "<textarea name=message style=width:298px;height:120px></textarea>"
          .            "</td>"
          .           "</tr>"
          .          "</table>"
          .         "</td>"
          .        "</tr>"
          .       "</table>"
          .       "<table width=300>"
          .        "<tr><td height=12></td></tr>"
          .        "<tr>"
          .         "<td height=36 align=center>"
          .          "you can attach a file, if you need to<br>"
          .          "<em>maximum attached file size: " . intval ($feedbackmaxfile / 1024) . " Kb</em>"
          .         "</td>"
          .        "</tr>"
          .       "</table>"
          .       "<table width=300 class=tb align=center>"
          .        "<tr>"
          .         "<td>"
          .          "<table width=298 style=margin:1px>"
          .           "<tr>"
          .            "<td class=fs>"
          .             "<input type=file name=attachment style=width:296px size=34>"
          .            "</td>"
          .           "</tr>"
          .          "</table>"
          .         "</td>"
          .        "</tr>"
          .       "</table>"
          .       "<table width=300 align=center>"
          .         "<tr><td height=12></td></tr>"
          .         "<tr>"
          .          "<td colspan=3 height=32 align=center>"
          .           "type following number in field to the right"
          .          "</td>"
          .         "</tr>"
          .         "<tr>"
          .          "<td>"
          .           $captcha_code
          .          "</td>"
          .          "<td style=\"padding:6px;font:$kfont0\">"
          .           "="
          .          "</td>"
          .          "<td>"
          .           "<table style=\"width:80px;background-color:black;text-align:center\">"
          .            "<tr>"
          .             "<td>"
          .              "<table width=78 style=margin:1px>"
          .               "<tr>"
          .                "<td class=in>"
          .                 "<input class=sf type=text name=conf value=\"\" maxlength=5 style="
          .                  "width:78px;height:30px;text-align:center>"
          .                "</td>"
          .               "</tr>"
          .              "</table>"
          .             "</td>"
          .            "</tr>"
          .           "</table>"
          .          "</td>"
          .         "</tr>"
          .         "<tr><td height=9></td></tr>"
          .       "</table>"
          .      "</td>"
          .      "<td width=12></td>"
          .      "<td width=100 valign=bottom>"
          .       "<table width=100>"
          .        "<tr>"
          .         "<td class=ts style=line-height:120%>"
          .          "Please note that if you don't give staff members a way to reply "
          .          "to your message, you won't get a reply, so you might include your email in "
          .          "the message, unless you are logged in, in which case we could reply with a "
          .          "private message. Also, please note that nobody may send more than one form "
          .          "per day, so make this chance count!"
          .         "</td>"
          .        "</tr>"
          .        "<tr><td height=12 class=ff></td></tr>"
          .        "<tr>"
          .         "<td height=24 align=center>mail copies to:</td>"
          .        "</tr>"
          .        "<tr>"
          .         "<td>"
          .          "<table width=100 bgcolor=black>"
          .           "<tr>"
          .            "<td>"
          .             "<table width=98 style=margin:1px>"
          .              "<tr>"
          .               "<td class=ls style=padding:2px>"
          .                "<input type=checkbox name=man checked>&nbsp;manager<br>"
          .                "<input type=checkbox name=adm checked>&nbsp;admins<br>"
          .                "<input type=checkbox name=mod checked>&nbsp;moderators"
          .               "</td>"
          .              "</tr>"
          .             "</table>"
          .            "</td>"
          .           "</tr>"
          .          "</table>"
          .         "</td>"
          .        "</tr>"
          .        "<tr><td height=9></td></tr>"
          .       "</table>"
          .      "</td>"
          .     "</tr>"
          .     "<tr>"
          .      "<td colspan=3>"
          .       "<input class=vs type=submit name=submit value=\"CLICK HERE TO SEND ALL\" style=width:412px>"
          .      "</td>"
          .     "</tr>"
          .    "</table>"
          .   "</td></table>"
          . "</form>"
          . "</table>";

}

/*
 *
 * cleanup temporary files
 *
 */

if (@file_exists ($attachment_file)) {

  @unlink ($attachment_file);

}

/*
 *
 * determine permalink presence (present if no arguments given)
 *
 */

$args_not_given = ((count ($_GET)) || (count ($_POST))) ? false : true;

/*
 *
 * permalink initialization
 *
 */

$permalink = ($enable_permalinks && $args_not_given)

  ? str_replace

      (

        "[PERMALINK]", $perma_url . "/" . $feedback_keyword, $permalink_model

      )

  : "";

/*
 *
 * template initialization
 *
 */

$tellme = str_replace

  (

    array (

      "[FORM]",
      "[PERMALINK]"

    ),

    array (

      $status,
      $permalink

    ),

    $tellme

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

if (($login) && ($nspy != "yes")) {

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

echo (pquit ($tellme));



?>
