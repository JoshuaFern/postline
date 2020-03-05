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

/*
 *
 *      postline includes
 *
 */

include ('settings.php');
include ('suitcase.php');
include ('sessions.php');
include ('template.php');

/*
 *
 *      function determining the possibility of an intervent of the serverbot
 *
 */

unset ($lineIndex);
unset ($lineText);
unset ($tellingToServer);

function sbReplyInOrder () {

        /*
         *
         *      read currently available lines (before and after locking)
         *
         */

        $availableLines = all ('serverbot/conversation', asIs);

        /*
         *
         *      at least one line must be in the conversation, for the following to be carried out
         *
         */

        if (count ($availableLines) > 0) {

                /*
                 *
                 *      is someone addressing the last phrase to the serverbot?
                 *
                 */

                $GLOBALS['tellingToServer'] = false;

                $lastLine = strAfter ($availableLines[count ($availableLines) - 1], '>>');
                $lastLineWords = split ('[' . chr (32) . '.,:;!?]', strtolower ($lastLine));

                foreach ($GLOBALS['serverbot_reacts_to'] as $w) {

                        $w = strtolower ($w);

                        if (in_array ($w, $lastLineWords)) {

                                $GLOBALS['tellingToServer'] = true;

                                break;

                        }

                } // each keyword

                if ($GLOBALS['tellingToServer'] == true) {

                        /*
                         *
                         *      yes, so trigger serverbot script but to work on the sole
                         *      last line: this way it'll not be a random intervent of the
                         *      serverbot, but a targetted one, otherwise you may get the
                         *      bot confusingly answering to unrelated things said before
                         *
                         */

                        $GLOBALS['lineIndex'] = count ($availableLines) - 1;
                        $GLOBALS['lineText'] = $availableLines[$GLOBALS['lineIndex']];

                        return (true);

                } // serverbot was directly called into action

                else {

                        /*
                         *
                         *      no, but at this point the serverbot may randomly respond
                         *      to key phrases - to which it didn't yet respond and to
                         *      which it might KNOW a possible response - by scanning
                         *      recorded conversations (stored there by input.php); this
                         *      kind of intervent gets however more likely if someone's
                         *      RECENTLY called the serverbot into action: as time goes by
                         *      from the last intervent's epoch saved to serverbot/counters
                         *      by following code, an intervent gets progressively less
                         *      probable (as the serverbot isn't "feeling" involved in the
                         *      conversation and may not regularly "desturb" conversations)
                         *      upto a minimum of once every 450 refreshes of the chat
                         *      frame per online member who may be watching the chat frame
                         *      (triggering this script), i.e. averagely once every 450
                         *      refreshes, at 1 refresh every 5 seconds, once every 2250 s.
                         *      or 37.5 minutes; then, the serverbot under these
                         *      circumstances picks a random phrase in its
                         *      'serverbot/conversation' file storing upto 6 records
                         *      holding recent phrases to which it still didn't answer,
                         *      and may "answer" - or better - "comment" on one of them,
                         *      providing, still, the serverbot can acknowledge the syntax
                         *
                         */

                        $peopleOnline =

                                count (all ('stats/guests', makeProper)) +
                                count (all ('stats/sessions', makeProper)) - 1;

                        $lastIntervent = intval (get (

                                'serverbot/counters', 'counter>last_intervent', 'epoch'

                        ));

                        $maxRangeTop = 450 * $peopleOnline;
                        $rangeTop = (int) ((($GLOBALS['epc'] - $lastIntervent) * $peopleOnline) / 90);
                        $rangeTop = $rangeTop * $rangeTop * $rangeTop;
                        $rangeTop = ($rangeTop > $maxRangeTop) ? $maxRangeTop : $rangeTop;

                        if (mt_rand (0, $rangeTop) == ((int) ($rangeTop / 2))) {

                                /*
                                 *
                                 *      pick a random line
                                 *
                                 */

                                $GLOBALS['lineIndex'] = mt_rand (0, count ($availableLines) - 1);
                                $GLOBALS['lineText'] = $availableLines[$GLOBALS['lineIndex']];

                                return (true);

                        } // random ("surprise") serverbot intervent

                } // serverbot was not called into action

        } // conversation isn't void

        return (false);

}

