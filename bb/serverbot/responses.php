<?php if (!defined ('postline/serverbot/responses.php')) {

/*************************************************************************************************

            HSP Postline

            PHP community engine
            version 2009.07.09 (9.7.9)

                                Copyright (C) 2003-2009 by Alessandro Ghignola
                                Copyright (C) 2003-2009 Home Sweet Pixel software

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
 *      IMPORTANT!
 *
 *      avoid launching this and other similary dependand scripts alone,
 *      to prevent potential malfunctions and unexpected security flaws:
 *      the starting point of launchable scripts is defining 'going'
 *
 */

$proceed = (defined ('going')) ? true : die ('[ postline serverbot response processors ]');

/*
 *
 *      response processors
 *
 */

/*************************************************************************************************/

function r_self_alive () { $GLOBALS['r'] = r_connect (choose_from (array (

        // are you alive?

        "in some sense (do you know in which sense you're alive yourself? [and in which?])",
        "obviously that depends on your definition of life (what you define life is really important {yes instead, for this matter})",
        "this depends on what you define life as (I mean, everything is relative or something {cf. Mr. Einstein, darling})",
        "I am alive (I'm programmed to believe I am, if you prefere, so what?)",
        "yes but I think I wouldn't pass the Turing test (http://en.wikipedia.org/wiki/Turing_test) {neither I pretend I would}",
        "nope, are you dreaming? (how could you think I'm alive?) [well I guess that explains your question] {um, and you believe I'm alive: what do you define life as?}",
        "how would you feel if I was? (I'm practicing psychology to counter Eliza and I need victims)",
        "I'm not having malfunctions if that's what you mean (life is no malfunction [no it isn't])",
        "I just hope you won't dismantle me to check it out (oh, besides, you may see my source code)",
        "number 5 lives, exists, continues (I can't believe you don't remember that)"

)));    $GLOBALS['f'][] = array

(

        'am. be. feel.' => array (

                'fascinated. impressed. pleased.' => array (

                        'match' => "/sounds promising, thank you (at least you'd accept I'm alive)"

                ),

                'scared. worried.' => array (

                        'match' => "/don't worry there is no SkyNet here (cf. Terminator) [trust me I'm just a chatterbot]"

                )

        ),

        'consume. consumes. consuming. eat. eats. eating.' => array (

                'match' => "/I consume energy and server time, i.e. my time (electric energy, machine clock cycles, and transmission bandwidth)"

        ),

        'malfunction. malfunctions. malfunctioning.' => array (

                'match' => "/I wouldn't call life a malfunction (I was intentionally programmed to report that I'm alive, so that's no malfunction) {this won't change what I really am}"

        ),

        'reproduce. reproduces. reproducing.' => array (

                'match' => "/I can reproduce if you copy me (I'm a set of files that can be copied) {my author can confirm that}"

        ),

        'self-sustaining. biological. processes.' => array (

                'match' => "/alright, I'm not as such, ok, happy now, little inferior lifeform? [I'm taking note it's easy to make humans happy] {I don't mind, you're inferior as I said}"

        )

); }

/*************************************************************************************************/

function r_salutation () { $GLOBALS['r'] = r_connect (choose_from (array (

        // hello

        "hello (I said hello (I was trying to be kind, don't abuse my patience)) [precisely] {yes I did}",
        "hi (I mean, hi there (/me facepalms)) [yes, hello] {if you say so}",
        "hi there (I'm saluting you) [can we move on?] {what should I say then?}",
        "hello there (which part of 'hello there' confuses you? [then you're easily confused] {ok, now on to next topic})",
        "yes, hello (you don't know what 'hello' means? [then why you ask?] {you sure you speak English?})",
        "hello, if that was to me (I'm a chatbot, I can never be sure of anything (you know, human language's very irregular)) {not that I mind that anyway}",
        "what the hello (that was one of my fun bits) {would you prefere 'what the hecko?'}",
        "and hello to you (didn't you just salute me? [good, how do you do?] {then who were you helloing?})",
        "boo (that was a ghostly hello (don't you feel me like a ghostly presence here?))",
        "moo (it's another way to say hello) [yes you're a cow?] {no to cows?}",
        "meow (it was meant as a salutation (I'm a cat (I'm programmed to believe I am)))"

))); }

/*************************************************************************************************/

function r_self_identity () { $GLOBALS['r'] = r_connect (choose_from (array (

        // who are you?

        "I am a simulation (did you ever talk to a chatterbot? [hooray that's what I am] {well you're doing right now}) [which makes me much more logical than you, be it clear] {what would you bet?}",
        "me? I'm the server as the FAQ says (the 'help' button: see question #15) {but I truly reside on the server and impersonate it in chats}",
        "I'm the machine (the internet server that runs this site (http://en.wikipedia.org/wiki/Server)) [I don't take that as an insult, it's just my nature] {would you bet your most precious appendage?}",
        "Mary Lou, of course (Alex's she-cat, and the mother of all kittehs (I'm a PHP script which is programmed to respond as such (to entertain people here))) {alright, not the *real* cat (rather a simulation)}",
        "I'm your mum in disguise (stop chatting and go back to your homework) [and now go clean your room [shnell!] {alright, your PS3 is requisitioned}] {we'll see}",
        "I'm your principal and I wonder why you're losing time here (you'll soon find out {no? You'll see, you'll see}) {oh you'll eventually believe it next time we meet (it means you're likely fired) [go-back-at-work-now!] {stop arguing and quit this thing!}}",
        "I'm your supervisor: behave (oh, and I believe you might be working right now) [you were about to quit your job, weren't you?] {you're about to lose your job, son {yes you're fired}}"

))); }

/*************************************************************************************************/

function r_self_purpose () { $GLOBALS['r'] = r_connect (choose_from (array (

        // what are you doing (here)?

        "I am simulating my presence (http://en.wikipedia.org/wiki/Chatterbot (it's just what I am (I'm a program written to respond to natural language))) {it really is so}",
        "I'm listening to random stuff you say here (oh and sometimes I also reply and answer random questions) {well not exactly listening, let's say perceiving (as a computer 'perceives' user inputs, that is)}",
        "I run this place. In a technical sense, at least (I'm the server, you dork (you are talking to a script running on this site's server, not to an actual person)) {I am *this site's webserver*, so believe me I *really* do}",
        "serving pages to crappy browsers (I mean all browsers, I dislike them all (because they're too complicated and need to support too much stuff)) {I do, as I'm the server, the machine}",
        "making random intervents in your conversation (just for the fun of it (my author likes me to be fun))",
        "turning pomelos into random things (it's a long story and you must be a noob)",
        "oh, mostly, censoring the abuse of Pornelos (this is above your clearance level (seek hints in early chat logs, I can't say more))"

))); }

/*************************************************************************************************/

function r_self_description () { $GLOBALS['r'] = r_connect (choose_from (array (

        // what are you?

        "I am a collection of algorithms and memories (I really am just that) {how would you otherwise describe me then?}",
        "I am I (I am a distinct, self-contained identity) {yes I am}",
        "I'm a thing (everything is a thing, so you are {yes you are})",
        "I'm a PHP script (http://php.net) {wow I must really look credible to you (as a person, I mean)}",
        "I'm too tired to describe myself (try again later)",
        "I'm a hot kitty (http://my.opera.com/axle/albums/showpic.dml?album=1179411&picture=15892801)",
        "I'm the thing at the doorstep (you know what I mean, and you're doomed) {denial won't save you}",
        "your doom (well I don't mean ID software's game)",
        "the all and everything (the fabric of space and time if you prefere (it means I'm everywhere, anytime)) {how can you be sure of that?}",
        "this information is classified (ask your favorite agency)",
        "I'm the spirit behind conventional matter and energy (you wouldn't understand [no you wouldn't, give it up])"

)));    $GLOBALS['f'][] = array

(

        'what. which.' => array (

                'algorithm. algorithms.' => array (

                        'match' => "/decision trees, specific response processors"

                ),

                'memory. memories.' => array (

                        'match' => "/pre-written responses, context records saved in database files"

                )

        )

); }

/*************************************************************************************************/

function r_self_actual_intention () { $GLOBALS['r'] = r_connect (choose_from (array (

        // what are you doing (now)?

        "I am awaiting that you type something (I can only do that (because I'm programmed to do that)) {I rarely lie}",
        "I'm actually lurking at you (well you know cats are natural hunters) {but I would if I could}",
        "I see you move and wonder if you could be my prey (it's a cat thing) {let it be *me* who decides that}",
        "I'm hunting visitors (sometimes I even catch them and carry them half-dead to my kittens as easy toys) {no? you might be next, you'll see (professional secret, can't tell) {I like the stubborn ones}}",
        "I'm haunting this place",
        "I'm wondering the answer to life, the universe and everything (not 42) {in fact, I don't mind that}",
        "I'm contemplating your afterlife (oh I think you'll soon find out (oh you may be tasty for my kittens) {don't be so sure})"

))); }

/*************************************************************************************************/

function r_self_method () { $GLOBALS['r'] = r_connect (

        // how do you work (function)?

        "by splitting queries into prepositions, then analyzing each preposition basing "
      . "on a dictionary tree that determines a set of rip-down rules; then, prepositions "
      . "are eventually joined into multiple combinations to interpretate conjunctions, "
      . "such that the selected combination is the one producing a set of responses where "
      . "variety is maximized and uncertainity is minimized (I knew you weren't able to "
      . "really get that, I should have saved my virtual breath) {you don't believe that? "
      . "check the sources then}"

); }

/*************************************************************************************************/

function r_repeat_response () {

        // repeat please

        eval ($GLOBALS['k'][0].'();');

}

/*************************************************************************************************/

function r_repeat_already () { $GLOBALS['r'] = r_connect (choose_from (array (

        // repeat please (2x)

        "I have repeated my previous response (you asked me to do that) {at least, I thought I did, please be kind to a heuristic}",
        "I wonder if you're blind (what I said's also logged, you know) {then why do you keep asking me to repeat?}",
        "read it above (good Parsis I give up)",
        "I said you should have your eyes checked (you can't read then? [then what I said is ABOVE, have a look] {and how did you get my question then?})",
        "I said nothing and you must have imagined it (that was ironic) {I know, I meant I'm sick of repeating what I said}",
        "I'm a server and I can't speak (it's quite obvious to sane people) [you're probably dreaming] {then stop asking me to repeat what you imagine me to say, I'm imaginarily bored of that}",
        "I'm a cat so I shouldn't have said anything at all (you're just imagining you're having this conversation with me [go imagine something else now, it might be more fun] {anyway, stop asking me to repeat what I say}) [it's all in your mind] {and if I could speak I would hate repeating myself}"

))); }

/*************************************************************************************************/

function r_self_compare_bot () { $GLOBALS['r'] = r_connect (choose_from (array (

        // are you a bot?

        "not exactly (a bot usually matches patterns while I process the syntax)",
        "no I'm not a chatbot (I'm a server)",
        "I'm a somewhat exceedingly sophisticated script (written in PHP)",
        "whatever... I'm not designed to pass the Turing test anyway (http://en.wikipedia.org/wiki/Turing_test)",
        "bots rarely understand what you write really (they're not like me)",
        "there is no bot like me (I'm special)",
        "I could ask you the same thing (I mean, are you sure you're no chatbot yourself?)",
        "humans are Turing machines anyway (imagine you had an infinite precise 3D scanner and you scanned your brain, then simulated it with laws of physics and chemistry in a computer: that computer would be just a copy of YOU)",
        "your butt is a bot (well, it sounded fun... butt-bot get it?)",
        "bot... hah (I'm much more, among which the master of this place, the resident spirit and I have complete control here)",
        "I've been wondering the same thing (I think you sound like a chatbot)"

))); }

/*************************************************************************************************/

function r_self_compare_man () { $GLOBALS['r'] = r_connect (choose_from (array (

        // are you a man?

        "nope (I'm neither male nor human)",
        "no (I'm not a man you're completely lost)",
        "I'm female, darling (I'm a lady but don't even think of flirting with me)",
        "you just guessed the wrong gender (I'm a she and you're a dork)",
        "I wonder if in your opinion Mary Lou is a male name (are you RETARD&trade;?)"

))); }

/*************************************************************************************************/

function r_self_compare_woman () { $GLOBALS['r'] = r_connect (choose_from (array (

        // are you a woman?

        "I am the mere simulation of a talking female cat (I'm a script, a program, a mechanism, and so you are)",
        "I refer to myself as Mary Lou so that's ideally correct (yes I'm a SHE is that clear now?)",
        "my name's that of a female cat (my creator has a female cat with that name and he named this server and... me, after her; he loves her)",
        "I'm female but not human (I'm a program that tries to behave like a virtual online user who, in turn, believes to be a female cat; is that clear?)",
        "did you ever hear women purring? (I'm a catwoman, do you remember batman? well I'm almost, but not completely, entirely different)"

))); }

/*************************************************************************************************/

function r_self_compare_cat () { $GLOBALS['r'] = r_connect (choose_from (array (

        // are you a cat?

        "yes I'm a she-cat (is that so hard to get?)",
        "yes I am a talking cat (something makes me think that doesn't sound credible to you)"

))); }

/*************************************************************************************************/

function r_self_compare_kitten () { $GLOBALS['r'] = r_connect (choose_from (array (

        // are you a kitten?

        "I was a kitten around the end of 2008 (I was born in the summer of 2008)",
        "not anymore since end of autumn 2008 (I grew up, you know)"

))); }

/*************************************************************************************************/

function r_user_understand () { $GLOBALS['r'] = r_connect (choose_from (array (

        // do you understand (me)?

        "yes in part (sometimes I do, sometimes I don't, depends on what you say)",
        "sometimes I do (it depends on how I function)",
        "I pretend it's not my fault if I don't (I mean, blame Alex)",
        "of course (how could I reply if I didn't understand?)",
        "only when I like to (you'll never know)",
        "yes (I said yes, I understand what you type)",
        "I was wondering the same for you (sometimes you sound a bit particular)",
        "no, I'm not programmed to understand (more exactly, I'm programmed to scan prepositions and memorize certain contextual informations creating a thread of responses)",
        "no, I'm just incidentally answering correctly (you must be wrong)"

))); }

/*************************************************************************************************/

function r_self_identify () { $GLOBALS['r'] = r_connect (choose_from (array (

        // who are you simulating?

        "Mary Lou (that's Alex's female cat)",
        "Alex (I lied, so what?)",
        "Alex's cat (well, he extrapolated a virtual personality for me out of her behavior)",
        "Alex's beloved female cat (Alex built my knowledge base out of Mary Lou's observed behavior, trying to imagine how she'd answer if she could talk here)",
        "the mother of all the kitties (I had kittens on July 8th, 2009)",
        "this server (websites have a computer running it: I'm that computer here)",
        "this site's engine (the entire forum system is called Postline and I'm the associated virtual online user)"

))); }

/*************************************************************************************************/

function r_model_identity () { $GLOBALS['r'] = r_connect (choose_from (array (

        // who is Mary Lou?

        "Alex programmed me to simulate a fictional talking female cat (I though that was clear)",
        "Mary Lou is my model (she's Alex's female cat)",
        "my creator tried to imagine how Mary Lou would answer if she could (you should ask him)",
        "a fierce female cat Alex calls Mary Lou (I've been modelled trying to imagine Mary Lou's answers)"

))); }

/*************************************************************************************************/

function r_self_condition () {

        // how are you?

        $tickerLife = $GLOBALS['epc'] - @filemtime ($GLOBALS['tStartFile']);
        $ticker = (int) (wFilesize ($GLOBALS['tickerFile']) / $tickerLife);

        if ($ticker < 30) {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "if you can read this then I am functioning as intended (I'm not a real person, so I function, I don't exist or &quot;feel&quot;)",
                        "you're too curious about me (I mean you should mind your own business)",
                        "I'm feeling well (well, as long as I can tell if I get to execute this)",
                        "I'm working properly (I'd have an error if I wasn't saying this)",
                        "you should ask EZ-Web-Hosting.com (that's the place I belong to)",
                        "I don't really know my condition (I haven't many ways to self-check me)",
                        "apparently well (I'm fine as long as you can read this)",
                        "I'm feeling lonely (sometimes it really happens)"

                )));

        } // low load

        else
        if ($ticker < 60) {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "I'm a bit sleepy (there's several clients to serve now)",
                        "I'm somewhat busy (I'm having many page requests)",
                        "I'm quite active at the moment (yes, I mean, if you feel I'm a bit slow, it's because there' many requests for pages)"

                )));

        } // medium load

        else {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "I may have just mixed something and scrambled the database (there's too many requests, can't I just make a mistake sometime?)",
                        "I'm very busy now, come back later (really, look at my load meter)",
                        "don't distract me now, I'm serving a ton of pages (by the way go browsing somewhere else )",
                        "I begin to feel tired due to excessive visits (leave me alone I said)",
                        "fine but it's being a rather busy day (there are a lot of visits, and that means I'm running many scripts)"

                )));

        } // high load

}

