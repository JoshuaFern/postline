<?php require ('widgets/overall/primer.php'); if (!defined ("$productName/widgets/errors.php")) {

/*************************************************************************************************

            Copyright (C) 2009 by Alessandro Ghignola

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

/*
 *
 *      template selection and configuration (defaults)
 *
 */

$escapeMessage = false;
$escapeExplain = true;
$errorTemplate = 'widgets/layout/html/errors.html';
$errorExtraCSS = voidString;

/*
 *
 *      functions
 *
 */

function because ($reason) {

        global $em, $ex, $errorHandlers;
        global $escapeMessage, $escapeExplain, $errorTemplate, $errorExtraCSS;

        $bogusCall = (is_string ($em[$reason])) ? false : true;

        /*
         *
         *      unrecoverable error message assembly (using given template):
         *      called generally as die (because ('reason')), with 'reason' as a key within $em
         *
         */

        foreach ($errorHandlers as $h) {

                eval ($h);

        }

        if ($bogusCall) {

                $errorMessage = '[ missing error message ]';
                $errorExplain = '[ no explanation available ]';

        }

        else {

                $errorMessage = ($escapeMessage)

                        ? addslashes ($em[$reason])
                        : $em[$reason];

                $errorExplain = (is_string ($ex[$reason]))

                        ? (($escapeExplain) ? addslashes ($ex[$reason]) : $ex[$reason])
                        : '[ no explanation available ]';

        }

        $text = str_replace

                (

                        array

                                (

                                        '[ERROR-MESSAGE]',
                                        '[ERROR-EXPLAIN]',
                                        '[ERROR-STYLING]'

                                ),

                        array

                                (

                                        $errorMessage,
                                        $errorExplain,
                                        $errorExtraCSS

                                ),

                        @file_get_contents ($errorTemplate)

                );

        return ($text);

}

define ("$productName/widgets/errors.php", true); } ?>
