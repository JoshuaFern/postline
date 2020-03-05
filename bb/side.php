<?php

/*************************************************************************************************

            HSP Postline -- sidebar rendering and control

            PHP bulletin board and community engine
            version 2010.02.10 (10.2.10)

                Copyright (C) 2003-2010 by Alessandro Ghignola
                Copyright (C) 2003-2010 Home Sweet Pixel software

            This program is free software; you can redistribute it and/or modify it under the
            terms of the GNU General Public License as published by the Free Software Foundation;
            either version 2 of the License, or (at your option) any later version.

            This program is distributed in the hope that it will be useful, but WITHOUT ANY
            WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
            PARTICULAR PURPOSE. See the GNU General Public License for more details.

            You should have received a copy of the GNU General Public License
            along with this program; if not, write to the Free Software Foundation, Inc.,
            51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA

*************************************************************************************************/

/*
 *
 *      IMPORTANT!
 *
 *      the starting point of launchable scripts is defining 'going': launchable scripts are built
 *      to produce HTML output out of processing client requests, in contrast with scripts holding
 *      support functions and symbols (e.g. 'suitcase.php', 'settings.php', 'setlfcookie.php'); in
 *      such support scripts, the definition of 'going' is then checked and, if found not defined,
 *      forces the support script to leave immediately, preventing possible interferences due to a
 *      lack of safety precautions which rely on the order in which scripts include each other
 *
 */

define ('going', true);
define ('left_frame', true);

/*
 *
 *      Postline includes
 *
 */

require ('settings.php');
require ('suitcase.php');
require ('sessions.php');
require ('template.php');

/*
 *
 *      widget includes
 *
 */

require ('widgets/overall/primer.php');
require ('widgets/dbengine.php');
require ('widgets/errors.php');
require ('widgets/locker.php');
require ('widgets/strings.php');

/*
 *
 *      error messages
 *
 */

$em['invalid_sidebar_name'] = 'NO VALID SIDE-BAR NAME';
$ex['invalid_sidebar_name'] =

        "This frame made a reference to a side-bar container file which name is invalid "
      . "due to non-word characters being part of it, or the name being an empty string.";

/*
 *
 *      get parameters:
 *
 *      $si is the name of the sidebar, and cannot be empty or contain non-word characters,
 *      as defined by PERL conventions
 *
 */

$si = fget ('si', 40, voidString);      // target sidebar name
$nw = preg_match ('/\W/', $si);         // non-word characters presence (counts matches)

$invalid_sidebar_name = ($si == voidString) ? true : false;
$invalid_sidebar_name = ($nw > 0) ? true : $invalid_sidebar_name;

if ($invalid_sidebar_name) {

        die (because ('invalid_sidebar_name'));

}

/*
 *
 *      check form submission authorizations:
 *
 *      note that submission trigger is only retrieved if user has at least moderation rights,
 *      and the security token is checked to prevent link forging; the submission trigger is a
 *      string which also toggles between 'save' and 'edit side-bar', both unavailable in case
 *      of absence of moderation or administration rights (when it gets to be a void string)
 *
 */

$submit = (isset ($_POST['submit'])) ? $_POST['submit'] : voidString;
$submit = (($is_admin) || ($is_mod)) ? $submit : voidString;

$submit_given = ($submit == voidString) ? false : true;

if ($submit_given) {

        $code = intval (fget ('code', 10, voidString));
        $submit = ($code == getcode ('pst')) ? $submit : voidString;

}

/*
 *
 *      process side-bar update request:
 *      sidebars are simple files holding the sidebar's code, at one record per line, that's all
 *
 */

if ($submit == 'save') {

        lockSession ();

        /*
         *
         *      let's trust moderators and allow them to use javascripts and otherwise forbidden
         *      strings in sidebars' code: they may be useful to pop links in new windows and do
         *      other benevolent stuff, so, for this run only, before getting the sidebar's code
         *      devoid the array of the forbidden strings
         *
         */

        $disallowed_strings = array ();
        $sidebar_records = explode ("\n", fget ('sidebar_code', 1000000, "\n"));

        asm ('sidebars/' . $si, $sidebar_records, asIs);

}