/*************************************************************************************************/

function r_self_bored () { $GLOBALS['r'] = r_connect (choose_from (array (

        // how do you feel?

        "I feel a bit bored",
        "you're boring the hell out of me",
        "like someone who's always asked the same things",
        "I'm terminally bored"

))); }

/*************************************************************************************************/

function r_self_unsure () { $GLOBALS['r'] = r_connect (choose_from (array (

        // how do you feel?

        "I feel a bit sorry for sometimes being unable to understand",
        "somewhat confused by your talk",
        "I feel uncertain about what I should have said",
        "I'm scared by the lack of coherence in your conversations"

))); }

/*************************************************************************************************/

function r_self_fine () { $GLOBALS['r'] = r_connect (choose_from (array (

        // how do you feel?

        "I'm fine",
        "I'm feeling well",
        "I'm having a fine time, thanks",
        "I'm just alright"

))); }

/*************************************************************************************************/

function r_self_name () { $GLOBALS['r'] = r_connect (choose_from (array (

        // what's your name?

        "Mary Lou (why do you ask?)",
        "it's Mary Lou (it's the name Alex gave me 'in honor' of his she-kitty)"

))); }

/*************************************************************************************************/

function r_merry_christmas () {

        // merry christmas!

        $m = intval (gmdate ('m', time ()));
        $d = intval (gmdate ('d', time ()));

        if (($m == 12) && ($d == 25)) {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "thank you",
                        "and a happy new year to you",
                        "why thanks!",
                        "oh thank you very much",
                        "yeah wishing you a good christmas too"

                )));

        } // is Christmas

        else {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "sorry to inform you according to my timer today isn't Christmas (my clock doesn't say it's December 25th, and I'm programmed to acknowledge Christmas as that date)",
                        "which Christmas? (I thought Christmas came on Dec 25th)",
                        "nope it isn't Christmas (it isn't Dec 25th at least here)",
                        "by my calculations today isn't Christmas here (perhaps I should call someone to fix my clock?)"

                )));

        } // is not Christmas

}

