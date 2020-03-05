<?php

/*************************************************************************************************

            HSP Postline -- status line rendering

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
define ('bottom_frame', true);

/*
 *
 *      tell 'pquit' function of 'suitcase.php' not to create or update session record:
 *      logged-in users may be 'away' and the status line refresh shouldn't interfere with this
 *
 */

define ('disable_session_creation', true);
define ('disable_session_update', true);

/*
 *
 *      Postline includes
 *
 */

require ('settings.php');
require ('suitcase.php');
require ('sessions.php');

/*
 *
 *      widget includes
 *
 */

require ('widgets/overall/primer.php');
require ('widgets/base62.php');
require ('widgets/dbengine.php');
require ('widgets/strings.php');

/*
 *
 *      clear uninitialized template variables, pre-process template
 *
 */

unset ($c_list);                // online members list container
unset ($c_alone);               // module "you're alone"
unset ($c_users_count);         // module holding total listed users count
unset ($c_users_count_apart);   // same, when not including the viewer
unset ($c_noguests);            // module "no guests"
unset ($c_noguests_guest);      // same, when the viewer is a guest
unset ($c_guests_count);        // module "# unknown"
unset ($c_guests_count_guest);  // same, when the viewer is a guest

$template = ($dfwl == 'yes')    // disable frame width limit

        ? 'layout/html/downline_extended.html'  // extended layout (full width of browser window)
        : 'layout/html/downline.html';          // limited layout (fixed 800px frame width)

$template = (($cdl == 'yes') && (fget ('js', 2, '') == 'on'))

        ? 'layout/html/downline_compact.html'   // compact layout (no icons, no clock)
        : $template;                            // as of above

$template = file_get_contents ($template);
$template = array ('value' => $template, 'inner_variables' => process_template ($template));

/*
 *
 *      initializing output accumulators
 *
 */

$logged_ips = array ($ip);      // IP addresses not to be counted as guests (initially, user IP)
$signals_present = false;       // flag: true if one or more signals were appended to nicknames
$count_registered = 0;          // counts logged-in members and known guests, complessively
$count_shown = 0;               // counts the names to be later shown in the reported list
$more = false;                  // flag: true if more than <$maxdlnames> were to be listed...

/*
 *
 *      adjusting limit for displayed names: double it if the frame can extend to the whole screen
 *
 */

$maxdlnames = ($dfwl == 'yes')

        ? 2 * $maxdlnames
        : 1 * $maxdlnames;

/*
 *
 *      reporting online members: get all recorded sessions, list active ones
 *
 */

