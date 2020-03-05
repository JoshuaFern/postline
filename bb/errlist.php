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

}

/*
 *
 *      utilities
 *
 */

function dotquad_ip ($h) {

        $a = base_convert (substr ($h, 0, 2), 16, 10);
        $b = base_convert (substr ($h, 2, 2), 16, 10);
        $c = base_convert (substr ($h, 4, 2), 16, 10);
        $d = base_convert (substr ($h, 6, 2), 16, 10);

        return ("$a.$b.$c.$d");

}

/*
 *
 *      replacing all characters matching the following expression with blanks:
 *      URIs of intrusion attempts may contain "tricky" notations to inject HTML or break strings
 *
 */

$allowedCharacterRange = '/[^a-zA-Z0-9\/\~\!\#\(\)\-\+\_\=\[\]\{\}\;\\' . chr (39) . '\:\"\,\.\/\?]/';

/*
 *
 *      retrieving error code to process
 *
 */

$errorCode = fget ('code', 3, voidString);
$errorCode = empty ($errorCode) ? '404' : $errorCode;

/*
 *
 *      processing ignorelist additions
 *
 */

$entry = fget ('i', 1000, voidString);
$doAdd = empty ($entry) ? false : true;
$doAdd = $is_admin == true ? $doAdd : false;

if ($doAdd == true) {

        lockSession ();

        $existingRecord = get ("errors/{$errorCode}-ignorelist", 'request>' . urldecode ($entry), wholeRecord);
        $existingRecord = empty ($existingRecord) ? false : true;

        if ($existingRecord == false) {

                set ("errors/{$errorCode}-ignorelist", newRecord, wholeRecord, '<request>' . urldecode ($entry));

        } // the entry didn't already appear in the ignorelist

} // been adding an entry to the ignorelist

/*
 *
 *      processing ignorelist deletions
 *
 */

$entry = fget ('r', 1000, voidString);
$doRem = empty ($entry) ? false : true;
$doRem = $is_admin == true ? $doRem : false;

if ($doRem == true) {

        lockSession ();
        set ("errors/{$errorCode}-ignorelist", 'request>' . urldecode ($entry), wholeRecord, deleteRecord);

} // been deleting an entry from the ignorelist

/*
 *
 *      displaying list of tracked HTTP errors
 *
 */

$record = array_reverse (all ("errors/{$errorCode}", makeProper));

$info

        = makeframe

        (

                "Listing tracked errors (HTTP code {$errorCode})",

                "Following list shows the latest {$errorspercode} tracked requests that generated "
              . "an HTTP error of code {$errorCode} within the scope tracked by these scripts, "
              . "in reverse chronological order (the first entry is the most recent). "
              . "This kind of error tracking is primarily intended to help finding broken links: "
              . "if the broken link cannot be solved, administrators may decide to send the "
              . "corresponding request URIs to the ignore list, causing Postline to stop tracking "
              . "requests for that exact URI (an URI takes the form of the path to a file held in "
              . "the server's documents root). Provided tracking is enabled, you may obtain error "
              . "records for the following HTTP error codes:<br>"
              . "<ul>"
              . "<li><a href=errlist.php?code=400>400</a> (bad request)</li>"
              . "<li><a href=errlist.php?code=401>401</a> (authorization required)</li>"
              . "<li><a href=errlist.php?code=403>403</a> (forbidden)</li>"
              . "<li><a href=errlist.php?code=404>404</a> (not found)</li>"
              . "<li><a href=errlist.php?code=500>500</a> (internal server error)</li>"
              . "</ul>"
              . "<em>Please note:</em> the ignorelist may be controlled by administrators only.",

                false

        )

        . '<table width=100%>'
        .  '<tr>'
        .   "<td width=52% class=tah>Recorded strings</td>"
        .   "<td width=16% class=tah style=text-align:center>Time stamp</td>"
        .   "<td width=16% class=tah style=text-align:center>Client IP</td>"
        .   "<td width=16% class=tah style=text-align:center>Login</td>"
        .  '</tr>';

