<?php



/*************************************************************************************************

            HSP Postline

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
 *      about the $bx string, which is applied to right-side pages, it's a patch convincing
 *      certain browsers (actually seen on Opera 9.60) to REDRAW the frame after it's fully
 *      loaded: Opera insists leaving a horizontal scrollbar in right-side pages even if no
 *      parts of the document get past the right edge of the frame; redrawing compells that
 *      browser to realize that the horizontal scroller is superfluous... it's one of those
 *      things someone will catch before or later, but annoying enough to require patching:
 *      the trick used reduces the bottom padding to 3 px (from 4 px)
 *
 */

$bx = (($on_opera) && (defined ('left_frame')))

      ? ''

      : implode (chr (32), array

                (

                        '',
                        'id="thisFrame"',
                        "onload=\"document.getElementById('thisFrame').style.paddingBottom='3px'\""

                ));

/*
 *
 *      IMPORTANT!
 *
 *      avoid launching this and other similary dependand scripts alone,
 *      to prevent potential malfunctions and unexpected security flaws:
 *      the starting point of launchable scripts is defining 'going'
 *
 */

$proceed = (defined ('going')) ? true : die ('[ postline template parts ]');

/*
 *
 *      variables dimensioning, positioning and spacing various visual elements being referred
 *      outside of this script
 *
 */

$tt_hint_length = 30;           // max. characters of thread title to show in posts listing
$dbx_lines_size = 62;           // characters per line in database explorer's file editor
$dbx_windowsize = 10;           // number of lines in database explorer's file editor
$f_base_yoffset = 30;           // offset of first forum icon along Y
$f_icon_spacing = 1;            // increment along Y per forum icon
$f_news_xoffset = 36;           // relative offset of new posts icon along X in forums listing
$f_news_yoffset = 13;           // relative offset of new posts icon along Y in forums listing
$t_base_yoffset = 30;           // offset of first thread icon along Y
$t_icon_spacing = 1;            // increment along Y per thread icon
$t_rule_spacing = 8;            // height of the horizontal rule following pinned threads
$t_news_xoffset = 36;           // relative offset of new posts icon along X in threads listing
$t_news_yoffset = 13;           // relative offset of new posts icon along Y in threads listing
$t_sign_spacing = 24;           // spacing of signs marking polls, new posts etc near threads
$t_sign_xoffset = 88;           // offset of first sign from the right
$t_sign_yoffset = 34;           // offset of all signs from top of thread cartridge

if ($ofie == "yes") {

  $t_sign_xoffset += 18;

}

/*
 *
 *      depth of paging links,
 *      which affect the amount of page numbers linked in several 'navigation bars'
 *
 */

$pp_forum = 5;                  // number of additional page numbers in forum navigation bars
$pp_posts = 2;                  // number of additional page numbers in thread navigation bars
$pp_mlist = 3;                  // number of additional page numbers in member list nav bars
$pp_years = 5;                  // number of additional page numbers in logs directory nav bars

/*
 *
 *      colors of Internal Frespych Chat,
 *      matching their equivalents from 'chatpage.html' and 'chatlog.html' in 'layout/html'
 *
 */

$ifc_server     = "546880";     // color of server messages (and of the sole manager nickname)
$ifc_admin      = "188088";     // color of administrators' nicknames (all purposes)
$ifc_mod        = "48F";        // color of moderators' nicknames (all purposes)
$ifc_member     = "000";        // color of members' nicknames (all purposes)
$ifc_typo       = "A60";        // color of typo corrections ("someone meant: ...")
$ifc_yelling    = "00F";        // color of yelled phrase ("someone yells: ...")
$ifc_whispering = "8498B0";     // color of whispered phrase ("someone whispers: ...")

/*
 *
 *      width of left frame's panels:
 *
 *      $pw is the width of the frame as specified by "settings.php", minus a few pixels to leave
 *      room for an eventual vertical scrollbar; $iw = $pw minus 2 because 2 are the pixels taken
 *      by the constant black edges of a "cartridge" (defined later)
 *
 *      PS.
 *      $pw stands for panel width, $iw stands for panel's "inner width"
 *
 */

$pw = $leftframewidth - $scrollbarwidth;
$iw = $pw - 2;

/*
 *
 * this will replace the [PERMALINK] variable in later page models
 *
 */

$permalink_model =

  "<div class=plink"
. " title=\"GET PERMALINK\""
. " onClick=\"javascript:alert('PERMANENT REMOTE LINK:\\n[PERMALINK]')\">"
.  "<img src=layout/images/plink.png width=44 height=32>"
. "</div>";

/*
 *
 * page header for all pages and all panels, and overall stylesheet defines,
 * except accessory frames (navigation, status, chat frame, and chat input/command line)
 *
 */

$page_header =

    "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">"
  . "<html>"
  . "<head>"
  . "<title>"
  .   "{$sitename}_internal_frame"
  . "</title>"
  . "<style type=text/css>"
  . "<!--\n"

  // general document body

  . "body{"
  .   "margin:0;"
  .   "padding:0;"
  .   $body_background
  .

(($sb2 == "yes")

  ?   ""

  :

(($sb1 == "yes")

  ?   "scrollbar-face-color:#000;"
  .   "scrollbar-shadow-color:#000;"
  .   "scrollbar-highlight-color:#000;"
  .   "scrollbar-3dlight-color:#000;"
  .   "scrollbar-darkshadow-color:#000;"
  .   "scrollbar-track-color:#c4c0c8;"
  .   "scrollbar-arrow-color:#c4c0c8"

  :   "scrollbar-face-color:#$scroll_bar_face;"
  .   "scrollbar-shadow-color:#$scroll_bar_face;"
  .   "scrollbar-highlight-color:#$scroll_bar_face;"
  .   "scrollbar-3dlight-color:#$scroll_bar_face;"
  .   "scrollbar-darkshadow-color:#$scroll_bar_face;"
  .   "scrollbar-track-color:#$scroll_bar_trck;"
  .   "scrollbar-arrow-color:#$scroll_bar_trck"

  )

  )

  . "}"
  .

  // HTC caller, to properly show alpha blending PNGs in MSIE

(($ofie == "yes")

  ? "img{"
  .   "behavior:url(layout/behaviors/inset_iepngfix.htc)"
  . "}"

  :  ""

  )

  // making tables be simple rectangular areas, no borders, no paddings, no margins

  . "table{"
  .   "margin:0;"
  .   "border:0;"
  .   "padding:0;"
  .   "border-collapse:collapse"
  . "}"

  // default settings for a table cell

  . "td{"
  .   "padding:0;"
  .   "color:$most_text_color;"
  .   "font:$kfont2;"
  .   "line-height:150%;"
  .   "$font_variant"
  . "}"

  // default settings for a hypertext link

  . "a:link,a:active,a:visited{"
  .   "color:$nrm_links_color;"
  .   "text-decoration:none"
  . "}"
  . "a:hover{"
  .   "color:$hov_links_color"
  . "}"

  // default settings for a horizontal rule

  . "hr{"
  .   "height:1px;"
  .   "border:0;"
  .   "border-bottom: 1px dotted black"
  . "}"

  // default settings for form inputs

  . "form{"
  .   "display:inline"
  . "}"

  . "input,select,textarea{"
  .   "font:$kfont2;"
  .   "$font_variant"
  . "}"

  // settings for text editing boxes

  . "textarea{"
  .   "margin:0;"
  .   "padding:0 0 0 2px;"
  .   "border:0 solid;"