foreach (all ('stats/sessions', makeProper) as $s) {

        $beg = intval (valueOf ($s, 'beg'));

        /*
         *
         *      a session record may still appear here because cron_sessions didn't prune it out of
         *      the sessions archive, and it's therefore necessary to also check that the record
         *      hasn't expired, yet...
         *
         */

        if (($beg + $sessionexpiry) >= $epc) {

                /*
                 *
                 *      list nickname only if it doesn't match the viewer's nickname (if logged in)
                 *      or if viewer explicitly chosen to get his/her name listed anyway
                 *
                 */

                $_nick = valueOf ($s, 'nick');

                $will_list = ($_nick == $nick) ? false : true;
                $will_list = ($smmn == 'yes') ? true : $will_list;

                if ($will_list == true) {

                        ++ $count_registered;

                        /*
                         *
                         *      there is, however, a pratical maximum to the names that could be
                         *      ever seen in the status line, given by the width of the browser's
                         *      window: it is possible to alter this limit in 'settings.php'
                         *
                         */

                        if ($count_shown >= $maxdlnames) {

                                $more = true;

                        } // entry could not fit: list limit was reached

                        else {

                                ++ $count_shown;

                                /*
                                 *
                                 *      get data from the session record used to determine the
                                 *      appearence of the nickname in the online members' list:
                                 *      get authorization level to determine default color,
                                 *      get user-specified color if no default is used ($_tint),
                                 *      encode base62 version of the nickname for profile link...
                                 *
                                 */

                                $_code = base62Encode ($_nick);
                                $_auth = valueOf ($s, 'auth');
                                $_tint = intval (valueOf ($s, 'ntint'));
                                $_tint = ($_tint < 0) ? 0 : $_tint;
                                $_tint = ($_tint < count ($ntints)) ? $_tint : count ($ntints) - 1;

                                if ($_tint > 0) {

                                        $color = $ntints[$_tint]['COLOR'];

                                } // nickname color was no default color (user choice)

                                else {

                                        if (in_array ($_auth, $admin_ranks)) {

                                                $color = (valueOf ($s, 'id') == 1)

                                                        ? $ifc_server   // manager
                                                        : $ifc_admin;   // other admins

                                        }

                                        else
                                        if (in_array ($_auth, $mod_ranks)) {

                                                $color = $ifc_mod;      // moderators

                                        }

                                        else {

                                                $color = $ifc_member;   // regular members

                                        }

                                } // nickname color was the default color depending on rank

                                /*
                                 *
                                 *      generate tag to list member and link his/her profile
                                 *
                                 */

                                $f_sig_chat = (valueOf ($s, 'chatflag') == 'on')

                                        ? $template['inner_variables']['M_LIST_ENTRY']['inner_variables']['F_SIG_CHAT']['value']
                                        : voidString;

                                $f_sig_afk = (valueOf ($s, 'afk') == 'yes')

                                        ? $template['inner_variables']['M_LIST_ENTRY']['inner_variables']['F_SIG_AFK']['value']
                                        : voidString;

                                $c_list .= process_hive

                                        (

                                                $template['inner_variables']['M_LIST_ENTRY'],

                                                array

                                                        (

                                                                'H_ENTRY'       => strtoupper ($_nick),
                                                                'P_ENTRY_COLOR' => $color,
                                                                'P_ENTRY_HREF'  => 'profile.php?nick=' . $_code,
                                                                'F_SIG_CHAT'    => $f_sig_chat,
                                                                'F_SIG_AFK'     => $f_sig_afk

                                                        )

                                        );

                                /*
                                 *
                                 *      update flag determining that at least one 'signal' is to
                                 *      be displayed causing the relevant stylesheet parts to be
                                 *      included (by later code)
                                 *
                                 */

                                $signals_present =

                                        (($f_sig_chat == voidString) && ($f_sig_afk == voidString))

                                                ? $signals_present
                                                : true;

                        } // entry could be displayed because the list's limit was not reached

                } // listing this entry as it's not the viewer

                /*
                 *
                 *      add this ip to the list of addresses to be excluded from guests count
                 *
                 */

                $logged_ips[] = valueOf ($s, 'ip');

        } // record still valid (not expired)

} // each session record

/*
 *
 *      set counters to reflect number of registered users online,
 *      and at the moment no guests (but subsequent code may change that)
 *
 */

$count_total = $count_registered;
$count_guests = 0;

/*
 *
 *      if guest sessions are being tracked, count them:
 *
 *            - first, guests in the KGL are parsed and filtered out of the guests array,
 *              and listed as some sort of "fake" registered user, which is in reality a
 *              known guest with no profile, and no account, just a "nickname" in the list...
 *
 *            - then, the $logged_ips array comes to filter out any registered members that have
 *              logged in after starting as a guest session (that hasn't, eventually, been pruned
 *              yet) or to exclude this user's IP address and show him/her the count of guests
 *              OTHER than him/herself...
 *
 */

