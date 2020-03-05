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
 * utilities
 *
 */

function desaturate ($color) {

  /*
    the color's 24-bit value is assumed to be given as a hex. code (base 16):
    first of all convert to base 10, ie. a regular integer, for more comfortable operation...
  */

  $color = base_convert ($color, 16, 10);

  $r = $color >> 16;            // red is in most significant 8 bits, so shift 16 bits right
  $g = $color & 0x00ff00;       // green is in middle 8 bits, so insulate those...
  $g >>= 8;                     // ...and then shift 8 bits right
  $b = $color & 0x0000ff;       // blue is least significant 8 bits: simply insulate them
  $r *= 0.00098039;             // multiply red by 0.25/255: range goes 0 to 1, saturation 25%
  $r += 0.74;                   // add 74% intensity (25+74=99%, not 100% to avoid overflows)
  $g *= 0.00098039;             // multiply green by 0.25/255: range goes 0 to 1, saturation 25%
  $g += 0.74;                   // add 74% intensity (25+74=99%, not 100% to avoid overflows)
  $b *= 0.00098039;             // multiply blue by 0.25/255: range goes 0 to 1, saturation 25%
  $b += 0.74;                   // add 74% intensity (25+74=99%, not 100% to avoid overflows)
  $r = intval ($r * 255);       // convert red to an integer ranging from 0 to 255, as normal
  $g = intval ($g * 255);       // convert green to an integer ranging from 0 to 255, as normal
  $b = intval ($b * 255);       // convert blue to an integer ranging from 0 to 255, as normal

  /*
    assemble final color value,
    return that value converted back to base 16 (as hex. code again)
  */

  $color = ($r * 0x10000) + ($g * 0x100) + $b;

  return base_convert ($color, 10, 16);

}

function dotquad_ip ($h) {

  /*
    return original dotquadded IPv4 from hexadecimal representation of the same address
    Postline uses hex. for IPs to more comfortably check groups of nybbles: while base-10
    addresses given in "dotquadded" notation may be easier to read and ASCII is universal,
    when you get to examine partial matches the dotquadded notation is a big problem.
  */

  $a = base_convert (substr ($h, 0, 2), 16, 10);
  $b = base_convert (substr ($h, 2, 2), 16, 10);
  $c = base_convert (substr ($h, 4, 2), 16, 10);
  $d = base_convert (substr ($h, 6, 2), 16, 10);

  return "$a.$b.$c.$d";

}

function choose_from ($p) {

  /*
    comes from the serverbot commons,
    selects a random quote to describe what the serverbot may be doing...
  */

  $n = mt_rand (0, count ($p) - 1);

  return (explode ('--', $p[$n]));

}

/*
 *
 * this is an HTML fragment visualizing the button to reload this same script
 *
 */

$refresh = "<form action=onlines.php>"
         . "<tr>"
         .  "<td>"
         .   "<input class=ky type=submit style=width:{$pw}px value=\"reload this list\">"
         .  "</td>"
         . "</tr>"
         . "</form>";

/*
 *
 * generate output (will use search results as the template)
 *
 * - start by getting an array with all active sessions (all registered members actually online)
 *
 * - initialize $logged_ips, to be filled with IPs of online members: it will be used to filter
 *   the array holding IP addresses of guests; when a guest logs in, its IP will not immediately
 *   disappear from the "stats/guests" archive (it's left there to naturally expire after
 *   $g_sessionexpiry seconds), but this way a user that just logged in, and who is no longer
 *   a guest, might no longer be counted among guest sessions...
 *
 */

$logged_ips = array ($ip);

$sessions = all ("stats/sessions", makeProper);
$c = count ($sessions);