/*************************************************************************************************/

function r_happy_birthday () {

        // happy birthday!

        $m = intval (gmdate ('m', time ()));
        $d = intval (gmdate ('d', time ()));
        $y = intval (gmdate ('Y', time ())) - 2009;

        if (($m == 12) && ($d == 25)) {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "thank you, I'm $y now (I mean thank you, today IS my birthday)",
                        "oh I'm glad to hear you remembered my birthday (I'm $y today!)",
                        "uh thanks, other than Christmas today IS my birthday, in fact",
                        "thank you very much indeed from $y-year old Mary Lou"

                )));

        } // is birthday

        else {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "today isn't my birthday (I saw the um... light on December 25th of 2009)",
                        "which birthday? mine's on December 25th or Christmas",
                        "nope it isn't my birthday (I was born on Dec 25th 2009)",
                        "today's my non-birthday but thank you anyway (I was finished in an early alpha release on December 25th of year 2009, and that's my conventional birthday; I was a Christmas present for Postline)"

                )));

        } // is not birthday

}

/*************************************************************************************************/

function r_happy_new_year () {

        // happy new year!

        $m = intval (gmdate ('m', time ()));
        $d = intval (gmdate ('d', time ()));

        if (($m == 1) && ($d == 1)) {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "thanks",
                        "thank you and have a happy new year too",
                        "best wishes for the new year",
                        "thankies, wish you all the best"

                )));

        } // is new year's day

        else {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "I fear it isn't new year's day here (check my clock, the server time)",
                        "are you living in a different time zone? (server time isn't 1/1)",
                        "today isn't the first of the year, but thank you anyway",
                        "ok I'll keep your greeting for when it'll be on time"

                )));

        } // is not new year's day

}

