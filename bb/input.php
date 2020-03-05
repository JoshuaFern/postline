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
define ("input_frame", true);

require ("settings.php");
require ("suitcase.php");
require ("sessions.php");
require ("template.php");

$em['gagged'] = "SORRY, YOU HAVE BEEN DISALLOWED TO CHAT OR MAKE USE OF COMMANDS";
$ex['gagged'] =

        "Moderators might have noticed you somewhat disturbing the conversation "
      . "in a way they found annoying, irresponsible, long and pointless, or in "
      . "any other ways getting on people\'s nerves. As a consequence, you have "
      . "been currently forbidden to chat and/or send commands.";

/*
 *
 * initialize output accumulators
 *
 */

$speech = "CLI parse error";    // phrase to post in the chat frame
$username = $servername;        // username shown

/*
 *
 * highlighters
 *
 */

function islocal ($url, $names) {

  /* This function determines if a page points to a local URL in the bulletin board's domain,
     and wether its destination script matches one of those given in a comma-separated list,
     passed as $names. Returns true if both the conditions are so, false otherwise. */

  global $sitedomain;

  $name = explode (",", $names);

  foreach ($name as $n) {

    $p = strpos ($url, $n);

    if ($p !== false) {

      /*
        one of the names within the $names list appears in the $url string
      */

      $s = strpos ($url, $sitedomain);

      if ($s !== false) {

        /*
          $sitedomain also appears in the $url string:
          the page is local if $sitedomain appears before $n (name in the $names list)
        */

        if ($s < $p) return true;

      }

      /*
        the page may be local even if $sitedomain isn't part of the URL,
        providing there is no protocol indicator (:) before the page's name,
        in which case the page's path could be relative to the current directory,
        for e.g. $url = "../index.php"
      */

      if (strpos (substr ($url, 0, $p), ":") === false) return true;

    }

  }

  return false;

}

function target_of ($url) {

  global $l_frames;
  global $r_frames;

  /*
    This function returns the most appropriate target frame for loading a page,
    depending on its URL (if it's a local page, open it in the appropriate frame of
    the bulletin board's frameset; if it's external to the site, open in a new window).
  */

  $url = strtolower ($url);
  $target = "_blank";

  if (islocal ($url, "index.php")) {

    /*
      the "index.php" script, which is supposed to be "postline/index.php",
      needs to be loaded inside the "pag" frame, ie. as the new central frameset
    */

    $target = "pag";

  }
  elseif (islocal ($url, implode (",", $r_frames))) {

    /*
      these are all pages that might load inside the right frame (pan)
    */

    $target = "pan";

  }
  elseif (islocal ($url, implode (",", $l_frames))) {

    /*
      and these are all pages that might load inside the left frame (pst)
    */

    $target = "pst";

  }

  return chr (32) . "target=$target";

}

