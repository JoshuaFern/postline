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

include ("settings.php");
include ("suitcase.php");
include ('sessions.php');
include ("template.php");

/*
 *
 * This is an introductive page, generally explaining the main features of Postline:
 * begins by initializing the very few variable elements of the intro text...
 *
 */

$intro = $site_intro;

$types = count ($cd_filetypes);

$q_demultiplier =

    intval (get ("stats/counters", "counter>signups", "count"))
  - intval (get ("stats/counters", "counter>resigns", "count"));

$q_demultiplier = ($q_demultiplier >= $types) ? $q_demultiplier / $types : $q_demultiplier;
$q_demultiplier = ($q_demultiplier == 0) ? 1 : $q_demultiplier;

$uname =

  ($login)

  ? " back, $nick"      // "welcome back" if it's a known and logged-in user
  : "";                 // "welcome" alone if it's a guest

$no_registration_allowed = (get ("stats/counters", "counter>community_locker", "state") == "on")

        ? true
        : false;

$majoritybanstate =

    (get ("stats/counters", "counter>majorityban", "state") != "off")

      ?

      (

    (get ("stats/counters", "counter>mbnotify", "state") == "on")

      ? "merciful (the system only sends reports to staff members)"
      : "fully enabled (the system actively bans excessively ignored members)"

      )

      : "disabled (being excessively ignored leads to no system intervent)";

$majoritybanconsequences = array

    (

        "merciful (the system only sends reports to staff members)"

                => "automatically sends misbehavior reports about that person to staff members",

        "fully enabled (the system actively bans excessively ignored members)"

                => "automatically sets a permanent ban on that person",

        "disabled (being excessively ignored leads to no system intervent)"

                => "does currently nothing, but could be told to take action"

    );

/*
 *
 * generate overall community statistics, which works as an introductive
 * paragraph about the community
 *
 */

$forums = all ("forums/index", makeProper);
$f_count = 0;

foreach ($forums as $f) {

  $not_hidden = (valueOf ($f, "istrashed") == "yes") ? false : true;
  $not_locked = (valueOf ($f, "islocked") == "yes") ? false : true;
  $not_closed = (valueOf ($f, "isclosed") == "yes") ? false : true;

  if (($not_hidden) && ($not_locked) && ($not_closed)) {

    $f_count ++;
    $f_first = intval (valueOf ($f, "id"));

  }

}

$balance = readFrom ("cd/balance");
$counters = readFrom ("stats/counters");

$c_title = ucfirst ($sitename);
$s_count = intval (strFromTo ($counters, "signups<count>", "<"));
$r_count = intval (strFromTo ($counters, "resigns<count>", "<"));
$m_count = number_format ($s_count - $r_count);
$p_count = number_format (intval (strFromTo ($counters, "newposts<count>", "<")));
$t_count = number_format (intval (strFromTo ($counters, "newthreads<count>", "<")));
$i_count = number_format (intval (strFromTo ($balance, "<count>files<value>", "<")));

$f_count =

  (($f_count == 1)

    ? "<a href=threads.php?f=$f_first>the discussion forum</a>"
    : "<a href=forums.php>$f_count active discussion forums</a>"

  );

$t_count =

  (($t_count == 1)

    ? "a single thread"
    : "$t_count threads"

    );

$i_count =

  (($i_count == 1)

    ? "a file is"
    : "$i_count files are"

    );

$intro .= "<table class=stats>"
       .  "<td>"
       .   "<em>$c_title</em>: an online community"
       .

     (($m_count)

       ?   " of <a href=members.php>$m_count members</a>"
       :   ",<br>still under construction.."

       )

       .

     (($f_count)

       ?   "<br>daily contributing to $f_count"
       :   ""

       )

       .

     (($t_count)

       ?   "<br>where $p_count messages are listed in $t_count"
       :   ""

       )

       .

     (($i_count)

       ?   "<br>and $i_count kept in our public "
       .   "<a target=pst href=cdisk.php>community disk</a>"
       :   ""

       )

       .  "</td>"
       .  "</table>";

/*
 *
 * Define paragraphs in intro page:
 * especially rules may be changed if you don't share the point of view of Anywherebb, here.
 *
 */