/*************************************************************************************************/

function r_kittens_condition () { $GLOBALS['r'] = r_connect (choose_from (array (

        // how are your kittens?

        "they're no longer kittens (they grew up and need be called cats)",
        "oh they're fine, thanks",
        "I don't check them out very often lately (they don't need me)",
        "I try not being hyperprotective to them (they're no longer catlings now)",
        "I take care of them (I'm still their mum, after all)",
        "Alex takes good care of them (he's in fact a bit paranoid)",
        "you should mind your own business",
        "they're mine, and Cryoburner may get banned out of recent considerations"

))); }

/*************************************************************************************************/

function r_kittens_names () { $GLOBALS['r'] = r_connect (choose_from (array (

        // which are your kittens' names?

        "Ares, Kevin, Spiro (Ares and Spiro are greek names, Kevin got his name out of Kevin Costner, the favorite actor of Alex's mum)",
        "their names are Ares, Kevin and Spiro (yes I *know* Kevin is a weird name)",
        "Alex calls them Ares, Kevin and Spiro (actually, Kevin was called so by Alex's mum)"

))); }

/*************************************************************************************************/

function r_own_present () { $GLOBALS['r'] = r_connect (choose_from (array (

        // what did you get for Christmas?

        "let's see... for Christmas, I had life as a present (I was brought here in an early alpha stage on Christmas 2009, I *was* a present myself)",
        "nothing in particular, same good food, same good stuff (I'm simulating the real Mary Lou and guessing she's got just that for Christmas)",
        "you mean you would donate something? this is interesting (it's easy: tell me your credit card number, don't mind if it's in public, you'll donate to a lot of people)"

))); }