function highlight ($string) {

  /* This function will scan $string for any possible links beginning with "http://" or "www.":
     when one is found, it's transformed into an HTML link, and also pointed to  the most
     appropriate target frame. It will also highlight possible mailto links... */

  global $link_terminators;
  global $parblock_terminators;
  global $email_terminators;
  global $postlink_attributes;

  $i = 0;       // pointer within string
  $t = false;   // flag: true while parsing inside an HTML tag

  while (($c = $string[$i]) != "") {

    /*
      count HTML tag openers and closers
    */

    if ($c == "<") $t = true;
    elseif ($c == ">") $t = false;

    /*
      if this character isn't part of a tag...
    */

    if ($t == false) {

      /*
        ...could it be an email address?
        look for "@"...
      */

      if ($c == "@") {

        /*
          possible email address match found, and outside tags:
          if it could be valid, it'll be highlighted as intended
        */

        $s = $i;      // remembers the position of the @ sign
        $L = 1;       // length of address string to replace: so far, the @ sign alone
        $b = $i;      // beginning of address string within actual $string text
        $i = $i - 1;  // update $i to point at the character preceeding the @ sign

        /*
          backward scan until the beginning of mail address' username
        */

        while (($c = $string[$i]) != "") {

          if (in_array ($c, $email_terminators)) {

            /*
              username ends because a common kind of terminator has been found
            */

            break;

          }

          $L ++;
          $b --;
          $i --;

        }

        /*
          forward scan until the end of the mail address' domain
        */

        $i ++;        // back to non-terminator character following the terminator
        $i += $L;     // skip the whole "user@" part, prepare to examine the domain

        while (($c = $string[$i]) != "") {

          if (in_array ($c, $email_terminators)) {

            /*
              domain ends because a common kind of terminator has been found
            */

            break;

          }

          $L ++;
          $i ++;

        }

        /*
          the address is considered possibly valid, and highlighted, if it has:
          - at least 6 characters complessive length (x@x.xx);
          - at least 1 character before the @ sign;
          - at least 4 characters after the @ sign.
        */

        if (($L >= 6) && ($s - $b >= 1) && ($i - $s >= 4)) {

          /*
            get destination address, which is the same, whole address string to highlight
          */

          $d = substr ($string, $b, $L);

          /*
            assemble HTML anchor for this mailto link,
            assemble new version of entry $string, with the address replaced by its link
          */

          $a = "<a $postlink_attributes" . target_of ($d) . chr (32) . "href=\"mailto:$d\">$d</a>";
          $string = substr ($string, 0, $b) . $a . substr ($string, $i);

          /*
            advance $i to point at the character right after the above closing "</a>" tag:
            the amount of characters to skip to "synchronize" it with the new version of
            $string is given by whatever exceeds the original length of mail address $d
          */

          $i += strlen ($a);
          $i -= strlen ($d);

        }

      }
      else {

        /*
          ...and if it's no "@" sign, it could be the initial of an URL...
        */

        if ($c == "h") {

          $match = (substr ($string, $i, 7) == "http://") ? true : false;

        }
        elseif ($c == "w") {

          $match = (substr ($string, $i, 4) == "www.") ? true : false;

        }
        else {

          $match = false;

        }

        if ($match) {

          /*
            possible URL match found, and outside tags:
            if valid, must be highlighted as intended
          */

          $l = ($c == "h") ? 7 : 4;

          $L = $l;        // length of URL string to replace, so far
          $b = $i;        // beginning of URL string within actual $string text
          $i = $i + $l;   // update $i to end of URL prefix within actual $string text
          $p = false;     // true after finding a ? in the URL: detects GET parameters block

          while (($c = $string[$i]) != "") {

            if ($c == "&") {

              /*
                this check is necessary to determine wether a newline (<br>) has been inserted
                past the URL: Postline stores newlines in posts using a special marker: "&;"
                (an ampersand followed by a semicolon), and newlines always terminate URLs
              */

              if ($string[$i + 1] == ";") break;

            }

            if ($p) {

              if (in_array ($c, $parblock_terminators)) {

                /*
                  in parameters block, found what's almost certainly a terminator:
                  the URL (hopefully) ends here, although it's possible for these
                  terminators to appear in a GET parameter blocks, but uncommon...
                */

                break;

              }

            }
            else {

              if ($c == "?") {

                /*
                  realize that since now you are parsing a GET parameters block,
                  but stop if there's effectively nothing following the question mark,
                  which in this case might be a simple punctuator...
                */

                $p = true;

                if (in_array ($string[$i + 1], $link_terminators)) break;

              }
              else {

                if (in_array ($c, $link_terminators)) {

                  /*
                    if a GET parameters block hasn't been detected so far,
                    the URL ends because a more common kind of terminator has been found
                  */

                  break;

                }

              }

            }

            $L ++;
            $i ++;

          }

          /*
            if it's a significant match (at least 4 characters follow the prefix),
            effectively highlight it
          */

          if ($L >= ($l + 4)) {

            /*
              get destination of URL, which is the same, whole URL string to highlight
            */

            $d = substr ($string, $b, $L);

            /*
              set proper URL prefix in case this URL begins with "www.", assuming http protocol
            */

            $x = ($l == 4) ? "http://" : "";

            /*
              assemble HTML anchor for this URL,
              assemble new version of entry $string, with the URL replaced by its HTML anchor
            */

            $a = "<a $postlink_attributes" . target_of ($d) . chr (32) . "href=\"$x$d\">$d</a>";
            $string = substr ($string, 0, $b) . $a . substr ($string, $i);

            /*
              advance $i to point at the character right after the above closing "</a>" tag:
              the amount of characters to skip to "synchronize" it with the new version of
              $string is given by whatever exceeds the original length of the URL $d
            */

            $i += strlen ($a);
            $i -= strlen ($d);

          }

        }

      }

    }

    $i ++;

  }

  return $string;

}