//  .   "line-height:120%"
  . "}"

  // settings for emphasis, used for text over page background

  . "em{"
  .   "color:$frameboundcolor;"
  .   "font-style:normal"
  . "}"

  // used for table cells embedding file selectors (not for the selectors themselves)

  . ".fs{"
  .   "padding:1px;"
  .   "background-color:white"
  . "}"

  // used for table cells framing input fields

  . ".in{"
  .   "color:000;"
  .   "background-color:white"
  . "}"

  // style for input buttons in right frames

  . ".rf{"
  .   "height:20px;"
  .   "color:000;"
  .   "background-color:white;"
  .   "border:1px solid black"
  . "}"

  // page generation time indication

  . ".gt{"
  .   "color:$serverloadcolor"
  . "}"

  // table background for "cartridges", holding most of the text in left frames:
  // this will determine the fact that the outline border of such cartridges is
  // coloured in black, because real outline borders were found glitchy, rendered
  // in different ways by different browsers, and causing the tables to misalign
  // on edges... hence the invention of those "cartridges"...

  . ".tb{"
  .   "width:{$pw}px;"
  .   "background-color:000"
  . "}"

  // inner table of a cartridge (inside tables made with the above "tb" class)

  . ".ti{"
  .   "width:{$iw}px;"
  .   "margin:1px"
  . "}"

  // list slot, almost always used as the class for what's inside a cartridge

  . ".ls{"
  .   "padding:5px 10px 5px 10px;"
  .   "color:000;"
  .   "background-color:white;"
  .   "line-height:150%"
  . "}"

  // list link: a hypertext or mailto link showing inside the above list element

  . "a.ll:link,a.ll:visited,a.ll:active{"
  .   "color:2040D0"
  . "}"
  . "a.ll:hover{"
  .   "color:D04020"
  . "}"

  // legacy "poll header": now used for frame titles made with "makeframe",
  // and also where $ph_top_padding is used...

  . ".ph{"
  .   "height:22px;"
  .   "color:000;"
  .   "background-image:url(layout/images/gr.png);"
  .   "padding:2px 4px 2px 4px;"
  .   "line-height:100%"
  . "}"

  // frame link: a hypertext or mailto link showing inside "ph" tables,
  // so this is the style used for paging links in right frames

  . "a.fl:link,a.fl:visited,a.fl:active{"
  .   "padding:1px 3px 0 3px;"
  .   "color:$v_high_contrast"
  . "}"
  . "a.fl:hover{"
  .   "border-bottom:1px solid #$v_high_contrast"
  . "}"

  // legacy "poll body": now used for frame bodies made with "makeframe"

  . ".pb{"
  .   "padding:4px;"
  .   "color:000;"
  .   "background-image:url(layout/images/hl.png)"
  . "}"

  // post link: a hypertext or mailto link showing inside a post

  . "a.pk:link,a.pk:visited,a.pk:active{"
  .   "padding:0 2px 0 2px;"
  .   "border-bottom:1px dashed black;"
  .   "color:800000"
  . "}"
  . "a.pk:hover{"
  .   "border-bottom:1px solid black"
  . "}"

  // inverted text: used in titles for left frames ("Are you a member?"),
  // originarily REALLY inverted with $most_text_color and $most_text_bkgnd,
  // but then changed to a constant white-over-black pattern, which matches
  // the black edges of cartridges better to the eye...

  . ".inv{"
  .   "padding:0;"
  .   "color:white;"
  .   "background-image:url(layout/images/sh.png);"
  .   "font:$kfont5"
  . "}"

  // used for "permanently banned" indication in profile forms and eventually elsewhere

  . ".alert{"
  .   "padding:0 3px 0 3px;"
  .   "color:white;"
  .   "background-image:url(layout/images/al.png)"
  . "}"

  // edge of informative frames

  . ".ie{"
  .   "background-color:$most_text_bkgnd;"
  . "}"

  // caption of informative frames having no content

  . ".ic{"
  .   "height:24px;"
  .   "padding:1px 3px 0 6px;"
  .   "background-image:url(layout/images/$graphicsvariant.png);"
  .   "color:$v_high_contrast;"
  .   "font:$kfont0;"
  .   "$font_variant"
  . "}"

  // caption of informative frames having content

  . ".ik{"
  .   "padding:6px 0 6px 10px;"
  .   "background-color:$most_text_bkgnd;"
  .   "color:$v_high_contrast;"
  .   "font:$kfont5;"
  .   "$font_variant"
  . "}"

  // caption of toppers

  . ".tp{"
  .   "height:24px;"
  .   "background-image:url(layout/images/bd.png);"
  .   "color:000;"
  .   "text-align:center;"
  .   "font:$kfont0;"
  .   "line-height:100%;"
  .   "font-variant:small-caps"
  . "}"

  . ".s6{"
  .   "background-image:url(layout/images/$graphicsvariant.png)"
  . "}"

  // text inside informative frames in "compact" mode (in left frames)

  . ".if,.ig{"
  .   "background-image:url(layout/images/$graphicsvariant.png);"
  .   "width:100%;"
  .   "padding:3px 9px 9px 9px;"
  .   "font:$kfont2;"
  .   "line-height:125%;"
  .   "$font_variant"
  . "}"

  // text inside informative frames, such as those on "intro.php"

  . ".if{"
  .   "line-height:150%;"
  .   "$font_variant"
  . "}"

  // thread titles

  . ".tt{"
  .   "font:$kfont0;"
  .   "line-height:120%;"
  .   "$font_variant"
  . "}"

  // thread starter, but also for small details over no solid color,
  // such as in the tellme.php feedback form for instructions.

  . ".ts{"
  .   "font:$kfont5;"
  .   "line-height:120%"
  . "}"

  // final field: once supposed to divide some forms, now used to divide pinned threads
  // from the rest, and in the tellme.php script above the "mail copies to:" controls.

  . ".ff{"
  .   "border-bottom:1px solid #$innerframecolor"
  . "}"

  // full-width shortcut class (replaces <table style="width:100%">)

  . ".w{"
  .   "width:100%"
  . "}"

  // hidden shortcut class (typical of fake images used as spacers)

  . ".h{"
  .   "visibility:hidden"
  . "}"

  // be: generic horizontal black edge (table or td)
  // he: top edge of frames ("makeframe")
  // hd: class used for <td> element representing a horizontal rule (<d>, <hr> tags)

  . ".be,.he,.hd{"
  .   "width:100%;"
  .   "height:1px;"
  .   "background-color:000"
  . "}"

  // we: wrapping edge of frames ("makeframe")

  . ".we{"
  .   "width:100%;"
  .   "border:1px solid black;"
  .   "border-top:0"
  . "}"

  // ew: edge within frames ("makeframe")

  . ".ew{"
  .   "width:100%;"
  .   "border:1px solid black"
  . "}"

  // plink: permalink holder

  . ".plink{"
  .   "width:44px;"
  .   "height:32px;"
  .   "position:absolute;"
  .   "top:0;"
  .   "right:" . (($ofie == "yes") ? "20px" : "0") . ";"
  .   "z-index:3;"
  .   "cursor:pointer"
  . "}"

  // subnavbar reflection and shadow

  . ".snh{"
  .   "position:fixed;"
  .   "top:0;"
  .   "left:0;"
  .   "z-index:-3;"
  .   "width:100%;"
  .   "overflow:hidden"
  . "}"

  . ".snr{"
  .   "height:32px;"
  .   "background-image:url(layout/images/subreflection.png)"
  . "}"

  . ".sns{"
  .   "height:12px;"
  .   "background-image:url(layout/images/subshadow.png)"
  . "}"

  ;

/*
 *
 * stylesheet's additional modules:
 *
 * $style_input defines the styles for controls in the left frame, such as buttons,
 * text fields, text areas... in general, styling of buttons is fine but styling of
 * other elements such as radio buttons, checkboxes and file selectors doesn't give
 * good results; if the browser is left free to render those elements as it intends
 * to, forms might look cuter, especially using Opera, but also Firefox looks good.
 *
 */

