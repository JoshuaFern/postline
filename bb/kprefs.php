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
 * an utility to remove backslashes from strings: in cookies, they tend to duplicate
 * I don't worry much, and cut it short by replacing backslashes with forward slashes,
 * since for the uses made here there's no need to distinguish them...
 *
 */

function kget ($key, $maxlen, $cr_replacement) {

  $f = fget ($key, $maxlen, $cr_replacement);
  $f = str_replace ("\\", "/", $f);

  return $f;

}

/*
 *
 * check if there's a request to change something in the preferences ($submit)
 *
 */

$submit = $_POST["submit"];
$report = "Settings";

if ($submit == "default") {

  /*
    revert prefs to defaults:
    $default_style is defined in "settings.php"
  */

  $kflags = "nnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnn";

  $kflags[3] = (strstr ($_SERVER["HTTP_USER_AGENT"], "MSIE") !== false) ? "y" : "n";

  $style = $default_style;
  $backgr = "";

  $kfont0 = $default_kfont0;
  $kfont1 = $default_kfont1;
  $kfont2 = $default_kfont2;
  $kfont3 = $default_kfont3;
  $kfont4 = $default_kfont4;
  $kfont5 = $default_kfont5;

  /*
    replace prefs. cookie with a dummy one holding the sole string "default":
    just like with the login cookie, I think it's safer than relying on expiration
  */

  setcookie ($prefs_cookie_name, "default", 2147483647, "/");

}
elseif ($submit == "save") {

  /*
    get preferences from $POST arguments, beginning with the visual style:
    it's a number that must be in the range 0 to no_of_styles - 1 ("template.php"),
    used as an index to access records from the $styles array defined in "template.php"
  */

  $style = intval (fget ("style", 6, ""));

  $style = ($style < 0) ? 0 : $style;
  $style = ($style < count ($styles)) ? $style : (count ($styles) - 1);

  /*
    set $kflags array depending on checkboxes' status:
    some flags here are only effective, and collected, while logged in...
  */

  $kflags[1] = (isset ($_POST["mlfr"])) ? "y" : "n";    // make left frame resizable
  $kflags[2] = (isset ($_POST["aclf"])) ? "y" : "n";    // always collapse left frame
  $kflags[3] = (isset ($_POST["ofie"])) ? "y" : "n";    // optimize for IE
  $kflags[6] = (isset ($_POST["dusc"])) ? "y" : "n";    // do use small caps
  $kflags[8] = (isset ($_POST["bigs"])) ? "y" : "n";    // i've got a quite bit screen

  $kflags[9] = (isset ($_POST["dfwl"])) ? "y" : "n";    // disable frame width limit
  $kflags[10] = (isset ($_POST["hcb"])) ? "y" : "n";    // halve chat bar
  $kflags[11] = (isset ($_POST["hii"])) ? "y" : "n";    // hide image informations (in cdimage.php)
  $kflags[12] = (isset ($_POST["csn"])) ? "y" : "n";    // compact side navigation bars (icon only)
  $kflags[13] = (isset ($_POST["cnb"])) ? "y" : "n";    // compact navigation bar (text links only)
  $kflags[14] = (isset ($_POST["cdl"])) ? "y" : "n";    // compact downline (no icons and no clock)
  $kflags[15] = ($_POST["sbc"] === '1') ? "y" : "n";    // scrollbar coloring matches controls
  $kflags[16] = ($_POST["sbc"] === '2') ? "y" : "n";    // no scrollbar coloring
  $kflags[17] = (isset ($_POST["sbi"])) ? "y" : "n";    // use scrolling backdrop image

  if ($login) {

    $kflags[0] = (isset ($_POST["lffn"])) ? "y" : "n";  // left frame follows navigation
    $kflags[4] = (isset ($_POST["hhip"])) ? "y" : "n";  // hide help in preview
    $kflags[5] = (isset ($_POST["nspy"])) ? "y" : "n";  // dont' spy me
    $kflags[7] = (isset ($_POST["smmn"])) ? "y" : "n";  // show me my name online

    /*
      BTW: if there's a request to keep Postline from tracking the page that the user's viewing,
      immediately delete last tracked address...
    */

    if ($kflags[5] == "y") {

      setlsp ($id, "");

    }

  }

  /*
    retrieve and update custom background file path and font specs
  */

  $backgr = kget ("backgr", 160, "");

  $f0 = $kfont0 = (isset ($_POST["f0"])) ? kget ("f0", 40, "") : $default_kfont0;
  $f1 = $kfont1 = (isset ($_POST["f1"])) ? kget ("f1", 40, "") : $default_kfont1;
  $f2 = $kfont2 = (isset ($_POST["f2"])) ? kget ("f2", 40, "") : $default_kfont2;
  $f3 = $kfont3 = (isset ($_POST["f3"])) ? kget ("f3", 40, "") : $default_kfont3;
  $f4 = $kfont4 = (isset ($_POST["f4"])) ? kget ("f4", 40, "") : $default_kfont4;
  $f5 = $kfont5 = (isset ($_POST["f5"])) ? kget ("f5", 40, "") : $default_kfont5;

  /*
    save modified preferences cookie
  */

  setcookie (

    $prefs_cookie_name,

    sprintf

    (

          $prefs_cookie_text . "%06d" . "A" . $kflags . $prefscfgversion
        . $backgr . "__$f0" . "__$f1" . "__$f2" . "__$f3" . "__$f4" . "__$f5",

        $style

    ),

    2147483647, "/"

  );

  /*
    report successful update of prefs cookie
  */

  $report = "Ok, all settings updated!";

}

