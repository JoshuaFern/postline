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



define ("going", true);

require ("settings.php");
require ("suitcase.php");
require ("sessions.php");
require ("template.php");

/*
 *
 * FAQ array:
 * questions alternate to answers, as strings in the following array
 *
 */

$faq = array (

  "I fear I've lost my password!",

        "Then I hope you have specified an email address to send it back to, in your profile "
      . "form. If you did, you just have to fill your nickname in the following field and "
      . "click the button, then check your mailbox and you might find a message holding a "
      . "specially-crafted &#171;temporary password&#187;. Go on..."
      . "<form action=sendpw.php enctype=multipart/form-data method=get>"
      . "<p>"
      .  "<input type=text name=nick value=\"type nickname here\">"
      .  "&nbsp;<input type=submit name=submit value=\"mail password\">"
      . "</p>"
      . "</form>"
      . "Once you have been given a temporary password, you may use that "
      . "password to log in and, once you are logged in, to <a href=#q{$chpass_faq_number}>"
      . "change your 'real' password</a>, by using the 'chpass' command with your temporary "
      . "password in place of the old one, but you will have to do that <em>within an hour</em>, "
      . "after which the temporary password will expire. You may keep generating new temporary "
      . "passwords once per day, so in case you failed to use 'chpass' properly in due time, "
      . "simply try again the day after.",

  "I need &#171;top-of-page&#187; links on this page and elsewhere!",

        "Well, I don't. Ever tried pressing the HOME key?<br>"
      . "Yup, at least you'll have learned something...",

  "It looks very cramped, I have no room to read stuff comfortably...",

        "Well, it's designed for 1024x768 screen resolution, as a first thing to note. It may "
      . "resist 800x600 if you send the browser to full-screen mode, by pressing the F11 key, "
      . "but extremely low resolutions, like 640x480, would kill its intended layout. Aside "
      . "of that, you may wonder why the layout does not take the entire window, but is limited to "
      . "a certain, suitable width, determined by the browser window's own width: it's an intended "
      . "restriction, to encourage members to write messages, present images and code fragments, "
      . "such that they might be more likely to fit a sort of pre-agreed width, presumably shared "
      . "by several clients, without causing horizontal scrollbars to appear. There's an option "
      . "to remove the limit in your <a target=pst href=kprefs.php>preferences form</a> (once "
      . "there, check &#171;disable frame width limit&#187;, then click the 'save' button, and "
      . "finally click the 'apply' button) but you'll notice that the default setting for that "
      . "option is to have the limit enabled, exactly <em>to encourage people to endure the "
      . "presence of the limit</em>, so that visitors of this site using a comparable resolution "
      . "will be more likely to read messages the way their authors write them. Rather than "
      . "disabling that limit, you may recover some space by checking other options, to obtain "
      . "a more compact layout for the navigation bars and the gadgets surrounding these pages.",

  "Is this AnywhereBB.com, AnyNowhere.com, or 0x44.com?",

        "Technically, none of the above. Technically, it's on a shared-server IP address. "
      . "However, to make things clear, you may access the site as http://anywherebb.com, which "
      . "is the main domain name pointing to this homestead. The others are parked domains, which "
      . "however can replace anywherebb.com in URLs as well, and are therefore exact equivalents. "
      . "AnyNowhere.com in particular replaced the displayed site name by the end of 2009, out of "
      . "the author's personal matters concerning his born-again hidden part, and his discovery "
      . "about his slightly split personality; ultimately, the 'No' symbolizes the negation of "
      . "a recent past made of inner conflicts, and of certain false aspects of the author's "
      . "way-of-life.",

  "What's this site?",

        "It's <a target=_blank href=http://en.wikipedia.org/wiki/user:Alessandro_Ghignola>my</a> "
      . "hobby. I'm a private programmer, and I often produce some freeware that I'd like "
      . "to share with the rest of the world, to see what's the rest of the world thinking about "
      . "it. So, this site is mostly my outlet, a collection of my works. Yes, I also made all "
      . "the graphics, and the PHP infrastructure that supports the community. This site is "
      . "hosted by <a target=_blank href=http://www.ez-web-hosting.com>EZ Web Hosting</a> and "
      . "costs me about 20 &euro; per month to be kept online. Because it's so cheap, I don't "
      . "need any forms of financial help, and it's perfectly fine for me to give out everything "
      . "for free. Often, I place contents under public licenses that allow others to "
      . "redistribute my stuff, and so do many of the members that present their personal "
      . "projects in this site's forums. Our favorite lecturer, "
      . "&#171;<a href=members.php?present=rc6eLr5eGdHh>Stargazer</a>&#187;, in one of his "
      . "illuminating posts, has further extended my above explanation in a way that deserves "
      . "being mentioned as the true spirit of this site:<br>"
      . "<br>"
      . "<div class=par>"
      . "Put it another way: the HSP pages is all about sharing inspiration and ideas at one of "
      . "the last pages on the internet which does not yet take payment for anything. It's about "
      . "a dream of the internet being a place where money has no power, but is a global source "
      . "of shared inspiration and dreams, ideas, tales and art from users all over the world. "
      . "That's why the internet was made in the first place: not to make money out of it, but to "
      . "use it to share knowledge and ideas. Of course, if you do want to take money for your "
      . "work, then who are we to tell you not to do so? You have your own free mind and are free "
      . "to do whatever you please with your own work."
      . "</div>"
      . "<br>"
      . "In a nutshell, those who contribute to this site's contents are mostly idealists "
      . "overloaded with spare time. Most people who live in the real world (i.e. market) will "
      . "suspect this will end, sooner or later, and it may happen, but I'm strongly intentioned "
      . "to keep this thing going until at least year 2050, basically because time doesn't exist, "
      . "and years are nothing, for a lasting child. There's already little sense to our lives... "
      . "so why couldn't we prefere to keep up something that, in my opinion, makes more sense "
      . "than barely surviving? Yeah, one day we may be attempting to get some money out of our "
      . "works, but I'm sure there will be a difference between attempting to suck all you can "
      . "from the client's wallet and trying to get a really fair compensation.",

  "The colors and the fonts are giving me headaches!",

        "If you have Windows XP&trade;, or something similar, and an LCD monitor, there might be "
      . "an option to make the operating system render the fonts with a more readable style "
      . "(especially when fonts are kept rather small):<br>"
      . "<br>"
      . "<div class=par>"
      . "right-click on a void point, not covered by any icons or windows, of the desktop, and "
      . "then choose the &#171;Properties&#187; option from the popup menu: now, in the resulting "
      . "window, select the &#171;Appearence&#187; tab, and then click the &#171;Effects&#187; "
      . "button. Once there, check for a drop-down list (a combo box) where you might read "
      . "&#171;Standard&#187; and well, if you click on that, you might see that its options are "
      . "in facts two, &#171;Standard&#187; and &#171;ClearType&#187;. Now select "
      . "&#171;ClearType&#187; and click &#171;Ok&#187; in both windows to confirm your change. "
      . "Small fonts might now look a bit more round, and smoother, than before."
      . "</div>"
      . "<br>"
      . "That said, you can change the overall visual style from the <a target=pst "
      . "href=kprefs.php>site preferences form</a>, where your indications will be saved in a "
      . "cookie (so you don't need to have an account here for changing the look and feel of the "
      . "site): apart from precisely specifying the fonts for the different parts of the "
      . "frameset, it's even possible to override the background by specifying the URL of your "
      . "preferred background image (examine the example note in the preferences form to see "
      . "what you may write there).",

  "What is &quot;Frespych&quot;? How do I chat? What's the c-disk?",

        "Frespych is the HTML microchat (or an announcement board, depending on personal "
      . "interpretations), that was first implemented on a version of phpBB and later became "
      . "an integral part of Postline's scripts (when Postline came to life). It's formed "
      . "by the chat frame you can show or hide using the &#171;show chat&#187; button of the "
      . "navigation bar, and by the grey input line that appears under that frame. Frespych's "
      . "input field is also used to send certain commands, like the command to change your "
      . "password, as well as other staff-level commands.",

  "I had registered an account, but it's no longer in the members' list?",

        "Chances are that an admin, or the manager, deleted your account suspecting you to be a "
      . "returning user with a bad reputation, or the account got simply pruned for inactivity. "
      . "Pruning happens when you register, but then you post nothing, and you leave the account "
      . "here without logging in for at least " . intval ($pruneolderthan / 86400) . " days: in "
      . "such a case, if inactive accounts pruning has been enabled, Postline automatically "
      . "removes the account, to shorten the members list and highlight those who really are, or "
      . "have been, active members. Because Postline keeps a log listing all the removed "
      . "accounts, you could check that list (<a href=logs/prunelog.txt>by clicking here</a>) to "
      . "see if it mentions your account. If that link leads to a nonexisting page (a 404 error), "
      . "it means that accounts pruning was never enabled for this community, or that no accounts "
      . "have been pruned yet, or that the log was recently deleted to discard old records.",

  "I've registered an account: now, is my password safe?",

        "Yes, in theory, but it depends on what you do: you must know that the login cookie does "
      . "never disappear from the computer you're using to access this community, UNLESS you want "
      . "it to. If you leave the cookie intact on the computer, anyone who uses that computer and "
      . "surfs this site, will be automatically logged in AS YOU. So, it is important for you, "
      . "whenever you leave this site from a computer that's used NOT ONLY by you, to use the LOG "
      . "OUT link provided in the <a target=pst href=mstats.php>login panel</a>. If you "
      . "do so, the login cookie will be overwritten with an invalidating string, making it "
      . "unusable, and making it impossible to login here with your identity unless someone else "
      . "knew, guessed, or intercepted, your password.",

  "I think my password is really easy to guess, I'd like to change it.",

        "Use the following command, typing it at the chat prompt:<br>"
      . "<br>"
      . "/chpass old=&quot;old password&quot; new=&quot;new password&quot;<br>"
      . "<br>"
      . "...and remember to include the quotes around the new password. Remember to ALSO DOUBLE "
      . "CHECK the password typed between quotes, be sure you can retype that exactly. Now, if "
      . "you don't want others to see the server's answer outputted in public to the chat frame, "
      . "use the /q switch after the new password specification, as in:<br>"
      . "<br>"
      . "/chpass old=&quot;old password&quot; new=&quot;new password&quot; /q<br>"
      . "<br>"
      . "Oh... you'll notice that after changing your password, you will find yourself logged "
      . "out, and you will have to relogin: that's because the cookie that held login "
      . "informations will be outdated and will need to be re-created by re-logging in with the "
      . "new password. It's just that, don't panic...",

  "I'm using Internet Explorer&trade; and I'm having problems...",

        "To properly render the site's <em>generous amount of alpha-blended graphics</em> you "
      . "need at least version 5.5 of that browser. As far as I've seen you might experience two "
      . "problems: several pages will load up very slowly, due to HTC-based preprocessing for "
      . "alpha blending, and at least my IE6/XPclient would block, by default, the site's "
      . "cookies. The solution for cookies' issues is to double click the little \"eye\" icon in "
      . "the browser's status bar (on bottom) and choose to show the browser's privacy report. "
      . "From there, you might right-click on one of the entries coming from this site's domain "
      . "($perma_url), and tell the browser to accept all cookies. Later on this same page, "
      . "you'll find explanations about what the cookies contain exactly, which I do believe to "
      . "be even better than generic privacy policy statements. The first problem - slow page "
      . "loads - has no solution, other than removing alpha blending from the site's engine, but "
      . "that's not being taken in consideration because I love blending, and PNG is a really "
      . "advanced image format for the web that should be honored as it deserves. Generally, I'd "
      . "like to strongly encourage whoever feels like it, and is in control of the computer, to "
      . "install either <a href=http://www.getfirefox.com target=_blank>Firefox</a> or <a "
      . "href=http://www.opera.com target=_blank>Opera</a>, both of which don't suffer of "
      . "that problem, and last but not least, come in several different flavors for many "
      . "popular platforms. Optimization for IE6 leads to downgrading the site's look due to "
      . "difficulties in management of fixed elements in cascading style sheets, and because the "
      . "rendering of alpha-blended backgrounds appears to be impossible.",

  "How do polls work?",

        "Polls, attached to threads in the hope that the person who started that topic INCLUDED "
      . "a precise question either as the topic's title or in the message's text, allow voting "
      . "only for one time: there's no way to get your vote back unless the whole poll is removed "
      . "and re-submitted. Only the person who issues the poll will be able to monitor votes "
      . "without voting: any others have to vote before they could see the results of a poll, "
      . "because if results were shown before voting, they could potentially influence the "
      . "voter's opinion (tendence to vote for the most voted options, tendence to disagree at "
      . "all costs).",

  "How many cookies does this site send me?",

        "A few, but of course none of them is harmful for your privacy. They are:<br>"
      . "<ul>"
      . "<li class=list>{$login_cookie_name}<br>"
      . "...which is the login cookie, and holds a base-62 encrypt of your nickname along with "
      . "a twicely encrypted representation (base62 of an MD5 hash) of your password. However, "
      . "despite all of that encrypting, the login cookie effectively works as a 100% efficient "
      . "key to your account, so I RECOMMEND YOU to use the logout link when you leave a PUBLIC "
      . "COMPUTER: that link will log you out and invalidate the login cookie. The login cookie "
      . "contains no other informations.</li>"
      . "<li class=list>{$prefs_cookie_name}<br>"
      . "...is a set of yes/no flags, styles and fonts specifications reflecting your preferred "
      . "behavior of the site as you left it in the <a target=pst href=kprefs.php>site "
      . "preferences form</a>: along with the following 'cflag' cookie, this couple cookies "
      . "needs not to be encrypted...</li>"
      . "<li class=list>{$frame_cookie_name}<br>"
      . "...is the cookie that remembers which pages you've been seeing most recently in both the "
      . "left-side and the right-side frames (the central frames, not considering the navigation "
      . "bars all around): this cookie allows the site to reload in about the same way you left "
      . "it last time.</li>"
      . "<li class=list>{$cflag_cookie_name}<br>"
      . "...is the cookie informing the system whether you are keeping the chat frame on or off, "
      . "such that your session informations can be updated and others can know when your could, "
      . "and could not, listen to things typed in the chat frame.</li>"
      . "</ul>"
      . "No informations from those cookies is tracked and/or sent to any kind of spambot, third "
      . "party, targeted advertising manager etc... even because it'd be quite hard to guess "
      . "what are your interests from the bare contents of those cookies, which are mostly "
      . "thread/forum identification numbers and cryptic flags. If you don't believe me, you can "
      . "check the contents of the cookies yourself. For example, Firefox and Opera easily "
      . "allow you to monitor all of the received cookies: you will see that their contents "
      . "are base-62 encrypted and look like a sequence of letters and numbers. Base-62 "
      . "encrypting has the only purpose of representing text strings that may contain invalid "
      . "characters for a cookie or for an URL, in a form that's surely matching the recipient's "
      . "(cookie or URL) characters range. It is perfectly possible to decode base-62 cookies' "
      . "contents in a do-it-yourself way, by using the following couple of PHP functions:<br>"
      . "<span style=font-variant:normal;font-size:12px;line-height:13px;letter-spacing:-1px><pre>"
      . "function convert_from_base62 (\$number) {\n"
      . "\n"
      . "  \$k = 1;\n"
      . "  \$result = 0;\n"
      . "\n"
      . "  \$c = 0;\n"
      . "  \$n = strlen (\$number);\n"
      . "\n"
      . "  while (\$c < \$n) {\n"
      . "\n"
      . "    \$d = ord (\$number[\$c]);\n"
      . "\n"
      . "    if ((\$d >= 48) && (\$d <=  57)) \$result += \$k * (\$d - 48);\n"
      . "    if ((\$d >= 97) && (\$d <= 122)) \$result += \$k * (\$d - 97 + 10);\n"
      . "    if ((\$d >= 65) && (\$d <=  90)) \$result += \$k * (\$d - 65 + 36);\n"
      . "\n"
      . "    \$k *= 62;\n"
      . "\n"
      . "    \$c ++;\n"
      . "\n"
      . "  }\n"
      . "\n"
      . "  return \$result;\n"
      . "\n"
      . "}\n"
      . "\n"
      . "function decode (\$string) {\n"
      . "\n"
      . "  \$result = \"\";\n"
      . "  \$c = 0;\n"
      . "\n"
      . "  while (1) {\n"
      . "\n"
      . "    if (\$string[\$c] == \"\") {\n"
      . "\n"
      . "      break;\n"
      . "\n"
      . "    }\n"
      . "\n"
      . "    \$sub = substr (\$string, \$c, 4);\n"
      . "    \$sum = convert_from_base62 (\$sub);\n"
      . "\n"
      . "    \$ch1 = (\$sum % 225) + 31; if (\$ch1 > 31) \$result.= chr (\$ch1);\n"
      . "    \$sum = (int) (\$sum / 225);\n"
      . "    \$ch2 = (\$sum % 225) + 31; if (\$ch2 > 31) \$result.= chr (\$ch2);\n"
      . "    \$sum = (int) (\$sum / 225);\n"
      . "    \$ch3 = (\$sum % 225) + 31; if (\$ch3 > 31) \$result.= chr (\$ch3);\n"
      . "\n"
      . "    \$c += strlen (\$sub);\n"
      . "\n"
      . "  }\n"
      . "\n"
      . "  return \$result;\n"
      . "\n"
      . "}\n"
      . "</pre></span>"
      . "...and then calling the &#171;decode&#187; function passing the cookie's contents (a "
      . "string) as that function's only argument. The return value will be the text contained "
      . "in the cookie. Yeah, of course you must have a working PHP parser.",

  "What's the meaning of the system load indication?",

        "Well, it works this way: every time a page request is made to a script which is part "
      . "of Postline (meaning the status line, the chat input line, and the two parts of the "
      . "central frameset), an internal stopwatch measures how much time passes "
      . "between the moment the script begins executing and the moment the server sends the "
      . "output to the client's browser. That is, approximately, how much time it took for the "
      . "server to execute that script. Now, after measuring that, the said measured time will "
      . "cause a certain file, residing on the server, to be slightly extended in size: this way "
      . "the said file (called &#171;ticker&#187;) keeps growing proportionally to how much time "
      . "has been spent by the server while executing Postline scripts for all logged-in users "
      . "and all guests. Every " . ((int) ($avg_load_reset) / 60) . " minutes the ticker file "
      . "is voided and a new span of time begins. To conclude, the &#171;system load&#187; "
      . "indication is computed as the percent of the time spent executing Postline scripts with "
      . "respect to the total time that passed since the ticker was voided for the last time. "
      . "It has very little to do with the effective, physical load of the server's CPU, since "
      . "many PHP scripts, even among those of this board, may be running simultaneously, and "
      . "thus their time slices may be overlapping most of the time: therefore, the indication "
      . "will frequently be overestimated. Because of this, there would be nothing surprising in "
      . "even seeing that meter go over 100%. Yet, when the load goes high, response times might "
      . "get effectively longer, and when that happens, the indication shows whether it's because "
      . "of your connection or of the board's server.",

  "Who is $servername?",

        "$servername is the computer, or the network, serving you these pages and executing "
      . "these scripts to keep the community going. $servername only occasionally writes "
      . "responses and warnings to the public chat frame. Some of its messages are temporary, "
      . "i.e. they aren't even stored in persistent chat logs, and will be lost after having been "
      . "pushed away by the conversations flowing there; some others are persistent and will be "
      . "logged. $servername logs only what might be significant for staff members to know, such "
      . "as occasional malfunctions and other important events. As Postline accumulated more and "
      . "more features and underwent fun and less fun troubles, whether $servername developed "
      . "a sentient mind became an open debate.",

  "Why are you almost never online, Alex?",

        "Because I have a little trouble with my personality: people have several aspects that "
      . "form their personality, and sometimes two or more of these aspects may be condradictory, "
      . "like when you have to choose to do something to please yourself versus doing something "
      . "else in favor of someone else. Sometimes the choice can be hard, but in any cases this "
      . "might not interfere with one's ability of actually making a choice. In my case, "
      . "and with particular reference to my own personal projects, like this site and my wish "
      . "to take an 'adventurous' effort in game development in contrast to that of pursuing a "
      . "more 'usual' career, the conflict, so far, did never resolve to a clear choice, causing "
      . "growing tension until, at some time between the end of 2002 and the beginning of 2003, "
      . "and without being entirely aware of this, I have somehow split in two. To clarify, let's "
      . "call the original 'Alex', the one who had fun developing Noctis, Crystal Pixels, this "
      . "site during its first years, the attic's files and other such entertaining stuff; and "
      . "let's call the post-2003 personality 'Axle', a somewhat scrambled version of the "
      . "original. But let's also point out that this is a rough and unrealistic simplification, "
      . "since there isn't a true separation of the two 'selves', not much more than in any other "
      . "person: it's not a matter of hearing voices telling you what to do and what not, yet the "
      . "conflict surfaces in certain specific matters like managing this site, often in the form "
      . "of being not willing to come here, but also in many other forms - including stress, "
      . "confusion, uneasyness, even mind-induced symptoms, that I'd collectively define as "
      . "the <em>tendence to lie to yourself</em>. One such key 'lie' has been me focusing on a "
      . "pathological form of perfectionism, whereas the development of the new Noctis became a "
      . "sort of 'holy mission', what was done in past years was a 'mess', and what was to come "
      . "had to be absolutely more 'clean', 'canonical', 'elegant', and other such absolutes: it "
      . "was in fact never a sane tendence toward improvement, but rather the search for an "
      . "absolute, leading to a binary view of the past versus the future: zero and one, and no "
      . "midway allowed. L.in.oleum 2 was never released because it was never satisfactory, as "
      . "it could never be when its comparison was against an ideal 'perfect form of assembly'. "
      . "Back to the main answer, what happened through these damn years is that 'Alex' rarely "
      . "came here, because he thought he had not fulfilled his deeds. Rather, the one who was "
      . "allowed to get here, exclusively to bring something new and ideally better, was 'Axle'; "
      . "on the other side, my true self tended to agree with the false one's considerations, "
      . "judging his own self far from perfect, somewhat defective, and henceforth silently "
      . "dismissing himself, other than feeling restrained and frustrated by all the 'orders' "
      . "coming from the false one and concerning the management of this place; and all this "
      . "severely limited the presence of 'any of the two' here. "
      . "Ultimately, what's happening is that the true Alex began to acknowledge the presence of "
      . "the false one as an extraneous entity, and to disagree to him; an effect of this has "
      . "been the reprise of the conflict in an exacerbated form, which my recently created, "
      . "cryptic <a target=\"_blank\" href=\"http://my.opera.com/axle\">blog</a>, poetically "
      . "described as the transition between an 'eternal autumn' and a colder, less pleasant, but "
      . "more resolutive, 'inner winter'. To conclude, I just don't know what the future holds, "
      . "but since the summer of 2009 I'm trying to support the 'true me'; however, throughout "
      . "six years of manifest self-rejection, and possibly even a total of twelve years in which "
      . "this kind of problem had been not so manifest, there's been so many factors leading to a "
      . "rather different 'thought model' and induced by the 'false' self, that it's currently "
      . "pretty hard to tell what remains of the true one, and put the pieces of him back "
      . "together. I suppose I could take this whole tale tragically, or ironically; I definitely "
      . "prefer the latter as I keep listening to Lillian Axe's 'Body Double'.",

  "How does the inspector work? [mod]",

        "The inspector is that small box holding a couple switches and the icon of a "
      . "<u>magnifier</u> that moderators and administrators can see under every member's "
      . "avatar in profile forms. It might be useful when you're attempting to spot returning "
      . "people who registered under a certain identity but that you suspect they may have "
      . "registered again (typically to circumvent a ban). It basically operates by comparing "
      . "some factors concerning the inspected member against all the rest of the members' "
      . "database. These factors are all the nybbles (4-bit fields) of the IP address, plus some "
      . "informations about the user's system environment that can be frequently gathered "
      . "through javascripts (the 'system fingerprint fields'). It is not always possible to "
      . "gather all the informations, although the inspector will recalibrate its results "
      . "considering eventual void fields. The switches allow to 'fine-tune' the inspector's "
      . "duties: you can decide whether to consider or not the IP address as a relevant match "
      . "(you might, unless you suspect the member to be registering every time from totally "
      . "different addresses), and if it's ok to use some fuzzy logic to compare against "
      . "numeric fields such as the dimensions of the desktop and the browser's window (if you "
      . "used fuzzy logic, similar values should still give significant matches even if two "
      . "numbers aren't exactly equal).<br>"
      . "<br>"
      . "Technically, that's all, but eventually, you should be very careful about what the "
      . "inspector will report: if a match is around at least 50-60% and considering all the "
      . "available informations, it's effectively at least probable that two persons are "
      . "<u>using the same computer</u>, and, especially if they've proven to behave similarly, "
      . "that they may in fact be the same person. In other cases, you might at least take a "
      . "look to the 'match details' and try to evaluate them by yourself: for instance, it's of "
      . "course nothing particularly weird that someone's browser window dimensions may be the "
      . "same, or nearly the same, as that of someone else's.",

  "Is there a way to punish a member's misbehavior? [mod]",

        "Yes, Postline does quite much for this issue, although it has to be said that the "
      . "plague of trolls is endemic to the internet, because the internet grants user's privacy "
      . "at least in respect to other users (although, often, not to national authorities). "
      . "Anyway, first of all, there's the &#171;/kick&#187; command: it has to be typed in the "
      . "chat prompt, following the syntax:<br>"
      . "<br>"
      . "/kick \"nickname\"<br>"
      . "<i>(note: you MUST put the name between quotes)</i><br>"
      . "<br>"
      . "the server will log out the user, keep him/her from logging in again for 5 minutes, and "
      . "also temporarily block the IP address of the member to keep him/her from registering a "
      . "new account under a different nickname. Additionally, for more severe infringements, "
      . "the duration of the login restriction can be raised upto "
      . intval ($maxkickdelay / (3600)) . " hours by appending an optional duration (in minutes) "
      . "to the command, as in the following example:<br>"
      . "<br>"
      . "/kick \"nickname\" 120<br>"
      . "<i>(quotes around the name, but no quotes around the number)</i><br>"
      . "<br>"
      . "...which would keep the user from logging in again for as much as 2 hours. Now, you may "
      . "often see some trolls succeeding in registering new accounts because their connections "
      . "have a dynamic IP address. Because the IP address may change, the registration lock "
      . "could be spoiled.<br>"
      . "<br>"
      . "So what can be done, at this point? Well, apart from kicking the troll again (which will "
      . "cause the lock to dynamically follow his IP address), when the idiot on the other end of "
      . "the line insists in showing the world how clever HE is (no, I don't think women can be "
      . "trolls, basically because I strongly suspect women to be the second, and bug-fixed, "
      . "release of men) by demostrating that mankind does not really deserve freedom and "
      . "anonymous identities... er, nevermind: when the troll insists it's a personal question "
      . "for him to get back here and destroy the peaceful convivence of everyone else, there's "
      . "the possibility, for administrators, to ban the troll, eventually along with an IP "
      . "subnet mask that matches one or more hexadecimal digits of the troll's address. It is "
      . "true that IP addresses can dynamically change, but they don't typically change so much "
      . "if the target of the ban keeps using the same internet access provider: often, banning "
      . "a 4-digit subnet mask will keep a whole provider's users from registering an account "
      . "here. Now for the pratical part: banning is done from the <a href=defense.php "
      . "target=pst>Community Defense Panel</a>, where other admin-level management features are "
      . "also accessible. The CDP is readable by moderators, to ideally monitor what admins are "
      . "doing, but it takes administration access rights to change the state of lists and "
      . "switches of the CDP. IP addresses are typically (but not in this case) expressed in "
      . "\"dotquadded\" form, which is their well-know w.x.y.z format. However, this decimal-"
      . "coded format is difficult for software to manage, so Postline scripts convert it "
      . "to a sequence of 8 hexadecimal digits, also known as 8 binary &#171;nybbles&#187;. As "
      . "in a normal decimal number, the leftmost hexadecimal digit of the IP is the most "
      . "significant digit, that is, the highest in value. As you proceed to the right of the "
      . "IP, digits become always less significant, until the very last two digits, corresponding "
      . "to the least significant byte of the IP address. The more digits you include in the "
      . "\"banned subnets list\", as a one-line entry of that list, the more you restrict the "
      . "range of IP addresses that will be banned, and consequentially you will be banning less "
      . "potential new, and innocent, members. The less digits you include, the more potential "
      . "new members you will be banning for no reason, so be very careful there: a ban of a "
      . "1-digit subnet mask bans averagely 1/16th of the whole internet, and a ban on 2 digits "
      . "bans 1/256th of the entire world wide web. The situation will probably get better once "
      . "IPv6, with its 128-bit range, will grant a personal identification address to every "
      . "single internet account, allowing for safer identification, but for the time being, "
      . "we'll have to deal with IPv4's tight amount of addresses (IPv4 allows slightly more "
      . "than 4 billion addresses, and you know there's over 6 billion humans on this planet, "
      . "and then consider that many servers have their own, static IP address).",

  "How do side bars work? Can they be changed? [mod]",

        "Both moderators and administrators can change the contents of the &#171;side-bars&#187;, "
      . "those short, narrow pages appearing along with a &#171;home thread&#187; in reply to a "
      . "click on one of the navigation bar's buttons. Side-bars are supposed to hold quick links "
      . "to particular forums, threads and even single messages, that in some way concern the "
      . "topic of the corresponding section of the site. Side-bars are basically individual "
      . "archives of short, plain text files: for each side-bar there's a corresponding text. "
      . "The text associated with a side bar is written in a special format, which is detailed "
      . "below.<br>"
      . "<br>"
      . "As a first thing, side-bars' texts are delimited by carriage returns (or if you prefere, "
      . "line breaks): their entries can be separated by one or more carriage returns. <em>Now, "
      . "the first line of a side-bar's text defines a title for that side-bar</em>. The rest can "
      . "be: HTML fragments, links to a forum, links to a thread, links to a single post, text "
      . "paragraphs, or horizontal divisors. Save for simple text paragraphs and divisors, each "
      . "kind of entry is marked by the very first (and STRICTLY lowercase) character of the "
      . "entry's line of text, so that:<br>"
      . "<br>"
      . "f = forum<br>"
      . "t = thread<br>"
      . "m = message (post)<br>"
      . "<br>"
      . "...each of those three entities must be followed by a number, then a colon, and finally "
      . "the text that will indicate the link in the side-bar's layout. The number is the hard "
      . "part: it represents the internal identification code of a forum, of a thread, or of a "
      . "message. It is relatively easy to find out the code: by exploring the forums, you have "
      . "to pay attention to the browser's status bar <em>while you hover a thread's or a forum's "
      . "title</em>. Because the title is also the link to the forum or to the thread, you'll "
      . "notice the browser's status bar will be holding an URL to one of the scripts of "
      . "Postline, PLUS, after a question mark, a set of arguments, looking like a sequence "
      . "of equates separated by ampersands: among the arguments, what you see as the numbers "
      . "following \"f=\" and \"t=\", are what you were looking for, respectively as a forum's "
      . "ID or as a thread's ID. For messages, you basically do the same, only that in this case, "
      . "your most reliable reference becomes the link to EDIT that message, which will show the "
      . "\"m=\" equate followed by the message's internal ID. One last thing: instead of "
      . "&#171;t&#187;, it is possible to use the lowercase &#171;l&#187; to mean a link to the "
      . "very LAST post of the very LAST page of the given thread's ID. To conclude, have some "
      . "examples:<br>"
      . "<br>"
      . "f4:Click here to access the forum that has ID 4.<br>"
      . "t345:Click here, instead, to access the thread that has ID 345.<br>"
      . "l345:Same as above, but accesses the last post of that thread.<br>"
      . "m1568:Click here, finally, to see only message with ID 1568.<br>"
      . "<br>"
      . "All what remains to explain is about text paragraphs and divisors: well, divisors are "
      . "marked by a line that contains the sole word \"div\" (without quotes around). Text "
      . "paragraphs, finally, are identified by exclusion: IF the specification is neither a "
      . "link, nor a divisor, AND the line begins with an UPPERCASE letter or another sign, THEN "
      . "it must be a paragraph, and so is rendered. Finally, the \"i\" letter can be used to "
      . "create interstitial divisors, rendered in white over a darkened background, and normally "
      . "more visible than regular divisors, e.g. \"i:Title\".<br>"
      . "<br>"
      . "Uh-oh... looks like I forgot to talk about the \"h\" letter: followed by the "
      . "conventional colon, it marks an HTML fragment, a single line of HTML code to be "
      . "inserted as it is in the corresponding point of the side bar. Typically, you'll want "
      . "to use this to mark links to entities that are none of the abovely considered stuff "
      . "(not a thread, not a forum, not a post), such as links to external sites or resources "
      . "not concerning Postline. However, you must be aware that an HTML link, by "
      . "default, loads the target URL in the SAME frame where the link is held: so, if you "
      . "said:<br>"
      . "<br>"
      . "h:&lt;a href=http://this.other.site.com&gt;click here&lt;/a&gt;<br>"
      . "<br>"
      . "...you would end up, when the link is clicked, loading this.other.site.com within the "
      . "frame that's holding the side bar! Which is a very small frame, of course. The solution "
      . "is simple: adding a proper &#171;target&#187; specification to the HTML tag. It works "
      . "like so:<br>"
      . "<br>"
      . "h:&lt;a target=_blank href=http://this.other.site.com&gt;click here&lt;/a&gt;<br>"
      . "<br>"
      . "...that would load this.other.site.com in a new browser's window. But of course there's "
      . "several alternatives to the _blank destination. Here's a comprehensive list of "
      . "plausible destinations:<br>"
      . "<br>"
      . "pan: the right frame (where you read this FAQ)<br>"
      . "pst: the left frame (the default destination)<br>"
      . "_top: replacing this site<br>"
      . "_blank: opening in a new browser window<br>"
      . "_parent: parent frame, which is also \"pag\", the central part of the page.",

  "How do I create/delete/manage forums? [admin]",

        "With the following set of chat-line commands:<br>"
      . "<br>"
      . "/newforum name=\"new forum's name\" desc=\"a brief description of it\"<br>"
      . "/renforum old name=\"actual name\" new name=\"another name\" new desc=\"a new "
      . "description\"<br>"
      . "/delforum \"name of forum to trash\"<br>"
      . "/undelete \"name of forum to recover from trash\"<br>"
      . "<br>"
      . "...that's creating, renaming, deleting and undeleting, now:<br>"
      . "<br>"
      . "/moveup \"forum to move up one place in the list\"<br>"
      . "/movedown \"forum to move down one place in the list\"<br>"
      . "<br>"
      . "...and those were for managing the order of the forums.<br>"
      . "<br>"
      . "Finally there's a consideration to be done about trashed forums: they aren't permanently "
      . "deleted, as you can see given the presence of an undelete command. Trashed forums are "
      . "simply hidden to the view of any member who's neither an administrator, nor a moderator. "
      . "Trashed forums are completely stealth to regular members: Postline reacts to any and all "
      . "requests to access trashed forums in the same way it reacts for unexisting forums; also, "
      . "discussions going on in trashed forums are neither indexed for the keyword-based search, "
      . "nor echoed in the recent discussions' archive. Apart that you can use an intentionally "
      . "trashed forum to discuss privately among other moderators and administrators, sure that "
      . "no regular member can see what you're talking about, if the trashed forum was deleted "
      . "because at a certain point it became useless, you may want to free the space taken up by "
      . "it in the database. For this purpose, there's a command:<br>"
      . "<br>"
      . "/lose \"name of a TRASHED forum to be physically deleted\"<br>"
      . "<br>"
      . "...which physically erases all the contents, all the threads and all the posts, of an "
      . "already trashed forum. This process makes the deleted forum completely unrecoverable, "
      . "and helps freeing some database space, BUT: it's usually a very slow process. Now, many "
      . "hosts run PHP on their servers in safe mode. In safe mode, the server unconditionally "
      . "limits the execution time of a PHP script to a certain amount of time, normally 30 "
      . "seconds. So be aware of a couple facts: first, if the server does not complete the "
      . "execution of the /lose command in time, then you'll have a fragmented database with "
      . "parts of the trashed forum not being entirely deleted and most probably causing "
      . "malfunctions when accessed; secondly, your users will experience a temporary lock up of "
      . "the community, because the lock-file that synchronizes concurrent sessions will have "
      . "remained in locked state. This will pass in about 5 seconds, but it will be noticeable "
      . "in crowded communities. How do you solve this? Well, by re-executing /lose on the same "
      . "forum's name: the process will continue from about where it was interrupted. If you "
      . "keep executing it, before or later (depending on the size and complexity of the target "
      . "forum's structure) it will complete without being interrupted by the time limit, but "
      . "you might also be causing lots of inconveniences to your users.<br>"
      . "<br>"
      . "One last thing: sometimes you'll want to use a forum as a trashcan for entire threads. "
      . "It is rather long and boring to go and delete every single message from a thread in "
      . "order to remove the whole thread, when the said thread is very long. Therefore, as a "
      . "facility, you can build a forum to be called, say, \"Trashcan\", and then hide it with "
      . "/delforum. After that, whenever you have a long thread to delete, you can simply move "
      . "it to the trashcan forum. Now, if you periodically emptied the trashcan, it would be a "
      . "good way to keep the server's disk space free from deleted threads, and yet be "
      . "performed rather quickly by /lose, because you'd limit this operation to the threads "
      . "that were moved in the trashcan since the last time it was emptied. BUT, using the "
      . "regular syntax of /lose, you'd end up, every time, removing ALSO the trashcan forum's "
      . "entry in the forums' list. To workaround this problem, you can add the /e switch to "
      . "the /lose command statement (/e stands for \"e...mpty, but don't remove forum\"):<br>"
      . "<br>"
      . "/lose \"Trashcan\" /e<br>"
      . "<br>"
      . "And if you want to keep the server from showing everyone what you just did, as with all "
      . "the other commands above, use the /q (/q is for \"q...uiet mode\") switch along:<br>"
      . "<br>"
      . "/lose \"Trashcan\" /eq"

);

