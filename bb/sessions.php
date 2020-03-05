<?php if (!defined ('postline/sessions.php')) {

/*************************************************************************************************

            HSP Postline -- member session initialization

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
 *      avoid launching this or other similarly dependand scripts alone,
 *      to prevent potential malfunctions and unexpected security flaws:
 *      the starting point of launchable scripts is defining 'going'
 *
 */

$proceed = (defined ('going')) ? true : die ('[ postline session initialization ]');

/*
 *
 *      Postline includes
 *
 */

require ('settings.php');
require ('suitcase.php');

/*
 *
 *      widget includes
 *
 */

require ('widgets/overall/primer.php');
require ('widgets/base62.php');
require ('widgets/dbengine.php');
require ('widgets/errors.php');
require ('widgets/locker.php');
require ('widgets/stopwtch.php');
require ('widgets/strings.php');

/*
 *
 *      error messages
 *
 */

$em['under_planned_maintenance'] = 'UNDER PLANNED MAINTENANCE';
$ex['under_planned_maintenance'] =

        "This bulletin board system is undergoing planned maintenance tasks, and has been "
      . "volountary disabled by the management to avoid interferences. This is a strictly "
      . "temporary situation which may be protracting from minutes to a few hours. Please "
      . "accept our apologies and try again later.";

/*
 *
 *      start execution time measurement,
 *      according to concurrent access prevention (see notes around 'lockSession' in 'locker.php')
 *
 *      wait for any other concurrent PHP threads belonging to Postline to complete flushing their
 *      write-behind cache: this way, once we get out of 'waitReadClearance', we will be sure that
 *      no file is in the middle of a write (and so could not be read); later, a 'lockSession' may
 *      occur in one or more points of the scripts, to keep other threads from writing to database
 *      files. 'lockSession', when done the first time in a run, apart from waiting lock clearance
 *      again (but this time it would look for an exclusive lock) will invalidate any cached files
 *      read until that point, to discard possibly outdated informations that could get RE-WRITTEN
 *      over more recently changed files after the lock has been obtained
 *
 */

stopwatchStart ();
waitReadClearance ();

/*
 *
 *      retrieve client IP:
 *
 *      will be used for subnet-based banning, overload prevention, and as a general thing to know;
 *      note that it won't bother wether HTTP_X_FORWARDED_FOR or HTTP_VIA are set among environment
 *      variables through getenv ('HTTP...'), because given the existence of High Anonimity Proxies
 *      such headers can't apparently be trusted; if the REMOTE_ADDR is that of a proxy, it will be
 *      accepted as the IP address of the user; individual proxies must be banned via the Community
 *      Defense Panel
 *
 *      note:
 *      subnet banning always concerns registration, not login via cookie, so it's ok to have this
 *      piece of code set aside of parts that authenticate via cookies ('session.php')
 *
 */

$ip = (empty ($_SERVER['REMOTE_ADDR'])) ? $_ENV['REMOTE_ADDR'] : $_SERVER['REMOTE_ADDR'];
$ip = (empty ($ip)) ? 'ffffffff' : ipEncode ($ip);

/*
 *
 *      session management:
 *
 *      the login cookie may be invalidated by writing the sole string 'out' as its content,
 *      which is what the button to log out does in 'mstats.php'; if it's not so, the login
 *      cookie may not exist, which resolves its content as a void string as of above lines;
 *      in all other cases, the cookie is expected to hold a base62-encoded string formed by
 *      an existing member's ID followed by the MD5 hash of the password; the two fields, in
 *      the encoded string, are divided by a single "greater than" sign
 *
 *            - the reason why the cookie holds the ID rather than the nickname, is that by
 *              doing so this script can avoid a query on the 'members/bynick' file, and so
 *              possibly speed up this frequent task; the drawback of this is that when the
 *              members' database gets defragmented and many IDs may change, several people
 *              will need to perform a new login to update the cookie
 *
 */

$login_cookie_given = ($login_cookie_hold === '') ? false : true;
$login_cookie_given = ($login_cookie_hold === 'out') ? false : $login_cookie_given;

if ($login_cookie_given) {

        /*
         *
         *      session cookie appears to hold valid contents:
         *      decode contents and look for a matching member account...
         *
         */

        list ($c_id, $c_pass) = wExplode ('>', base62Decode ($login_cookie_hold));

        $fields_given = ((isset ($c_id)) && (is_numeric ($c_id))) ? true : false;
        $fields_given = ((isset ($c_pass)) && (is_string ($c_pass))) ? $fields_given : false;

        if ($fields_given) {

                $c_id = intval ($c_id);

                $fields_given = ($c_id <= 0) ? false : true;
                $fields_given = ($c_pass == voidString) ? false : $fields_given;

        }

        if ($fields_given) {

                /*
                 *
                 *      a possibly valid member ID and password were found in the cookie
                 *
                 */

                $db = 'members' . intervalOf ($c_id, $bs_members);
                $mr = get ($db, "id>$c_id", wholeRecord);
                $ok = ($mr == voidString) ? false : true;

                if ($ok) {

                        /*
                         *
                         *      a non-void record was read basing on that ID:
                         *      read corresponding authorizations (rank field) and set flags
                         *
                         */

                        $c_auth = valueOf ($mr, 'auth');
                        $c_is_mod = (in_array ($c_auth, $mod_ranks)) ? true : false;
                        $c_is_admin = (in_array ($c_auth, $admin_ranks)) ? true : false;

                        /*
                         *
                         *      permanent ban check
                         *
                         */

                        if ($c_is_admin == true) {

                                /*
                                 *
                                 *      admins cannot be banned
                                 *
                                 */

                                $allow_login = true;

                        }

                        else {

                                /*
                                 *
                                 *      determining if nickname appears in banned members list
                                 *
                                 */

                                $c_nick = strtolower (valueOf ($mr, 'nick'));
                                $banlist = wExplode ("\n", strtolower (readFrom ('stats/bml')));
                                $allow_login = (in_array ($c_nick, $banlist)) ? false : true;

                        }

                        /*
                         *
                         *      kick out delay check
                         *
                         */

                        $t_lkick = intval (valueOf ($mr, 'kicked_on'));
                        $t_delay = intval (valueOf ($mr, 'kicked_for'));
                        $allow_login = ($t_lkick + $t_delay < $epc) ? $allow_login : false;

                        /*
                         *
                         *      if member is not witheld from logging in via kickout delays, or
                         *      by permanent bans, login may be allowed in three distinct ways:
                         *
                         *            - $man_pass:      for the manager only, the given password is
                         *                              the same as that of the entire SQL database
                         *
                         *            - $md5_pass:      for regular members, MD5 hash of the given
                         *                              password corresponds to known password hash
                         *
                         *            - $tmp_pass:      in case of forgotten password recovery, the
                         *                              password hash corresponds to the has that's
                         *                              been deposited as a temporary password, for
                         *                              the account corresponding to $c_id
                         */

                        if ($allow_login) {

                                $man_pass = (($c_id == 1) && ($c_pass === $dbPass))

                                        ? true
                                        : false;

                                $md5_pass = (($c_id > 1) && ($c_pass === valueOf ($mr, 'pass')))

                                        ? true
                                        : false;

                                $t_passwd = valueOf ($mr, 'temp_pass');
                                $t_expire = intval (valueOf ($mr, 'last_sent')) + 3600;
                                $tmp_pass = (($t_expire > $epc) && ($c_pass === $t_passwd))

                                        ? true
                                        : false;

                                if (($man_pass) || ($md5_pass) || ($tmp_pass)) {

                                        /*
                                         *
                                         *      identity has been verified:
                                         *      allow login, assign login variables for this run
                                         *
                                         */

                                        $login      = true;                     // logged-in status
                                        $id         = $c_id;                    // account ID
                                        $nick       = valueOf ($mr, 'nick');    // nickname
                                        $auth       = $c_auth;                  // rank
                                        $is_admin   = $c_is_admin;              // admin privileges
                                        $is_mod     = $c_is_mod;                // mod privileges
                                        $jtime      = valueOf ($mr, 'reg');     // join date/time
                                        $newpm      = valueOf ($mr, 'newpm');   // new PM flag
                                        $ntint      = valueOf ($mr, 'ntint');   // nickname color
                                        $bcol       = valueOf ($mr, 'bcol');    // balloon color
                                        $is_muted   = valueOf ($mr, 'muted');   // chat mute flag

                                        /*
                                         *
                                         *      process session variables that need processing
                                         *
                                         */

                                        $ilist = wExplode (';', valueOf ($mr, 'ignorelist'));
                                        $is_muted = ($is_muted == 'yes') ? true : false;

                                        /*
                                         *
                                         *      if member is at least a moderator,
                                         *      load the intercom call flag
                                         *
                                         */

                                        if (($is_admin) || ($is_mod)) {

                                                $intercom = intval (valueOf ($mr, 'intercom'));

                                        }

                                        /*
                                         *
                                         *      check for major age promotion,
                                         *      unless it's already a major member
                                         *
                                         */

                                        if (valueOf ($mr, 'major') == 'yes') {

                                                $is_major = true;

                                        }

                                        else {

                                                $is_major = majoragepromotioncheck ($id);

                                                if ($is_major) {

                                                        /*
                                                         *
                                                         *      if member has been, right now,
                                                         *      reaching major age, remember
                                                         *      that the ignore list is void and
                                                         *      that the former isn't valid anymore
                                                         *
                                                         */

                                                        $ilist = array ();

                                                } // clearing ignorelist after promotion to major

                                        } // checking promotion to major

                                } // password match

                        } // login allowed (not banned, not under kickout delay)

                } // referred member record exists

        } // login cookie fields' correctness checked

} // login cookie present and effective (not 'out')

/*
 *
 *      get preferences' cookie flags
 *
 */

$prefs_token_given = $prefs_cookie_hold[6] === 'A' ? true : false;
$prefs_token_given = $prefs_cookie_hold[51] === $prefscfgversion ? $prefs_token_given : false;

if ($prefs_token_given) {

        /*
         *
         *      preference cookie appears to be valid and having the expected layout version
         *
         */

        $style  = intval (substr ($prefs_cookie_hold, 0, 6));   // style index (6 digits)
        $kflags = substr ($prefs_cookie_hold, 7, 44);           // flags (forty-four 'y/n' letters)
        $kfonts = substr ($prefs_cookie_hold, 52);              // user fonts, past the flags

        list

                (

                        $backgr, $kfont0, $kfont1, $kfont2, $kfont3, $kfont4, $kfont5

                ) = explode ('__', $kfonts);

}

$mlfr = ($kflags[1] == 'y') ? 'yes' : '';               // make left frame resizable
$aclf = ($kflags[2] == 'y') ? 'yes' : '';               // always collapse left frame
$ofie = ($kflags[3] == 'y') ? 'yes' : '';               // optimize for IE
$dusc = ($kflags[6] == 'y') ? 'yes' : '';               // do use small caps
$bigs = ($kflags[8] == 'y') ? 'yes' : '';               // I've got a quite bit screen

$dfwl = ($kflags[9] == 'y') ? 'yes' : '';               // disable frame width limit
$hcb = ($kflags[10] == 'y') ? 'yes' : '';               // halve chat bar
$hii = ($kflags[11] == 'y') ? 'yes' : '';               // hide image informations
$csn = ($kflags[12] == 'y') ? 'yes' : '';               // compact side navigation bars
$cnb = ($kflags[13] == 'y') ? 'yes' : '';               // compact top navigation bar
$cdl = ($kflags[14] == 'y') ? 'yes' : '';               // compact status line
$sb1 = ($kflags[15] == 'y') ? 'yes' : '';               // scrollbar coloring matches controls
$sb2 = ($kflags[16] == 'y') ? 'yes' : '';               // no scrollbar coloring
$sbi = ($kflags[17] == 'y') ? 'yes' : '';               // use scrolling backdrop image

if ($login == false) {

        $lffn = '';                                     // not available to guests
        $hhip = '';                                     // not available to guests
        $nspy = '';                                     // not available to guests
        $smmn = '';                                     // not available to guests

}

else {

        $lffn = ($kflags[0] == 'y') ? 'yes' : '';       // left frame follows navigation
        $hhip = ($kflags[4] == 'y') ? 'yes' : '';       // hide help in preview
        $nspy = ($kflags[5] == 'y') ? 'yes' : '';       // don't spy me
        $smmn = ($kflags[7] == 'y') ? 'yes' : '';       // show me my name online

}

/*
 *
 *      clamping selected style index to fit the limits of the settings array
 *
 */

$style = ($style < 0) ? 0 : (($style < count ($styles)) ? $style : count ($styles) - 1);

/*
 *
 *      overriding default (style-dependant) background image, when $backgr from the preferences
 *      form is not a void string; that's why there's a "variable inside a variable" in template
 *      styles for argument at offset 1 of each style entry: [TRUE-BACKGROUND], a pure file name,
 *      is a "secondary" variable contained in "primary" variable [BODY-OVERRIDING]
 *
 */

$true_background = ($backgr == voidString)

        ? 'layout/backgrounds/' . $styles[$style]['[TRUE-BACKGROUND]'] : $backgr;

$styles[$style]['[BODY-BACKGROUND]'] = str_replace

        (

                '[TRUE-BACKGROUND]', $true_background, $styles[$style]['[BODY-BACKGROUND]']

        );

/*
 *
 *      setting up stylesheet modules by filling arguments depending on the selected style
 *
 */

$body_background = $styles[$style]['[BODY-BACKGROUND]'];
$most_text_color = $styles[$style]['[MOST-TEXT-COLOR]'];
$most_text_bkgnd = $styles[$style]['[MOST-TEXT-BKGND]'];
$scroll_bar_trck = $styles[$style]['[SCROLL-BAR-TRCK]'];
$scroll_bar_face = $styles[$style]['[SCROLL-BAR-FACE]'];
$nrm_links_color = $styles[$style]['[NRM-LINKS-COLOR]'];
$hov_links_color = $styles[$style]['[HOV-LINKS-COLOR]'];
$serverloadcolor = $styles[$style]['[SERVERLOADCOLOR]'];
$frameboundcolor = $styles[$style]['[FRAMEBOUNDCOLOR]'];
$innerframecolor = $styles[$style]['[INNERFRAMECOLOR]'];
$graphicsvariant = $styles[$style]['[GRAPHICSVARIANT]'];

/*
 *
 *      depending on $graphicsvariant, assembles a color that may be white or black,
 *      to be given to parts that may be highly readable (caution titles) over the background
 *
 */

$v_high_contrast = $graphicsvariant . $graphicsvariant . $graphicsvariant;

/*
 *
 *      determining if use of small-caps variant is allowed by user:
 *
 *      it looks less readable if clear-type or other smooth algorythms aren't used for tt fonts,
 *      but on the other hand it looks MUCH more readable if they ARE used, so it must be an option
 *
 */

$font_variant = ($dusc == 'yes') ? 'font-variant:small-caps' : '';

/*
 *
 *      this is to balance padding for links in title frames on MSIE:
 *      pratically, such links are only used for paging links in right central frames
 *
 */

$ph_top_padding = ($ofie == 'yes') ? '3' : '2';

/*
 *
 *      MSIE's interpretation of the size of the Trebuchet font, when the font body is 14 pixels,
 *      is slightly bigger than Firefox and Opera... but it will match if brought to 13 pixels
 *
 */

$apply_kfont1mod = ($kfont1 == $default_kfont1) ? true : false;
$apply_kfont1mod = ($ofie == 'yes') ? $apply_kfont1mod : false;

if ($apply_kfont1mod) {

        $kfont1 = str_replace ('14px', '13px', $kfont1);

}

/*
 *
 *      if left frame is made resizable, witheld navigation bar buttons' reflections,
 *      which would be misaligned by a browser-specific amount of pixels that couldn't be patched
 *
 */

if (($mlfr == 'yes') || ($aclf == 'yes')) {

        $styles[$style]['[APPLYREFLECTION]'] = false;

}

/*
 *
 *      clamping selected nickname color index to fit the limits of the settings array
 *
 */

$ntint = ($ntint === voidString) ? 0 : intval ($ntint);
$ntint = ($ntint < 0) ? 0 : (($ntint < count ($ntints)) ? $ntint : count ($ntints) - 1);

/*
 *
 *      clamping selected message frame color index to fit the limits of the settings array
 *
 */

$bcol = ($bcol === voidString) ? $default_bcol : intval ($bcol);
$bcol = ($bcol < -1) ? -1 : (($bcol < count ($bcols)) ? $bcol : count ($bcols) - 1);

/*
 *
 *      get chatframe cookie flags to update status line signs in sessions table (done by 'pquit'):
 *      in lack of such cookie, and by default, the chat frame is off
 *
 */

$cflag = ($cflag_cookie_hold == 'on') ? 'on' : 'off';

/*
 *
 *      effect of global maintenance lock, denoted by the $all_off flag:
 *      set by the Community Defense Panel, makes the entire board unreactive except to the manager
 *
 */

$all_off = (@file_exists ('widgets/sync/system_locker')) ? true : false;
$manager = (($login == true) && ($id == 1)) ? true : false;

if (($all_off == true) && ($manager == false)) {

        die (because ('under_planned_maintenance'));

} // global maintenance lock engaged, but access not performed by the community manager

define ('postline/sessions.php', true); } ?>