/*
 *
 * define form parameters:
 * set "checked" tags for checkboxes where needed
 *
 */

$lffn_checked = ($kflags[0] == "y") ? chr (32) . "checked" : "";
$mlfr_checked = ($kflags[1] == "y") ? chr (32) . "checked" : "";
$aclf_checked = ($kflags[2] == "y") ? chr (32) . "checked" : "";
$ofie_checked = ($kflags[3] == "y") ? chr (32) . "checked" : "";
$hhip_checked = ($kflags[4] == "y") ? chr (32) . "checked" : "";
$nspy_checked = ($kflags[5] == "y") ? chr (32) . "checked" : "";
$dusc_checked = ($kflags[6] == "y") ? chr (32) . "checked" : "";
$smmn_checked = ($kflags[7] == "y") ? chr (32) . "checked" : "";
$bigs_checked = ($kflags[8] == "y") ? chr (32) . "checked" : "";
$dfwl_checked = ($kflags[9] == "y") ? chr (32) . "checked" : "";
$hcb_checked = ($kflags[10] == "y") ? chr (32) . "checked" : "";
$hii_checked = ($kflags[11] == "y") ? chr (32) . "checked" : "";
$csn_checked = ($kflags[12] == "y") ? chr (32) . "checked" : "";
$cnb_checked = ($kflags[13] == "y") ? chr (32) . "checked" : "";
$cdl_checked = ($kflags[14] == "y") ? chr (32) . "checked" : "";
$sbc_value_1 = ($kflags[15] == "y") ? chr (32) . "checked" : "";
$sbc_value_2 = ($kflags[16] == "y") ? chr (32) . "checked" : "";
$sbc_value_0 = ($kflags[15] . $kflags[16] == "nn") ? chr (32) . "checked" : "";
$sbi_checked = ($kflags[17] == "y") ? chr (32) . "checked" : "";

/*
 *
 * define form parameters:
 *
 * define frame and URL to load as the destination of the "apply" button, which need to be
 * set to reload 'index.php' in the parent frameset (the central frameset) unless a change
 * was made to the "disable frame width limit" option, in which case the "apply" button
 * will reload the whole 'layout/html/frameset.html' in the top frame (the outer frameset)
 *
 */

$layout_before =

        (($dfwl == "yes") ? 1 : 0)
      + (($hcb  == "yes") ? 2 : 0)
      + (($csn  == "yes") ? 4 : 0)
      + (($cnb  == "yes") ? 8 : 0)
      + (($cdl  == "yes") ? 16 : 0)
      + (($sbi  == "yes") ? 32 : 0);

$layout_actual =

        (($kflags[9]  == "y") ? 1 : 0)
      + (($kflags[10] == "y") ? 2 : 0)
      + (($kflags[12] == "y") ? 4 : 0)
      + (($kflags[13] == "y") ? 8 : 0)
      + (($kflags[14] == "y") ? 16 : 0)
      + (($kflags[17] == "y") ? 32 : 0);