$style_input =

  // input: single field

    ".sf{"
  .   "margin:0 0 1px 0;"
  .   "border:0 solid;"
  .   "padding:2px 0 2px 2px;"
  .   "color:202880;"
  .   "background-color:white"
  . "}"

  // input: multiple fields, where there's the need for separation between fields

  . ".mf{"
  .   "margin:0;"
  .   "border:0 solid;"
  .   "border-bottom:1px dotted #C83232;"
  .   "padding:0 0 0 2px;"
  .   "color:202880;"
  .   "background-color:white"
  . "}"

  // input: key, generic button (eg. "logout / destroy cookie", "cancel"...)

  . ".ky{"
  .   "margin:0;"
  .   "border:1px solid black;"
  .   "padding:0 0 1px 2px;"
  .   "height:24px;"
  .   "color:000;"
  .   "background-image:url(layout/images/input.gif);"
  .   "background-position:right bottom;"
  .   "background-repeat:no-repeat"
  . "}"

  // input: submitter, red button (eg. "submit changes", "save"...)

  . ".su{"
  .   "margin:0;"
  .   "border:1px solid black;"
  .   "padding:0 0 1px 2px;"
  .   "height:24px;"
  .   "color:000;"
  .   "background-image:url(layout/images/submit.gif);"
  .   "background-position:right bottom;"
  .   "background-repeat:no-repeat"
  . "}"

  // emoticon list elements

  . ".em{"
  .   "color:000;"
  .   "background-color:white"
  . "}";

/*
 *
 * stylesheet's additional modules:
 *
 * $style_postaccessories defines styles for messages rendering.
 *
 */

$style_postaccessories =

  // ft: message frame top
  // fb: message frame bottom

    ".ft,.fb{"
  .   "width:100%;"
  .   "border-left:1px solid black;"
  .   "border-right:1px solid black"
  . "}"

  // fm: message frame middle

  . ".fm{"
  .   "width:100%;"
  .   "background-color:000"
  . "}"

  . ".fi{"
  .   "width:100%;"
  . "}"

  // user title cart

  . ".ut{"
  .   "padding:0 1px 1px 1px;"
  .   "color:000;"
  .   "background-color:FFFF80;"
  .   "font:10px arial,sans-serif;"
  .   "text-align:center"
  . "}"

  // balloon header: indications on top of speech balloons (author, date)

  . ".bh{"
  .   "padding:3px " . chr (32) . ($avatarpixelsize + 44) . "px 3px 0;"
  .   "color:000;"
  .   "background-image:url(layout/images/pf.png);"
  .   "font:$kfont5;"
  .   "letter-spacing:1px;"
  .   "text-align:right"
  . "}"

  // author link (stays in "bh")

  . "a.i:link,a.i:visited,a.i:active{"
  .   "padding:3px 3px 3px 3px;"
  .   "color:000"
  . "}"
  . "a.i:hover{"
  .   "background-color:white"
  . "}"

  // balloon frame sides (holding no text, decorative)

  . ".bs{"
  .   "width:20px;"
  .   "background-image:url(layout/images/pf.png)"
  . "}"

  // balloon footer: indications on bottom of speech balloons (edit links)

  . ".bf{"
  .   "height:3px;"
  .   "padding:3px 0 3px 8px;"
  .   "background-image:url(layout/images/sh.png);"
  .   "font:$kfont5;"
  .   "letter-spacing:1px"
  . "}"

  // message controls in "bf" (links such as: reply, edit, delete, split, etc...)

  . "a.j:link,a.j:visited,a.j:active{"
  .   "padding:3px 2px 3px 3px;"
  .   "margin-right:2px;"
  .   "color:white"
  . "}"
  . "a.j:hover{"
  .   "color:000;"
  .   "background-color:white"
  . "}"

  // frame shadow, last edit notice:
  // MSIE will not endure alpha-blended backgrounds, so witheld the shadow there

  . ".fh{"
  .   "height:7px;"
  .   "padding:3px 0 4px 0;"
  .

(($ofie == "yes")

  ?   ""
  :   "background-image:url(layout/images/shadow.png);"
  .   "background-repeat:no-repeat;"

  )

  .   "color:$most_text_color;"
  .   "font:$kfont5;"
  .   "letter-spacing:1px"
  . "}"

  // post message body

  . ".post{"
  .   "width:100%;"
  .   "padding:6px 0 12px 0;"
  .   "color:000;"
  .   "font:$kfont1;"
  .   "line-height:150%;"
  .   "$font_variant"
  . "}"

  // post message body after closing a "<h>eadline" or a "post brea<k>"

  . ".xpost{"
  .   "padding:6px 0 12px 0;"
  .   "color:000;"
  .   "font:$kfont1;"
  .   "line-height:150%;"
  .   "$font_variant"
  . "}"

  // quote tail: holds the little "back" icon before "x said: ..."

  . ".qt{"
  .   "padding-right:3px;"
  .   "border:1px solid black;"
  .   "border-right:0 solid"
  . "}"

  // quote head: holds "x said: ..."

  . ".qh{"
  .   "width:100%;"
  .   "color:000;"
  .   "border:1px solid black;"
  .   "border-left:0 solid;"
  .   "font:$kfont1;"
  .   "$font_variant"
  . "}"

  // quote body: holds what x said :)

  . ".qb{"
  .   "color:000;"
  .   "padding:8px;"
  .   "border:1px solid black;"
  .   "border-top:0 solid;"
  .   "font:$kfont1;"
  .   "$font_variant"
  . "}"

  // quote body after "<h>eadline" or "post brea<k>"

  . ".xqb{"
  .   "color:000;"
  .   "padding:12px 8px 12px 8px;"
  .   "border:1px solid black;"
  .   "border-top:0 solid;"
  .   "font:$kfont1;"
  .   "$font_variant"
  . "}"

  // code box body, which is a table cell

  . ".cbody{"
  .   "padding:1px 0 1px 10px;"
  .   "font:$kfont4"
  . "}"

  // multiline code box body, which is a textarea

  . ".multilinecb{"
  .   "width:100%;"
  .   "padding:0;"      // (Opera is completely broken here: it'd work like a weird margin)
  .   "font:$kfont4"
  . "}"

  // poll list element: determines appearence of an option

  . ".pl{"
  .   "color:000;"
  .   "padding-left:4px"
  . "}"

  // paragraph control: alternate font

  . ".pc_alt{"
  .   "font:$kfont3"
  . "}"

  // paragraph control: headline body

  . ".pchl,.pchr,.pc_h{"
  .   "border-top:1px solid black;"
  .   "border-bottom:1px solid black;"
  . "}"

  . ".pchl,.pchr{"
  .   "background-image:url(layout/images/gadgets/edge.gif);"
  .   "background-repeat:repeat-y"
  . "}"

  . ".pchr{"
  .   "background-position:right"
  . "}"

  . ".pc_h{"
  .   "color:000;"
  .   "padding:1px 0 1px 0;"
  .   "font:$kfont1;"
  .   "line-height:150%;"
  .   "$font_variant"
  . "}"

  // paragraph control: headline body in quote block

  . ".pchq{"
  .   "color:000;"
  .   "padding:1px 8px 1px 8px;"
  .   "border:1px solid black"
  . "}"

  // paragraph control: post break frame

  . ".pc_k{"
  .   "border-top:1px solid black;"
  .   "border-bottom:1px solid black;"
  .   "padding:0 1px 0 1px;"
  .   "color:white;"
  .   "font:$kfont1;"
  .   "$font_variant"
  . "}"

  // paragraph control: post break in quote body

  . ".pckq{"
  .   "border:1px solid black;"
  .   "font:$kfont1;"
  .   "$font_variant"
  . "}"

  // paragraph control: emphasis

  . ".pc_e{"
  .   "font-style:italic;"
  .   "letter-spacing:1px;"
  .   "text-decoration:underline"
  . "}"

  // paragraph control: outline border

  . ".pc_o{"
  .   "border:1px solid black;"
  .   "padding:.2em .2em .2em .2em;"
  .   "line-height:2.5em"
  . "}"

  // paragraph control: small text

  . ".pc_s{"
  .   "font-size:10px"
  . "}"

  // paragraph control: large text

  . ".pc_l{"
  .   "font-size:18px"
  . "}"

  // paragraph control: tiny text, intentionally unreadable

  . ".pc_y{"
  .   "font-size:3px"
  . "}"

  // paragraph control: underscored text

  . ".pc_u{"
  .   "border-bottom:1px solid #4880FF"
  . "}"

  // paragraph control: mistake marker

  . ".pc_m{"
  .   "border-bottom:1px dashed #FF2820"
  . "}"

  // paragraph control: table cell inside messages' simplified tables

  . ".pc_t{"
  .   "color:000;"
  .   "padding:1px 4px 1px 4px;"
  .   "border:1px solid black;"
  .   "font:$kfont2;"
  .   "$font_variant"
  . "}"

  // search result keyword highlight

  . ".key{"
  .   "padding-left:2px;"
  .   "padding-right:2px;"
  .   "color:white;"
  .   "background-color:000"
  . "}";