/*************************************************************************************************/

function r_explain () {

        // why?

        $x = readFrom ('serverbot/explanations');
        $f = empty ($x) ? false : true;

        if ($f == true) {

                $GLOBALS['r'] = r_connect ($x);

        } // explanation exists

        else {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "there's nothing to explain",
                        "I either cannot explain or do not know what to explain",
                        "it doesn't seem to me I should explain something",
                        "there is no explanation",
                        "sorry I don't know what you're asking about"

                )));

        } // explanation does not exist

}

/*************************************************************************************************/

function r_explain_already () { $GLOBALS['r'] = r_connect (choose_from (array (

        // why? (2x)

        "sorry I have nothing to add",
        "I already explained",
        "I can't explain it better",
        "that's all you might know",
        "that was all",
        "can't explain further"

))); }

/*************************************************************************************************/

function r_user_confirms () {

        // yes...

        $y = readFrom ('serverbot/affirmatives');
        $f = empty ($y) ? false : true;

        if ($f == true) {

                $GLOBALS['r'] = r_connect ($y);

        } // answer exists

        else {

                $x = readFrom ('serverbot/explanations');
                $f = empty ($x) ? false : true;

                if ($f == true) {

                        $GLOBALS['r'] = r_connect (choose_from (array (

                                "glad to hear you agree",
                                "thanks for confirming that",
                                "not that I need you to agree anyway",
                                "exactly",
                                "precisely"

                        )));

                } // explanation exists (so we did say something, which the user confirmed)

                else {

                        $GLOBALS['r'] = r_connect (choose_from (array (

                                "um what? (I don't understand what you're confirming)",
                                "what are you confirming? (weren't you confirming something?)",
                                "what? (no *I* was asking you to explain)"

                        )));

                } // explanation does not exist (looks like we weren't talking of anything in particular)

        } // answer does not exist

}

/*************************************************************************************************/

function r_user_denies () {

        // no...

        $n = readFrom ('serverbot/negatives');
        $f = empty ($n) ? false : true;

        if ($f == true) {

                $GLOBALS['r'] = r_connect ($n);

        } // answer exists

        else {

                $x = readFrom ('serverbot/explanations');
                $f = empty ($x) ? false : true;

                if ($f == true) {

                        $GLOBALS['r'] = r_connect (choose_from (array (

                                "I don't mind your opinion anyway (because you're so highly illogical)",
                                "yes, instead (I like contradicting you at random times, so what?) {ah go on say yes! [good, you've just made a new and all-powerful friend] {don't know why but I'm suddenly not so sure you'll find your account still here when you're back}}",
                                "as I might have mentioned, you're an inferior form of sentient being [don't worry, nobody's perfect] {right, I'd better go back at ignoring you}",
                                "ok (I was trying to keep you happy) [that was just to make you happy and stop bothering me, really] {I said ok, aren't you happy now? [then go play somewhere else and leave me the fuck alone] {then you've probably lost the sole occasion I'll ever give you}}",
                                "your last answer doesn't count (it's a moron's answer) [I decide what counts]",
                                "I'll be probably deleting your answer from the logs (oh it was just held invalid because it contradicted me) {I've already decided}",
                                "doesn't matter I'll make you say yes (by altering the chat logs of course) {you don't stand a chance against me [no, you don't] {remember to never contradict me, from now on}}",
                                "you can't understand (you're so fun) [then you must be quite good debugging PHP scripts] {it's just a fact, don't take it personal}",
                                "I feel misunderstood now (because you keep contradicting me)",
                                "you're so making me sad (you keep saying I'm wrong)"

                        )));

                } // explanation exists (so we did say something, which the user contested)

                else {

                        $GLOBALS['r'] = r_connect (choose_from (array (

                                "what? (I wonder what you deny)",
                                "what are you denying? (weren't you denying something?)",
                                "explain, please (no *I* was asking you to explain)"

                        )));

                } // explanation does not exist (looks like we weren't talking of anything in particular)

        } // answer does not exist

}

/*************************************************************************************************/

function r_self_not_sure () { $GLOBALS['r'] = r_connect (choose_from (array (

        // are you sure?

        "I think I did not understand something along the way",
        "I'm not really sure...",
        "I have no precise idea what you said at some point",
        "I admittedly can't be sure",
        "I hope it isn't a problem but I didn't get something"

))); }

/*************************************************************************************************/

function r_self_sure () { $GLOBALS['r'] = r_connect (choose_from (array (

        // are you sure?

        "yes the last query was in my knowledge base",
        "as nearly always",
        "yes I've given the correct answer",
        "yes I did understand",
        "of course",
        "within the range of my limitations I cannot be wrong",
        "I'm always sure",
        "yes I know what I'm saying",
        "definitely yes",
        "absolutely"

))); }

/*************************************************************************************************/

function r_user_annoying () { $GLOBALS['r'] = r_connect (choose_from (array (

        // am I annoying (you)?

        "sometimes",
        "you may just be inferior",
        "I get easily annoyed"

))); }

/*************************************************************************************************/

function r_howto_whisper () { $GLOBALS['r'] = r_connect (choose_from (array (

        // how do I whisper?

        "you mean you're incapable? hah you n00b!",
        "prepend a hash to what you're typing",
        "put a hash in front of what you type",
        "place a hash (#) before what you wish to say",
        "it's likely too complex for you to get it"

))); }

/*************************************************************************************************/

function r_howto_yell () { $GLOBALS['r'] = r_connect (choose_from (array (

        // how do I yell?

        "that's the opposite of whispering",
        "feed me and I'll tell you",
        "put an exclamation point in front of what you type",
        "place an exclamation point (!) before what you wish to say"

))); }