$paragraphs =
  array (

  // first things first: warn that it might be better to continue reading the page...

    "welcome$uname"
 => "This page briefly explains how this site works, and lists most of its features. "
  . "If you're new to this site, you might want to read this page in full before you continue. "
  . "If you lose this page following its links, click <em>home</em> in the navigation bar that "
  . "you might see above. Thank you for visiting and have a nice stay.",

  // registration

    "account registration: just a nickname and a password"
 => "Unregistered visitors, known as <em>guests</em>, may navigate public forums, read "
  . "messages posted there by registered members, and download files from the "
  . "<a target=pst href=cdisk.php>community disk</a>. Registration grants the rights to chat, "
  . "post messages, upload files, send or receive private messages, vote in polls. "
  . "Registration is free and does not require you to reveal any personal informations, but "
  . "<em>requires</em> you to allow cookies to be stored on your computer. That said, all you "
  . "need to register an account is choose a nickname (up to 20 characters long) and provide a "
  . "password of your choice (between 6 and 20 characters long). You only need to register "
  . "<em>once</em>, as multiple accounts are generally not allowed.",

  // login

    "logging in and out"
 => "You may log in using the proper form <a href=mstats.php target=pst>loaded to the left</a>. "
  . "By logging in you initiate a <em>session</em>, a cookie is stored on the computer you're "
  . "using, and afterwards everything that is browsed or sent through that computer will be "
  . "acknowledged as part of your session. If you do nothing, after <em>"
  . ((int) ($sessionexpiry / 60)) . " minutes "
  . "</em> your session will expire and you will be automatically logged out. To log out without "
  . "waiting for the session to expire, you may use the <em>logout</em> link presented in the "
  . "<a href=mstats.php target=pst>session control</a> panel (it appears there once you have "
  . "successfully signed in), which is especially important when you're using a "
  . "<em>public computer</em>: if you leave the cookie intact on the computer, whoever uses that "
  . "machine will control your account here.",

  // rules

    "community rules, intended for peacekeeping"
 => "By accessing this community you implicitly agree to be bound by the following set of rules: "
  . "at any time you may decide to leave the community, by accessing your profile form and "
  . "checking the three boxes to <em>resign</em>, should you no longer agree with our rules. "
  . "Actively participating to the community, as well as selecting links to show the chat frame, "
  . "read messages, download files from the c-disk, is solely under your own responsibility and "
  . "implies that you agree with the following rules, which are strongly enforced by moderators "
  . "and administrators, who are equally entitled to take actions in case of infringements.</p>"
  . "<ol>"
  .  "<li class=rule>"
  .   "You are supposed to do your best to comply to the so-called &#171;netiquette&#187;, "
  .   "an unwritten set of rules generally aimed to create a peaceful and productive "
  .   "atmosphere of friendship between the participants to internet communities. "
  .   "This includes, but is not limited to, avoiding to attack other members, to intentionally "
  .   "and repeately disturb discussions, to practice any forms of explicit redundant advertising "
  .   "for commercial goods and services."
  .  "</li>"
  .  "<li class=rule>"
  .   "Do not submit material generally considered not suitable for a public place. This includes "
  .   "material characterized by: race and/or gender discrimination, explicit pornography, "
  .   "excessive and/or untargeted violence, bigotry."
  .  "</li>"
  .  "<li class=rule>"
  .   "Do not submit copyrighted material whose copyright owners did not authorize you to "
  .   "distribute in public. Do not link to sites in direct possession of such material. If you "
  .   "are not sure you hold appropriate distribution rights over something, seek informations "
  .   "before submitting it."
  .  "</li>"
  .  "<li class=rule>"
  .   "Do not lie. Do not willfully deceive the other members of the community by spreading "
  .   "information you know is false. Consider that there is a difference between deception "
  .   "and joking. That said, we do not want to compromise your privacy: you can, and should, "
  .   "omit answering personal questions."
  .  "</li>"
  .  "<li class=rule>"
  .   "Do not try to stress the system (and its users) with meaningless spam, or with "
  .   "the repetitive creation of threads, messages, chat phrases (&#171;flooding&#187;), "
  .   "c-disk files, having no meaningful reason to be submitted. Yet, you may feel free to "
  .   "look for glitches and problems in Postline's code, and test the system under special "
  .   "conditions to see how it reacts, but only if you strive not to disturb other users, "
  .   "and for the sole purpose of eventually reporting your discoveries so that underlying "
  .   "problems could be solved."
  .  "</li>"
  .  "<li class=rule>"
  .   "Do not register multiple accounts. You are entitled to register only one account. "
  .   "You are allowed to register again only after your original account has been "
  .   "pruned for inactivity, or deleted by system malfunctions. By successfully completing "
  .   "the registration process, you implicitly agree that all community staff members will "
  .   "be granted the right to collect and monitor available informations about you and your "
  .   "computer, with the sole purpose of identifying multiple accounts. On their side, staff "
  .   "members agree on keeping such informations strictly confidential."
  .  "</li>"
  .  "<li class=rule>"
  .   "The official community language is English. Slang, dialects and distortions of English "
  .   "are completely tolerated, and although preferably limited by common sense, rough "
  .   "language is also tolerated, unless it is used to insult one or more members, therefore "
  .   "breaking rule number 1. It is your own responsibility to comply with your actual "
  .   "legislation concerning objectionable language that you may encounter in this database. "
  .   "Significantly long conversations and messages made in languages different from English "
  .   "and its variants are not allowed."
  .  "</li>"
  . "</ol>"
  . "<p align=justify>Repeately acting against any of the above rules may cause your actual "
  . "account to be temporarily or permanently disabled, your IP address range to be eventually "
  . "blacklisted, and abuse reports to be eventually sent to your access provider.",

  // c-disk concerns

    "about the community disk"
 => "<a name=cdisk>"
  . "</a>"
  . "The <a href=cdisk.php target=pst>community disk</a> (also known as c-disk or just cd) is "
  . "part of the server's disk space that's been made available for arbitrary files uploading "
  . "from the members. Any registered members, once logged in, may save any number of files into "
  . "the c-disk, which makes them available for download to any visitors, including guests. "
  . "By uploading one or more files to the community disk (c-disk) you implicitly agree to be "
  . "bound by the following rules:</p>"
  . "<ol>"
  .  "<li class=rule>"
  .   "Any file(s) that you upload to the c-disk is (are) under your sole responsibility. "
  .   "You must not consider the c-disk to be reliable enough for storing important informations "
  .   "which loss could cause damage to you and/or to third parties, and in general informations "
  .   "that exist in no further copies, or in lack of any safe backup copies."
  .  "</li>"
  .  "<li class=rule>"
  .   "In no circumstance whatsoever this community itself, its members apart from you, "
  .   "its hosts, its managers, its moderators and its administrators, will be held responsible "
  .   "for any inconveniences and/or damages your uploaded files may cause to you and/or to "
  .   "third parties, either directly, or consequentially, or indirectly, and even if advised "
  .   "of the possibility of such inconveniences and/or damages."
  .  "</li>"
  .  "<li class=rule>"
  .   "The community's manager, administrators and moderators reserve the right to delete "
  .   "any file(s) uploaded to the c-disk that they don't see fit, either because they "
  .   "explicitly break one or more of the community rules, or for any other unspecified "
  .   "reasons at their own discretion, as soon as such file(s) are identified."
  .  "</li>"
  . "</ol>"
  . "<p align=justify>One suggestion: although there is no condition that blocks you from "
  . "uploading more than a given amount of files, you might try to avoid exceeding your quota. "
  . "To see how much of it you have used at a given time, go to "
  . "<a target=pst href=profile.php?nick=i74>your profile</a> and select the button to list all "
  . "your c-disk files. On top of that list, it tells you whether you exceeded your quota or not. "
  . "However, the indication is only informative and, given good reasons for you to exceed the "
  . "quota, you may be allowed to do that. One final precisation: the c-disk simulates the "
  . "way a real disk works, so it calculates its allocated space in units called "
  . "&#171;clusters&#187;: when you upload a file, its allocated space will be calculated by "
  . "rounding the file size to the next multiple of the cluster size. "
  . "This, indirectly, gives a maximum amount of smallest files (taking one cluster each) that "
  . "could be ever stored to the c-disk, because no file can take less than one cluster. Here "
  . "follow overall informations about the c-disk:</p>"
  . "<ul>"
  .  "<li class=list>"
  .   "capacity in clusters, max. uploadable files: " . number_format ($cd_disksize)
  .  "</li>"
  .  "<li class=list>"
  .   "cluster size: " . number_format ((int) ($cd_clustersize / 1024)) . " Kb."
  .  "</li>"
  .  "<li class=list>"
  .   "overall capacity: " . number_format ((int) ($cd_disksize * ($cd_clustersize / 1024))) . " Kb."
  .  "</li>"
  .  "<li class=list>"
  .   "quota per member: " . number_format ((int) (($cd_disksize / $q_demultiplier) * ($cd_clustersize / 1024))) . " Kb."
  .  "</li>"
  . "</ul>"
  . "<p align=justify>While current members' quotas will be progressively shrinking as new "
  . "accounts are created, there can be several solutions: members, moderators and "
  . "administrators may agree to be more tolerant about those who exceed their quotas; the "
  . "community manager may decide to allocate more server space for the c-disk; members may be "
  . "pleased to go and seek any unnecessary files to make room for newcomers; moderators may "
  . "begin to patrol the c-disk for useless, large files, and, last but not least, "
  . "administrators may decide to remove inactive accounts that couldn't be automatically "
  . "pruned, but whose owners aren't logging in since long time.",

  // ignore lists and majority ban

    "self-moderation: personal ignore lists"
 => "So, there's moderators and administrators, but they're not the only ones who can do "
  . "something to moderate the board: even &quot;major members&quot; can contribute to reduce "
  . "the presence of people who may not be playing fair. It all depends on the <em>ignore "
  . "lists</em>. Every member is given, in the database, a personal ignore list: it's the list "
  . "of those members you don't want to read posts from. The forums will filter out those "
  . "member's posts when you access them: all you will see at that point will be a short notice "
  . "(&#171;ignored: nickname&#187;), in place of any of their messages. Also, the system will "
  . "not deliver any private messages to recipients who ignore the sender. To add someone to "
  . "your ignore list, access that member's profile, and select the button at its bottom that "
  . "reads &#171;ignore his/her messages&#187;. For stronger moderation, Postline is told to "
  . "consider the indications of a significant number of <em>major members</em>; actually, that "
  . "number is $majorityban members. What happens is that, when <em>at least $majorityban major "
  . "members</em> choose to ignore <em>the same person</em>, then Postline "
  . $majoritybanconsequences[$majoritybanstate]
  . ". Members will reach &#171;major age&#187; <em>after having been registered for at least "
  . "$majoragedays days and posted at least $majorageposts messages</em> in the boards. Once you "
  . "become a major member, note that your initial ignore list will be cleared: from that point "
  . "onwards, you have more decisional power, so you will need to rebuild your list, being "
  . "careful determining who you decide to ignore, and why.",

    "postscript"
 => "The ignore lists' moderation features may be disabled by administrators, should they suspect "
  . "they are being abused, so please try to be careful ignoring random people around. The actual "
  . "state of such features, as the {$majorityban}-member threshold is reached, is: "
  . "<em>$majoritybanstate</em>.",

    "source code"
 => "The source code of this bulletin board and content management system, by the name of "
  . "&#171;Postline&#187;, and in the form of a set of PHP scripts and additional graphics "
  . "parts, is Copyright &copy;2001-2010 by Alessandro Ghignola, and is distributed under "
  . "GNU General Public License. The entire source code can be downloaded free of charge by "
  . "clicking <a href=cd/postline.zip>this link</a>."

  );

/*
 *
 * remove registration paragraph in case the community was closed to new registrations
 *
 */

if ($no_registration_allowed) {

  unset ($paragraphs["account registration: just a nickname and a password"]);

}

/*
 *
 * appending paragraphs to introduction
 *
 */

foreach ($paragraphs as $c => $p) {

  $intro .= makeframe ($c, "<p align=justify>$p</p>", false);

}

/*
 *
 * template initialization
 *
 */

$permalink = ($enable_permalinks)

  ? str_replace

      (

        "[PERMALINK]", $perma_url . "/" . $home_keyword, $permalink_model

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