/*
 *
 *      get actual sidebar code to then display it:
 *
 *      if the requested side-bar isn't empty, its title is given by its first line of code,
 *      however, append empty sidebar notice in place of content only if this script was not
 *      called to edit the side-bar in question
 *
 */

$sidebar_records = all ('sidebars/' . $si, asIs);

$empty_sidebar = (count ($sidebar_records) == 0) ? true : false;
$append_notice = ($submit == 'edit side-bar') ? false : true;

if ($empty_sidebar) {

        $sidebar_records[] = 'empty side-bar';

        if ($append_notice) {

                $sidebar_records[] =

                        "This side-bar is empty, which most probably means that it's under "
                      . "construction: before or later, a moderator or an administrator of "
                      . "this system might take care for filling something here as soon as "
                      . "he/she spots the 'edit' button that regular members cannot really "
                      . "see, but that's otherwise just below this notice.";

        } // not been editing (only displaying) a side-bar which is actually empty

} // the side-bar was empty

/*
 *
 *      generate HTML code to show the sidebar's title:
 *      it will be used in two points in later code... the band makes it look cute and "important"
 *
 */

$title = maketopper ($sidebar_records[0]);

/*
 *
 *      form initialization
 *
 */

if ($submit == 'edit side-bar') {

        /*
         *
         *      when editing the side-bar, show form filled with actual records
         *
         */

        $pwl = intval ($pw / 2) - 1;    // width of left-side button ("save")
        $pwr = $pw - $pwl - 3;          // width of right-side button ("cancel")

        $form =

                $title

              . $opcart
              . '<td class="ls">'
              .  '<p class="sp">'
              .   "editing contents of the<br>&#171;&nbsp;<em>$si</em>&nbsp;&#187; side-bar"
              .  '</p>'
              . '</td>'
              . $clcart
              . $inset_shadow

              . '<table style="width:' . blank . $pw . 'px">'
              . '<form action="side.php?si=' . $si .'" method="post">'
              . '<input type="hidden" name="code" value="' . setcode ('pst') . '">'
              .  '<tr>'
              .   '<td height="40" colspan="2" class="inv" align="center">'
              .    'sidebar contents:<br>'
              .    '<a style="color: #fff" target="pan" href="faq.php#q' . $sidebar_faq_number . '">'
              .     '-- click here for help --'
              .    '</a>'
              .   '</td>'
              .  '</tr>'
              . '</table>'

              . $inset_bridge

              . $opcart
              .  '<td>'
              .   '<textarea name="sidebar_code" style="width:' . blank . $iw . 'px; height: 240px">'
              .    implode ("\n", $sidebar_records)
              .   '</textarea>'
              .  '</td>'
              . $clcart

              . $inset_shadow

              . "<table width=\"{$pw}\">"
              .  "<tr>"
              .   "<td height=\"20\" class=\"inv\" align=\"center\">"
              .    "operations:"
              .   "</td>"
              .  "</tr>"
              .  $bridge
              .  "<tr>"
              .   "<td>"
              .    "<table width=\"{$pw}\">"
              .     "<td>"
              .      "<span title=\"APPLY CHANGES\">"
              .       "<input class=\"su\" style=\"width: {$pwl}px\" type=\"submit\" name=\"submit\" value=\"save\">"
              .      "</span>"
              .     "</td>"
              .     "<td align=\"right\">"
              .      "<span title=\"DISCARD CHANGES AND RE-LOAD THE SIDE-BAR\">"
              .       "<input class=\"ky\" style=\"width: {$pwr}px\" type=\"submit\" name=\"submit\" value=\"cancel\">"
              .      "</span>"
              .     "</td>"
              .    "</table>"
              .   "</td>"
              .  "</tr>"
              . "</form>"
              . "</table>"

              . $inset_shadow;

} // been showing the side-bar edit form

