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
 * utilities
 *
 */

$pts = 0;
$cal = 0;

$imx = "????????";
$imy = "????????";

$rec = "";
$cmp = "";

function compare_ipx ($x, $y) {

  global $pts, $imx, $imy;

  if ((strlen ($x) + strlen ($y)) != 16) {

    return;

  }

  $ptx = 0;

  if ($x[0] == $y[0]) {
    $ptx += 1;
    if ($x[1] == $y[1]) {
      $ptx += 1;
      if ($x[2] == $y[2]) {
        $ptx += 1;
        if ($x[3] == $y[3]) {
          $ptx += 1;
          if ($x[4] == $y[4]) {
            $ptx += 2;
            if ($x[5] == $y[5]) {
              $ptx += 3;
              if ($x[6] == $y[6]) {
                $ptx += 4;
                if ($x[7] == $y[7]) {
                  $ptx += 5;
                }
              }
            }
          }
        }
      }
    }
  }

  if ($ptx >= $pts) {
    $imx = $x;
    $imy = $y;
    $pts = $ptx;
  }

}

function compare_time_zone () {

  global $rec;
  global $cmp;
  global $cal;

  $z1 = valueOf ($rec, "tz");
  $z2 = valueOf ($cmp, "tz");

  if ($z1 == "" || $z2 == "") {
    $cal += 3;
    return 0;
  }

  if (abs ($z1 - $z2) <= 60)
    return 3;
  else
    return 0;

}

function fully_compare ($a, $b, $delta) {

  global $cal;

  if ($a == "" || $b == "")
    $cal += $delta;
  else {
    if ($a == $b)
      return $delta;
  }

  return 0;

}

function fuzzy_compare ($a, $b, $delta) {

  global $cal;

  if ($a == "" || $b == "")
    $cal += $delta;
  else {
    if ($a == 0 && $b == 0)
      return $delta;
    else {
      $d = $a - $b;
      $r = $delta - ($d * $d * $delta / ($a + $b));
      if ($r > 0) return $r;
    }
  }

  return 0;

}

function dotquad_ip ($h) {

  $a = base_convert (substr ($h, 0, 2), 16, 10);
  $b = base_convert (substr ($h, 2, 2), 16, 10);
  $c = base_convert (substr ($h, 4, 2), 16, 10);
  $d = base_convert (substr ($h, 6, 2), 16, 10);

  return "$a.$b.$c.$d";

}

/*
 *
 * get parameters
 *
 */

$pnick = fget ("nick", -1000, "");
$fname = base62Encode ($pnick);
$versus = fget ("vs", -1000, "");
$gip = strtolower (fget ("gip", 8, ""));

/*
 *
 * process duties
 *
 */