if (count ($record) == 0) {

        $info

              .= '<tr>'
              .   "<td colspan=4 class=tab style=text-align:center>(no record)</td>"
              .  '</tr>';

} // no records

else {

        foreach ($record as $r) {

                $requestURI = base64_decode (valueOf ($r, 'request'));
                $printedURI = preg_replace ($allowedCharacterRange, chr (32), $requestURI);
                $referrer = base64_decode (valueOf ($r, 'referrer'));
                $referrer = preg_replace ($allowedCharacterRange, chr (32), $referrer);
                $userAgent = base64_decode (valueOf ($r, 'useragent'));
                $userAgent = preg_replace ($allowedCharacterRange, chr (32), $userAgent);
                $method = base64_decode (valueOf ($r, 'method'));
                $method = preg_replace ($allowedCharacterRange, chr (32), $method);
                $timeStamp = intval (valueOf ($r, 'time'));
                $timeStamp = gmdate ('M j, Y H:i:s', $timeStamp);
                $clientIP = dotquad_ip (valueOf ($r, 'client'));
                $loginNick = valueOf ($r, 'nick');
                $loginNick = empty ($loginNick)

                        ? 'unknown'
                        : "<a target=pst href=profile.php?nick=" . base62Encode ($loginNick) . ">{$loginNick}</a>";

                $info

                        .= '<tr>'
                        .   '<td width=40% class=tab>'
                        .    "Request: {$printedURI}" . (($is_admin == true) ? blank . "<a href=errlist.php?code={$errorCode}&i=" . urlencode (base64_encode ($requestURI)) . ">[ignore]</a>" : voidString) . "<br>"
                        .    "Referrer: {$referrer}<br>"
                        .    "Method: {$method}<br>"
                        .    "Agent: {$userAgent}"
                        .   '</td>'
                        .   "<td width=20% class=tab style=text-align:center>{$timeStamp}</td>"
                        .   "<td width=20% class=tab style=text-align:center>{$clientIP}<br>(<a href=inspect.php?gip=" . valueOf ($r, 'client') . ">find</a>, <a href=nslookup.php?ip={$clientIP}>look-up</a>)</td>"
                        .   "<td width=20% class=tab style=text-align:center>{$loginNick}</td>"
                        .  '</tr>';

        } // each record

} // records present

$info

      .= '<tr>'
      .   '<td height=6>'
      .   '</td>'
      .  '</tr>'
      . '</table>';

/*
 *
 *      displaying relevant ignore list
 *
 */

$record = all ("errors/{$errorCode}-ignorelist", makeProper);

$info

        .= makeframe

           (

                "Current URI ignorelist for HTTP code {$errorCode}",
                "To remove entries from the ignorelist, click the 'remove' link for the "
              . "corresponding entry: Postline error scripts will reprise tracking the "
              . "offending URI when this error code occurs. <em>Please note:</em> the "
              . "ignorelist may be controlled by administrators only. You need administration "
              . "or service management access rights to add or remove entries to and from the "
              . "following list.",

                false

           )

        . '<table width=100%>';

if (count ($record) == 0) {

        $info

              .= '<tr>'
              .   "<td class=tab style=text-align:center>(no record)</td>"
              .  '</tr>';

} // no records

else {

        foreach ($record as $r) {

                $requestURI = base64_decode (valueOf ($r, 'request'));
                $printedURI = preg_replace ($allowedCharacterRange, chr (32), $requestURI);

                $info

                        .= '<tr>'
                        .   '<td class=tab>'
                        .    "Request: {$printedURI}" . (($is_admin == true) ? blank . "<a href=errlist.php?code={$errorCode}&r=" . urlencode (base64_encode ($requestURI)) . ">[remove]</a>" : voidString) . "<br>"
                        .  '</tr>';

        } // each record

} // records present

$info

      .= '<tr>'
      .   '<td height=6>'
      .   '</td>'
      .  '</tr>'
      . '</table>';

/*
 *
 *      template initialization
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