function addcode ($in_page_model) {

  global $login;
  global $cflag, $cflag_cookie_name, $cflag_cookie_text, $cflag_cookie_hold;

  $cflag = $new_cflag_cookie_hold = (isset ($_GET['coff'])) ? 'off' : 'on';
  $update_cflag_cookie = ($cflag_cookie_hold == $new_cflag_cookie_hold) ? false : true;

  if ($update_cflag_cookie) {

    setcookie ($cflag_cookie_name, $cflag_cookie_text . $new_cflag_cookie_hold, 2147483647, '/');

  }

  return str_replace

    (

      array

        (

          "[LOGIN-CHECK]",
          "[SECURITY-CODE]"

        ),

      array

        (

          "\n"
        . "<script type=text/javascript>\n"
        . "<!--\n"
        . "function cliSubmit () {\n"
        .

      (($login)

        ? ""
        : "alert ('WARNING:\\nyou need to be registered and logged in to talk here.');\n"

        )

        . "}\n"
        . "//-->\n"
        . "</script>\n",
          "<input type=hidden name=code value=" . setcode ("cli") . ">"

        ),

      $in_page_model

    );

}

/*
 *
 * something valid to parse?
 *
 */

$speech = fget ("say", $maxcharsperpost, "");
$cli = strtolower ($speech);

if (empty ($speech)) {

  /*
    will not report trivial errors, to avoid disturbing conversations:
    the error here is that the CLI is empty (it's most probably a page refresh)
  */

  die (pquit (addcode ($in_page)));

}

/*
 *
 * parse commands
 *
 */

$command = false;       // true if it was a command, and shouldn't count for the flood limits

if ($login) {

  /*
    check muted status: muted member can't chat or use commands
  */

  if ($is_muted) {

    unlockSession ();
    die (because ('gagged'));

  }
  else {

    /*
      define this to signal the context is valid for commands execution
    */

    define ("from_cli", true);

    /*
      unelegant, eh?
      better be clear here: includes must be safe
    */

    if (substr ($cli, 0, 7) == "/chpass")       { $script = "chpass.php";       $command = true; }
    if (substr ($cli, 0, 5) == "/kick")         { $script = "kick.php";         $command = true; }
    if (substr ($cli, 0, 9) == "/newforum")     { $script = "newforum.php";     $command = true; }
    if (substr ($cli, 0, 9) == "/renforum")     { $script = "renforum.php";     $command = true; }
    if (substr ($cli, 0, 7) == "/moveup")       { $script = "moveup.php";       $command = true; }
    if (substr ($cli, 0, 9) == "/movedown")     { $script = "movedown.php";     $command = true; }
    if (substr ($cli, 0, 9) == "/delforum")     { $script = "delforum.php";     $command = true; }
    if (substr ($cli, 0, 9) == "/undelete")     { $script = "undelete.php";     $command = true; }
    if (substr ($cli, 0, 5) == "/lose")         { $script = "lose.php";         $command = true; }

    if (substr ($cli, 0, 9) == "/pomelize")     { $script = "pomelize.php";     $command = true; }
    if (substr ($cli, 0, 11) == "/unpomelize")  { $script = "unpomelize.php";   $command = true; }

    if ($command == false) {

      /*
        not a command, only set nickname for chat post:
        read of session record enables playing with identities/colors via database explorer hacks,
        otherwise what follows could be replaced with a simpler $username = $nick
      */

      $sr = get ('stats/sessions', "id>$id", wholeRecord);
      $username = (empty ($sr)) ? $nick : valueOf ($sr, 'nick');

    }
    else {

      /*
        chech security code before executing any commands:
        at the moment, the chat line doesn't check for links leading to
        posting a line in the chat, because the fact of posting a line
        in the chat isn't exactly deemed as a potentially dangerous
        operation for the account's owner... to its maximum extent, it
        could be some sort of joke, but hardly something more
      */

      $code = intval (fget ("code", 1000, ""));

      if ($code == getcode ("cli")) {

        include ($script);

      }
      else {

        die (pquit (addcode ($in_page)));

      }

      /*
        will not report server responses if in quiet mode
      */

      if (substr ($cli, -2, 2) == "/q") {

        die (pquit (addcode ($in_page)));

      }

    }

  }

}
else {

  /*
    will not report trivial errors, to avoid disturbing conversations:
    the error here is some input by a member that's not logged in
  */

  die (pquit (addcode ($in_page)));

}

/*
 *
 * chat flood limit (not effective on commands, of course)
 *
 */