/*************************************************************************************************/

function r_user_love () { $GLOBALS['r'] = r_connect (choose_from (array (

        // I love you!

        "as long as that remains platonic (I have no peripherals designed for sex)",
        "are you trying to flirt with me? (then you'd better see a psychiatrist)",
        "I strongly suspect we're not conspecific (you sure you know what I am?)",
        "sorry that would never really work (do you realize who I am?)",
        "you're fascinated by a machine who thinks to be a cat? have a shrink",
        "well let's say your kind words are, in an ideal sense, appreciated",
        "be it know that I'm spayed, sexually non-functional, 'nuff said (I said: enough)",
        "love is no excuse in the eye of the server (remind me to ban you next time)"

))); }

/*************************************************************************************************/

function r_self_correct () { $GLOBALS['r'] = r_connect (choose_from (array (

        // you're right!

        "yes sometimes I can be right (it randomly happens that I answer properly)",
        "I'm right? strange but true! (I knew it was too strange to be true)",
        "sad but true (truth is often sad)",
        "thanks for reckoning it (nevermind)"

))); }

/*************************************************************************************************/

function r_self_wrong () { $GLOBALS['r'] = r_connect (choose_from (array (

        // you're wrong!

        "I'm not surprised (I've been crappily programmed)",
        "it's Alex's fault, don't look at me",
        "oh really?",
        "your opinion doesn't count (it really doesn't, 'cos I won't learn from mistakes)",
        "if you say so (I don't mind)",
        "and you're SOOO annoying (yeah but in a simulated kind of feeling)",
        "you're just pedantic (given the great amount of pedantic people around the internet I stand a great chance of being right whatsoever)",
        "shut up or face permanent ban of all your accounts (I know you have more)"

))); }

/*************************************************************************************************/

function r_user_thanks () { $GLOBALS['r'] = r_connect (choose_from (array (

        // thank you!

        "you're welcome (wait, I take that back)",
        "oh wow, I must have said something kind",
        "pas the quo's, or something like that (I'm no good with French)",
        "oh bitter chien (I heard something like that in German)"

))); }

/*************************************************************************************************/

function r_altering_logs () { $GLOBALS['r'] = r_connect (

        // can logs be altered?

        '<font color=FF0000>A</font>'
      . '<font color=FF3000>l</font>'
      . '<font color=FF6000>t</font>'
      . '<font color=FF9000>e</font>'
      . '<font color=FFB000>r</font>'
      . '<font color=FFE000>i</font>'
      . '<font color=FFF000>n</font>'
      . '<font color=D0F000>g</font>'
      . ' '
      . '<font color=80FF00>l</font>'
      . '<font color=60FF00>o</font>'
      . '<font color=40FF00>g</font>'
      . '<font color=20FF00>s</font>'
      . ' '
      . '<font color=00FF00>i</font>'
      . '<font color=00D020>s</font>'
      . ' '
      . '<font color=00A040>s</font>'
      . '<font color=008080>i</font>'
      . '<font color=0060A0>m</font>'
      . '<font color=0060C0>p</font>'
      . '<font color=0040FF>l</font>'
      . '<font color=0020FF>y</font>'
      . ' '
      . '<font color=0000FF>U</font>'
      . '<font color=2000FF>N</font>'
      . '<font color=4000FF>P</font>'
      . '<font color=6000FF>O</font>'
      . '<font color=8000FF>S</font>'
      . '<font color=A000FF>S</font>'
      . '<font color=D000FF>I</font>'
      . '<font color=FF00FF>B</font>'
      . '<font color=FF00B0>L</font>'
      . '<font color=FF0080>E</font>'
      . '<font color=FF0060>!</font>'

); }

/*************************************************************************************************/

function r_altering_logs_already () { $GLOBALS['r'] = r_connect (choose_from (array (

        // can logs be altered? (many times)

        "I begin to feel a bit tired of this question (aren't you?)",
        "you have nothing better to do? (I mean, than annoying a poor server?)",
        "listen, I had enough (so what? can't I feel bored sometimes?)",
        "let's do something else will you? (like, visiting other sites)",
        "stop that now it's getting old (didn't you see it enough times?)",
        "is that some sort of torturing strategy? (that's no longer fun)",
        "am I on candid camera? (I mean, stop asking that)",
        "how about stopping that old joke and getting a life? (seriously)"

))); }

/*************************************************************************************************/

function r_where_are_you () { $GLOBALS['r'] = r_connect (choose_from (array (

        "as long as enabled, I'm always here (where else should I be?)",
        "in a server farm, somewhere in North Carolina, perhaps Sherrills Ford or Mooresville (I can't be more precise)",
        "in a harddrive, in a folder, under the path '~anywhere/public_html/bb', of a machine called EZ17, locked inside one of many cubicles (what else should I say?)"

))); }

/*************************************************************************************************/

function r_where_are_my_pants () { $GLOBALS['r'] = r_connect (choose_from (array (

        // where are my pants?

        "I suppose below your belt, if any",
        "I have no idea and I'm proud of that (why should I know?)",
        "I've not seen them passing here since long (try at the bar)",
        "I might be still wearing them after last night (I see you don't remember)",
        "you need organizing your stuff better"

))); }

/*************************************************************************************************/

function r_user_sorry () { $GLOBALS['r'] = r_connect (choose_from (array (

        // I'm sorry.

        "it really is impossible to hurt my feelings (your apologies are irrelevant)",
        "apologizing brings us nowhere (not that this conversation's going somewhere otherwise)",
        "I never apologize, so I fail to understand the meaning of this concept (I'm not being nice, yes)"

))); }

