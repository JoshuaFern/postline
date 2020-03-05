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

define ('going', true);

require ('settings.php');
require ('suitcase.php');
require ('sessions.php');
require ('template.php');

$em['moderation_rights_required'] = "MODERATION RIGHTS REQUIRED";
$ex['moderation_rights_required'] =

        "The link you have been following leads to a section of this system which is restricted "
      . "to members which have been authenticated as moderators or administrators. If you might "
      . "not be seeing this message, please make sure that your login cookie is intact and that "
      . "you are accessing this site from the same domain you used to perform the login.";

/*
 *
 *      this script is generally restricted to administrators and moderators
 *
 */

if (($is_admin == false) && ($is_mod == false)) {

        unlockSession ();
        die (because ('moderation_rights_required'));

} // no access rights

/*
 *
 *      get IP address to lookup, and perform reverse DNS lookup
 *
 */

$lookup = fget ('ip', 1000, voidString);
$ipHex1 = substr (strtoupper (ipEncode ($lookup)), 0, 6);
$ipHex2 = substr (strtoupper (ipEncode ($lookup)), 6, 2);

$info

        = makeframe

        (

                "Reverse DNS look-up for IP address: {$lookup}",
                "=" . blank . '<span style=font:150%/1>' . gethostbyaddr ($lookup) . '</span>' . blank . ', actual look-up result<br>'
              . "=" . blank . '<span style=font:150%/1>' . $ipHex1 . $ipHex2 . '</span>' . blank . ', full hexadecimal IPv4 match<br>'
              . "=" . blank . "<span style=font:150%/1;color:{$hov_links_color}>" . $ipHex1 . '</span>' . blank . ', suggestion for the <a target=pst href=defense.php#knownguests>known guests list</a>',

                false

        );

/*
 *
 *      template initialization
 *
 */

$generic_info_page

        = str_replace

        (

                array

                (

                        '[INFO]',
                        '[PERMALINK]'

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
 *      saving navigation tracking informations for recovery of central frame page upon showing or
 *      hiding the chat frame, and ONLY for that: this URL is confidential and it's not tracked.
 *
 */

include ('setrfcookie.php');

/*
 *
 *      releasing locked session frame, page output
 *
 */

echo (pquit ($generic_info_page));

?>
