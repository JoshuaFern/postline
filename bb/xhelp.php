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
define ("left_frame", true);

require ("settings.php");
require ("suitcase.php");
require ("sessions.php");
require ("template.php");

/*
 *
 * setup Database Explorer's help sidebar
 *
 */

$form =

      maketopper (

        "Database Explorer Help"

      )

      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "QUICK LINKS"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .      "<a class=ll href=xhelp.php#hist>a bit of history</a>"
      .  "<br><a class=ll href=xhelp.php#power>power to the people</a>"
      .  "<br><a class=ll href=xhelp.php#seeseenot>what you see, what you don't</a>"
      .  "<br><a class=ll href=xhelp.php#ataglance>at a glance</a>"
      .  "<br><a class=ll href=xhelp.php#exploring>exploring the database</a>"
      .  "<br><a class=ll href=xhelp.php#engine>its own relational engine</a>"
      .  "<br><a class=ll href=xhelp.php#where>where do I click?</a>"
      .  "<br><a class=ll href=xhelp.php#deleting>deleting files</a>"
      .  "<br><a class=ll href=xhelp.php#sfbackup>single-file backup</a>"
      .  "<br><a class=ll href=xhelp.php#sfrestore>single-file restore</a>"
      .  "<br><a class=ll href=xhelp.php#rwarning>careful restoring this</a>"
      .  "<br><a class=ll href=xhelp.php#multibackup>multiple file backup</a>"
      .  "<br><a class=ll href=xhelp.php#strategy>huge boards' strategy</a>"
      .  "<br><a class=ll href=xhelp.php#multirestore>multiple file restore</a>"
      .  "<br><a class=ll href=xhelp.php#totalrecall>total recall</a>"
      .  "<br><a class=ll href=xhelp.php#managerprotection>manager protection</a>"
      .  "<br><a class=ll href=xhelp.php#postscript>postscript: browser wars</a>"
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=hist></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "A BIT OF HISTORY"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "The Database Explorer is a script, coming as part of Postline's package, that "
      .  "allows to access most of the board's files at &quot;raw&quot; level. Until a "
      .  "certain point, Postline saved its archives using direct (or flat) file access, "
      .  "until, having found that method not enough reliable in crowded situations, a "
      .  "switch to MySQL databases was felt necessary. But at a price: contrarily to "
      .  "plain text files saved in the server's file system, SQL archives aren't directly "
      .  "accessible via FTP, file managers and other common means. There are SQL database "
      .  "managers out there, but... the Database Explorer was made mainly because such "
      .  "management systems are, in fact, OUT there: out of Postline's frames and windows, "
      .  "and I personally thought that having a specific management script, fit for most "
      .  "of the common duties that were formerly done via FTP sessions (repairing damaged "
      .  "files, performing backups, correcting small parts in consequence of changes in "
      .  "some script, etc), would be comfortable. So the Database Explorer was born."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=power></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "POWER TO THE PEOPLE"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "The DB explorer is visible to all moderators and all administrators, but only "
      .  "administrators may write to database files, and this includes restoring files "
      .  "from backups. Moderators, though, can keep their own backup copies, eventually "
      .  "spanning almost the whole database, and eventually updated on a regular basis. "
      .  "Should something be lost from the server in consequence of a malfunction or a "
      .  "mistake, multiple recent backups would allow to restore the affected files in no "
      .  "time. This is one of the key advantages in respect to other DB management tools "
      .  "which are typically installed server-side so that only the site administrators "
      .  "could access them."
      . "</td>"
      . $clcart
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "It comes along that, being almost every part of the database visible to all "
      .  "mods and admins, there will be several persons capable of monitoring anything, "
      .  "including - ahem - private messages. This increases Postline's staff members "
      .  "responsibility in respect to most other community and content management systems."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=seeseenot></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "WHAT YOU SEE, WHAT YOU DON'T"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "You can see all folders and all files holding database informations saved via SQL "
      .  "queries, with the some exception of the 'members' folder, access to which is only "
      .  "granted to the community manager, to protect public exposure of password hashes. "
      .  "This still includes most of the significant informations about the board: there's "
      .  "the c-disk virtual directories (files in the 'cd' folder, which are holding file "
      .  "descriptors), forums' and threads' listings (the 'forums' folder), the (usually "
      .  "large) archive of messages ('posts'), and even everyone's private messages ('pms') "
      .  "and internal counters and special statistics records ('stats'). "
      .  "The board is pratically naked, seen throught the Database Explorer."
      . "</td>"
      . $clcart
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "But, is there something that remains in the dark, even with such a powerful light? "
      .  "Yes, there is: it's every thing that doesn't get saved via SQL queries, or if you "
      .  "prefere, the parts that were left to live as regular files, still managed by the "
      .  "server's file system. The selection was driven by a matter of performance: what the "
      .  "Database Explorer can't see or manage are files holding extremely large or dynamic "
      .  "contents. They are: files stored in the c-disk, keyword indexs for the wordsearch "
      .  "functions, and three tables holding last-viewed-page tracking informations, the list "
      .  "of viewed recent discussions for every member (to highlight posts as read or unread), "
      .  "and one last table called SCT, Security Code Table, holding everchanging numeric "
      .  "codes that are used to validate form submission requests to the server (they guard "
      .  "against the possibility of locally or remotely setting up working links to the scripts "
      .  "of this server). None of these tables hold unrecoverable informations: apart from the "
      .  "files saved to the c-disk folders, there's nothing that's worth to backup outside the "
      .  "SQL database."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=ataglance></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "AT A GLANCE"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "Look at the Explorer's form: there are many controls and fields, yes, and it may "
      .  "look complicated at a first glance. In reality... yeah, it is. But I did my best "
      .  "to make interacting with it as simple as possible, and it's also been programmed "
      .  "to be highly fail-safe. Yet it's very powerful, and administrators need to be "
      .  "quite careful when writing files or when restoring backups: if your action is an "
      .  "evident mistake, such as trying to restore a non-existing file or saving backups "
      .  "in the wrong folder, the DB Explorer will notice; in more subtle cases, it won't. "
      .  "Yet, subtle cases include the case in which someone just deletes, for instance, "
      .  "the first block of member account records, or even worse, the 'bynick' archive from "
      .  "the 'members' folder: the DB Explorer will never refuse to do such things, but they "
      .  "would cause, as a consequence, to completely lock out access to the board by any "
      .  "members, including the administrators BUT except the manager! The board would have "
      .  "to wait for the manager to recover it."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=exploring></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "EXPLORING THE DATABASE"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "As a first thing to know, you need javascripts enabled to use the DB Explorer. "
      .  "The bar on top holds a couple drop-down lists: one for folders, one for the files "
      .  "in the selected folder. Selecting a different folder automatically reloads the "
      .  "Explorer's page to provide a different list of files. Another way to force it to "
      .  "reload the list is by clicking the 'refresh' button. The selected file, which will "
      .  "initially be the first file found in the selected folder, will be loaded and its "
      .  "contents shown in the large text box placed in the middle of the page. About that..."
      . "</td>"
      . $clcart
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "KEEP AN EYE on the two green stripes holding status messages and file informations, "
      .  "just above the text box: one will tell you that the file has been READ (and not, "
      .  "for instance, written or deleted), and the other will reflect how many records and "
      .  "bytes are taken by effective data in the file. If there's an error, such as when a "
      .  "folder contains no files or all its files are hidden, the frame around the central "
      .  "parts of the page becomes RED; in consequence of operations that may alter the "
      .  "content of one or more files, the frame becomes YELLOW. Under normal circumstances, "
      .  "the frame is GREY."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=engine></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "ITS OWN RELATIONAL ENGINE"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "You may have been asking yourself why, being an SQL database made of tables, rows "
      .  "and columns, I've been talking of folders and files instead. It's a tribute to the "
      .  "origins of Postline, when those things were still effectively folders and files. "
      .  "But other than that, it's because as a consequence of its origins, Postline has its "
      .  "own relational database engine. And SQL? It's used to 'virtualize' a file system: "
      .  "basically, what was formerly a file became a row in a table, made of a name and an "
      .  "associated content; what was formerly a folder became a table in the database. Now, "
      .  "while SQL manages those virtual folders and files, the internal engine is what "
      .  "handles the stream of data saved inside the files. That stream is made of records, "
      .  "and each record has its own fields. Record boundaries separate records (they are, "
      .  "in reality, newline codes inside the data, but they are encoded in a more visible "
      .  "and less ambiguous way when seen in the DB Explorer's text box), and weird XML-like "
      .  "markers separate fields in each record. Every field name is encapsulated between a "
      .  "couple of &lt;these&gt; signs (less than, greater than), followed by the content of "
      .  "that field, but differently from XML and in lack of any 'closing tags', the content "
      .  "of a field is terminated by the 'less than' sign that marks the name of the next "
      .  "field. In absence of a next field (at the end of the record), the last field is so "
      .  "forced to terminate by appending a singleton 'less than' sign at the end of each "
      .  "record. That's the way Postline always worked: if you're gonna change files, you "
      .  "will have to follow these rules. You can even add or remove records, if you do so "
      .  "along with their boundary markers (___RECORD), change field names and contents. "
      .  "Especially, most often you will probably have to deal with contents - and beware "
      .  "that each file and each field has its own meaning: making random changes without "
      .  "knowing what that means to the scripts forming Postline is a sure way to get the "
      .  "whole thing to malfunction in no time."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=where></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "WHERE DO I CLICK?"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "Alright, I've been way too verbose. Descending into business, the most frequent "
      .  "actions to be done on the database are simplified by the controls below the text "
      .  "box, the first of which is the one to delete a file. You'd better not try that, "
      .  "just to make a test (if you are a moderator, you can: the Explorer will deny write "
      .  "access to you anyway). But since there's no buttons, where does one click to "
      .  "perform an action? On the circular gadget (a radio button) visible on the left "
      .  "side of each section. When you click that, you submit the form that constitutes "
      .  "most of the Explorer's page, and effectively request the script to take action. "
      .  "Next paragraphs explain when, and how, each action is supposed to be performed."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=deleting></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "DELETING FILES"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "You can delete one file per time, and it's intentionally made difficult to do "
      .  "by mistake. There are complessively four clicks into different points needed to "
      .  "make a valid request to delete the selected file (the one shown in the text "
      .  "box, and whose name appears in the drop-down list on top): you must first click "
      .  "on each of the three checkboxes, then click the radio button. If you leave one "
      .  "or more checkboxes unchecked, the Explorer will claim it's an unconfirmed request "
      .  "to delete the file, and the file will be left untouched. I suppose that the only "
      .  "possible use of the delete function is to delete a file that's been placed in a "
      .  "certain folder by mistake, or that was given the wrong name upon recreating it "
      .  "from a backup copy. In general, there are no useless files in the database. "
      .  "Finally, one last note: if you delete all files from a folder, the folder itself "
      .  "will cease to exist (its SQL table will be dropped), to be then re-created only if "
      .  "some script writes a new file to that folder."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=sfbackup></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "SINGLE-FILE BACKUP"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "To backup the selected file you may simply click the radio button in the proper "
      .  "section: the browser might pop up a dialog box prompting for where the backup is "
      .  "to be saved, as a file on your own computer. After backing up a file, the backup "
      .  "could be repeated at any time by clicking the same radio button again, but the "
      .  "said button will remain 'lit' in subsequent page loads for as long as you have "
      .  "been given an updated backup copy of that file. The button goes off when someone "
      .  "makes something that involves a write to that file: at that point, the list of "
      .  "updated backup owners is cleared, to mean that any former backup copy of that file "
      .  "is now ideally outdated. Ideally, because there are files, such as 'codes' in the "
      .  "'stats' folder (holding temporary sign-up validation codes), that are written very "
      .  "often and yet, they would be completely unimportant to restore the database in case "
      .  "of data loss. What I mean is: you may not worry for keeping your backup copies "
      .  "continuously up to date. For most uses, daily or even weekly backups will hold up "
      .  "perfectly fine, given a few targeted adjustments in the worst cases."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=sfrestore></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "SINGLE-FILE RESTORE"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "Should you (you, as an administrator) need to repair a single damaged file by "
      .  "saving back on the server your latest backup copy, you may proceed in two ways, "
      .  "depending on the situation. If the file was only corrupted, but still exists in "
      .  "the database, you could restore it using the 'overwrite this file' section, by "
      .  "clicking the corresponding 'browse' button and selecting the backup copy of that "
      .  "file; then, you just click on the radio button and wait for the server to receive "
      .  "the copy. Be sure that the selected file reflects the file you want to restore. "
      . "</td>"
      . $clcart
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "If, instead, the file was entirely deleted from its folder, you could still restore "
      .  "it by selecting the destination folder, then typing the name of the file that seems "
      .  "to have been completely deleted from there (eg. if 'cd/balance' was deleted, you'd "
      .  "navigate to the 'cd' folder and then type 'balance'): the name has to be typed in "
      .  "the text field next to the 'create file from backup' section. The file selector "
      .  "next to that field, like in the above paragraph, allows to select the backup copy "
      .  "from your computer. Finally, click the radio button on that line and you're done."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=rwarning></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "CAREFUL RESTORING THIS"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "There's a particular file, only one, that needs special attention when changing "
      .  "it or restoring it from a backup: it's 'counters' in the 'stats' folder. It holds "
      .  "several important counters, which purpose is creating unique integer identificators "
      .  "for member accounts, forums, threads, posts, and private messages entries. Each of "
      .  "those archives is organized by assigning a progressively-increasing integer ID to "
      .  "each record. For instance, the first account ever registered is that of the manager "
      .  "and is given an ID of 1. The second account has an ID of 2, and so on. Should one "
      .  "or more accounts get removed, their identification numbers are NOT reassigned for "
      .  "future accounts: they simply remain unused. But if, upon registering a new account, "
      .  "Postline reads the counter from that file, and finds the ID is already in use, it "
      .  "will refuse to register the account claiming an 'ID conflict', and until the "
      .  "counter corresponding to member 'signups' has been raised enough to no longer "
      .  "reflect an existing ID, nobody could ever register a new account. The same thing, "
      .  "more or less, happens as well for threads, posts, and private messages submissions. "
      . "</td>"
      . $clcart
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "Well, so what happens if you restore an outdated copy of 'counters' in the 'stats' "
      .  "folder? It may cause those malfunctions. So what if you don't have a recent backup "
      .  "of that file? You may simply try to check out how many progressive IDs have been "
      .  "assigned to each archive: which is the last member's ID? which is the last post's "
      .  "ID? which is the one of the last thread? and of the last forum? and so on... until "
      .  "you could simply re-assign the counters (by changing the 'counters' file) to what "
      .  "you know to be unused IDs for each archive."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=multibackup></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "MULTIPLE FILE BACKUP"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "As the board database grows larger, single backup files could be used to make "
      .  "copies of relatively recent additions and recently changed files; but every so "
      .  "often, staff members could feel the need to backup the whole database, thinking "
      .  "to, although improbable, a complete data loss, or in case the database must be "
      .  "transferred to another web host who cannot directly import archives from the "
      .  "actual host. In such cases, backup packets are surely useful to reduce the number "
      .  "of files constituting a full backup of the database. Backup packets are made out "
      .  "of all files in a given folder: but, since folders can hold very large amounts of "
      .  "files, and since there is a limit to the size of a file that could be uploaded to "
      .  "a server via HTTP forms, backup packets of large folders cannot usually hold the "
      .  "whole bouquet of files contained in those folders. The packet size limit is by "
      .  "default set to 1200 KiloBytes; typical web server settings allow to upload upto "
      .  "2000 KiloBytes in a single file (when the time comes to restore backup packets), "
      .  "and the extra 800 KB left by the default settings should allow for packets that "
      .  "are less than 2000 KB. That 'reserve' of extra KBs is left because when a packet "
      .  "is generated, it keeps accumulating files until the given limit is exceeded: so, "
      .  "there will always be an excess of data getting past the given limit, and yet it's "
      .  "highly improbable that a single file should add more than 800 Kb to the packet. "
      .  "Finally, other than the limit, packets have progressive indexing numbers, that "
      .  "might help storing them on the user's computer. Since their names are different "
      .  "from filenames of single backup copies, packets may also be held in your general "
      .  "backup directory (on your computer) all together with single backup files."
      . "</td>"
      . $clcart
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "In practice, here follows a step-by-step guide to completely backup a folder:"
      .  "<ol>"
      .   "<li>select the folder to backup</li>"
      .   "<li>click 'create backup packet'</li>"
      .   "<li>save packet file to your PC</li>"
      .   "<li>increase packet index by 1</li>"
      .   "<li>repeat 2-4 'til end of files</li>"
      .  "</ol>"
      .  "This way you will have downloaded all the data from the chosen folder. When "
      .  "you're done, the set of packets you saved on your computer is a complete backup "
      .  "of that folder. Hmm... in theory: but if you formerly made single backups out of "
      .  "selected files from the folder, the server will not include them in the packets. "
      .  "In those cases you might consider one further step between steps 1 and 2 of the "
      .  "above list, and this preliminary step consists in clicking 'disclaim backup copies' "
      .  "until the server tells you there's no more files to disclaim backups of. "
      .  "Doing this pratically tells the server to forget that you may own single-file "
      .  "backups, so it will include really all files in the packets. A quick way to check "
      .  "if you have up-to-date backups of parts of a folder is to activate the checkbox "
      .  "labeled 'list files to backup', and then to look at the DB Explorer's title: if "
      .  "it says there are NO HIDDEN FILES, you don't have any former backup copies of any "
      .  "files in that folder."
      . "</td>"
      . $clcart
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "Once you have created backup packets for all files in all folders of the database, "
      .  "you might keep your backups up to date by using 'list files to backup' as a filter "
      .  "that lets you see which files have changed since your last complete backup: daily, "
      .  "you could then download single backup copies to integrate the ones you have in the "
      .  "packets. In case of a future complete restore (due to catastrophic data loss) you "
      .  "should proceed to restore all folders from saved packets and then apply any single "
      .  "files downloaded after the packets, to 'patch' the database and bring it back to a "
      .  "relatively very recent state."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=strategy></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "HUGE BOARDS' STRATEGY"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "If your board grows very large, featuring thousand members and hundred thousand "
      .  "posts, you may also consider keeping your backups in more folders on your PC, then "
      .  "assign one to the full backup and the others to specially crafted patching packets. "
      .  "Then, rather than using single-file backups as a way to patch the database upon "
      .  "restoring it, you could use patching packets made out of any changed files from a "
      .  "folder in respect to the latest full backup. Consider that most of the oldest files "
      .  "in a database will typically show no changes for long periods of time (who should "
      .  "be ever interested in, say, editing a post in a 2-years-old thread?). If you're "
      .  "gonna use such a backup strategy, in practice, to make a set of patching packets "
      .  "you would follow all of the above steps except the one to disclaim former backup "
      .  "copies, so that your packets will automatically contain changed files only. Then, "
      .  "restoring such a database would begin by restoring the full (but outdated) backup, "
      .  "followed by restoring packets from each of your computer's backup folders, made in "
      .  "different dates after the latest full backup. With such large boards, consider not "
      .  "getting too picky in backing up everything too often: one set of patching packets "
      .  "every week, coupled with full backups every two or three months should be more than "
      .  "sufficient. After all if among a million messages, at a certain point a few hundreds "
      .  "got lost, it wouldn't be a big loss, would it?"
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=multirestore></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "MULTIPLE FILE RESTORE"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "This operation is far more dangerous and must be executed much more carefully "
      .  "than the above creation of backup packets for each folder. Restoring all files "
      .  "in a folder from a set of packets should not be a common circumstance: it would "
      .  "normally be necessary only if the database gets entirely lost, or if you're "
      .  "moving it to another web host. Yet, having complete backups done on, say, a montly "
      .  "basis, helps reduce the number of single-file backups necessary to 'patch' the "
      .  "database in case such a difficult recovery should be really needed. Anyway, keep "
      .  "in mind the following..."
      . "</td>"
      . $clcart
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "Restoring a massive part of the database from a set of packets is an operation "
      .  "that consumes server time and keeps the board locked for most of the time the "
      .  "operation goes on. It will slow down the server's capability of responding to "
      .  "users, not to say that user operations could conflict with the restore while in "
      .  "progress. Thus, a wise administrator would better disable board access to anyone "
      .  "who's not an administrator as well, by using the global community locker in the "
      .  "defense panel (CDP link): one single admin should take, at a given time, the "
      .  "responsibility to make multiple file restores, and tell the other admins not to "
      .  "interfere with the process by making anything that could cause a file to get "
      .  "written with new data (a simple note in the chat frame might be sufficient)."
      . "</td>"
      . $clcart
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "Now, for the pratical thing: restoring from backup packets is even easier than "
      .  "creating the packets themselves. You simply select your packets, one after the "
      .  "other (or even in sparse order, it doesn't really matter as long as all packets "
      .  "will be processed), using the very last file selector's 'browse' button to "
      .  "locate the packets on your PC, and them submitting the packet by clicking the "
      .  "button next to 'restore files from your saved packet'. The DB Explorer will do "
      .  "everything on its own: it takes the packet, verifies if its files belong to the "
      .  "actually selected folder, and if yes, proceeds to extract the files and write or "
      .  "create them in the database. If there are no errors, you can proceed to the next "
      .  "packet, and that's all."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=totalrecall></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "TOTAL RECALL"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "It's finally time to speak of total data loss and of moving the database to "
      .  "other hosts: the only one who may do that safely is the community manager. "
      .  "After installing Postline's scripts onto the other host, and creating a void "
      .  "SQL database for it to use, you will need to re-register a manager account on "
      .  "the new board, so you can grasp access to the DB Explorer and proceed to "
      .  "restore a full backup of the former board there. BUT THERE'S A WARNING: as you "
      .  "restore the database, the Explorer will not be overwriting your new management "
      .  "account, and the 'bynick' list giving the manager's nickname. So, at the end "
      .  "of the restore process, they will not reflect your former management account: "
      .  "for the solution, see what the following paragraph says."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=managerprotection></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "MANAGER PROTECTION"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "There is only one limit to the operations that can be done to data saved to "
      .  "the board's SQL database throught the DB Explorer: nobody, in no event, can "
      .  "alter or delete two very special records into two very special files. These "
      .  "are:"
      .  "<ol>"
      .   "<li>"
      .    "rec. #1 of 'members" . intervalOf (1, $bs_members) . "'"
      .   "</li>"
      .   "<li>"
      .    "rec. #1 of 'members/bynick'"
      .   "</li>"
      .  "</ol>"
      .  "No matter how you try, the DB Explorer will never alter those two records: "
      .  "they are what allows the community manager to access the board with proper "
      .  "access rights to perform operations via the DB Explorer. They are strongly "
      .  "protected because, even in case administration passwords should be stolen, "
      .  "eventual data loss could be restored from backups, but at that point there "
      .  "still would be the need for at least an account having enough privileges to "
      .  "restore damaged data."
      . "</td>"
      . $clcart
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "For all rules there are exceptions: if you're the manager, you will find an "
      .  "additional field at the bottom of the DB Explorer's page. It calls it MPO, "
      .  "Management account Protection Override, and allows to perform changes to the "
      .  "management account in exchange of an additional password (that has to be typed "
      .  "into that field before writing to the abovely mentioned records). There might "
      .  "be only one case in which this gets effectively useful: when the board is "
      .  "restored starting from a completely void, newly created database. In that case "
      .  "the manager has to signup before accessing the DBX to restore backups, but the "
      .  "new management account would be a new account, and would need to be restored "
      .  "along with the others, which you couldn't do unless you typed the MPO password "
      .  "before attempting to restore the relevant files into the 'members' folder."
      . "</td>"
      . $clcart
      . $inset_bridge

      . "<a name=postscript></a>"
      . "<table width=$pw>"
      .  "<tr>"
      .   "<td height=21 class=inv align=center>"
      .    "POSTSCRIPT: BROWSER WARS"
      .   "</td>"
      .  "</tr>"
      . "</table>"
      . $inset_bridge
      . $opcart
      . "<td class=ls>"
      .  "'Browser Wars' indicate the lack of standard behavior in different web browsers, "
      .  "while they're interpreting HTML, CSS, Javascript and other features of a web page. "
      .  "Because the interface to the DB Explorer is a web page, it can get affected by such "
      .  "browser-specific troubles. Myself, I design this set of scripts using Opera, which "
      .  "appears to be respecting the standards better than others, and which... ahem... "
      .  "well, it makes the forms look much cuter, but that's not the point. I then use the "
      .  "Firefox browser to typically surf the net, because it seems to reject any and all "
      .  "invasive advertisings and has very useful features. And I know MS Internet Explorer "
      .  "as well, because... it's impossible to ignore it. Each browser has its own specific "
      .  "advantages and disadvantages, however, what counts for the scope of this help page "
      .  "is analyzing the reactions of each of those three famous browser to the DB Explorer "
      .  "form, and here comes the pain. My MSIE6/XPclient shows very strange reactions after "
      .  "downloading a backup file or a backup packet: it downloads the file correctly, if "
      .  "you don't care (but I do) about the silly disambiguation numbers it puts between a "
      .  "couple brackets just after the intended file name; after downloading the file, "
      .  "though, the javascript that reloads the DB Explorer starts to give 'unknown' errors "
      .  "and eventually the browser would crash. I have no idea why, and all I can do is to "
      .  "discourage Postline administrators from using MSIE 6 to access the DB Explorer. Pick "
      .  "either Opera or Firefox, at your choice. IE is then rather fine to navigate other "
      .  "parts of Postline, just... avoid the DB Explorer with it. Then there's Firefox: it "
      .  "has the bad habit of not wrapping long lines of text in a text box unless the text "
      .  "includes explicit whitespace codes (blanks or tabs). Then, as well as in all other "
      .  "browsers, if a line of text is very long, it mysteriously starts to hide the line "
      .  "from the text box: extra long lines just wouldn't display properly. But Postline's "
      .  "database files can often contain such long lines, and that'd be a major problem, "
      .  "which, however, can be solved: if the DB Explorer script detects that you're using "
      .  "Firefox, it will split long lines in fragments of {$dbx_linesize} characters, "
      .  "fitting the text box. "
      .  "To later get the contents of the text box (upon writing manual changes to a "
      .  "file), and remember which lines were connected in a long line, it places special "
      .  "markers at the end of each splitted line: the markers are formed by a doublequote "
      .  "sign (&quot;) and a blank space (effectively forcing the browser to word-wrap). "
      .  "Those markers are completely removed upon submitting changes to a file, so they "
      .  "are harmless; besides, a file cannot contain an unescaped doublequote (which, if "
      .  "appearing in posts and other records, is instead escaped as '&amp;quot;'). Apart "
      .  "from noting how irritating such problems can be, that's all about them, so far."
      . "</td>"
      . $clcart
      . $inset_shadow;

/*
 *
 * template initialization
 *
 */

$sidebar = str_replace

  (

    array

      (

        "[FORM]",
        "[PERMALINK]"

      ),

    array

      (

        $form,
        $permalink

      ),

    $sidebar

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page upon showing or
 * hiding the chat frame, and for the online members list links (unless no-spy flag checked).
 *
 */

include ("setlfcookie.php");

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($sidebar));



?>