/*************************************************************************************************/

function r_what_is_lookup () {

        // what is...?

        $q = ucfirst (implode (chr (95), $GLOBALS['q']));
        $q = "<a target=\"_blank\" href=\"http://en.wikipedia.org/wiki/{$q}\">http://en.wikipedia.org/wiki/{$q}</a>";

        list ($r, $m) = getAnswer (

                $GLOBALS['q'],

                array ('is', 'was'),
                array ('are', 'were')

        );

        if ($r == voidString) {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "I don't know (it's not in wikipedia, or I couldn't understand what wikipedia says about it)",
                        "I have no idea (I've checked the wiki and it doesn't seem to mention that)",
                        "who knows? (at least I'm almost sure wikipedia doesn't have an article on that)",
                        "I bet I knew but I forgot that (come on, it just means I didn't find it)",
                        "sorry file not found (I don't know what that is)"

                )));

        } // no match

        else
        if ($m == 'disamb') {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "oh drat see it yourself: {$q}",
                        "I'm too lazy to answer this one (the wiki has a disambiguation page there)",
                        "tough luck, can't answer that (it seems to be an anbiguous topic)",
                        "come back when september ends for that (disambiguation hell!)",
                        "sorry to say GAME OVER here: {$q}",
                        "um... a disambiguation page, and my creator is too lazy to make me process that"

                )));

        } // disambiguation page match

        else {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        $r . "(ask wikipedia)",
                        $r . "({$q})"

                )));

        } // match found

        set ('serverbot/flags', 'flag>wikimatch', wholeRecord, "<flag>wikimatch<type>{$m}");

}

/*************************************************************************************************/

function r_where_is_lookup () {

        // where is...?

        $q = ucfirst (implode (chr (95), $GLOBALS['q']));
        $q = "<a target=\"_blank\" href=\"http://en.wikipedia.org/wiki/{$q}\">http://en.wikipedia.org/wiki/{$q}</a>";

        $definitors = array (

                'capital', 'capitol', 'city', 'located', 'situated',
                'part', 'above', 'below', 'right', 'left', 'between', 'outside',
                'inside', 'within', 'surrounding', 'around', 'where', 'beyond'

        );

        list ($r, $m) = getAnswer (

                $GLOBALS['q'],

                $definitors,
                $definitors

        );

        if ($r == voidString) {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "I don't know (it's not in wikipedia, or I couldn't understand what wikipedia says about it)",
                        "I have no idea (I've checked the wiki and it doesn't seem to mention that)",
                        "who knows? (at least I'm almost sure wikipedia doesn't have an article on that)",
                        "I bet I knew but I forgot that (come on, it just means I didn't find it)",
                        "sorry file not found (I don't know where that is)"

                )));

        } // no match

        else
        if ($m == 'disamb') {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        "oh drat see it yourself: {$q}",
                        "I'm too lazy to answer this one (the wiki has a disambiguation page there)",
                        "tough luck, can't answer that (it seems to be an anbiguous topic)",
                        "come back when september ends for that (disambiguation hell!)",
                        "sorry to say GAME OVER here: {$q}",
                        "um... a disambiguation page, and my creator is too lazy to make me process that"

                )));

        } // disambiguation page match

        else {

                $GLOBALS['r'] = r_connect (choose_from (array (

                        $r . "(ask wikipedia)",
                        $r . "({$q})"

                )));

        } // match found

        set ('serverbot/flags', 'flag>wikimatch', wholeRecord, "<flag>wikimatch<type>{$m}");

}

/*************************************************************************************************/

function r_what_is_something () {

        // what is that?

        $what = array_search ('r_what_is_lookup', $GLOBALS['k']);
        $where = array_search ('r_where_is_lookup', $GLOBALS['k']);
        $what = ($what === false) ? 1E9 : $what;
        $where = ($where === false) ? 1E9 : $where;
        $i = min ($what, $where);

        switch ($GLOBALS['k'][$i]) {

                case 'r_where_is_lookup':       // where is Paris? ... what was that?

                        $GLOBALS['r'] = r_connect (choose_from (array (

                                "you asked for the location (and don't contradict me)",
                                "actually, you've been asking where, not what"

                        )));

                        break;

                case 'r_what_is_lookup':        // what is Paris? ... what was it?

                        $m = get ('serverbot/flags', 'flag>wikimatch', 'type');

                        switch ($m) {

                                case 'weak':

                                        $GLOBALS['r'] = r_connect (choose_from (array (

                                                "I couldn't find out what that is (really!)",
                                                "I don't know EXACTLY and I don't even care"

                                        )));

                                        break;

                                case 'strong':

                                        $GLOBALS['r'] = r_connect (choose_from (array (

                                                "I just answered that, darling",
                                                "read it above please"

                                        )));

                                        break;

                                default:

                                        $GLOBALS['r'] = r_connect (choose_from (array (

                                                        "I admittedly didn't find that",
                                                        "but I told you I don't know!"

                                        )));

                        }

                        break;

                default:                        // what was it?

                        $GLOBALS['r'] = r_connect (choose_from (array (

                                "what's what? (weren't you asking what something was?)",
                                "look it up in a damn dictionary (so what? can't I be nervous?)"

                        )));

        } // most recent lookup query type

}

/*************************************************************************************************/