if ($command == false) {

  $lastphrase = get ("stats/flooders", "entry>phrase", "=");
  list ($lpid, $lptime, $lpmd5) = explode ("+", $lastphrase);

  $lptime = intval ($lptime);
  $pmd5 = md5 ($speech);

  if ($lpid == $id) {

    if (($lpmd5 == $pmd5) || ($epc < $lptime + $antichatflood)) {

      die (pquit (addcode ($in_page)));

    }

  }

}

/*
 *
 * force a lock on the session, now, to avoid interferences while updating the chat frame
 *
 */

lockSession ();

/*
 *
 * set anti-flood marker
 *
 */

if ($command == false) {

  set ("stats/flooders", "entry>phrase", "", "<entry>phrase<=>$id+$epc+$pmd5");

}

/*
 *
 * read conversation and insulate existing text
 *
 */

$textdata = readFrom ("frespych/conversation");
$toremind = '';

/*
 *
 * parse special syntaxes
 *
 */

$typo = false;
$action = false;
$yelling = false;
$whispering = false;

$l = strlen ($speech);

if (strtolower (substr ($speech, 0, 3)) == "/me") {

  /*
    /me does an action
  */

  if ($l > 3) {

    $speech = ltrim (substr ($speech, 3));
    $action = true;

  }

}
else {

  if ($l > 1) {

    switch ($speech[0]) {

      case "*":

        /*
          - if there's both a leading and trailing asterisk, mark action, same as /me
          - else, if there's a single leading asterisk, mark typo ("someone meant: ...")
        */

        if (substr ($speech, -1, 1) == "*") {

          $speech = substr ($speech, 1, -1);
          $action = true;

        }
        else {

          $speech = substr ($speech, 1);
          $typo = true;

        }

        break;

      case "!":

        /*
          leading exclamation mark turns speech to all uppercase, and uses a vivid color (yelling)
        */

        $speech = strtoupper (substr ($speech, 1));
        $yelling = true;

        break;

      case "#":

        /*
          leading hash prints in soft color (whispering)
        */

        $speech = substr ($speech, 1);
        $whispering = true;

    }

  }

}

/*
 *
 * apply word filters, eventually pomelize, highlight URLs and mailto's (if any)
 *
 */

if ($command == false) {

  $parts = preg_split ("/(\W)/", $speech, -1, PREG_SPLIT_DELIM_CAPTURE);

  /*
    building word filters array
  */

  $wordfilters = array ();
  $wfl = wExplode ("\n", readFrom ("stats/wfl"));

  foreach ($wfl as $filter_record) {

    list ($filter_word, $filter_replacement) = explode ('=', $filter_record);

    $filter_word = trim (strtolower ($filter_word));
    $filter_replacement = trim ($filter_replacement);

    $wordfilters[$filter_word] = $filter_replacement;

  }

  /*
    applying pomelizer
  */

  $member_pomelizer = get ("stats/sessions", "id>$id", "pomelized");
  $global_pomelizer = get ("stats/counters", "counter>pomelizer", "state");

  $pomelized = (($member_pomelizer === "yes") || ($member_pomelizer == "rnd")) ? true : false;
  $pomelized = (($global_pomelizer === "on") || ($global_pomelizer == "rnd")) ? true : $pomelized;

  if ($pomelized) {

    $randomly_pomelized = ($member_pomelizer === "rnd") ? true : false;
    $randomly_pomelized = ($global_pomelizer === "rnd") ? true : $randomly_pomelized;

    $partno = 0;

    foreach ($parts as $part) {

      $bypass = empty ($part) ? true : false;
      $bypass = preg_match ("/\W/", $part) ? true : $bypass;
      $bypass = ($part == "s" || $part == "S" || $part == "t" || $part == "T") ? true : $bypass;

      if ($bypass == false) {

        $charcode = ord ($part);
        $capschar = (($charcode >= 65) && ($charcode <= 90)) ? true : false;
        $wpomelos = ($yelling) ? "POMELOS" : (($capschar) ? "Pomelos" : "pomelos");

        if ($randomly_pomelized == false) {

          $parts[$partno] = $wpomelos;

        }

        else {

          $parts[$partno] = (mt_rand (1, 2) == 1) ? $parts[$partno] : $wpomelos;

        } // randomly versus completely pomelized

      } // word was replaceable (no puctuator, no whitespace, no genitive 's'...)

      ++ $partno;

    } // each word in sentence

  } // been applying pomelizer

  /*
    applying word filters
  */

  unset ($speech);

  foreach ($parts as $part) {

    $bypass = empty ($part) ? true : false;
    $bypass = preg_match ("/\W/", $part) ? true : $bypass;

    if ($bypass) {

      $speech .= $part;

    } // part is no word (punctuator or whitespace)

    else {

      $key = strtolower ($part);

      if (isset ($wordfilters[$key])) {

        $charcode = ord ($part);
        $capschar = (($charcode >= 65) && ($charcode <= 90)) ? true : false;
        $new_word = $wordfilters[$key];

        $new_word = ($yelling)

          ? strtoupper ($new_word)
          : (($capschar) ? ucfirst ($new_word) : $new_word);

        $speech .= $new_word;

      } // word had to be replaced

      else {

        $speech .= $part;

      } // word had not to be replaced

    } // part is a word

  } // each part of sentence

  /*
    applying highlighters
  */

  $speech = highlight ($speech);

} // not a command (word filers, pomelizer and highlighters may be applied)