/*
 *
 * miscellaneous HTML parts:
 *
 * $endstyle is a fragment that closes page-specific modular stylesheet in forum pages and panels
 * $buttonseparator is used in navigation bar and status line to leave space between button groups
 *
 * $opcart and $clcart open and close a "cartridge", a specially crafted table that's used only in
 * left-frame page models: it presents a 1-pixel-thick, solid black edge around the <tr> elements
 * that may be filled between $opcart and $clcart; what's most important about those cartridges is
 * that they can be laid around paragraphs of texts and controls without encountering different
 * behaviors in calculation of edges by different browsers (the extremely inconstant behavior of
 * the "border" attribute of tables is what triggered me to define $opcart and $clcart instead...)
 * note: styling of cartridges is defined by "tb" and "ti" entries of the general embedded CSS.
 *
 * $shadow is a <tr> containing the image of a drop shadow that can be inserted inside cartridges
 * $bridge is a <tr> containing a drop shadow and an arrow that can be inserted inside cartridges
 * $inset_shadow and $inset_bridge are the same as the above two, made to stay OUTSIDE cartridges
 *
 * note: in $bridge and $inset_bridge, value of $graphicsvariant is used to determine addition of
 * a "00" or "ff" suffix to the name of the graphics file (bridge00.png/bridgeff.png); the "ff"
 * version is used over dark backgrounds to make the arrows stand out better (has a white arrow)
 *
 * $fspace is a spacing <tr>, typically used to (vertically) space out text fields in cartridges
 * $postlink_attributes are tailed to any <a href...> tags placed into forum messages (posts)
 *
 */

$endstyle =

    "\n-->"
  . "</style>"
  . "</head>";

$buttonseparator =

    "<td class=s>"
  .  "<div style=width:3px>"
  .  "</div>"
  . "</td>";

$opcart =

    "<table class=tb>"
  .  "<tr>"
  .   "<td>"
  .    "<table class=ti>";

$clcart =

       "</table>"
  .   "</td>"
  .  "</tr>"
  . "</table>";

$shadow =

    "<tr>"
  .  "<td colspan=2>"
  .   "<img src=layout/images/shadow.png width=$pw height=7>"
  .  "</td>"
  . "</tr>";

$bridge =

    "<tr>"
  .  "<td colspan=2>"
  .   "<img src=layout/images/bridge$graphicsvariant.png width=$pw height=7>"
  .  "</td>"
  . "</tr>";

$fspace =

    "<tr>"
  .  "<td class=in colspan=2 width=$pw height=3>"
  .  "</td>"
  . "</tr>";

$inset_shadow = "<img src=layout/images/shadow.png width=$pw height=7><br>";
$inset_bridge = "<img src=layout/images/bridge$graphicsvariant.png width=$pw height=7><br>";

$postlink_attributes = "class=pk";

/*
 *
 * input frame (command and chat prompt)
 *
 */

$in_page =

    "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">"
  . "<html>"
  . "<head>"
  . "[LOGIN-CHECK]"
  . "<style type=text/css>"
  . "<!--\n"
  . "body{"
  .   "margin:1px 0 1px 1px;"
  .   "padding:0;"
  .   "background-color:#000"
  . "}"
  . "table{"
  .   "margin:0;"
  .   "border:0;"
  .   "padding:0;"
  .   "border-collapse:collapse"
  . "}"
  . "td{"
  .   "font:$kfont5;"
  .   "white-space:nowrap"
  . "}"
  . "a:link,a:active,a:visited,a:hover{"
  .   "color:000;"
  .   "text-decoration:none"
  . "}"
  . "input{"
  .   "border:0 solid;"
  .   "margin:0;"
  .   "padding:0;"
  .   "color:000;"
  .   "background-color:E4E0E8;"
  .   "font:12px courier new,courier,sans-serif"
  . "}"
  . ".b{"
  .   "border-left:1px solid #000;"
  .   "padding:0 8px 0 8px;"
  .   "background-color:c4c0c8;"
  .   "font:10px 'microsoft sans serif',sans-serif"
  . "}"
  . ".d{"
  .   "width:100%;"
  .   "padding:0 0 0 5px;"
  .   "background-color:E4E0E8"
  . "}"
  . $endstyle
  . "<body onload=document.cli.say.focus()>"
  . "<table style=width:100%;height:21px>"
  . "<form name=cli action=input.php enctype=multipart/form-data method=post onSubmit=cliSubmit()>"
  . "[SECURITY-CODE]"
  . "<tr>"
  . "<td class=d><span title=\"command and chat prompt\"><input style=width:100% type=text name=say value=\"\" maxlength=$maxcharsperpost></span></td>"
  . "<td class=b><a title=\"type your phrase and press enter or click here to send\" href=# onClick=\"cliSubmit();document.cli.submit()\">&lt;&#9496; SAY IT</a></td>"
  . "</tr>"
  . "</form>"
  . "</table>"
  . "</body>"
  . "</html>";

/*
 *
 * This function returns the HTML for a frame holding $contents,
 * with $title being written in an upper box. While $title is mandatory,
 * $content may be void, and if it is, there'll be no frame below the title's box.
 *
 * The $compact flag is used in left frames, to tell this function to keep the frame
 * as narrow as possible, because the left frame isn't usually very wide...
 *
 */

$frame_edge_width = 5;
$frame_edge_indent = 27;