/*
 *
 * note having defaults set to refresh the entire frameset allows some graceful degradation in case
 * javascripts were turned off and properties of the 'apply' button couldn't be updated
 *
 */

$apply_toframe = "_top";
$apply_address = "layout/html/frameset.html";

$apply_toframe = ($layout_before == $layout_actual) ? "_parent" : $apply_toframe;
$apply_address = ($layout_before == $layout_actual) ? "index.php" : $apply_address;

/*
 *
 * define form parameters:
 * build style selection combo's HTML code, by analyzing $styles defined in "template.php"
 *
 */

$styleselect = "<select name=style style=width:{$iw}px>";

for ($n = 0; $n < count ($styles); ++ $n) {

  if ($styles[$n]["[TRUE-BACKGROUND]"] == "none") {

    /*
      "none" found in place of a background file name marks a plain color style
    */

    $info = chr (32) . "(plain)";

  }
  else {

    /*
      otherwise, it looks for an occurrence of "no-repeat" within the background tag,
      and reports it's an "image" when found, else it reports "tile"; in any cases,
      it also reports the size of the image file in Kb (modem users may care)
    */

    $type = (strstr ($styles[$n]["[BODY-BACKGROUND]"], "no-repeat")) ? "" : ", tile";
    $leng = wFilesize ("layout/backgrounds/" . $styles[$n]["[TRUE-BACKGROUND]"]);
    $size = round ($leng / 1024);
    $size = ($size > 0) ? "$size Kb" : "$leng b";
    $info = chr (32) . "($size$type)";

  }

  /*
    append option to combo box,
    eventually along with "selected" tag to let the browser know if it's the current style
  */

  $select = ($n == $style) ? chr (32) . "selected=selected" : "";
  $styleselect .= "<option value=$n$select>" . $styles[$n]["STYLE_DESCRIPTION"] . "$info</option>";

}

$styleselect .= "</select>";

/*
 *
 * define form parameters:
 * setup HTML for extra checkboxes if visitor is logged in
 *
 */

if ($login) {

  $extra_1 = "<span title=\"check this to automatically load post-a-message forms for threads and forums\">"
           . "<input type=checkbox name=lffn$lffn_checked>&nbsp;auto-reload left frame</span><br>";

  $extra_2 = "<span title=\"check this to hide encumbering help notes by default shown in message previews\">"
           . "<input type=checkbox name=hhip$hhip_checked>&nbsp;hide help in preview</span><br>";

  $extra_3 = "<span title=\"check this to keep others from knowing the page you're seeing\">"
           . "<input type=checkbox name=nspy$nspy_checked>&nbsp;don't spy me</span><br>";

  $extra_4 = "<span title=\"by default, Postline hides your name TO YOU (you're supposed to know you're online)\">"
           . "<input type=checkbox name=smmn$smmn_checked>&nbsp;show me my name online</span><br>";

}
else {

  $extra_1 = $extra_2 = $extra_3 = $extra_4 = "";

}

/*
 *
 * generate page output as $form
 *
 */

$pwl = intval ($pw / 2) - 1;    // left buttons width
$pwr = $pw - $pwl - 3;          // right buttons width