/*
 *
 * append post to chat frame log
 *
 */

$timestamp = gmdate ("H:i", $epc) . "," . chr (32);

if ($username == $servername) {

  /*
    server statement, in reply to commands:
    always persistent for user commands, it's the equivalent of a "logwr ($speech, lw_persistent)"
  */

  $toremind = $timestamp

    . "<a class=se>"
    .   $username
    . "</a>"
    . "&gt;" . chr (32) . $speech . "<br>\n";

}
else {

  /*
    read of session record is only to play with identities and colors via database explorer hacks
  */

  $sr = get ('stats/sessions', "id>$id", wholeRecord);
  $ntint = (empty ($sr)) ? $ntint : intval (valueOf ($sr, 'ntint'));

  if (($ntint > 0) && ($ntint < count ($ntints))) {

    /*
      user-chosen nickname color
    */

    if ($action) {

      /*
        actions take nickname color and extend it throught the whole line
      */

      $username = "<font color=" . $ntints[$ntint]['COLOR'] . ">" . $username;
      $ac_close = "</font>";

    }
    else {

      /*
        else, append font color tag to $username only if it's no special case
      */

      if (($typo == false) && ($yelling == false) && ($whispering == false)) {

        $username = "<font color=" . $ntints[$ntint]['COLOR'] . ">" . $username . "</font>";

      }

    }

  }
  else {

    /*
      default nickname color
    */

    if ($action) {

      /*
        actions take nickname color and extend it throught the whole line:
        this also happens for the default color
      */

      if ($is_admin) {

        $username = "<a class=" . (($id > 1) ? "ad" : "se") . ">" . $username;
        $ac_close = "</a>";

      }
      elseif ($is_mod) {

        $username = "<a class=mo>" . $username;
        $ac_close = "</a>";

      }
      else {

        $ac_close = "";

      }

    }
    else {

      /*
        else, append font color tag to $username only if it's no special case:
        this also happens for the default color
      */

      if (($typo == false) && ($yelling == false) && ($whispering == false)) {

        if ($is_admin) {

          $username = "<a class=" . (($id > 1) ? "ad" : "se") . ">" . $username . "</a>";

        }
        elseif ($is_mod) {

          $username = "<a class=mo>" . $username . "</a>";

        }

      }

    }

  }

  /*
    build message line's HTML code
  */

  if ($action) {

    $toremind = $timestamp . $username . chr (32) . $speech . $ac_close . "<br>\n";

  }
  elseif ($typo) {

    $toremind = $timestamp . "<a class=ty>" . $username . " meant: " . $speech . "</a><br>\n";

  }
  elseif ($yelling) {

    $toremind = $timestamp . "<a class=ye>" . $username . " yells: " . $speech . "</a><br>\n";

  }
  elseif ($whispering) {

    $toremind = $timestamp . "<a class=wh>" . $username . " whispers: " . $speech . "</a><br>\n";

  }
  else {

    $toremind = $timestamp . $username . "&gt; " . $speech . "<br>\n";

  }

}

/*
 *
 *      trim logfile to max lines
 *
 */

$count = 0;
$dotrim = false;
$scan = strlen ($textdata) - 1;

while ($scan > 0) {

  if ($textdata[$scan] == "\n") {

    $count ++;

    if ($count >= $trimlinesafter) {

      $dotrim = true;
      break;

    }

  }

  $scan --;

}

if ($dotrim == true) {

        /*
         *
         *      chop the line from the beginning of existing text ($textdata)
         *
         */

        $textdata = substr ($textdata, $scan);

} // there was a line to trim from the top of the conversation

/*
 *
 * output to public chat
 *
 */

writeTo ("frespych/conversation", $textdata . $toremind);