function makeframe ($title, $contents, $compact) {

  global $ofie;
  global $v_high_contrast;
  global $frame_edge_width;
  global $frame_edge_indent;

  if ((is_null ($contents)) || (empty ($contents))) {

    return "<table class=he>"
         .  "<td>"
         .  "</td>"
         . "</table>"
         . "<table class=we>"
         .  "<td class=ic>"
         .   $title
         .  "</td>"
         . "</table>"
         . "<table class=w>"
         .  "<td height=7 valign=top>"
         .   (($ofie == "yes") ? "" : "<img src=layout/images/s.png width=100% height=4>")
         .  "</td>"
         . "</table>";

  }
  else {

    $frame_edge_indent = ($compact) ? $frame_edge_width : $frame_edge_indent;
    $frame_inner_style = ($compact) ? "ig" : "if";

    return "<table class=he>"
         .  "<td>"
         .  "</td>"
         . "</table>"
         . "<table class=we>"
         .  "<tr>"
         .   "<td colspan=3 class=ik>"
         .    $title
         .   "</td>"
         .  "</tr>"
         .  "<tr>"
         .   "<td width=$frame_edge_indent class=ie>"
         .    "<img width=$frame_edge_indent class=h>"
         .   "</td>"
         .   "<td>"
         .    "<table class=ew>"
         .     "<tr>"
         .      "<td class=s6>"
         .       (($ofie == "yes") ? "" : "<img src=layout/images/s.png width=100% height=6>")
         .      "</td>"
         .     "</tr>"
         .     "<tr>"
         .      "<td class=$frame_inner_style>"
         .       $contents
         .      "</td>"
         .     "</tr>"
         .    "</table>"
         .   "</td>"
         .   "<td width=$frame_edge_width class=ie>"
         .    "<img width=$frame_edge_width class=h>"
         .   "</td>"
         .  "</tr>"
         .  "<tr>"
         .   "<td colspan=3 height=$frame_edge_width class=ie>"
         .    "<img height=$frame_edge_width class=h>"
         .   "</td>"
         .  "</tr>"
         . "</table>"
         . "<table class=w>"
         .  "<td height=7 valign=top>"
         .   (($ofie == "yes") ? "" : "<img src=layout/images/s.png width=100% height=6>")
         .  "</td>"
         . "</table>";

  }

}

/*
 *
 * This function returns the HTML for a "decorative" header,
 * used to highlight the title itself when it counts...
 *
 */

function maketopper ($title) {

  global $ofie;

  return "<table class=be>"
       .  "<td>"
       .  "</td>"
       . "</table>"
       . "<table class=w style=\"border:1px solid black;border-top:0\">"
       .  "<td class=tp>"
       .   $title
       .  "</td>"
       . "</table>"
       . "<table class=w>"
       .  "<td height=7 valign=top>"
       .   (($ofie == "yes") ? "" : "<img src=layout/images/s.png width=100% height=6>")
       .  "</td>"
       . "</table>";

}

/*
 *
 * Model: site intro (on top of intro.php, the welcome page)
 *
 */

$site_intro =

  makeframe (

    gmdate ("l F d, Y - g:i a", $epc), false, false

  )

  . "<div style=width:492px;height:320px>"
  .  "<div style=position:relative;top:92px>"
  .   "<img src=layout/images/custom/logo.png width=492 height=228>"
  .  "</div>"
  . "</div>"

  . "<div style=position:absolute;left:78px;top:277px;z-index:1>"
  .  "<div style=overflow:hidden>"
  .   "<img src=layout/images/custom/sitename.png width=400 height=100>"
  .  "</div>"
  . "</div>"

  . "<a target=_top title=\"Surf The Rail\" href=http://www.therail.com/cgi/junction/406>"
  .  "<img src=layout/images/custom/railicon.png ismap width=114 height=31 border=0 style=\""
  .   "position:absolute;left:316px;top:255px\">"
  . "</a>";

/*
 *
 * preparing accessories for right central frame models
 *
 */

$frame_x = - $leftframewidth;
$frame_y = "0";

$gentime =

    "<table class=w>"
  .  "<td class=gt nowrap=nowrap>"
  .   "[EXECUTION-TIME] s."
  .  "</td>"
  .  "<td class=w align=right>"
  .   "<a title=\"CLICK HERE TO GET THE OTHER FRAMES!\" target=_top"
  .   " href={$path_to_postline}/layout/html/frameset.html>"
  .    "this frame is part of the " . ucfirst ($sitename) .  " network"
  .   "</a>"
  .  "</td>"
  . "</table>";

$body_overriding = str_replace

  (

    array (

      "[FRAME-X]",
      "[FRAME-Y]",
      "[F-EXTRA]",
      "[BX]"

    ),

    array (

      $frame_x,
      $frame_y,
      "padding:8px 5px 4px 6px",
      $bx

    ),

    $styles[$style]["[BODY-OVERRIDING]"]

  )

  . "<div style=position:absolute;left:-32px;top:0;z-index:-1>"
  .  "<div style=overflow:hidden>"
  .   "<img src=layout/images/custom/stripes.png width=512 height=400>"
  .  "</div>"
  . "</div>"

  .

(($ofie == "yes")

  ? ""

  :

  (

(($styles[$style]["[APPLYREFLECTION]"])

  ? "<div class=snh>"
  .  "<div class=snr style=background-position:-" . $leftframewidth . "px>"
  .  "</div>"
  . "</div>"
  : ""

  )

  .

(($styles[$style]["[APPLYNAVBSHADOW]"])

  ? "<div class=snh>"
  .  "<div class=sns>"
  .  "</div>"
  . "</div>"
  : ""

  )

  . "<div style=\"width:14px;height:100%;position:fixed;right:0;top:0;z-index:-1;"
  .  "background-image:url(layout/images/revsh.png);"
  .  "background-position:top right;"
  .  "background-repeat:repeat-y\">"
  . "</div>"

  )

  );

/*
 *
 * page models for right central frame
 *
 */

$generic_info_page =

    $page_header

  // emphasized paragraph, as seen in the FAQ page

  . ".par,.tab,.tah{"
  .   "padding:6px;"
  .   "border:1px dashed #$innerframecolor"
  . "}"

  // table entry in inspector and errors list

  . ".tab{"
  .   "background-image:url(layout/images/$graphicsvariant.png)"
  . "}"

  // table header in inspector and errors list

  . ".tah{"
  .   "background-color:$most_text_bkgnd"
  . "}"

  // wide list entry

  . ".list{"
  .   "display:block;"
  .   "margin-bottom:6px;"
  .   "text-align:justify;"
  .   "text-indent:-12px;"
  .   "font:$kfont2;"
  .   "$font_variant"
  . "}"

  // rule entry, as on welcome page

  . ".rule{"
  .   "margin-bottom:6px;"
  .   "text-align:justify;"
  .   "font:$kfont2;"
  .   "$font_variant"
  . "}"

  // stats cartridge, on welcome page

  . ".stats{"
  .   "position:absolute;"
  .   "top:45px;"
  .   "right:" . (($ofie == "yes") ? "23" : "5") . "px;"
  .   "z-index:2;"
  .   "border-bottom:1px solid #$innerframecolor;"
  .   "text-align:right"
  . "}"

  // recent discussions link, on welcome page

  . ".rd{"
  .   "position:absolute;"
  .   "top:45px;"
  .   "left:5px;"
  .   "z-index:2;"
  .   "border-bottom:1px solid #$innerframecolor"
  . "}"

  . $endstyle
  . $body_overriding
  . "<table style=width:100%;height:100%>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[INFO]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=6>"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";