if ($c) {

  /*
    initialize list accumulator
  */

  $results = "";

  /*
    start from first session record,
    loop for each session record
  */

  $n = 0;

  while ($n < $c) {

    /*
      is this session active? (did not expire yet)
      it checks because there may be expired sessions not yet removed from "stats/sessions"
    */

    $_beg = intval (valueOf ($sessions[$n], "beg"));

    if (($_beg + $sessionexpiry) >= $epc) {

      /*
        retrieve nickname and authorization level
      */

      $_nick = valueOf ($sessions[$n], "nick");
      $_auth = valueOf ($sessions[$n], "auth");

      /*
        determine nickname color
      */

      $_tint = intval (valueOf ($sessions[$n], "ntint"));
      $_tint = ($_tint < 0) ? 0 : $_tint;
      $_tint = ($_tint < count ($ntints)) ? $_tint : count ($ntints) - 1;

      if ($_tint > 0) {

        /*
          nickname color is no default color (user choice)
        */

        $color = $ntints[$_tint]["COLOR"];

      }
      else {

        /*
          nickname color is the default color depending on this member's rank
        */

        if (in_array ($_auth, $admin_ranks)) {

          $color = (valueOf ($sessions[$n], "id") == 1) ? $ifc_server : $ifc_admin;

        }
        elseif (in_array ($_auth, $mod_ranks)) {

          $color = $ifc_mod;

        }
        else {

          $color = $ifc_member;

        }

      }

      /*
        assemble nickname tag ($tx1), holding profile link,
        and signals indicating chat frame state and the "away" sign
      */

      $profile_link = "members.php?present=" . base62Encode ($_nick);
      $tx1 = "<a"
           . " style=color:$color"
           . " title=\"view $_nick's presentation\""
           . " target=pan href=$profile_link>" . strtoupper ($_nick) . "</a>";

      if (valueOf ($sessions[$n], "chatflag") == "on") $tx1 .= chr (32) . "<span class=sym>&#x25CF;</span>";
      if (valueOf ($sessions[$n], "afk") == "yes") $tx1 .= chr (32) . "<span class=sym>&#x2020;</span>";

      /*
        get last tracked URI:
        by default, claim you can't track this member (may have chosen not to be tracked)
      */

      $v = getlsp (intval (valueOf ($sessions[$n], "id")));
      $what = "couldn't track $_nick";

      if (empty ($v)) {

        /*
          no tracked URI, no icon
        */

        $tx2 = "<img width=24 height=24 class=h>";

      }
      else {

        /*
          examining the name of last tracked PHP script (without the extension),
          and eventually producing a corresponding description
        */

        $unknown_page = false;

        switch (strFromTo ($v, "{$path_to_postline}/", ".")) {

          case "authlist":      $what = "viewing staff page";           break;
          case "cdimage":       $what = "viewing a c-disk image";       break;
          case "faq":           $what = "reading the f.a.q";            break;
          case "forums":        $what = "viewing forums index";         break;
          case "intro":         $what = "viewing welcome page";         break;
          case "logs":          $what = "reviewing chat logs";          break;
          case "members":       $what = "viewing members list";         break;
          case "posts":         $what = "reading a thread";             break;
          case "preview":       $what = "previewing a message";         break;
          case "result":        $what = "reading search results";       break;
          case "tellme":        $what = "writing a feedback form";      break;
          case "threads":       $what = "listing a forum's threads";    break;
          case "viewpm":        $what = "reading a private message";    break;

          default:

            $unknown_page = true;

        }

        /*
          add the icon only if the tracked URI corresponds to some known part of Postline
        */

        $tx2 =

           (($unknown_page)

             ? "<img width=24 height=24 class=h>"
             : "<a title=\"go to the page that $_nick is viewing\" target=pan href=\"$v\">"
             .  "<img src=layout/images/arrow.png width=24 height=24 border=0>"
             . "</a>"

             );

      }

      /*
        add special description to serverbot
      */

      if ($_nick == $servername) {

        list ($what, $tx2) = choose_from (array (

                "running the whole site",
                "haunting this place",
                "lurking at you all",
                "answering HTTP requests",
                "doing housekeeping",
                "hunting random visitors",
                "flirting with Googlebot",
                "ignoring everyone",
                "initiating Skynet",
                "eating virtual tuna",
                "assimilating the Borg",
                "minding her own business",
                "DoS'ing wikipedia",
                "making a LOLhuman",
                "baking cake for kittens",
                "watching you move",
                "eating random spiders",
                "looking for litter box",
                "chasing c-disk files",
                "hiding behind site logo",
                "port-scanning your IP",
                "wondering if you're tasty",
                "inventing captcha numbers",
                "avoiding Yahoo! Slurp",
                "frumpling the Internet",
                "abstaining from replying",
                "covering the crap",
                "detangling fur",
                "shredding toilet paper",
                "reading Scientific American",
                "driving her stardrifter",
                "doing online shopping",
                "criticizing your grammar--<img src=layout/images/del.png style=width:24px;height:24px>",
                "altering chat logs",
                "spamming someone's inbox",
                "rejecting Yandex's advances--<img src=layout/images/back.png style=width:24px;height:24px>",
                "sleeping on Alex's bed",
                "looking cute",
                "waving her wild tail",
                "walking by her wild lone",
                "yowling at the Moon",
                "kneading on the status line",
                "doing nothing in particular",
                "posting on her blog",
                "adding malicious javascripts",
                "spying your activities",
                "spamming staff mailboxes",
                "selling Cryoburner's pants",
                "censoring use of Pornelos",
                "being sick of your reloads",
                "asserting her innocence",
                "just not looking",
                "pomelizing Friend Computer",
                "laughing at 404 errors",
                "purring in silence",
                "hypnotizing spambots",
                "interfacing with Destiny",
                "halting and catching fire",
                "defragmenting your thoughts",
                "distributing pirated stuff",
                "repositioning the H.S.T.",
                "geolocating your IP",
                "no idea what's she doing--<img src=layout/images/poll.png style=width:24px;height:24px>",
                "telling Cuill dirty tales",
                "slurping Yahoo! Slurp",
                "slapping the SQL server",
                "taking control of the farm",
                "assembling fationic cannons",
                "verifying her suspicions",
                "playing with sock<small>et</small>s",
                "recompiling the kernel",
                "playing with marbles",
                "dating Maru",
                "hiding secret plans",
                "disposing of latest corpses",
                "confusing Googlebot",
                "taking a nap",
                "disregarding accessibility",
                "escaping Firefox",
                "running up a high hill",
                "wielding a double-bevel axe",
                "rebooting the Internet",
                "checking her investments",
                "playing strip poker",
                "expelling the warp core",
                "drinking from <small>bit</small>torrents",
                "altering poll results",
                "inviting you to <a target=pan href=/secret.html>click here</a>",
                "cleaning her ports",
                "penetrating your firewall",
                "now loading...",
                "taking her medicine",
                "walking on keybdhgdgff",
                "+NAN",
                "searching door into summer",
                "looking straight at you",
                "gotcha!--<img src=layout/images/arrow.png style=width:24px;height:24px>",
                "moving furniture",
                "having a cuter icon :p--<img src=layout/images/star.png style=width:24px;height:24px>",
                "forecasting snowy weather--<img src=layout/images/last.png style=width:24px;height:24px>"

        ));

      }

      /*
        append entry to accumulator
      */

      $results .= $inset_bridge
               .  $opcart
               .  "<tr>"
               .   "<td>"
               .    "<table width=100%>"
               .     "<tr>"
               .      "<td class=l bgcolor=" . desaturate ($color) . " width=24>"
               .       $tx2
               .      "</td>"
               .      "<td class=r>"
               .       "$tx1<br>"
               .       "<span class=hn>"
               .        $what
               .       "</span>"
               .      "</td>"
               .     "</tr>"
               .    "</table>"
               .   "</td>"
               .  "</tr>"
               .  $clcart;

      /*
        add logged member IP address to filter guest sessions
      */

      $logged_ips[] = valueOf ($sessions[$n], "ip");

    }

    $n ++;

  }

  /*
    appending "meaning of signs" paragraph, and a few instructions
  */

  $results .= $inset_shadow
           .  "<table width=$pw>"
           .   "<tr>"
           .    "<td height=20 class=inv align=center>"
           .     "meaning of signs:"
           .    "</td>"
           .   "</tr>"
           .  "</table>"
           .  $inset_bridge
           .  $opcart
           .   "<tr>"
           .    "<td class=ls>"
           .     "<span class=sym>&#x25CF;</span> &nbsp; keeps chat frame on<br>"
           .     "<span class=sym>&#x2020;</span> &nbsp; is away from keyboard"
           .    "</td>"
           .   "</tr>"
           .  $clcart
           .  $inset_shadow
           .

           makeframe (

              "Notes:",
              "Clicking nicknames leads to profiles, clicking "
           .  "icons leads to the member's last seen page. "
           .  "If you don't want to be tracked, check &#171;don't spy me&#187; "
           .  "in <a href=kprefs.php>your preferences</a>.", true

           );

  /*
    page output
  */

  $form =

        makeframe (

          "Who is online?", false, false

        )

        . "<table width=$pw>"
        .  $refresh
        . "</table>"
        . $results;

}
else {

  /*
    page output if no registered members are online
  */

  $form =

        makeframe (

          "No members online, currently.",
          "No registered members are logged in, at the moment...", true

        )

        . "<table width=$pw>"
        .  $refresh
        . "</table>"
        . $inset_shadow;

}