/*
 *
 * generating FAQ summary
 *
 */

$summary = "<ol>";      // summary opening (Ordered List)
$q = 1;                 // initial question number
$n = 0;                 // array base index

while ($faq[$n]) {

  $restrict = 0;

  if (strtolower (substr ($faq[$n], -5, 5) == "[mod]")) $restrict = 1;
  if (strtolower (substr ($faq[$n], -7, 7) == "[admin]")) $restrict = 2;

  if ($is_mod) $restrict -= 1;
  if ($is_admin) $restrict -= 2;

  if ($restrict <= 0) {

    $summary .= "<li><a href=faq.php#q$q>" . $faq[$n] . "</a></li>";
    $q ++;

  }

  $n += 2;

}

$summary .= "</ol>";    // summary closing

/*
 *
 * generating FAQ table
 *
 */

$intro =

    makeframe ("Questions that might be frequently asked...", false, false)
  . makeframe ("summary", $summary, false);

$intro .= "<a name=q1></a>";    // anchor to first question
$intro .= "<table width=100%>"; // FAQ table opening
$q = 1;                         // initial question number
$n = 0;                         // array base index

while ($faq[$n]) {

  $restrict = 0;

  if (strtolower (substr ($faq[$n], -5, 5) == "[mod]")) $restrict = 1;
  if (strtolower (substr ($faq[$n], -7, 7) == "[admin]")) $restrict = 2;

  if ($is_mod) $restrict -= 1;
  if ($is_admin) $restrict -= 2;

  if ($restrict <= 0) {

    $q ++;

    $intro .= "<tr>"
           .   "<td height=8>"
           .   "</td>"
           .  "</tr>"
           .  "<tr>"
           .   "<td>"
           .    makeframe (

                  "question",
                  "<span class=tt>Q:</span>&nbsp;&nbsp;" . $faq[$n],

                  false

                )
           .   "</td>"
           .  "</tr>"
           .  "<tr>"
           .   "<td>"
           .    makeframe (

                  "answer",
                  "<span class=tt>A:</span>&nbsp;&nbsp;" . $faq[$n+1],

                  false

                )
           .   "</td>"
           .  "</tr>"
           .  "</table>"
            . "<a name=q$q></a>"
           .  "<table width=100%>";

  }

  $n += 2;

}

$intro .=  "<tr>"
       .    "<td height=1200>"  // bottom spacer (for last question's anchor positioning)
       .    "</td>"
       .   "</tr>"
       .  "</table>";           // FAQ table closing

/*
 *
 * template initialization
 *
 */

$permalink = ($enable_permalinks)

  ? str_replace

      (

        "[PERMALINK]", $perma_url . "/" . $help_keyword, $permalink_model

      )

  : "";

$generic_info_page = str_replace

  (

    array (

      "[INFO]",
      "[PERMALINK]"

    ),

    array (

      $intro,
      $permalink

    ),

    $generic_info_page

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page
 * upon showing or hiding the chat frame (via cookie: this also works for guests)
 *
 */

include ("setrfcookie.php");

/*
 *
 * saving navigation tracking informations for the online members list links
 * (unless no-spy flag checked)
 *
 */

if (($login == true) && ($nspy != "yes")) {

  $this_uri = substr ($_SERVER["REQUEST_URI"], 0, 100);

  if (getlsp ($id) != $this_uri) {

    setlsp ($id, $this_uri);

  }

}

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($generic_info_page));



?>