$form =

      makeframe (

        $report, false, false

      )

      . "<table width=$pw>"
      . "<form name=prefs action=kprefs.php enctype=multipart/form-data method=post>"   /* (1) */
      .  "<td height=20 class=inv align=center>"
      .   "SYSTEM BEHAVIOR"
      .  "</td>"
      . "</table>"

      . $inset_bridge
      . $opcart

      . "<tr>"
      .  "<td class=in style=padding:2px>"
      .   "<span title=\"enables backdrop image animation, but beware this may result in the site using too much CPU time on slow combinations of machines and browsers\">"
      .   "<input type=checkbox name=sbi$sbi_checked>&nbsp;scrolling backdrop image</span><br>"
      .   $extra_1
      .   $extra_2
      .   $extra_3
      .   $extra_4
      .   "<span title=\"adapts several lists to large screens, ideally &gt;= 1280x1024\">"
      .   "<input type=checkbox name=bigs$bigs_checked>&nbsp;I've got quite a big screen</span>"
      .  "</td>"
      . "</tr>"

      . $clcart
      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=20 class=inv align=center>"
      .   "CONTENT LAYOUT"
      .  "</td>"
      . "</table>"

      . $inset_bridge
      . $opcart

      . "<tr>"
      .  "<td class=in style=padding:2px>"
      .   "<span title=\"check this to make left frame resizable\">"
      .   "<input type=checkbox name=mlfr$mlfr_checked>&nbsp;left frame resizable</span><br>"
      .   "<span title=\"hide left frame while browsing (making it resizable): useful at low resolutions\">"
      .   "<input type=checkbox name=aclf$aclf_checked>&nbsp;collapse left frame</span><br>"
      .   "<span title=\"adapt site graphics to better match the behavior of Internet Explorer 6&trade;\">"
      .   "<input type=checkbox name=ofie$ofie_checked>&nbsp;optimize for MSIE</span><br>"
      .   "<span title=\"makes text cuter and more readable if you use &quot;clear type&quot;\">"
      .   "<input type=checkbox name=dusc$dusc_checked>&nbsp;use clear type small-caps</span>"
      .  "</td>"
      . "</tr>"

      . $clcart
      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=20 class=inv align=center>"
      .   "SPACE RECOVERY"
      .  "</td>"
      . "</table>"

      . $inset_bridge
      . $opcart

      . "<tr>"
      .  "<td class=in style=padding:2px>"
      .   "<span title=\"let pages be as wide as the screen, instead of limiting them to 800 pixels\">"
      .   "<input type=checkbox name=dfwl$dfwl_checked>&nbsp;disable frame width limit</span><br>"
      .   "<span title=\"halve the height of the chat frame, when open\">"
      .   "<input type=checkbox name=hcb$hcb_checked>&nbsp;halve chat box height</span><br>"
      .   "<span title=\"hide informations' paragraph when seeing c-disk images\">"
      .   "<input type=checkbox name=hii$hii_checked>&nbsp;hide image informations</span><br>"
      .   "<span title=\"remove icons' captions from the left-side navigation bar, making it more compact\">"
      .   "<input type=checkbox name=csn$csn_checked>&nbsp;small side navigation bar</span><br>"
      .   "<span title=\"remove icons and spacers from top-side navigation bar, halving its height\">"
      .   "<input type=checkbox name=cnb$cnb_checked>&nbsp;small top navigation bar</span><br>"
      .   "<span title=\"remove clock, icons and spacers from status line, halving its height\">"
      .   "<input type=checkbox name=cdl$cdl_checked>&nbsp;small status line</span>"
      .  "</td>"
      . "</tr>"

      . $clcart
      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=20 class=inv align=center>"
      .   "SCROLLBAR TRACKS COLOR"
      .  "</td>"
      . "</table>"

      . $inset_bridge
      . $opcart

      . "<tr>"
      .  "<td class=in style=padding:2px>"
      .   "<span title=\"makes scrollbars' track color match that of the actual background\">"
      .   "<input type=radio name=sbc value=0$sbc_value_0>&nbsp;dominant background color</span><br>"
      .   "<span title=\"makes scrollbars' track color match that of surrounding board controls\">"
      .   "<input type=radio name=sbc value=1$sbc_value_1>&nbsp;use default (black, grey)</span><br>"
      .   "<span title=\"withelds coloring scrollbars (uses browser user interface appearence)\">"
      .   "<input type=radio name=sbc value=2$sbc_value_2>&nbsp;no color (browser UI)</span>"
      .  "</td>"
      . "</tr>"

      . $clcart
      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=40 class=inv align=center>"
      .   "VISUAL STYLE<br>"
      .   "<small>"
      .    "(wallpaper and color scheme)"
      .   "</small>"
      .  "</td>"
      . "</table>"

      . $inset_bridge
      . $opcart

      . "<td class=in>"
      .  $styleselect
      . "</td>"

      . $clcart
      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=40 class=inv align=center>"
      .   "WALLPAPER OVERRIDE<br>"
      .   "<small>"
      .    "eg. &#171;http://site.com/myphoto.jpg&#187;"
      .   "</small>"
      .  "</td>"
      . "</table>"

      . $inset_bridge
      . $opcart

      . "<td class=in>"
      .  "<input class=sf style=width:{$iw}px type=text name=backgr maxlength=160 value=\"$backgr\">"
      . "</td>"

      . $clcart
      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=40 class=inv align=center>"
      .   "CSS FONTS TABLE<br>"
      .   "<small>"
      .    "eg. &#171;bold 11px verdana, arial&#187;"
      .   "</small>"
      .  "</td>"
      . "</table>"

      . $inset_bridge
      . $opcart

      . "<td>"
      .  "<table width=$iw>"
      .   $fspace
      .   "<tr><td width=52 class=in align=right style=\"font:$kfont5\">TITLE:&nbsp;&nbsp;</td><td class=in><input class=mf style=width:".($pw-52)."px type=text name=f0 maxlength=40 value=\"$kfont0\"></td></tr>"
      .   "<tr><td width=52 class=in align=right style=\"font:$kfont5\">POST:&nbsp;&nbsp;</td><td class=in><input class=mf style=width:".($pw-52)."px type=text name=f1 maxlength=40 value=\"$kfont1\"></td></tr>"
      .   "<tr><td width=52 class=in align=right style=\"font:$kfont5\">MAIN:&nbsp;&nbsp;</td><td class=in><input class=mf style=width:".($pw-52)."px type=text name=f2 maxlength=40 value=\"$kfont2\"></td></tr>"
      .   "<tr><td width=52 class=in align=right style=\"font:$kfont5\">ALT:&nbsp;&nbsp;</td><td class=in><input class=mf style=width:".($pw-52)."px type=text name=f3 maxlength=40 value=\"$kfont3\"></td></tr>"
      .   "<tr><td width=52 class=in align=right style=\"font:$kfont5\">CODE:&nbsp;&nbsp;</td><td class=in><input class=mf style=width:".($pw-52)."px type=text name=f4 maxlength=40 value=\"$kfont4\"></td></tr>"
      .   "<tr><td width=52 class=in align=right style=\"font:$kfont5\">SNIP:&nbsp;&nbsp;</td><td class=in><input class=mf style=width:".($pw-52)."px type=text name=f5 maxlength=40 value=\"$kfont5\"></td></tr>"
      .   $fspace
      .  "</table>"
      . "</td>"

      . $clcart
      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=20 class=inv align=center>"
      .   "FIRST SAVE, THEN APPLY"
      .  "</td>"
      . "</table>"
      . $inset_bridge
      . $opcart
      .  "<td class=ls>"
      .   "Your settings are kept stored in a 'cookie': when you "
      .   "select the <b>save</b> button, the cookie is updated. "
      .   "After saving, when changes imply modifications to the "
      .   "layout of this site, for them to take full effect you "
      .   "might click the <b>apply</b> button."
      .  "</td>"
      . $clcart
      . $inset_bridge
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td>"
      .    "<table width=$pw>"
      .     "<td>"
      .      "<span title=\"UPDATES YOUR PREFERENCES (IN COOKIE)\">"
      .       "<input class=su type=submit name=submit style=width:{$pwl}px value=save>"
      .      "</span>"
      .     "</td>"
      .     "</form>"
      .     "<form action=\"$apply_address\" target=\"$apply_toframe\" type=\"multipart/form-data\" method=\"get\">"
      .     "<td align=right>"
      .      "<span title=\"APPLIES ANY VISUAL CHANGES (AFTER SAVING)\">"
      .       "<input class=ky type=submit name=submit style=width:{$pwr}px value=apply>"
      .      "</span>"
      .     "</td>"
      .     "</form>"
      .     "<form action=kprefs.php type=multipart/form-data method=post>"
      .    "</table>"
      .   "</td>"
      .  "</tr>"
      .  "<tr>"
      .   "<td height=3>"
      .   "</td>"
      .  "</tr>"
      .  "<tr>"
      .   "<td>"
      .    "<span title=\"REVERTS OPTIONS TO THEIR DEFAULTS\">"
      .     "<input class=su type=submit name=submit style=width:{$pw}px value=default>"
      .    "</span>"
      .   "</td>"
      .  "</tr>"
      .  $shadow
      . "</form>"                                                                       /* (1) */
      . "</table>";

/*
 *
 * template initialization
 *
 */

$kprefs = str_replace

  (

    array (

      "[FORM]",
      "[PERMALINK]"

    ),

    array (

      $form,
      $permalink

    ),

    $kprefs

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

echo (pquit ($kprefs));



?>