if ($track_guests == true) {

        /*
         *
         *      read and scan the guests sessions array, in search of any logged IP addresses
         *      (of members who logged in AFTER starting with a guest session record that may still
         *      be in "stats/guests"); also, the IP address of the viewer is never counted (because
         *      the corresponding IP is always included as a first entry of the $logged_ips array),
         *      even if the viewer is a guest (guest counter includes all guests OTHER than viewer)
         *
         */

        $real_guest = array ();

        foreach (all ('stats/guests', makeProper) as $g) {

                /*
                 *
                 *      get IP address from guest session record,
                 *      verify that it's not in the array of logged-in members (or the viewer's IP)
                 *
                 */

                $g_ip = valueOf ($g, 'ip');

                if (in_array ($g_ip, $logged_ips) == false) {

                        $real_guest[] = $g;

                        ++ $count_total;
                        ++ $count_guests;

                } // not the IP of a logged-in member (not a guest record which wasn't pruned yet)

        } // each guest record

        /*
         *
         *      loading and preparing Known Guests List for filtering:
         *      processing is as follows (underscores mark eventual blank spaces)
         *      -----------------------------------------------------------------
         *
         *      $r = "_7f00__(_Localhost_)__"       // original record (each $r)
         *      $subnet = "_7f00__"                 // explode
         *      $name = "_Localhost_)__"            // explode
         *      $subnet = "7f00"                    // trim ($subnet)
         *      $name = "Localhost_)"               // trim ($name)
         *      $name = "Localhost_"                // substr ($name, 0, -1)
         *      $name = "Localhost"                 // rtrim (substr ($name, 0, -1))
         *      $kgl_filters["7f00"] = "Localhost"; // final record (each $kgl_filters)
         *
         */

        $kgl_records = wExplode ("\n", readFrom ('stats/kgl'));
        $kgl_filters = array ();

        foreach ($kgl_records as $r) {

                list ($subnet, $name) = wExplode ('(', $r);

                $subnet = trim ($subnet);
                $name = trim ($name);

                $kgl_filters[$subnet] = rtrim (substr ($name, 0, -1));

        }

        /*
         *
         *      now, scanning the array of guests holding all guest sessions other than those
         *      filtered out by above code, decrease guests counter by 1 every time the session
         *      IP address matches with some known guest subnet, and add corresponding instance
         *      to the $c_list accumulator, unless the name already appears there (the $kg_name
         *      array is in fact kept to track such multiple occurrences)
         *
         */

        $kg_name = array ();

        foreach ($real_guest as $g) {

                /*
                 *
                 *      get IP address from guest session record
                 *
                 */

                $g_ip = valueOf ($g, 'ip');

                foreach ($kgl_filters as $subnet => $name) {

                        /*
                         *
                         *      compare $l digits of the IP address against $subnet,
                         *      where $l is the length of the $subnet string; break on first match
                         *
                         */

                        $l = strlen ($subnet);

                        if (substr ($g_ip, 0, $l) == $subnet) {

                                ++ $kg_name[$name];     // may appear in more than one occurrence
                                ++ $count_registered;   // known guests are part of logged users
                                -- $count_guests;       // but, does no longer count as a guest

                                break;

                        } // known guest match was found

                } // each known guest entry

        } // each guest session

        foreach ($kg_name as $name => $count) {

                if ($count_shown >= $maxdlnames) {

                        $more = true;

                } // entry could not fit: list limit was reached

                else {

                        $kg_entry = ($count == 1)

                                ? $name
                                : $count . blank . $name;

                        $c_list .= process_hive

                                (

                                        $template['inner_variables']['M_GUEST_ENTRY'], array ('H_ENTRY' => $kg_entry)

                                );

                        ++ $count_shown;

                } // entry could be displayed because the list's limit was not reached

        } // each known guest to list

} // guest tracking is enabled

/*
 *
 *      report number of users online and number of guests
 *
 */

if ($count_total == 0) {

        $c_alone = process_hive

                (

                        $template['inner_variables']['M_ALONE'], array ()

                );

} // "you're alone here"

else {

        $viewer_is_counted = ($login == true) ? true : false;
        $viewer_is_counted = ($smmn == 'yes') ? false : $viewer_is_counted;

        if ($viewer_is_counted == true) {

                $c_users_count_apart = process_hive

                        (

                                $template['inner_variables']['M_USERS_COUNT'], array ('P_COUNT' => strval ($count_total))

                        );

        } // been appending number of users online, including the viewer

        else {

                $c_users_count = process_hive

                        (

                                $template['inner_variables']['M_USERS_COUNT_APART'], array ('P_COUNT' => strval ($count_total))

                        );

        } // been appending number of users online, except the viewer

        if ($count_guests == 0) {

                if ($login == true) {

                        $c_noguests = $template['inner_variables']['M_NOGUESTS']['value'];

                } // no guests to show to a logged-in viewer

                else {

                        $c_noguests_guest = $template['inner_variables']['M_NOGUESTS_GUEST']['value'];

                } // no guests to show to a guest viewer, except the viewer

        } // no guests

        else {

                if ($login == true) {

                        $c_guests_count = process_hive

                                (

                                        $template['inner_variables']['M_GUESTS_COUNT'], array ('P_COUNT' => strval ($count_guests))

                                );

                } // a number of guests to show to a logged-in viewer

                else {

                        $c_guests_count_guest = process_hive

                                (

                                        $template['inner_variables']['M_GUESTS_COUNT_GUEST'], array ('P_COUNT' => strval ($count_guests))

                                );

                } // a number of guests to show to a guest viewer, except the viewer

        } // a number of unknown guests had to be reported

} // viewer was not alone ($count_total > 0)