if (empty ($gip)) {

  $pid = intval (get ("members/bynick", "nick>$pnick", "id"));
  $pdb = "members" . intervalOf ($pid, $bs_members);
  $rec = get ($pdb, "id>$pid", "");

  $fingerprint_li = valueOf ($rec, "logip");
  $fingerprint_ri = valueOf ($rec, "ri");
  $fingerprint_ua = valueOf ($rec, "ua");
  $fingerprint_al = valueOf ($rec, "al");
  $fingerprint_bw = valueOf ($rec, "bw");
  $fingerprint_bh = valueOf ($rec, "bh");
  $fingerprint_sw = valueOf ($rec, "sw");
  $fingerprint_sh = valueOf ($rec, "sh");
  $fingerprint_aw = valueOf ($rec, "aw");
  $fingerprint_ah = valueOf ($rec, "ah");
  $fingerprint_cd = valueOf ($rec, "cd");
  $fingerprint_tz = valueOf ($rec, "tz");
  $fingerprint_mt = valueOf ($rec, "mt");
  $fingerprint_pi = valueOf ($rec, "pi");
  $_password_hash = valueOf ($rec, "pass");

  if ($versus == "") {

    $matches = 0;
    $mpoints = array ();

    $blocks = getintervals ("members");

    foreach ($blocks as $blockfn) {

      list ($lo, $hi) = explode ("-", $blockfn);
      $blockfn = "members" . intervalOf (intval ($lo), $bs_members);

      $lst = all ($blockfn, makeProper);
      $n = 0;

      while (!empty ($lst[$n])) {

        $cmp = $lst[$n];

        if (valueOf ($cmp, "id") != $pid) {

          $cal = 0;
          $pts = 0;

          if (isset ($_GET["igip"])) {

            $cal += 18;

          }
          else {

            compare_ipx ($fingerprint_li, valueOf ($cmp, "logip"));
            compare_ipx ($fingerprint_li, valueOf ($cmp, "ri"));
            compare_ipx ($fingerprint_ri, valueOf ($cmp, "logip"));
            compare_ipx ($fingerprint_ri, valueOf ($cmp, "ri"));

          }

          $pts += compare_time_zone ();

          $pts += fully_compare ($fingerprint_ua, valueOf ($cmp, "ua"), 9);
          $pts += fully_compare ($fingerprint_al, valueOf ($cmp, "al"), 7);
          $pts += fully_compare ($fingerprint_sw, valueOf ($cmp, "sw"), 2);
          $pts += fully_compare ($fingerprint_sh, valueOf ($cmp, "sh"), 2);
          $pts += fully_compare ($fingerprint_cd, valueOf ($cmp, "cd"), 6);

          if (isset ($_GET["fuzy"])) {

            $pts += fuzzy_compare ($fingerprint_bw, valueOf ($cmp, "bw"), 2);
            $pts += fuzzy_compare ($fingerprint_bh, valueOf ($cmp, "bh"), 2);
            $pts += fuzzy_compare ($fingerprint_aw, valueOf ($cmp, "aw"), 2);
            $pts += fuzzy_compare ($fingerprint_ah, valueOf ($cmp, "ah"), 4);
            $pts += fuzzy_compare ($fingerprint_mt, valueOf ($cmp, "mt"), 5);
            $pts += fuzzy_compare ($fingerprint_pi, valueOf ($cmp, "pi"), 5);

          }
          else {

            $pts += fully_compare ($fingerprint_bw, valueOf ($cmp, "bw"), 2);
            $pts += fully_compare ($fingerprint_bh, valueOf ($cmp, "bh"), 2);
            $pts += fully_compare ($fingerprint_aw, valueOf ($cmp, "aw"), 2);
            $pts += fully_compare ($fingerprint_ah, valueOf ($cmp, "ah"), 4);
            $pts += fully_compare ($fingerprint_mt, valueOf ($cmp, "mt"), 5);
            $pts += fully_compare ($fingerprint_pi, valueOf ($cmp, "pi"), 5);

          }

          $pts += fully_compare ($_password_hash, valueOf ($cmp, "pass"), 33);

          if (($pts >= 3) && ($cal < 100)) {

            $cal = 100 / (100 - $cal);
            $pts = sprintf ("%3.1f", $pts * $cal);

            $mpoints[$matches] = str_pad ($pts, 5, "0", STR_PAD_LEFT) . ">" . valueOf ($cmp, "nick");
            $matches ++;

          }

        }

        $n++;

      }

    }

    rsort ($mpoints);

    $matches = array ();

    list ($mpoints[0], $matches[0]) = explode (">", $mpoints[0]);
    list ($mpoints[1], $matches[1]) = explode (">", $mpoints[1]);
    list ($mpoints[2], $matches[2]) = explode (">", $mpoints[2]);
    list ($mpoints[3], $matches[3]) = explode (">", $mpoints[3]);
    list ($mpoints[4], $matches[4]) = explode (">", $mpoints[4]);
    list ($mpoints[5], $matches[5]) = explode (">", $mpoints[5]);
    list ($mpoints[6], $matches[6]) = explode (">", $mpoints[6]);
    list ($mpoints[7], $matches[7]) = explode (">", $mpoints[7]);
    list ($mpoints[8], $matches[8]) = explode (">", $mpoints[8]);
    list ($mpoints[9], $matches[9]) = explode (">", $mpoints[9]);

    $info =  makeframe (

               "Top ten suspects matching: &#171;$pnick&#187;...", false, false

             )

          .  "<table width=100%>"
          .   "<tr>"
          .    "<td class=tab>"
          .     "<table width=100%>";

    if ($mpoints[0] >= 3) {

      $n = 0;

      while (($n < 10) && ($mpoints[$n] >= 3)) {

        $p = intval ($mpoints[$n]);
        $red = ($p <= 50) ? "" : ";color:$hov_links_color";

        $link = "inspect.php?nick=$fname&amp;vs="
              . base62Encode ($matches[$n])
              . (isset ($_GET["igip"]) ? "&amp;igip=1" : "")
              . (isset ($_GET["fuzy"]) ? "&amp;fuzy=1" : "");

        $info .= "<tr>"
              .   "<td>"
              .    "<a title=\"MATCH DETAILS\" href=$link>"
              .     "<img src=layout/images/inspect.png width=48 height=48 border=0>"
              .    "</a>"
              .    "<a title=\"review suspect's profile\" href=profile.php?nick=" . base62Encode ($matches[$n]) . " target=pst>"
              .     "<img src=layout/images/poll.png width=24 height=24 border=0>"
              .    "</a>"
              .    "<a title=\"back to subject's profile\" href=profile.php?nick=$fname target=pst>"
              .     "<img src=layout/images/back.png width=24 height=24 border=0>"
              .    "</a>"
              .   "</td>"
              .   "<td width=80%>"
              .    "<span style=font-size:18px$red>"
              .     "&nbsp; $p % &nbsp;"
              .      "<a title=\"MATCH DETAILS\" href=$link>"
              .       $matches[$n]
              .      "</a>"
              .    "</span>"
              .   "</td>"
              .  "</tr>";

        $n++;

      }

    }
    else {

      $info .= "<tr>"
            .   "<td height=6>"
            .   "</td>"
            .  "</tr>"
            .  "<tr>"
            .   "<td height=40 colspan=2 align=center>"
            .    "NO SUSPECTS, OR INSUFFICIENT DATA"
            .   "</td>"
            .  "</tr>";

    }

    $dqli = dotquad_ip ($fingerprint_li);
    $dqri = dotquad_ip ($fingerprint_ri);

    $info .=    "</table>"
          .    "</td>"
          .   "</tr>"
          .   "<tr>"
          .    "<td height=6>"
          .    "</td>"
          .   "</tr>"
          .  "</table>"

          .  makeframe (

               "Informations used as comparison factors (strictly confidential)",

               "Most recent IP: " . $fingerprint_li
          .    " (<a href=nslookup.php?ip=$dqli title=\"LOOKUP THIS IP ADDRESS\">$dqli</a>)"
          .    "<br>Reference login IP: " . $fingerprint_ri
          .    " (<a href=nslookup.php?ip=$dqri title=\"LOOKUP THIS IP ADDRESS\">$dqri</a>)"
          .    "<br>Browser window width: " . (($fingerprint_bw) ? $fingerprint_bw : "unknown")
          .    "<br>Browser window height: " . (($fingerprint_bh) ? $fingerprint_bh : "unknown")
          .    "<br>Full screen width: " . (($fingerprint_sw) ? $fingerprint_sw : "unknown")
          .    "<br>Full screen height: " . (($fingerprint_sh) ? $fingerprint_sh : "unknown")
          .    "<br>Desktop part width: " . (($fingerprint_aw) ? $fingerprint_aw : "unknown")
          .    "<br>Desktop part height: " . (($fingerprint_ah) ? $fingerprint_ah : "unknown")
          .    "<br>Screen color depth: " . (($fingerprint_cd) ? $fingerprint_cd : "unknown")
          .    "<br>Time zone: " . (($fingerprint_tz) ? $fingerprint_tz : "unknown")
          .    "<br>Browser Mime types: " . (($fingerprint_mt) ? $fingerprint_mt : "unknown")
          .    "<br>Browser plugins: " . (($fingerprint_pi) ? $fingerprint_pi : "unknown"), false

          );

  }
  else {

    $vid = intval (get ("members/bynick", "nick>$versus", "id"));
    $vdb = "members" . intervalOf ($vid, $bs_members);
    $cmp = get ($vdb, "id>$vid", "");

    $info =  makeframe (

               "Comparing: &#171;$pnick&#187; versus &#171;$versus&#187;...", false, false

             )

          .  "<table width=100%>"
          .   "<tr>"
          .    "<td width=5% class=tah></td>"
          .    "<td width=65% class=tah></td>"
          .    "<td width=10% class=tah style=text-align:right>$pnick</td>"
          .    "<td width=10% class=tah style=text-align:center>versus</td>"
          .    "<td width=10% class=tah style=text-align:left>$versus</td>"
          .   "</tr>";

    if (isset ($_GET["igip"])) {

      $imx = array ("&#45;","&#45;","&#45;","&#45;","&#45;","&#45;","&#45;","&#45;");
      $imy = array ("&#x2D;","&#x2D;","&#x2D;","&#x2D;","&#x2D;","&#x2D;","&#x2D;","&#x2D;");

    }
    else {

      $pts = 0;

      compare_ipx ($fingerprint_li, valueOf ($cmp, "logip"));
      compare_ipx ($fingerprint_li, valueOf ($cmp, "ri"));
      compare_ipx ($fingerprint_ri, valueOf ($cmp, "logip"));
      compare_ipx ($fingerprint_ri, valueOf ($cmp, "ri"));

    }

    $i =                 ($imx[0] == $imy[0]) ? "read" : "del"; $info .= "<tr><td class=tab align=right>1%&nbsp;</td><td class=tab>IP nybble #0</td><td class=tab align=right>".$imx[0]."</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".$imy[0]."</td></tr>";
    $i = ($i == "read" && $imx[1] == $imy[1]) ? "read" : "del"; $info .=           "<tr><td class=tab align=right>1%&nbsp;</td><td class=tab>IP nybble #1</td><td class=tab align=right>".$imx[1]."</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".$imy[1]."</td></tr>";
    $i = ($i == "read" && $imx[2] == $imy[2]) ? "read" : "del"; $info .=           "<tr><td class=tab align=right>1%&nbsp;</td><td class=tab>IP nybble #2</td><td class=tab align=right>".$imx[2]."</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".$imy[2]."</td></tr>";
    $i = ($i == "read" && $imx[3] == $imy[3]) ? "read" : "del"; $info .=           "<tr><td class=tab align=right>1%&nbsp;</td><td class=tab>IP nybble #3</td><td class=tab align=right>".$imx[3]."</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".$imy[3]."</td></tr>";
    $i = ($i == "read" && $imx[4] == $imy[4]) ? "read" : "del"; $info .=           "<tr><td class=tab align=right>2%&nbsp;</td><td class=tab>IP nybble #4</td><td class=tab align=right>".$imx[4]."</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".$imy[4]."</td></tr>";
    $i = ($i == "read" && $imx[5] == $imy[5]) ? "read" : "del"; $info .=           "<tr><td class=tab align=right>3%&nbsp;</td><td class=tab>IP nybble #5</td><td class=tab align=right>".$imx[5]."</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".$imy[5]."</td></tr>";
    $i = ($i == "read" && $imx[6] == $imy[6]) ? "read" : "del"; $info .=           "<tr><td class=tab align=right>4%&nbsp;</td><td class=tab>IP nybble #6</td><td class=tab align=right>".$imx[6]."</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".$imy[6]."</td></tr>";
    $i = ($i == "read" && $imx[7] == $imy[7]) ? "read" : "del"; $info .=           "<tr><td class=tab align=right>5%&nbsp;</td><td class=tab>IP nybble #7</td><td class=tab align=right>".$imx[7]."</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".$imy[7]."</td></tr>";

    $i = ($fingerprint_ua != "" && $fingerprint_ua == valueOf ($cmp, "ua")) ? "read" : "del"; $info .= "<tr><td class=tab align=right>9%&nbsp;</td><td class=tab>user-agent string hash</td><td class=tab align=right>$fingerprint_ua</td><td width=40 class=tab align=center><img src=layout/images/$i".".png width=24 height=24></td><td class=tab>".valueOf ($cmp, "ua")."</td></tr>";
    $i = ($fingerprint_al != "" && $fingerprint_al == valueOf ($cmp, "al")) ? "read" : "del"; $info .= "<tr><td class=tab align=right>7%&nbsp;</td><td class=tab>accept-language hash</td><td class=tab align=right>$fingerprint_al</td><td width=40 class=tab align=center><img src=layout/images/$i".".png width=24 height=24></td><td class=tab>".valueOf ($cmp, "al")."</td></tr>";

    $i = ($fingerprint_bw && $fingerprint_bw == valueOf ($cmp, "bw")) ? "read" : "del"; $info .= "<tr><td class=tab align=right>2%&nbsp;</td><td class=tab>browser window width</td><td class=tab align=right>$fingerprint_bw</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".valueOf ($cmp, "bw")."</td></tr>";
    $i = ($fingerprint_bh && $fingerprint_bh == valueOf ($cmp, "bh")) ? "read" : "del"; $info .= "<tr><td class=tab align=right>2%&nbsp;</td><td class=tab>browser window height</td><td class=tab align=right>$fingerprint_bh</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".valueOf ($cmp, "bh")."</td></tr>";
    $i = ($fingerprint_sw && $fingerprint_sw == valueOf ($cmp, "sw")) ? "read" : "del"; $info .= "<tr><td class=tab align=right>2%&nbsp;</td><td class=tab>screen width</td><td class=tab align=right>$fingerprint_sw</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".valueOf ($cmp, "sw")."</td></tr>";
    $i = ($fingerprint_sh && $fingerprint_sh == valueOf ($cmp, "sh")) ? "read" : "del"; $info .= "<tr><td class=tab align=right>2%&nbsp;</td><td class=tab>screen height</td><td class=tab align=right>$fingerprint_sh</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".valueOf ($cmp, "sh")."</td></tr>";
    $i = ($fingerprint_aw && $fingerprint_aw == valueOf ($cmp, "aw")) ? "read" : "del"; $info .= "<tr><td class=tab align=right>2%&nbsp;</td><td class=tab>desktop width</td><td class=tab align=right>$fingerprint_aw</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".valueOf ($cmp, "aw")."</td></tr>";
    $i = ($fingerprint_ah && $fingerprint_ah == valueOf ($cmp, "ah")) ? "read" : "del"; $info .= "<tr><td class=tab align=right>4%&nbsp;</td><td class=tab>desktop height</td><td class=tab align=right>$fingerprint_ah</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".valueOf ($cmp, "ah")."</td></tr>";
    $i = ($fingerprint_cd && $fingerprint_cd == valueOf ($cmp, "cd")) ? "read" : "del"; $info .= "<tr><td class=tab align=right>6%&nbsp;</td><td class=tab>screen color depth</td><td class=tab align=right>$fingerprint_cd</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".valueOf ($cmp, "cd")."</td></tr>";
    $i =                                       (compare_time_zone ()) ? "read" : "del"; $info .= "<tr><td class=tab align=right>3%&nbsp;</td><td class=tab>time zone +/- dst</td><td class=tab align=right>$fingerprint_tz</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".valueOf ($cmp, "tz")."</td></tr>";
    $i = ($fingerprint_mt && $fingerprint_mt == valueOf ($cmp, "mt")) ? "read" : "del"; $info .= "<tr><td class=tab align=right>5%&nbsp;</td><td class=tab>browser mime types</td><td class=tab align=right>$fingerprint_mt</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".valueOf ($cmp, "mt")."</td></tr>";
    $i = ($fingerprint_pi && $fingerprint_pi == valueOf ($cmp, "pi")) ? "read" : "del"; $info .= "<tr><td class=tab align=right>5%&nbsp;</td><td class=tab>browser plugins</td><td class=tab align=right>$fingerprint_pi</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>".valueOf ($cmp, "pi")."</td></tr>";
    $i =                  ($_password_hash == valueOf ($cmp, "pass")) ? "read" : "del"; $info .= "<tr><td class=tab align=right>33%&nbsp;</td><td class=tab>password hash</td><td class=tab align=right>(...)</td><td width=40 class=tab align=center><img src=layout/images/$i.png width=24 height=24></td><td class=tab>(...)</td></tr>";

    $info .=

             "<tr>"
          .  "<td colspan=5 class=tah style=text-align:right>"
          .

        (($_password_hash == valueOf ($cmp, "pass"))

          ?   "<form target=pst action=sendpm.php method=get>"
          .    "<input type=hidden name=anon value=1>"
          .    "<input type=hidden name=ccadm value=1>"
          .    "<input type=hidden name=ccmod value=1>"
          .    "<input type=hidden name=to value=" . base62Encode ($nick) . ">"
          .    "<input type=hidden name=cc value=" . base62Encode ("$pnick;$versus") . ">"
          .    "<input type=hidden name=sj value=" . base62Encode ("Shared password warning!") . ">"
          .    "<input type=hidden name=message value=\""
          .    "&lt;k&gt;&lt;div class=alert&gt;SHARED PASSWORD WARNING&lt;/div&gt;&lt;/k&gt;\n\n"
          .    "Your password is the same as that of another member, who has been warned of this "
          .    "problem as well; at this point you may want to change your password to something "
          .    "else, and possibly to something that might be less trivial. Please, refer to the "
          .    "&lt;a href=./faq.php#q{$chpass_faq_number}&gt;Questions &amp; Answers&lt;/a&gt; "
          .    "page to learn how to change your account's password.\n\n"
          .    "&lt;r&gt;Thank you in advance,\n"
          .    "An anonymous staff member...&lt;/r&gt;\n\n"
          .    "&lt;s&gt;This message was composed automatically.&lt;/s&gt;"
          .    "\">"
          .     "<input class=rf type=submit value=\"send password match warning\" style=margin-right:6px>"
          .   "</form>"
          :   ""

          )

          .   "<form action=inspect.php method=get>"
          .    "<input type=hidden name=nick value=$fname>"
          .    (isset ($_GET["igip"]) ? "<input type=hidden name=igip value=1>" : "")
          .    (isset ($_GET["fuzy"]) ? "<input type=hidden name=fuzy value=1>" : "")
          .    "<input class=rf type=submit value=\"return to suspects list\">"
          .   "</form>"
          .  "</tr>"
          .  "</table>";

  }

}
else {

  $matches = 0;
  $mpoints = array ();

  $blocks = getintervals ("members");

  foreach ($blocks as $blockfn) {

    list ($lo, $hi) = explode ("-", $blockfn);
    $blockfn = "members" . intervalOf (intval ($lo), $bs_members);

    $lst = all ($blockfn, makeProper);
    $n = 0;

    while (!empty ($lst[$n])) {

      $cmp = $lst[$n];
      $pts = 0;

      compare_ipx ($gip, valueOf ($cmp, "logip"));
      compare_ipx ($gip, valueOf ($cmp, "ri"));
      compare_ipx ($gip, valueOf ($cmp, "logip"));
      compare_ipx ($gip, valueOf ($cmp, "ri"));

      if ($pts) {

        $pts = sprintf ("%3.1f", $pts * 100 / 18);

        $mpoints[$matches] = str_pad ($pts, 5, "0", STR_PAD_LEFT) . ">" . valueOf ($cmp, "nick");
        $matches ++;

      }

      $n ++;

    }

  }

  rsort ($mpoints);

  $matches = array ();
  for ($n = 0; $n < 15; $n ++) list ($mpoints[$n], $matches[$n]) = explode (">", $mpoints[$n]);

  $info =  makeframe (

             "Top fifteen suspects basing on IP address: " . dotquad_ip ($gip), false, false

           )

         . "<table width=100%>"
         .  "<tr>"
         .   "<td class=tab>"
         .    "<table width=100%>";

  if ($mpoints[0] >= 10) {

    $n = 0;
    while ($n < 15 && $mpoints[$n] >= 10) {
      $p = intval ($mpoints[$n]);
      $red = ($p < 80) ? "" : ";color:$hov_links_color";
      $info .= "<tr>"
            .   "<td height=24>"
            .    "<span style=font-size:18px$red>"
            .     "&nbsp; $p % &nbsp;"
            .      "<a title=\"review suspect's profile\" href=profile.php?nick=" . base62Encode ($matches[$n]) . " target=pst>"
            .       $matches[$n]
            .      "</a>"
            .    "</span>"
            .   "</td>"
            .  "</tr>";
      $n++;
    }

  }
  else {

    $info .= "<tr>"
          .   "<td height=6>"
          .   "</td>"
          .  "</tr>"
          .  "<tr>"
          .   "<td height=40 colspan=2 align=center>"
          .    "NO SUSPECTS, OR INSUFFICIENT DATA"
          .   "</td>"
          .  "</tr>";

  }

  $info .=    "</table>"
        .    "</td>"
        .   "</tr>"
        .   "<tr>"
        .    "<td height=6>"
        .    "</td>"
        .   "</tr>"
        .  "</table>";
}

/*
 *
 * template initialization
 *
 */

$generic_info_page = str_replace

  (

    array

      (

        "[INFO]",
        "[PERMALINK]"

      ),

    array

      (

        $info,
        $permalink

      ),

    $generic_info_page

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page upon showing or
 * hiding the chat frame, and ONLY for that: this URL is confidential and it's not tracked.
 *
 */

include ("setrfcookie.php");

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($generic_info_page));



?>