/*
 *
 * if guests tracking enabled, show guest sessions
 *
 */

if ($track_guests) {

  /*
    retrieve all guest sessions from "stats/guests",
    initialize void array to hold filtered sessions ($real_guest) where nether the viewer's IP
    address, nor any logged member IP address appears (may be a remnant of former guest status)
  */

  $c = 0;     // will count "real" guest sessions

  $guest = all ("stats/guests", makeProper);
  $real_guest = array ();

  foreach ($guest as $g) {

    /*
      add guest session only if the IP doesn't match an online member:
      if it matches, it's just a remnant of the member's former "guest status"
    */

    if (in_array (valueOf ($g, "ip"), $logged_ips) == false) {

      $real_guest[] = $g;
      $c ++;

    }

  }

  /*
    loading and preparing Known Guests List for filtering:
    processing is as follows (underscores mark eventual blank spaces)
    -----------------------------------------------------------------
    $r = "_7f00__(_Localhost_)__"       // original record (each $r)
    $subnet = "_7f00__"                 // explode
    $name = "_Localhost_)__"            // explode
    $subnet = "7f00"                    // trim ($subnet)
    $name = "Localhost_)"               // trim ($name)
    $name = "Localhost_"                // substr ($name, 0, -1)
    $name = "Localhost"                 // rtrim (substr ($name, 0, -1))
    $kgl_filters["7f00"] = "Localhost"; // final record (each $kgl_filters)
  */

  $kgl_records = wExplode ("\n", readFrom ("stats/kgl"));
  $kgl_filters = array ();

  foreach ($kgl_records as $r) {

    list ($subnet, $name) = explode ("(", $r);

    $subnet = trim ($subnet);
    $name = trim ($name);

    $kgl_filters[$subnet] = rtrim (substr ($name, 0, -1));

  }

  /*
    basing on the abovely processed KGL records, divide the remaining guest sessions
    into "known" and "filtered" (where filtered will hold all remaining unknown guests)
  */

  $k = 0;       // will count "known" guest sessions
  $f = 0;       // will count "filtered" guest sessions

  $filtered_guest = array ();
  $known_guest = array ();

  if ($c > 0) {

    foreach ($real_guest as $r) {

      /*
        get IP address from guest session record,
        assume it's not been found in KGL yet ($known = false)
      */

      $g_ip = valueOf ($r, "ip");
      $known = false;

      foreach ($kgl_filters as $subnet => $name) {

        /*
          compare $l digits of the IP address against $subnet,
          where $l is the length of the $subnet string, and break on first match
        */

        $l = strlen ($subnet);

        if (substr ($g_ip, 0, $l) == $subnet) {

          /*
            known guests are indexed by name, but the array values are the counters
            of the instances of the same subnet which occured across the list:
            later, the counters will show in parenthesis...
          */

          $known_guest[$name] ++;

          $k ++;                // increase known guests counter
          $known = true;        // set flag so that the record wont be appended to $filtered_guest

          break;

        }

      }

      if ($known == false) {

        $f ++;
        $filtered_guest[] = $r;

      }

    }

  }

  /*
    if the are any known guests to list, list them
  */

  if ($k > 0) {

    $form .= "<table width=$pw>"
          .   "<tr>"
          .    "<td height=20 class=inv align=center>"
          .     "$k known guests"
          .    "</td>"
          .   "</tr>"
          .  "</table>"
          .  $inset_bridge
          .  $opcart
          .   "<td class=ls>"
          .    "<table>";

    foreach ($known_guest as $name => $instances) {

      $instances = ($instances > 1) ? "&nbsp;($instances)" : "";

      $form .= "<tr>"
            .   "<td class=ls>"
            .    "<span class=kg>$name</span>{$instances}&nbsp;"
            .   "</td>"
            .  "</tr>";

    }

    $form .=   "</table>"
          .   "</td>"
          .  $clcart
          .  $inset_shadow;

  }

  /*
    if there are any unknown guests to list, list them
  */

  if ($f > 0) {

    $form .= "<table width=$pw>"
          .

        (($login)

          ?   "<td height=20 class=inv align=center>"
          .    "$f unknown guests"
          .   "</td>"

          :   "<td height=40 class=inv align=center>"
          .    "$f unknown guests<br>"
          .    "<small>(other than you)</small>"
          .   "</td>"

          )

          .  "</table>"
          .  $inset_bridge
          .  $opcart
          .   "<td class=ls>"
          .    "<table class=w align=right>";

    $n = 1;

    foreach ($filtered_guest as $r) {

      $g_ip = valueOf ($r, "ip");
      $name = "(#$n)";

      if (($is_admin) || ($is_mod)) {

        $q_ip = dotquad_ip ($g_ip);

        $form .= "<tr>"
              .   "<td class=ls align=right><small>"
              .    "$q_ip&nbsp;"
              .   "</small></td>"
              .   "<td class=ls><small>"
              .    "&middot;&middot;"
              .    " <a class=ll target=pan href=inspect.php?gip=$g_ip>find</a>"
              .    " <a class=ll target=pan href=nslookup.php?ip=$q_ip>lookup</a>"
              .   "</small></td>"
              .  "</tr>";

      }
      else {

        $q_ip = dotquad_ip ($g_ip);

        list ($o1, $o2, $o3, $o4) = explode (".", $q_ip);
        $q_ip = implode (".", array ($o1, $o2, "*", "*"));

        $form .= "<tr>"
              .   "<td class=ls align=right>"
              .    $name
              .   "</td>"
              .   "<td class=ls align=right>"
              .    "$q_ip&nbsp;"
              .   "</td>"
              .  "</tr>";

      }

      $n ++;

    }

    $form .=   "</table>"
          .   "</td>"
          .  $clcart
          .  $inset_shadow;

  }

}

/*
 *
 * template initialization
 *
 */

$searchresults = str_replace

  (

    array (

      "[FORM]",
      "[PERMALINK]"

    ),

    array (

      $form,
      $permalink

    ),

    $searchresults

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

echo (pquit ($searchresults));



?>