$db_explorer =

    $page_header

  // file count

  . ".fc{"
  .   "font:$kfont4"
  . "}"

  // main table

  . ".mt{"
  .   "width:488px;"
  .   "border-left:1px solid black;"
  .   "border-right:1px solid black;"
  .   "margin-bottom:6px"
  . "}"

  // controls table

  . ".ct{"
  .   "width:488px;"
  .   "margin-bottom:3px"
  . "}"

  // file editing area backgrounds

  . ".xr{"
  .   "background-color:C00000;"
  .   "padding:6px 0 6px 0"
  . "}"

  . ".xy{"
  .   "background-color:FFF000;"
  .   "padding:6px 0 6px 0"
  . "}"

  . ".xg{"
  .   "background-color:C8C8CD;"
  .   "padding:6px 0 6px 0"
  . "}"

  // control box

  . ".cb{"
  .   "height:22px;"
  .   "padding:1px;"
  .   "color:000;"
  .   "background-color:white;"
  .   "border:1px solid black"
  . "}"

  // control box caption

  . ".cc{"
  .   "padding:1px 1px 1px 6px;"
  .   "color:000;"
  .   "background-color:C8C8CD;"
  .   "border-top:1px solid black;"
  .   "border-bottom:1px solid black"
  . "}"

  // coupled control box right-side caption

  . ".cr{"
  .   "padding:1px 1px 1px 6px;"
  .   "color:000;"
  .   "background-color:C8C8CD;"
  .   "border:1px solid black;"
  .   "border-bottom:0 solid"
  . "}"

  // single control box right-side caption

  . ".sr{"
  .   "padding:1px 1px 1px 6px;"
  .   "color:000;"
  .   "background-color:C8C8CD;"
  .   "border:1px solid black;"
  .   "border-left:0 solid"
  . "}"

  // warning table

  . ".wt{"
  .   "width:477px;"
  .   "align:center;"
  .   "margin-bottom:5px"
  . "}"

  // warning box

  . ".wb{"
  .   "padding:1px 6px 1px 6px;"
  .   "color:202020;"
  .   "background-color:D4E0C8;"
  .   "border:1px solid black"
  . "}"

  // windowed code block (to view and edit file contents)

  . ".multilinecb{"
  .   "white-space:normal!important;"
  .   "color:000;"
  .   "background-color:white;"
  .   "border:1px solid black;"
  .   "margin:0;"
  .   "padding:4px;"
  .   "font:$kfont4"
  . "}"

  . $endstyle
  . $body_overriding
  . "<table style=width:100%;height:100%>"
  .  "<tr>"
  .   "<td height=1 valign=top>"
  .    makeframe ("Postline Database Explorer ($dbUser @ $dbName)", false, false)
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[INFO]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "</body>"
  . "</html>";

$forums_index =

    $page_header

  // forum cartridge, forum entry, forum description, forum spacer

  . ".fc{"
  .   "width:54px;"
  .

(($ofie == "yes")

  ?   ""
  :   "background-image:url(layout/images/slot{$graphicsvariant}.png);"

  )

  . "}"

  . ".fe{"
  .   "padding-bottom:6px;"
  .

(($ofie == "yes")

  ?   ""
  :   "background-image:url(layout/images/slot{$graphicsvariant}.png);"
  .   "background-position:-54px;"

  )

  .   "white-space:nowrap"
  . "}"

  . ".fr{"
  .   "padding-left:6px;"
  .   "font:$kfont0;"
  .   "line-height:120%;"
  .   "$font_variant"
  . "}"

  . ".fd{"
  .   "padding-left:7px;"
  .   "font:$kfont5;"
  .   "letter-spacing:1px;"
  .   "line-height:120%;"
  .   "$font_variant"
  . "}"

  . ".tc{"
  .   "padding-bottom:6px;"
  .

(($ofie == "yes")

  ?   ""
  :   "background-image:url(layout/images/slot{$graphicsvariant}.png);"
  .   "background-position:right center;"

  )

  .   "text-align:right"
  . "}"

  . ".sp{"
  .   "height:{$f_icon_spacing}px"
  . "}"

  . $endstyle
  . $body_overriding
  . "<table style=width:100%;height:100%>"
  .  "<tr>"
  .   "<td height=1 valign=top>"
  .    makeframe ("<a class=fl " . link_to_forums ("") . ">. /</a>", false, false)
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[LIST]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=6>"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";

$forum_threads =

    $page_header

  // thread entry

  . ".tc{"
  .   "width:54px;"
  .

(($ofie == "yes")

  ?   ""
  :   "background-image:url(layout/images/slot{$graphicsvariant}.png);"

  )

  . "}"

  . ".te{"
  .   "padding-bottom:6px;"
  .

(($ofie == "yes")

  ?   ""
  :   "background-image:url(layout/images/slot{$graphicsvariant}.png);"
  .   "background-position:-54px;"

  )

  .   "white-space:nowrap"
  . "}"

  . ".tr{"
  .   "padding-left:6px;"
  .   "font:$kfont0;"
  .   "line-height:133%;"
  .   "$font_variant"
  . "}"

  . ".td{"
  .   "padding-left:7px;"
  .   "font:$kfont5;"
  .   "line-height:133%;"
  .   "$font_variant"
  . "}"

  . ".pc{"
  .   "white-space:nowrap;"
  .   "width:80px;"
  .   "padding-bottom:6px;"
  .

(($ofie == "yes")

  ?   ""
  :   "background-image:url(layout/images/slot{$graphicsvariant}.png);"
  .   "background-position:right center;"

  )

  .   "font:$kfont5;"
  .   "$font_variant"
  . "}"

  . ".ev{"
  .   "padding-left:2px;"
  .   "color:$serverloadcolor;"
  .   "font:10px arial,sans-serif;"
  .   "letter-spacing:1px;"
  .   "line-height:12px"
  . "}"

  . ".sp{"
  .   "height:{$t_icon_spacing}px"
  . "}"

  . ".pp{"
  .   "height:{$t_rule_spacing}px;"
  .   "background-image:url(layout/images/div{$graphicsvariant}.png);"
  .   "background-position:center top;"
  .   "background-repeat:repeat-x"
  . "}"

  . ".lr{"
  .   "padding-left:4px"
  . "}"

  . ".si{"
  .   "margin-bottom:6px"
  . "}"

  . $endstyle
  . $body_overriding
  . "<table style=width:100%;height:100%>"
  .  "<tr>"
  .   "<td height=1 valign=top>"
  .    makeframe ("<a class=fl " . link_to_forums ("") . ">. /</a>[FORUM]/ [PAGING]", false, false)
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[LIST]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=7>"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    makeframe ("browsing this forum", "[USERSLIST]", false)
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .

  makeframe (

    "meaning of icons",

    "<table>"
  .  "<td>"
  .   "<table>"
  .    "<tr>"
  .     "<td>"
  .      "<img src=layout/images/threadp.png width=48 height=48>"
  .     "</td>"
  .     "<td class=lr>"
  .      "pinned thread:<br>"
  .      "will stay always on top of the list"
  .     "</td>"
  .    "</tr>"
  .    "<tr>"
  .     "<td>"
  .      "<img src=layout/images/threadl.png width=48 height=48>"
  .     "</td>"
  .     "<td class=lr>"
  .      "locked thread:<br>"
  .      "no further messages are allowed there"
  .     "</td>"
  .    "</tr>"
  .   "</table>"
  .  "</td>"
  .  "<td>"
  .   "<img class=h width=20>"
  .  "</td>"
  .  "<td valign=bottom>"
  .   "<table>"
  .    "<tr>"
  .     "<td>"
  .      "<img src=layout/images/last.png width=24 height=24 class=si>"
  .     "</td>"
  .     "<td class=lr>"
  .      "view thread's history notices"
  .     "</td>"
  .    "</tr>"
  .    "<tr>"
  .     "<td>"
  .      "<img src=layout/images/poll.png width=24 height=24 class=si>"
  .     "</td>"
  .     "<td class=lr>"
  .      "signals that there is a poll"
  .     "</td>"
  .    "</tr>"
  .    "<tr>"
  .     "<td>"
  .      "<img src=layout/images/star.png width=24 height=24 class=si>"
  .     "</td>"
  .     "<td class=lr>"
  .      "link to first unread post"
  .     "</td>"
  .    "</tr>"
  .   "</table>"
  .  "</td>"
  . "</table>", false

  )

  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    makeframe ("<a class=fl " . link_to_forums ("") . ">. /</a>[FORUM]/ [PAGING]", false, false)
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";