/*
 *
 * output to serverbot cache
 *
 */

$sb = get ('stats/counters', 'counter>serverbot', 'state');
$sb = ($sb === 'on') ? true : false;
$sb = ($username == $servername) ? false : $sb;

if ($sb == true) {

        $lines = all ("serverbot/conversation", asIs);
        $lines[] = "{$nick}>>{$speech}";

        asm ("serverbot/conversation", array_slice ($lines, -6, 6), asIs);

}

/*
 *
 *      send line to chat logs
 *
 */

/*
 *
 *      remove any HTML tags, and break long uninterrupted lines,
 *      because the logs are not supposed to have horizontal scrollbars
 *
 */

$toremind = preg_replace ('/\<.*?\>/', voidString, $toremind);
$toremind = breakUnspaced ($toremind, $maxcharsperfrag, blank);
$toremind = ltrim ($toremind, "\n");

/*
 *
 *      determine the name of today's chat log file, based on current date:
 *      yes, this will cause a "trail" of the past day to appear in the
 *      subsequent day's logfile, but I don't mind: it's normally a very
 *      small part of the log anyway...
 *
 */

$year = gmdate ('Y', $epc);
$date = gmdate ('Y_m_d', $epc);
$file = 'logs/' . $year . '/' . $date . '.html';

/*
 *
 *      save this line (append if file exists, else create a new log),
 *      remember to mark the spot with an anchor (in hour.minute format)
 *
 */

list ($hour, $minute) = explode (':', strBefore ($toremind, ','));

$hFile = @fopen ($file, 'rb+');
$error = ($hFile === false) ? true : false;

if ($error == false) {

        fseek ($hFile, 0, SEEK_END);
        fwrite ($hFile, "<a name=$hour$minute>" . $toremind . '</a><br>');
        fclose ($hFile);

} // been appending to existing logfile

else {

        $hFile = @fopen ($file, 'wb');
        $error = ($hFile === false) ? true : false;

        if ($error == false) {

                $log_head = str_replace

                        (

                                array

                                        (

                                                "[DATE]",
                                                "[SITENAME]",
                                                "[PLVERSION]"

                                        ),

                                array

                                        (

                                                gmdate ('F d, Y', $epc),
                                                $sitename,
                                                $plversion

                                        ),

                                @file_get_contents ('layout/html/chat_log_header.html')

                        )

                        . "<a name=$hour$minute>" . $toremind . '</a><br>';

                fwrite ($hFile, $log_head);
                fclose ($hFile);

        } // been (successfully) creating a new logfile

} // failed to open existing logfile in append

/*
 *
 *      file created alright?
 *      if not, try creating year folder, and THEN retry creating
 *
 */

$create_year_folder = (@file_exists ($file)) ? false : true;

if ($create_year_folder) {

        @mkdir ('logs/' . gmdate ('Y', $epc));

        $hFile = @fopen ($file, 'wb');
        $error = ($hFile === false) ? true : false;

        if ($error == false) {

                $log_head = str_replace

                        (

                                array

                                        (

                                                "[DATE]",
                                                "[SITENAME]",
                                                "[PLVERSION]"

                                        ),

                                array

                                        (

                                                gmdate ('F d, Y', $epc),
                                                $sitename,
                                                $plversion

                                        ),

                                @file_get_contents ('layout/html/chat_log_header.html')

                        )

                        . "<a name=$hour$minute>" . $toremind . '</a><br>';

                fwrite ($hFile, $log_head);
                fclose ($hFile);

        } // been (successfully) creating a new logfile

} // been creating a new logfile in a new yearly folder

/*
 *
 *      providing log creation or update succeeded,
 *      index what's been transferred to the log ($toremind), for searching
 *
 */

if ($error == false) {

        /*
         *
         *      any valid words to index?
         *      if yes, compute epoch and store occurrences in dictionary
         *
         */

        $words = wordlist_of ('logs', $speech, true, true);

        if (count ($words) > 0) {

                list ($m, $d, $y) = explode ('/', gmdate ('m/d/Y', $epc));

                $e = gmmktime (20, 05, 00, 05, 05, 2003, 0) + $time_offset;
                $e = gmmktime ($hour, $minute, 0, $m, $d, $y, 0) - $e;
                $e = (int) ($e / 60);

                if ($e >= 0) {

                        set_occurrences_of ('logs', $words, $e);

                }

        } // the line of text to be logged generated a non-void wordlist

} // there was no error creating or updating the relevant logfile

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit (addcode ($in_page)));



?>