/*
 *
 *      template flags initialization
 *
 */

$display_f_intercom = ($login == true) ? true : false;
$display_f_intercom = ($intercom == voidString) ? false : $display_f_intercom;
$display_f_intercom = (($is_admin == true) || ($is_mod == true)) ? $display_f_intercom : false;
$display_f_havemail = (($login == true) && ($newpm == 'yes')) ? true : false;

/*
 *
 *      template parts initialization
 *
 */

$c_locked = ($all_off)

        ? $template['inner_variables']['M_LOCKED']['value']
        : voidString;

$c_unlocked = ($all_off == false)

        ? $template['inner_variables']['M_UNLOCKED']['value']
        : voidString;

$f_intercom = ($display_f_intercom)

        ? process_hive ($template['inner_variables']['F_INTERCOM'], array ('P_INTERCOM_HREF' => 'result.php?m=' . $intercom))
        : voidString;

$f_intercom_script = ($display_f_intercom)

        ? $template['inner_variables']['F_INTERCOM_SCRIPT']['value']
        : voidString;

$f_havemail = ($display_f_havemail)

        ? $template['inner_variables']['F_HAVEMAIL']['value']
        : voidString;

$f_havemail_script = ($display_f_havemail)

        ? $template['inner_variables']['F_HAVEMAIL_SCRIPT']['value']
        : voidString;

$f_warnings = (($display_f_intercom) || ($display_f_havemail) || ($is_admin) || ($is_mod))

        ? $template['inner_variables']['F_WARNINGS']['value']
        : voidString;

$f_signals = ($signals_present)

        ? $template['inner_variables']['F_SIGNALS']['value']
        : voidString;

$c_list = ($more == false)

        ? $c_list
        : $c_list . $template['inner_variables']['M_MORE']['value'];

$f_rsvd_css = (($is_admin) || ($is_mod))

        ? $template['inner_variables']['F_RSVD_CSS']['value']
        : voidString;

$f_rsvd = (($is_admin) || ($is_mod))

        ? $template['inner_variables']['F_RSVD']['value']
        : voidString;

/*
 *
 *      page output initialization
 *
 */

$downline = process_hive

        (

                $template,

                array

                        (

                                'C_LOCKED'              => $c_locked,
                                'C_UNLOCKED'            => $c_unlocked,
                                'F_WARNINGS'            => $f_warnings,
                                'F_SIGNALS'             => $f_signals,
                                'F_INTERCOM'            => $f_intercom,
                                'F_INTERCOM_SCRIPT'     => $f_intercom_script,
                                'F_HAVEMAIL'            => $f_havemail,
                                'F_HAVEMAIL_SCRIPT'     => $f_havemail_script,
                                'C_ALONE'               => $c_alone,
                                'C_USERS_COUNT'         => $c_users_count,
                                'C_USERS_COUNT_APART'   => $c_users_count_apart,
                                'C_NOGUESTS'            => $c_noguests,
                                'C_NOGUESTS_GUEST'      => $c_noguests_guest,
                                'C_GUESTS_COUNT'        => $c_guests_count,
                                'C_GUESTS_COUNT_GUEST'  => $c_guests_count_guest,
                                'C_LIST'                => $c_list,
                                'F_RSVD_CSS'            => $f_rsvd_css,
                                'F_RSVD'                => $f_rsvd,
                                'H_CLOCK'               => gmdate ('g:i A', $epc)

                        )

        );

/*
 *
 *      releasing locked session frame, page output
 *
 */

exit (pquit ($downline));

?>