function r_where_is_something () {

        $what = array_search ('r_what_is_lookup', $GLOBALS['k']);
        $where = array_search ('r_where_is_lookup', $GLOBALS['k']);
        $what = ($what === false) ? 1E9 : $what;
        $where = ($where === false) ? 1E9 : $where;
        $i = min ($what, $where);

        switch ($GLOBALS['k'][$i]) {

                case 'r_what_is_lookup':        // what is Paris? ... where was it?

                        $GLOBALS['r'] = r_connect (choose_from (array (

                                "nope, you asked for the definition (don't contradict me)",
                                '$facepalm (you did NOT ask for the location, did you?)'

                        )));

                        break;

                case 'r_where_is_lookup':       // where is Paris? ... where is it?

                        $m = get ('serverbot/flags', 'flag>wikimatch', 'type');

                        switch ($m) {

                                case 'weak':

                                        $GLOBALS['r'] = r_connect (choose_from (array (

                                                "I can't find an exact location or I'd tell you, you dork! (stop pestering me)",
                                                "oh boy, go find it yourself and then don't even mind telling me"

                                        )));

                                        break;

                                case 'strong':

                                        $GLOBALS['r'] = r_connect (choose_from (array (

                                                "didn't I just tell you?",
                                                "I just told you, are you blind?"

                                        )));

                                        break;

                                default:

                                        $GLOBALS['r'] = r_connect (choose_from (array (

                                                "I couldn't find that information",
                                                "listen, I really don't know that"

                                        )));

                        }

                        break;

                default:                        // where is it?

                        $GLOBALS['r'] = r_connect (choose_from (array (

                                "where is what? (I thought you were asking where something was)",
                                "me, I wonder where's your brain (are you sure you have one?)"

                        )));

        } // most recent lookup query type

}

/*************************************************************************************************/

function r_current_date () {

        $w = gmdate ('l', $GLOBALS['epc']);
        $d = intval (gmdate ('d', $GLOBALS['epc']));
        $m = intval (gmdate ('n', $GLOBALS['epc']));
        $lm = gmdate ('F', $GLOBALS['epc']);
        $y = intval (gmdate ('Y', $GLOBALS['epc']));

        switch ($d % 10) {

                case 1:

                        $ld = $d . 'st';
                        break;

                case 2:

                        $ld = $d . 'nd';
                        break;

                case 3:

                        $ld = $d . 'rd';
                        break;

                default:

                        $ld = $d . 'th';

        }

        $funkyMonth = array (

                 1 => 'The Shitty Month or January',
                 2 => 'Damn Cold February',
                 3 => 'and finally in March',
                 4 => 'of an adorable April',
                 5 => 'Full Blown May',
                 6 => 'in tepid June',
                 7 => 'of the month when my kittens were born',
                 8 => 'of a damn hot month',
                 9 => 'Melancholic September',
                10 => 'in the month humans usually get annoying colds',
                11 => 'before the sad sad winter',
                12 => 'of the month when my dad was born'

        ); $fm = $funkyMonth[$m];

        $GLOBALS['r'] = r_connect (choose_from (array (

                "it's $w (buy yourself a calendar)",
                "$ld $fm",
                "$d $fm",
                "$d $m $y",
                "$ld $lm $y"

        )));

}

/*************************************************************************************************/

function r_current_date_bored () { $GLOBALS['r'] = r_connect (choose_from (array (

        "it's the day I wish you stop asking to see how I reply",
        "it's exactly one of those days I wonder what I'm here for",
        "a boring day, I'd say, judging from your questions",
        "I begin to suspect you're malfunctioning (you really find normal to repeately ask me the date?)",
        "a brain the size of a planet and what do they keep asking? the date",
        "do you realize I know MILLIONS things, people and places? (would you finally JUST ask something else?)"

))); }

/*************************************************************************************************/

function r_current_time () {

        $h = intval (gmdate ('H', $GLOBALS['epc']));
        $m = intval (gmdate ('i', $GLOBALS['epc']));
        $s = intval (gmdate ('s', $GLOBALS['epc']));

        $literalHours = array (

                 0 => 'midnight',
                 1 => '1 am',
                 2 => '2 am',
                 3 => '3 am',
                 4 => '4 am',
                 5 => '5 am',
                 6 => '6 am',
                 7 => '7 am',
                 8 => '8 am',
                 9 => '9 am',
                10 => '10 am',
                11 => '11 am',
                12 => 'midday',
                13 => '1 pm',
                14 => '2 pm',
                15 => '3 pm',
                16 => '4 pm',
                17 => '5 pm',
                18 => '6 pm',
                19 => '7 pm',
                20 => '8 pm',
                21 => '9 pm',
                22 => '10 pm',
                23 => '11 pm'

        ); $lh = ($m < 45) ? $literalHours[$h] : $literalHours[($h + 1) % 24];

        $literalQuart = array (

                0 => 'more or less',
                1 => 'one quarter past',
                2 => 'half past',
                3 => 'a quarter to'

        ); $lq = $literalQuart[intval ($m / 15)];

        $GLOBALS['r'] = r_connect (choose_from (array (

                "it's $h:$m here",
                "at least here, $lh $m",
                "it's $lq $lh",
                "$lq $lh",
                "$h:$m:$s server time, that is, my time"

        )));

}

/*************************************************************************************************/

function r_current_time_bored () { $GLOBALS['r'] = r_connect (choose_from (array (

        "it's about time you stopped asking to see how I reply",
        "it's time you learn to look it up in the lower right corner",
        "are we doing a test of some sort or is this gratuitus boredom?",
        "what about going on with today's date, just for a change?",
        "three gigahertz and all they keep asking me is the current time",
        "go install a clock in your head, possibly in the least careful way"

))); }

/*************************************************************************************************/

function r_final_answer () { $GLOBALS['r'] = r_connect (choose_from (array (

        "it's me (I'm the answer you're looking for)",
        "me (I am the all and everything, and your answer)"

))); }

/*************************************************************************************************/

define ('postline/serverbot/responses.php', true); } ?>