$forum_posts =

    $page_header
  . $style_input
  . $style_postaccessories
  . $endstyle
  . $body_overriding
  . "<table style=width:100%;height:100%>"
  .  "<tr>"
  .   "<td height=1 valign=top>"
  .    makeframe ("<a class=fl " . link_to_forums ("") . ">. /</a>[FORUM]/[THREAD]/ [PAGING]", false, false)
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[LIST]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    makeframe ("reading this thread", "[USERSLIST]", false)
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    makeframe ("<a class=fl " . link_to_forums ("") . ">. /</a>[FORUM]/[THREAD]/ [PAGING]", false, false)
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";

$memberlist =

    $page_header
  . $style_input

  // outer table, member informations table

  . ".ot,.mi{"
  .   "width:100%;"
  .   "margin-top:7px;"
  .   "border:1px solid black"
  . "}"

  . ".mi{"
  .   "position:relative"
  . "}"

  // member name cart

  . ".n{"
  .   "border-bottom:1px solid #$serverloadcolor;"
  .   "font:$kfont0;"
  .   "$font_variant"
  . "}"

  // member avatar cell

  . ".mlcell{"
  .   "border:1px solid black;"
  .   "border-left:0;"
  .   "padding:10px;"
  .

(($ofie == "yes")

  ?   "background-image:url(layout/images/trans.gif)"
  :   "background-image:url(layout/images/trans.png)"

  )

  . "}"

  // member list row

  . ".mlrow{"
  .   "width:100%;"
  .   "padding:3px 6px 3px 6px;"
  .   "background-image:url(layout/images/$graphicsvariant.png);"
  .   "font:$kfont2;"
  .   "$font_variant;"
  .   "line-height:125%"
  . "}"

  // member list account ID column

  . ".mid{"
  .   "padding:5px 3px 0 3px;"
  .   "background-image:url(layout/images/$graphicsvariant.png);"
  .   "font:$kfont4;"
  .   "text-align:right"
  . "}"

  // member informations

  . ".micell{"
  .   "width:100%;"
  .   "height:" . (41 + $maxphotoheight) . "px;"
  .   "padding:10px;"
  .   "background-image:url(layout/images/$graphicsvariant.png);"
  .   "font:$kfont3;"
  .   "$font_variant;"
  .   "line-height:125%"
  . "}"

  // nickname shown in photograph frame

  . ".miname{"
  .   "position:absolute;"
  .   "left:5px;"
  .   "top:5px;"
  .   "z-index:1;"
  .   "border:1px solid black;"
  .   "padding:1px 3px 1px 3px;"
  .   "color:808080;"
  .   "background-color:e0e0e0;"
  .   "font:$kfont0;"
  .   "$font_variant;"
  . "}"

  // staff signals holder

  . ".sis{"
  .   "position:absolute;"
  .   "left:-30px;"
  .   "top:" . ($maxphotoheight - 100) . "px;"
  .   "z-index:1"
  . "}"

  // major member signals holder

  . ".sim{"
  .   "position:absolute;"
  .   "left:" . ($maxphotowidth - 140) . "px;"
  .   "top:" . ($maxphotoheight - 140) . "px;"
  .   "z-index:2"
  . "}"

  // ban signals holder

  . ".sib{"
  .   "position:absolute;"
  .   "left:-12px;"
  .   "top:-12px;"
  .   "z-index:3"
  . "}"

  // kick signals holder

  . ".sik{"
  .   "position:absolute;"
  .   "left:3px;"
  .   "top:15px;"
  .   "z-index:4"
  . "}"

  // "wanted" signals holder

  . ".siw{"
  .   "position:absolute;"
  .   "left:" . (($maxphotowidth / 2) - 65) . "px;"
  .   "top:" . (($maxphotoheight / 2) - 155) . "px;"
  .   "z-index:5"
  . "}"

  // "M.I.A." signals holder

  . ".mia{"
  .   "position:absolute;"
  .   "left:" . (($maxphotowidth / 2) - 65) . "px;"
  .   "top:" . (($maxphotoheight / 2) - 155) . "px;"
  .   "z-index:5"
  . "}"

  // signal

  . ".sig{"
  .   "width:150px;"
  .   "height:150px"
  . "}"

  // user title cart

  . ".ut{"
  .   "position:absolute;"
  .   "left:5px;"
  .   "top:" . ($maxphotoheight - 5) . "px;"
  .   "z-index:5;"
  .   "padding:1px 6px 2px 6px;"
  .   "border:1px solid black;"
  .   "color:000;"
  .   "background-color:FFFF80;"
  .   "font:10px arial,sans-serif;"
  .   "text-align:center"
  . "}"

  // photograph holder

  . ".pgh{"
  .   "position:absolute;"
  .   "top:0;"
  .   "left:0;"
  .   "width:" . (20 + $maxphotowidth) . "px;"
  .   "height:" . (20 + $maxphotoheight) . "px;"
  .   "background-image:url($path_to_photos/ancillary/holder.png)"
  . "}"

  // bit about

  . ".ba{"
  .   "padding-left:" . (34 + $maxphotowidth) . "px;"
  .   "padding-bottom:11px;"
  .   "border-bottom:1px dotted #$innerframecolor"
  . "}"

  // links

  . ".mll{"
  .   "font:$kfont1;"
  .   "$font_variant"
  . "}"

  // links list

  . ".linkslist{"
  .   "margin:0;"
  .   "padding-left:" . (50 + $maxphotowidth) . "px"
  . "}"

  // links list entry

  . ".links{"
  .   "text-indent:0"
  . "}"

  // binder holder

  . ".bih{"
  .   "width:63px;"
  .   "height:42px;"
  .   "position:absolute;"
  .   "right:-2px;"
  .   "top:-28px"
  . "}"

  // binder connecting informations paragraph to the list's row above

  . ".binder{"
  .   "width:63px;"
  .   "height:42px"
  . "}"

  . $endstyle
  . $body_overriding
  . "<table style=width:100%;height:100%>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[FORM]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    "[SOPS]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";

$cdimage =

    $page_header
  . $endstyle
  . $body_overriding
  . "<table style=width:100%;height:100%>"
  .  "<tr>"
  .   "<td height=1 valign=top>"
  .    "[TTL]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td align=center style=\"padding:1px;background-color:000\">"
  .    "[IMG]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=7 valign=top>"
  .    (($ofie == "yes") ? "" : "<img src=layout/images/s.png width=100% height=6>")
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    "[INF]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "</body>"
  . "</html>";

$logsmenu =

    $page_header

  // top divisor, bottom divisor

  . ".td,.bd{"
  .   "height:25px"
  . "}"

  // outer table of calendar sheets

  . ".ot{"
  .   "border:2px dashed #$innerframecolor;"
  .   "background-image:url(layout/images/{$graphicsvariant}.png)"
  . "}"

  // inner table of calendar sheets

  . ".it{"
  .   "margin:10px"
  . "}"

  // day label (mon, tue, wed...)

  . ".dl{"
  .   "width:48px;"
  .   "height:32px;"
  .   "border-bottom:1px solid #$innerframecolor;"
  .   "text-align:center"
  . "}"

  // day number

  . ".dn{"
  .   "width:48px;"
  .   "height:48px;"
  .   "font:$kfont0;"
  .   "font-size:24px;"
  .   "line-height:120%;"
  .   "text-align:center"
  . "}"

  // today's marker

  . "a.to:link,a.to:active,a.to:visited,a.to:hover{"
  .   "color:red"
  . "}"

  // day log size

  . ".ds{"
  .   "font:$kfont5"
  . "}"

  // sheets divisor

  . ".ld{"
  .   "height:50px;"
  .   "background-image:url(layout/images/div{$graphicsvariant}.png);"
  .   "background-position:center center;"
  .   "background-repeat:repeat-x"
  . "}"

  . $endstyle
  . $body_overriding
  . "<table style=width:100%;height:100%>"
  .  "<tr>"
  .   "<td valign=top>"
  .    makeframe ("<a class=fl " . link_to_forums ("") . ">. /</a>[DIR]/ [PAGING]", false, false)
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td>"
  .    "[M]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td valign=bottom>"
  .    makeframe ("<a class=fl " . link_to_forums ("") . ">. /</a>[DIR]/ [PAGING]", false, false)
  .

     makeframe (

       "information",

       "Whatever disappears from the top edge of the chat frame is transferred to "
     . "these log files. As people chat, and assuming the management didn't disable "
     . "search functions, logs are progressively indexed for <a target=pst "
     . "href=search.php?where=logs>searching</a> them by means of arbitrary keywords.", false

     )

  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";