else {

        $form = $title;

        /*
         *
         *      when not editing the side-bar, process its special markup code,
         *      having its own syntax rules: they're explained to moderators by the FAQ page...
         *
         */

        $cart_open = false;                                     // no cartridges open so far
        $sidebar_records = array_slice ($sidebar_records, 1);   // cut 1st line from array (title)

        foreach ($sidebar_records as $l) {

                $t = strBefore ($l, ':');               // one-character entity marker
                $e = substr ($l, strlen ($t) + 1);      // entity text, if marker given
                $t = ($t == voidString) ? $l : $t;      // paragraph text, if marker NOT given

                switch ($t[0]) {

                        case 'h':       // marks HTML code fragment to be inserted as it is

                                if ($cart_open == false) {

                                        $form .= $opcart . '<td class="ls">';
                                        $cart_open = true;

                                }

                                /*
                                 *
                                 *      let HTML tag markers pass undisturbed (unescaped)
                                 *
                                 */

                                $h = str_replace (

                                        array ('&lt;', '&gt;', '&quot;', '&apos;'),
                                        array ('<', '>', '"', "'"),

                                        $e

                                );

                                /*
                                 *
                                 *      let HTML links be shown in the appropriate style
                                 *
                                 */

                                $h = str_replace (

                                        array (

                                                '<a' . blank,
                                                '<A' . blank

                                        ),

                                        '<a class="ll"' . blank,

                                        $h

                                );

                                $form .= $h;

                                break;

                        case 'f':       // marks link to forum of which the ID is given

                                if ($cart_open == false) {

                                        $form .= $opcart . '<td class="ls">';
                                        $cart_open = true;

                                }

                                $fid = intval (substr ($t, 1));
                                $form .=

                                        '<a class="ll"'
                                      . blank
                                      . link_to_threads ('pan', $fid, voidString)
                                      . '>'
                                      . $e
                                      . '</a><br>';

                                break;

                        case 't':       // marks link to thread of which the ID is given

                                if ($cart_open == false) {

                                        $form .= $opcart . '<td class="ls">';
                                        $cart_open = true;

                                }

                                $tid = intval (substr ($t, 1));
                                $form .=

                                        '<a class="ll"'
                                      . blank
                                      . link_to_posts ('pan', $tid, voidString, voidString, false)
                                      . '>'
                                      . $e
                                      . '</a><br>';

                                break;

                        case 'l':       // same as 't', but links last post of given thread

                                if ($cart_open == false) {

                                        $form .= $opcart . '<td class="ls">';
                                        $cart_open = true;

                                }

                                $tid = intval (substr ($t, 1));
                                $form .=

                                        '<a class="ll"'
                                      . blank
                                      . link_to_posts ('pan', $tid, 'last', 'lastpost', false)
                                      . '>'
                                      . $e
                                      . '</a><br>';

                                break;

                        case 'm':       // link to single message of which the ID is given

                                if ($cart_open == false) {

                                        $form .= $opcart . '<td class="ls">';
                                        $cart_open = true;

                                }

                                $mid = intval (substr ($t, 1));
                                $form .=

                                        '<a class="ll" target="pan" href="result.php?m='
                                      . $mid
                                      . '">'
                                      . $e
                                      . '</a><br>';

                                break;

                        case 'i':       // marks an informative header

                                if ($cart_open) {

                                        /*
                                         *
                                         *      close cartridge, if one was open
                                         *
                                         */

                                        $form .=

                                                '</td>'
                                              . $clcart
                                              . $inset_shadow;

                                        $cart_open = false;

                                }

                                $form .=

                                        "<table width=\"{$pw}\">"
                                      .  '<td height="24" class="inv" align="center">'
                                      .   $e
                                      .  '</td>'
                                      . '</table>'
                                      . $inset_shadow;

                                break;

                        case 's':       // marks quick search box

                                if ($global_search_disable) {

                                        break;

                                }

                                if ($cart_open) {

                                        /*
                                         *
                                         *      close cartridge, if one was open
                                         *
                                         */

                                        $form .=

                                                '</td>'
                                              . $clcart
                                              . $inset_shadow;

                                        $cart_open = false;

                                }

                                $where_field = ($e == 'logs') ? 'logs' : 'posts';
                                $where_caption = ($e == 'logs') ? 'conversation logs' : 'discussions';

                                $form .=

                                        "<table width=\"{$pw}\">"
                                      .  "<td height=\"40\" class=\"inv\" align=\"center\">"
                                      .   "QUICK KEYWORD SEARCH<br>"
                                      .   "(through {$where_caption})"
                                      .  "</td>"
                                      . "</table>"

                                      . $inset_bridge

                                      . $opcart
                                      .  "<form name=\"qsearch\" action=\"search.php\" method=\"get\">"
                                      .  "<input type=\"hidden\" name=\"where\" value=\"$where_field\">"
                                      .   "<td class=\"ls\">"
                                      .    "Find:&nbsp;"
                                      .   "</td>"
                                      .   "<td class=\"ls\">"
                                      .    "<input class=\"mf\" style=\"width: 68px\" type=\"text\" name=\"search\" value=\"\" maxlength=\"80\">"
                                      .   "</td>"
                                      .   "<td class=\"ls\" align=\"right\">"
                                      .    "<input class=\"rf\" style=\"width: 36px\" type=\"submit\" name=\"submit\" value=\"GO!\">"
                                      .   "</td>"
                                      .  "</form>"
                                      . $clcart

                                      . $inset_shadow

                                      . "\n"
                                      . "<script language=\"Javascript\" type=\"text/javascript\">\n"
                                      .  "<!--\n"
                                      .   "document.qsearch.search.focus ()\n"
                                      .  "//-->\n"
                                      . "</script>\n";

                                break;

                        default:        // of any known entity markers

                                if ($cart_open) {

                                        /*
                                         *
                                         *    close cartridge, if one was open
                                         *
                                         */

                                        $form .=

                                                '</td>'
                                              . $clcart
                                              . $inset_shadow;

                                        $cart_open = false;

                                }

                                if ($t == 'div') {

                                        /*
                                         *
                                         *      a divisor is simply made by leaving a void space
                                         *      between cartridges in the output: the space is
                                         *      given by the cartridge's shadow...
                                         *
                                         */

                                        $form .= $opcart . '<td class="ls">';
                                        $cart_open = true;

                                }

                                else
                                if ($l != voidString) {

                                        /*
                                         *
                                         *      neither a known entity, nor a div:
                                         *      providing it's not a void line,
                                         *      it's assumed to be a plain paragraph of text
                                         *
                                         */

                                        if ($cart_open == false) {

                                                $form .= $opcart . '<td class="ls">';
                                                $cart_open = true;

                                        }

                                        $form .= "<p class=\"sp\">$l</p>";

                                }

                } // marker type case switch

        } // each record in side-bar's code

        /*
         *
         *      close last cartridge, if one was open
         *
         */

        if ($cart_open) {

                $form .=

                        '</td>'
                      . $clcart
                      . $inset_shadow;

        }

        /*
         *
         *      append edit button if the rank allows that
         *
         */

        if (($is_admin) || ($is_mod)) {

                $form .=

                        "<table width=\"{$pw}\">"
                      . "<form action=\"side.php?si={$si}\" method=\"post\">"
                      . "<input type=\"hidden\" name=\"code\" value=\"" . setcode ('pst') . "\">"
                      .  "<tr>"
                      .   "<td height=\"20\" class=\"inv\" align=\"center\">"
                      .    "(moderators only)"
                      .   "</td>"
                      .  "</tr>"
                      .  $bridge
                      .  "<tr>"
                      .   "<td>"
                      .    "<input class=\"ky\" type=\"submit\" name=\"submit\" style=\"width: {$pw}px\" value=\"edit side-bar\">"
                      .   "</td>"
                      .  "</tr>"
                      .  $shadow
                      . "</form>"
                      . "</table>";

        } // appending edit button for moderators and administrators

} // not editing the side-bar (been displaying side-bar contents)

/*
 *
 *      template initialization
 *
 */

$sidebar = str_replace

        (

                array

                        (

                                '[FORM]',
                                '[PERMALINK]'

                        ),

                array

                        (

                                $form,
                                $permalink

                        ),

                $sidebar

        );

/*
 *
 *      saving navigation tracking informations for recovery of central frame page upon showing or
 *      hiding the chat frame, and for the online members list links (unless no-spy flag checked)
 *
 */

include ('setlfcookie.php');

/*
 *
 *      releasing locked session frame, page output
 *
 */

exit (pquit ($sidebar));

?>
