<?php require ('widgets/overall/primer.php'); if (!defined ("$productName/widgets/stopwtch.php")) {

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
 *      settings
 *
 */

$tickerFile = 'widgets/sync/ticker.txt';        // path and name of ticker file
$tStartFile = 'widgets/sync/t_start';           // path and name of timer file
$avgLoadReset = 300;                            // seconds between each reset of the ticker file

/*
 *
 *      globals
 *
 */

$startTime = 0;         // epoch at which stopwatchStart was called last time

/*
 *
 *      execution time and system load calculation:
 *
 *      providing the 'sync' folder is available, allows profiling the time taken by one or more
 *      scripts executing between a call to stopwatchStart and one to stopwatchStop, with respect
 *      to the time those scripts have not been running; to obtain this, it uses a "ticker" file
 *      that gets one character appended for each hundredth of second of the elapsed time; the file
 *      is managed by stopwatchStop, and is deleted every n seconds with n = $avgLoadReset so that
 *      the timer can periodically reset and "adapt" to variations of the load due to the scripts
 *      being elapsed; obviously, each ticker file residing in the 'sync' directory of the calling
 *      script is specific to the script, or group thereof, which the ticker refers to
 *
 */

function stopwatchStart () {

        global $startTime;

        /*
         *
         *      this function retrieves the actual time as the Epoch's second and microseconds,
         *      and stores it as a floating-point value (in seconds) within variable $startTime
         *
         */

        list ($uSec, $sec) = explode (blank, microtime ());
        $startTime = (float) ($sec) + (float) ($uSec);

}

function stopwatchStop () {

        global $avgLoadReset, $tickerFile, $tStartFile;
        global $startTime;

        /*
         *
         *      this function calculates the seconds passed since stopwatch_start was last called,
         *      and manages the "cron/ticker.txt" file, by appending a char to the file for every
         *      100th of a second of the resulting time: the ticker will therefore keep growing in
         *      size until it's periodically "reset", which happens every $avg_load_reset seconds;
         *      the serverLoad function is told to analyze the length of the ticker to determine
         *      how much time was spent in the scripts for which execution time was elapsed
         *
         */

        if (@filemtime ($tStartFile) <= (time () - $avgLoadReset)) {

                @unlink ($tickerFile);          // delete ticker file (reset)
                @touch ($tStartFile);           // create or update this file for reference

        }

        /*
         *
         *      calculate $stopTime as floating-point "seconds.microseconds",
         *      subtract $startTime from it to get the time passed between the two calls
         *
         */

        list ($uSec, $sec) = explode (blank, microtime ());
        $stopTime = (float) ($sec) + (float) ($uSec) - $startTime;

        /*
         *
         *      update ticker file
         *
         */

        $ticks = intval ($stopTime * 100);

        if ($ticks > 0) {

                /*
                 *
                 *      well, normalize to 1000/100th of a second, ie. 10 seconds max,
                 *      to avoid extending the ticker file too much in a single run,
                 *      so to be sure not to eat excessive amounts of disk space in case
                 *      of malfunction...
                 *
                 */

                $ticks = ($ticks > 1000) ? 1000 : $ticks;
                $h = @fopen ($tickerFile, 'a');

                if ($h !== false) {

                        @fwrite ($h, str_repeat ('+', $ticks));
                        @fclose ($h);

                }

        }

        return (sprintf ('%.3f', $stopTime));

}

define ("$productName/widgets/stopwtch.php", true); } ?>