$tellme =

    $page_header
  . $style_input

  // title and indications

  . ".et{"
  .   "color:$hov_links_color;"
  .   "font:$kfont0"
  . "}"

  . ".fc{"
  .   "font:$kfont4"
  . "}"

  // outer table of "tellme" form

  . ".ot{"
  .   "margin:7px 0 7px 0;"
  .   "border:2px dashed #$innerframecolor;"
  .   "background-image:url(layout/images/{$graphicsvariant}.png)"
  . "}"

  // inner table of "tellme" form

  . ".it{"
  .   "width:412px;"
  .   "margin:10px"
  . "}"

  . $endstyle
  . $body_overriding
  . "<table style=width:100%;height:100%>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[FORM]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td height=1 valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";

/*
 *
 * preparing accessories for left central frame models
 *
 */

$frame_x = ($ofie) ? -1 : 0;
$frame_y = "0";

$gentime =

    "<span class=gt>"
  .  "[EXECUTION-TIME] s."
  . "</span>";

$body_overriding = str_replace

  (

    array (

      "[FRAME-X]",
      "[FRAME-Y]",
      "[F-EXTRA]",
      "[BX]"

    ),

    array (

      $frame_x,
      $frame_y,
      "border-left:1px solid black;padding:8px 0 4px 4px",
      $bx

    ),

    $styles[$style]["[BODY-OVERRIDING]"]

  )

//. "<div style=position:absolute;left:0;top:0;z-index:-1>"
//.  "<div style=width:" . ($pw + 9 - (($ofie == 'yes') ? 1 : 0)) . "px;overflow:hidden>"
//.   "<img src=layout/images/custom/stripes.png width=728 height=728>"
//.  "</div>"
//. "</div>"

  .

(($ofie == "yes")

  ? ""

  :

  (

(($styles[$style]["[APPLYREFLECTION]"])

  ? "<div class=snh>"
  .  "<div class=snr>"
  .  "</div>"
  . "</div>"
  : ""

  )

  .

(($styles[$style]["[APPLYNAVBSHADOW]"])

  ? "<div class=snh>"
  .  "<div class=sns>"
  .  "</div>"
  . "</div>"
  : ""

  )

  )

  );

/*
 *
 * page models for left central frame
 *
 */

$sidebar =

    $page_header
  . $style_input

  // title strip

  . ".band{"
  .   "height:22px;"
  .   "color:white;"
  .   "background-image:url(layout/images/bd.png);"
  .   "padding:2px 4px 2px 4px;"
  .   "text-align:center;"
  .   "line-height:100%"
  . "}"

  // paragraph

  . ".sp{"
  .   "padding:5px 0 5px 0;"
  .   "text-align:center;"
  .   "$font_variant"
  . "}"

  // note top (header) in mstats

  . ".ntop{"
  .   "height:20px;"
  .   "color:white;"
  .   "font:$kfont5;"
  .   "text-align:center"
  . "}"

  // note middle (body) in mstats

  . ".nmid{"
  .   "padding:5px 8px 5px 8px;"
  .   "color:000;"
  .   "background-color:white;"
  .   "font:$kfont5;"
  .   "line-height:150%"
  . "}"

  . $endstyle
  . $body_overriding
  . "<table style=width:$pw;height:100%>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[FORM]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";

$kprefs =

    $page_header
  . $style_input
  . $endstyle
  . $body_overriding
  . "<table style=width:$pw;height:100%>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[FORM]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";

$postpanel =

    $page_header
  . $style_input
  . $endstyle
  . $body_overriding
  . "<table style=width:$pw;height:100%>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[FORM]"
  .    "[MODCP]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "</body>"
  . "</html>";

$profile =

    $page_header
  . $style_input

  // avatar container (in a cartridge)

  . ".avatar{"
  .   "padding:10px;"
  .   "background-image:url(layout/images/trans.gif)"
  . "}"

  // "major member" strip

  . ".band{"
  .   "height:22px;"
  .   "color:white;"
  .   "background-image:url(layout/images/bd.png);"
  .   "padding:2px 4px 2px 4px;"
  .   "text-align:center;"
  .   "line-height:100%"
  . "}"

  . $endstyle
  . $body_overriding
  . "<table style=width:$pw;height:100%>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[FORM]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";

$pmpanel =

    $page_header
  . $style_input
  . $endstyle
  . $body_overriding
  . "<table style=width:$pw;height:100%>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[FORM]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";

$cdisk =

    $page_header
  . $style_input

  // file names

  . ".cd,a.cd:link,a.cd:visited,a.cd:active,a.cd:hover{"
  .   "padding:0 2px 1px 2px;"
  .   "border:1px solid black;"
  .   "word-spacing:-3px;"
  .   "color:000;"
  .   "font:$kfont5;"
  .   "$font_variant;"
  .   "background-color:white"
  . "}"
  . "a.cd:hover{"
  .   "background-color:orange"
  . "}"

  . $endstyle
  . $body_overriding
  . "<table style=width:$pw;height:100%>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[LIST]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td valign=bottom>"
  .    "[FORM]"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";

$searchresults =

    $page_header

  // in private message lists, left-side cell holding the checkbox

  . ".l{"
  .   "padding:3px"
  . "}"

  // in online members lists, right-side cell holding entry

  . ".r{"
  .   "color:000;"
  .   "background-color:white;"
  .   "padding:2px 2px 2px 4px;"
  .   "font:$kfont5"
  . "}"

  // in online members list, appearence of symbols (chat flag, away flag)

  . ".sym{"
  .   "font-size:18px;"
  .   "line-height:1px;"
  .   "letter-spacing:-4px"
  . "}"

  // in online members list, appearence of known guest names (searchbots etc...)

  . ".kg{"
  .   "color:800"
  . "}"

  // in search results, determines the appearence of links to results

  . ".i{"
  .   "padding:1px 3px 1px 3px;"
  .   "border-bottom:1px dotted black;"
  .   "color:000;"
  .   "background-color:#C8DCFF;"
  .   "font:$kfont5"
  . "}"

  // in search results, determines the appearence of hints

  . ".hn{"
  .   "background-color:white;"
  .   "color:000;"
  .   "padding:1px 2px 0 2px;"
  .   "font:$kfont5"
  . "}"

  // in search results, determines the appearence of authors and dates line

  . ".au{"
  .   "padding:0 3px 1px 3px;"
  .   "border-top:1px dotted black;"
  .   "color:C80000;"
  .   "background-color:white;"
  .   "font:$kfont4;"
  .   "letter-spacing:-1px"
  . "}"

  // in search results, determines the appearence of highlighted parts

  . ".h{"
  .   "border-bottom:1px dotted #$most_text_color"
  . "}"

  . $style_input
  . $endstyle
  . $body_overriding
  . "<table style=width:$pw;height:100%>"
  .  "<tr>"
  .   "<td valign=top>"
  .    "[FORM]"
  .   "</td>"
  .  "</tr>"
  .  "<tr>"
  .   "<td valign=bottom>"
  .    $gentime
  .   "</td>"
  .  "</tr>"
  . "</table>"
  . "[PERMALINK]"
  . "</body>"
  . "</html>";



?>