/*
 *
 *      serverbot hook:
 *      check serverbot enable switch
 *
 */

$sb = get ('stats/counters', 'counter>serverbot', 'state');
$sb = ($sb === 'on') ? true : false;

if ($sb == true) {

        /*
         *
         *      is serverbot called to reply or randomly deciding to comment on something?
         *
         */

        if (sbReplyInOrder ()) {

                /*
                 *
                 *      the serverbot script must lock the session while posting: lock and check
                 *      back if all conditions still hold true for a serverbot intervent; this is
                 *      mostly to avoid the bot to reply multiple times to a same query, given that
                 *      if another instance locked between the above check and the following
                 *      (identical) check, the first instance will be already processing some query
                 *
                 */

                lockSession ();

                if (sbReplyInOrder ()) {

                        list ($lineAuthor, $lineToProcess) = explode ('>>', $lineText);

                        $lineToProcess = preg_replace ('/\<.*?\>/', voidString, $lineToProcess);

                        include ('serverbot.php');

                        $conversation = all ('serverbot/conversation', asIs);

                        if ($GLOBALS['tellingToServer'] == false) {

                                /*
                                 *
                                 *      in this case nobody called the server, so the sole line
                                 *      to be removed is the one having been processed, leaving
                                 *      the remaining lines available for other such intervents
                                 *
                                 */

                                unset ($conversation[$lineIndex]);

                        } // been removing the random line selected by 'sbReplyInOrder'

                        else {

                                /*
                                 *
                                 *      in this case the last line called the server to reply:
                                 *      it may be a way to attract its attention after telling
                                 *      it something, so what's been asked was presumably the
                                 *      line before the last line, which, after updating the
                                 *      last intervent's epoch, might be soon processed by the
                                 *      other "random" case in subsequent runs
                                 *
                                 */

                                if (count ($conversation) < 2) {

                                        $conversation = array ();

                                }

                                else {

                                        $lineIndex = count ($conversation) - 2;
                                        $conversation = array ($conversation[$lineIndex]);

                                }

                                set (

                                        'serverbot/counters',
                                        'counter>last_intervent', wholeRecord,
                                        '<counter>last_intervent<epoch>' . strval ($epc)

                                );

                        } // been removing all but the line before the last one

                        asm ('serverbot/conversation', $conversation, asIs);

                } // intervent in order after locking

                unlockSession ();

        } // intervent in order before locking

} // the serverbot is enabled

/*
 *
 *      read conversation, split lines, slice their array according to the size of the chat frame
 *
 */

$cLines = intval (fget ('cLines', 2, ''));
$nLines = ($cLines) ? $cLines : (($hcb == 'yes') ? 3 : 6);
$nLines = ($nLines < 3) ? 3 : (($nLines > 12) ? 12 : $nLines);
$rLines = readFrom ('frespych/conversation');
$rLines = (empty ($rLines)) ? array () : wExplode ("<br>\n", $rLines);
$rLines = (count ($rLines) >= $nLines) ? array_slice ($rLines, - $nLines, $nLines) : $rLines;

/*
 *
 *      create base64-encoded output for chatpage.html
 *
 */

$conversation = implode ("<br>\n", $rLines);
$conversation = (empty ($conversation))

        ? base64_encode ('nobody talked here, yet')
        : base64_encode (utf8_encode ($conversation));

/*
 *
 *      page output (XML, read by chatpage.html)
 *
 */

if (substr_count ($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {

        ob_start ('ob_gzhandler');

} die

(

        "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
      . "<!DOCTYPE chrefresh [\n"
      . "\t<!ELEMENT chrefresh (conversation)>\n"
      . "\t<!ELEMENT conversation (#PCDATA)>\n"
      . "]>\n"
      . "<chrefresh>\n"
      . "\t<conversation>\n"
      . "\t\t{$conversation}\n"
      . "\t</conversation>\n"
      . "</chrefresh>"

);



?>
