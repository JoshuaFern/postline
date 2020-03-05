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

$em['moderation_rights_required'] = "MODERATION RIGHTS REQUIRED";
$ex['moderation_rights_required'] =

        "The link you have been following leads to a section of this system which is restricted "
      . "to members which have been authenticated as moderators or administrators. If you might "
      . "not be seeing this message, please make sure that your login cookie is intact and that "
      . "you are accessing this site from the same domain you used to perform the login.";

$em['management_rights_required'] = "SERVICE MANAGEMENT RIGHTS REQUIRED";
$ex['management_rights_required'] =

        "You attempted to access a folder of the database which is restricted to the "
      . "community manager. Such heavily protected folders contain perticularly sensitive "
      . "informations, like MD5 password hashes, potentially allowing to unlock access to "
      . "every account (except the manager's account).";

$em['no_security_code_match'] = "NO SECURITY CODE MATCH";
$ex['no_security_code_match'] =

        "All operations performed through Postline scripts must be authenticated by a security "
      . "code submitted with the rest of the arguments of each page requests. This code varies "
      . "for each request. The reason why this error message is being presented to you is that "
      . "a request has been just made from your computer to perform significant operations via "
      . "one of these scripts, but no security code, or an outdated security code, was sent in "
      . "its arguments. It may result from different causes, among which, hitting the 'back' "
      . "button of your browser after having submitted a form, as well as interacting with two "
      . "or more instances of these scripts together (e.g. from two or more distinct browsers, "
      . "tabs, or computers). In the worst case, if you did not at all expect this message, it "
      . "may have been an attempt to trick you into executing commands in this script by means "
      . "of remote links, eventually disguising as something else: this is the main reason why "
      . "the security codes are implemented, and in such cases, this message means the attempt "
      . "has failed.";

/*
 *
 * this script is generally restricted to administrators and moderators
 *
 */

if (($is_admin == false) && ($is_mod == false)) {

  unlockSession ();
  die (because ('moderation_rights_required'));

}

/*
 *
 * get safety records to protect management account
 *
 */

$man_block = intervalOf (1, $bs_members);
$man_entry = get ("members/bynick", "id>1", "");
$man_record = get ("members" . $man_block, "id>1", "");
$man_record = fieldSet ($man_record, "pass", "N/A");
$man_nickname = valueOf ($man_entry, "nick");
$man_protection_override = ($_POST["mpo"] == $mpo_password) ? true : false;

/*
 *
 * declare safe write function to make above protection effective:
 *
 * it's a wrapper for write_to, guarding only writes to two particular files, being the
 * nicknames list and the first block of members; in the nicknames list (members/bynick)
 * there must be $man_entry as a record (and only one occurrence of it); in the first
 * block of members, there must be $man_record as a record (and only one occurrence of it):
 * if it isn't so, the function FORCES the file to respect the above conditions, so neither
 * the nickname entry, nor the account record, for the account having ID #1, could be ever
 * deleted or changed via the DB Explorer, not even if this script is used by the manager in
 * person (imagine that someone discovered the manager's password: alright, he/she could even
 * delete all files in the database, but they could be later recovered from backups, BUT...
 * there must still be a way to access the DB explorer to at least restore the backups).
 *
 */

function safe_write_to ($file, $contents) {

  global $man_block;
  global $man_entry;
  global $man_record;
  global $man_nickname;
  global $man_protection_override;

  if ($man_protection_override) {

    writeTo ($file, $contents);

  }
  else {

    $to_check = ($file == "members/bynick") ? true : false;
    $to_check = ($file == "members" . $man_block) ? true : $to_check;

    if ($to_check) {

      $contents = rtrim ($contents, "<\n");
      $records_in = (empty ($contents)) ? array () : explode ("<\n", $contents);

      $records_out = array ();

      foreach ($records_in as $r) {
        if (intval (valueOf ($r, "id")) != 1) {
          if (valueOf ($r, "nick") != $man_nickname) {

            $records_out[] = $r;

          }
        }
      }

      $records_out[] = ($file == "members/bynick") ? $man_entry : $man_record;
      $contents = implode ("<\n", $records_out) . "<\n";

    }

    writeTo ($file, $contents);

  }

}

/*
 *
 * determine used browser:
 * as noted in tests, complex behavior of this form may need to address browser-specific issues
 *
 * AFFECTED BROWSER, HIGHEST VERSION TESTED, PROBLEM DESCRIPTION                CAN BE SOLVED??
 * --------------------------------------------------------------------------------------------
 * Firefox 1.5 won't wrap long lines of text unless meeting an explicit whitespace     YES
 * MSIE6.0/XP appends arbitrary disambiguation numbers to intended backup file names   NO
 * MSIE6.0/XP javascript fails after saving backup ("unknown" error claimed, or crash) ?
 * Opera 9.xx-9.10 has bugs in word-wrapping for textareas, making it hardly useable   NO
 *
 */

$fit_firefox = (strstr ($_SERVER["HTTP_USER_AGENT"], "Firefox") === false) ? false : true;
$fit_firefox = true;

/*
 *
 * get parameters
 *
 */

$folder = fget ("folder", 1000, "");    // submitted folder name
$file = fget ("file", 1000, "");        // submitted file name
$qn = isset ($_POST["qn"]);             // quick navigation flag

/*
 *
 * restrict access to "defrag" and "members" folders:
 * allow only community manager, to protect other administrators' MD5 password hashes
 *
 */

$restricted_folders =
  array (

    "members",
    "defrag"

  );

$is_restricted_folder = in_array ($folder, $restricted_folders);

if (($is_restricted_folder) && ($id != 1)) {

  unlockSession ();
  die (because ('management_rights_required'));

}

/*
 *
 * initialize accumulators
 *
 */

$nfiles = 0;                            // global counter of listed files (used in title)
$hfiles = 0;                            // global counter of hidden files (used in title)
$former_error = false;                  // true to preserve error messages on restore requests
$processed_packet = "";                 // reminder for the user to properly follow the sequence

/*
 *
 * get request triggers
 *
 */

$request_to_delete = isset ($_POST["request_to_delete"]);               // ADMIN-LEVEL
$request_to_save = isset ($_POST["request_to_save"]);                   // ADMIN-LEVEL
$request_to_restore = isset ($_POST["request_to_restore"]);             // ADMIN-LEVEL
$restore_backup_packet = isset ($_POST["restore_backup_packet"]);       // ADMIN-LEVEL

$disclaim_backups = isset ($_POST["disclaim_backups"]);                 // MOD-LEVEL
$request_to_backup = isset ($_POST["request_to_backup"]);               // MOD-LEVEL
$create_backup_packet = isset ($_POST["create_backup_packet"]);         // MOD-LEVEL

/*
 *
 * conflicting requests check:
 * this script requires to do only ONE thing at a time
 *
 */

$requests = array (

  $request_to_delete,
  $request_to_save,
  $request_to_restore,
  $restore_backup_packet,
  $disclaim_backups,
  $request_to_backup,
  $create_backup_packet

);

$effective_requests = wArrayDiff ($requests, array (false));

if (count ($effective_requests) > 1) {

  $operation = "CONFLICTING REQUESTS";
  $opclass = "xr";

  $request_to_delete = false;
  $request_to_save = false;
  $request_to_restore = false;
  $restore_backup_packet = false;
  $disclaim_backups = false;
  $request_to_backup = false;
  $create_backup_packet = false;

  $former_error = true;

}

/*
 *
 * check security code
 *
 */

if ($effective_requests) {

  $code = intval (fget ("code", 1000, ""));

  if ($code != getcode ("pan")) {

    unlockSession ();
    die (because ('no_security_code_match'));

  }

}

/*
 *
 * write access rights check:
 * moderators work in read-only mode, and may never save changes, restore, or delete files
 *
 */

if ($is_admin == false) {

  if (

       ($request_to_delete == true)  ||
       ($request_to_save == true)  ||
       ($request_to_restore == true)  ||
       ($restore_backup_packet == true)

     )

  {

    $operation = "MISSING ADMINISTRATIVE RIGHTS - WRITE ACCESS DENIED";
    $opclass = "xr";

    $former_error = true;

  }

  $request_to_delete = false;
  $request_to_save = false;
  $request_to_restore = false;
  $restore_backup_packet = false;

}

/*
 *
 * validate delete request:
 * delete requests must also be confirmed THRICE to be valid
 *
 */

if ($request_to_delete == true) {

  $confirm_1 = isset ($_POST["delete_confirm_1"]);
  $confirm_2 = isset ($_POST["delete_confirm_2"]);
  $confirm_3 = isset ($_POST["delete_confirm_3"]);

  if (($confirm_1 == false) || ($confirm_2 == false) || ($confirm_3 == false)) {

    $operation = "UNCONFIRMED DELETION";
    $opclass = "xr";

    $former_error = true;
    $request_to_delete = false;

  }

}

/*
 *
 * validate single-file restore request:
 * the request will be processed later, similarly to a "save changes" request
 *
 */

if ($request_to_restore == true) {

  /*
    if there's a request to restore, check out the respective file selector's results:
    - $copy_size is the size in bytes of the file that the user selected on his/her computer,
    - $copy_name is the name of the backup copy the user selected on his/her computer,
    - $copy_file is the name to which the same file was saved on the server.
  */

  $copy_size = (!empty ($_FILES["copy"]["size"])) ? $_FILES["copy"]["size"] : 0;
  $copy_name = (!empty ($_FILES["copy"]["name"])) ? $_FILES["copy"]["name"] : "";
  $copy_file = ($_FILES["copy"]["tmp_name"] != "none") ? $_FILES["copy"]["tmp_name"] : "";

  /*
    for a valid restore request, both above fields might not be void:
    the $copy_name field is otherwise ignored throught the rest of the process...
  */

  if (empty ($copy_name)) $request_to_restore = false;
  if (empty ($copy_file)) $request_to_restore = false;

  if ($request_to_restore == false) {

    $operation = "MISSING FILE TO RESTORE FROM";
    $opclass = "xr";

    $former_error = true;

  }

  /*
    for a valid restore request, the file must have been uploaded via the intended HTTP form,
    and it must not be void (uploading a void file would delete the existing file server-side)
  */

  if (is_uploaded_file ($copy_file) == false) $request_to_restore = false;
  if ($copy_size == 0) $request_to_restore = false;

  if (($former_error == false) && ($request_to_restore == false)) {

    $operation = "VOID BACKUP COPY OR INVALID REQUEST";
    $opclass = "xr";

    $former_error = true;

  }

}

/*
 *
 * build folder selector, verify submitted folder's existence:
 * oh, and hide restricted folders to non-managers; besides, they couldn't access them...
 *
 */

$folderselect = "<select name=folder style=width:100px onChange=reload_explorer()>";
$folders = getFolders ();

sort ($folders);

if ($id != 1) {

  $folders = wArrayDiff ($folders, $restricted_folders);

}

if (count ($folders) == 0) {

  $folderselect .= "<option value=\"(no folders)\">(no folders)</option>";
  $folder = "";

}
else {

  if (!in_array ($folder, $folders)) {

    $folder = $folders[0];

  }

  foreach ($folders as $f) {

    $folderselect .= "<option value=\"$f\""
                  .  (($f == $folder) ? chr (32) . "selected=selected" : "")
                  .  ">$f</option>";

  }

}

$folderselect .= "</select>";

/*
 *
 * if folder exists, handle eventual requests to create or restore a backup packet:
 * since this code comes first, they have precedence over single-file backup requests
 *
 */

if (!empty ($folder)) {

  if ($disclaim_backups == true) {

    /*
      request to disclaim all backup copies the user may have made of any files held
      in the selected folder: it's done in case of troubles downloading backups that
      may have been lost along the way or in general to create FURTHER backup copies
      and packets of the same files... in fact, the code that creates backup packets
      would refuse to "redo" a given packet again, unless its files were changed...
    */

    $files = getFiles ($folder);
    $disclaimed = 0;

    foreach ($files as $f) {

      $disclaimed += unSetBackupOwner ("$folder/$f", $nick);

    }

    $operation = "OK: $disclaimed FILES DISCLAIMED";
    $opclass = "xg";

    $former_error = true;       // it's no error, but to witheld "file has been read" message

  }

  elseif ($create_backup_packet == true) {

    /*
      request to create a backup packet:
      a packet consists of a series of files one after the other and separated by special
      boundaries, but on itself is a single file, which size has a top limit specified as
      a form argument ("packet_size") and expressed in kilobytes; this code will create a
      list of files in this folder and create the packet out of any file that the current
      user doesn't own an updated backup copy for (updated = done after last file write).
    */

    $files = getFiles ($folder);
    $filtered_files = array ();

    sort ($files);

    foreach ($files as $f) {

      if (!in_array ($nick, getBackupOwners ("$folder/$f"))) {

        $filtered_files[] = $f;

      }

    }

    if (count ($filtered_files) == 0) {

      $operation = "NO FILES TO BACKUP FROM THIS FOLDER";
      $opclass = "xr";

      $former_error = true;

    }
    else {

      /*
        get progressive index of backup packet, pad to 5 digits
      */

      $index = intval (fget ("packet_index", 10, ""));
      $index = str_pad ($index, 4, "0", STR_PAD_LEFT);

      /*
        begin creating backup packet:
        set headers for the browser to know what it might do with this page's output
      */

      header ("Content-type: text/plain");
      header ("Content-Disposition: attachment; filename={$folder}__packet_$index.txt");

      /*
        it is generally not necessary to keep the session locked, and creation of a backup
        packet may take long; it's not necessary because the only queries performed to
        modify the database, during this process, are those to list this user as a backup
        owner of the files in the packet, which might not suffer from concurrent access...
      */

      unlockSession ();

      /*
        before beginning the loop to output files to the packet, stream an indication:
        the name of the folder this packet belongs to; it may be an easy mistake to try
        restoring a packet in the wrong folder, or to select the wrong packet for the
        intended folder: in such cases, the code to restore from a packet will check the
        following indication and eventually claim a "folder mismatch" error before doing
        a potentially catastrophic overwrite over multiple files in the WRONG place...
      */

      echo ("INFORMATION: THIS PACKET BELONGS TO THE <$folder> FOLDER");

      $packet_volume = 0;
      $packet_size = intval (fget ("packet_size", 10, "")) * 1024;

      foreach ($filtered_files as $f) {

        /*
          record this user as a backup owner, so its nick shows in the subsequent report
        */

        setBackupOwner ("$folder/$f", $nick);

        $owners = getBackupOwners ("$folder/$f");
        $owners = (count ($owners) == 0) ? array ("NOBODY") : $owners;
        $report = ". KNOWN BACKUP OWNERS SINCE LAST FILE WRITE:\n. " . implode ("\n. ", $owners);

        /*
          output file boundary
        */

        echo ("\n\n___ FILE <$f>\n\n");

        /*
          read file contents, build compatible version
        */

        $blob = readFrom ("$folder/$f");
        $packet_volume += strlen ($blob);

        $text = str_replace (

          array (       "&quot;",       "&lt;",         "&gt;"          ),
          array (       "&amp;quot;",   "&amp;lt;",     "&amp;gt;"      ),

          preg_replace ("/(^\n+)|(\n+$)/", "", $blob)

        );

        $recs = ($text == voidString) ? array () : explode ("\n", rtrim ($text));
        $text = $report;

        $n = 1;

        foreach ($recs as $r) {

          $text .= "\n\n___ RECORD #$n\n\n";
          $text .= $r;

          $n ++;

        }

        /*
          send compatible version to the browser, as part of the page output
        */

        echo ($text);

        /*
          stop looping if maximum requested size of packet has been reached (or exceeded):
          ideally, remaining files to backup will be returned in later packets
        */

        if ($packet_volume >= $packet_size) {

          break;

        }

      }

      /*
        end packet creation:
        quitting with no further output from this script
      */

      exit;

    }

  }

  elseif ($restore_backup_packet == true) {

    /*
      request to restore files from a backup packet:
      said packet will be read as an attached file (saved in the server's temporary directory)
      and all files will be either overwritten with corresponding backup copies, or re-created
      in case they didn't exist in the database; needless to say, the operation is potentially
      very dangerous if done at the wrong time: a checkbox ("except_newer") allows to exclude,
      from the list of files to restore, any files that have been changed past the last backup
      made by this member. Yet, note that there is NO WAY to tell if the last backup was saved
      in a packet or as a single file: if the latter, the file may not be in the packet and it
      may not be restored; for this reason it's important to decide FIRST wether to use single
      files or packets to keep the backup copies of a certain folder, but it's a question that
      concerns only the user.
      packet analysis begins like with single-file restore process:
      the source file is in fact always only one, except that in this case it represents
      multiple files (the packet holds their data separated with appropriate boundaries)
    */

    $copy_size = (!empty ($_FILES["copy"]["size"])) ? $_FILES["copy"]["size"] : 0;
    $copy_name = (!empty ($_FILES["copy"]["name"])) ? $_FILES["copy"]["name"] : "";
    $copy_file = ($_FILES["copy"]["tmp_name"] != "none") ? $_FILES["copy"]["tmp_name"] : "";

    /*
      for a valid restore request, both above fields might not be void:
      the $copy_name field is otherwise ignored throught the rest of the process...
    */

    if (empty ($copy_name)) $restore_backup_packet = false;
    if (empty ($copy_file)) $restore_backup_packet = false;

    if ($restore_backup_packet == false) {

      $operation = "MISSING PACKET TO RESTORE FROM";
      $opclass = "xr";

      $former_error = true;

    }

    /*
      for a valid restore request, the file must have been uploaded via the intended HTTP form,
      and it must not be void (uploading a void file would delete the existing file server-side)
    */

    if (is_uploaded_file ($copy_file) == false) $restore_backup_packet = false;
    if ($copy_size == 0) $restore_backup_packet = false;

    if (($former_error == false) && ($restore_backup_packet == false)) {

      $operation = "VOID BACKUP PACKET OR INVALID REQUEST";
      $opclass = "xr";

      $former_error = true;

    }

    /*
      alright, now read the file, and strip leading/trailing whitespaces:
      it's done entirely into memory, so it's better NEVER to exceed with packets size!
    */

    if ($restore_backup_packet == true) {

      $text = trim (file_get_contents ($copy_file));
      $text = str_replace (array ("\r\n", "\r"), "\n", $text);

      /*
        match all file names in legal boundaries (giving the $names array),
        split file boundaries (giving $text array, sliced out whatever preceeds 1st boundary)
      */

      $filecount = preg_match_all ("/(\n*)\_\_\_(\s*)FILE(\s*)\<(.+)\>(\n+)/", $text, $names);
      $names = $names[0];     // insulate full pattern matches, ie. whole boundary lines

      $text = preg_split ("/(\n*)\_\_\_(\s*)FILE(\s*)\<(.+)\>(\n+)/", $text);
      $destination_folder = strFromTo ($text[0], "<", ">");
      $text = array_slice ($text, 1);

      if ($filecount == 0) {

        $operation = "NO FILE BOUNDARIES IN BACKUP PACKET";
        $opclass = "xr";

        $restore_backup_packet = false;
        $former_error = true;

      }

      elseif ($filecount != count ($text)) {

        $operation = "UNMATCHED BOUNDARIES IN BACKUP PACKET";
        $opclass = "xr";

        $restore_backup_packet = false;
        $former_error = true;

      }
      elseif (empty ($destination_folder)) {

        $operation = "NO TARGET FOLDER INDICATION IN PACKET";
        $opclass = "xr";

        $restore_backup_packet = false;
        $former_error = true;

      }
      elseif ($destination_folder != $folder) {

        if ($id == 1) {

          $one_way = (($destination_folder == "defrag") && ($folder == "members")) ? true : false;
          $create_new_folder = (in_array ($destination_folder, $folders)) ? false : true;

          if ($create_new_folder) {

            $folder = $destination_folder;
            $folders[] = $destination_folder;

            sort ($folders);

            $folderselect = "<select name=folder style=width:100px onChange=reload_explorer()>";

            foreach ($folders as $f) {

              $folderselect .= "<option value=\"$f\""
                            .  (($f == $folder) ? chr (32) . "selected=selected" : "")
                            .  ">$f</option>";

            }

            $folderselect .= "</select>";

          }

        }
        else {

          $one_way = false;
          $create_new_folder = false;

        }

        if (($one_way == false) && ($create_new_folder == false)) {

          $operation = "FOLDER MISMATCH - PACKET BELONGS TO &quot;$destination_folder&quot;";
          $opclass = "xr";

          $restore_backup_packet = false;
          $former_error = true;

        }

      }

    }

    /*
      if the file passed all above tests, begin restoring:
      it might still take long, but it CANNOT avoid locking access, this time
    */

    if ($restore_backup_packet == true) {

      lockSession ();

      $parsed = 0;
      $created = 0;
      $written = 0;
      $newer = 0;

      $existing_files = getFiles ($folder);

      foreach ($text as $t) {

        $f = strFromTo ($names[$parsed], "<", ">");
        $is_existing_file = in_array ($f, $existing_files);

        if (($is_existing_file == true) && (isset ($_POST["except_newer"]))) {

          $changed = (in_array ($nick, getBackupOwners ("$folder/$f"))) ? false : true;

        }
        else {

          $changed = false;

        }

        if ($changed == false) {

          $t = array_slice (preg_split ("/(\n*)\_\_\_(\s*)RECORD(\s*)\#(\d+)(\n+)/", $t), 1);

          unset ($blob);

          foreach ($t as $r) {

            $r = preg_replace ("/(\s*)\"(\s*)/", "", $r);     // may be long-line-breakpoint
            $blob .= str_replace ("\n", chr (32), $r) . "\n"; // append to "binary" object

          }

          if (!empty ($blob)) {

            /*
              if data is submitted via the web form, escaping "&amp;" tags into the text are
              automatically translated back by the browser upon submitting the form, but if
              data is read from a file, there's obviously no such backward-translation, and
              it's the responsibility of the following code to un-escape these 3 characters...
            */

            $blob = str_replace (

              array (   "&amp;quot;",   "&amp;lt;",     "&amp;gt;"      ),
              array (   "&quot;",       "&lt;",         "&gt;"          ),

              $blob

            );

            /*
              after the write (which is normally cached), call to "flush_cache" will force
              the cache to be flushed and emptied, so that dbfwrite is effectively called in
              w-mode to write the file; only then, list_backup_owner is called to update the
              written file's record and reflect the fact that the file was restored by this user:
              if "flush_cache" wasn't called, "list_backup_owner" would immediately write to
              the list of backup owners of that file, but then on completion of this script's
              execution, the cache would be flushed and the writes to cached files would discard
              the backup owner's nickname from the list; in a nutshell, it's important that the
              file gets effectively written before "list_backup_owner" is called...
            */

            safe_write_to ("$folder/$f", $blob);

            flushCache (); // flush before...
            setBackupOwner ("$folder/$f", $nick); // ...and list after

            /*
              if it existed, raise written files count, else write created files count
            */

            if ($is_existing_file == true) {

              $written ++;

            }
            else {

              $created ++;

            }

          }

        }
        else {

          $newer ++;

        }

        $parsed ++;

      }

      $operation = "OK: $parsed PARSED, $created CREATED, $written RESTORED, $newer NEWER";
      $opclass = "xy";

      $former_error = true;   // it's no error, but to witheld "file has been read" message
      $processed_packet = $copy_name;   // this is... just a commodity reminder for the user

    }

  }

}

/*
 *
 * build file selector, verify submitted file's existence
 *
 */

$fileselect = "<select name=file style=width:100px onChange=reload_explorer()>";
$files = getFiles ($folder);

sort ($files);

if (($request_to_restore == true) && (isset ($_POST["create_new"]))) {

  $filtered_files = array ($file);      // so it won't set $file to first existing file

}
else {

  $filtered_files = array ();

}

foreach ($files as $f) {

  if (isset ($_POST["show_files_to_backup"])) {

    $user_owns_backup = in_array ($nick, getBackupOwners ("$folder/$f"));

  }
  else {

    $user_owns_backup = false;          // so list anyway

  }

  if ($user_owns_backup == false) {

    $filtered_files[] = $f;

  }
  else {

    $hfiles ++;

  }

}

if (count ($filtered_files) == 0) {

  $fileselect .= "<option value=\"(no files)\">(no files)</option>";
  $file = "";

}
else {

  /*
    "normalize" missing or wrong file name to correspond to first existing file:
    in case of restore requests, if the request was made to re-create a file from its
    backup copy, the $filtered_files array will surely hold the file name, because it
    was forced there by above code; if the request was made to simply restore a file,
    without the "create_new" flag and the file doesn't appear in $filtered_files, the
    script will set $file to an empty string, which will later produce an error.
  */

  if (!in_array ($file, $filtered_files)) {

    if ($request_to_restore == false) {

      $file = $filtered_files[0];

    }
    else {

      $file = "";

    }

  }

  foreach ($filtered_files as $f) {

    $nfiles ++;

    $fileselect .= "<option value=\"$f\""
                .  (($f == $file) ? chr (32) . "selected=selected" : "")
                .  ">$f</option>";

  }

}

$fileselect .= "</select>";

/*
 *
 * set general error flag if either file or folder don't exist
 *
 */

if ((empty ($folder)) || (empty ($file))) {

  $error = true;

}
else {

  $error = false;

}

/*
 *
 * load selected file,
 * process requests to save changes or restore from backup
 *
 */

if ($error == true) {

  $operation = "NO FILE TO LIST (ALL FILES HIDDEN)";
  $opclass = "xr";

  $blob = "";

}
else {

  if (($request_to_save == false) && ($request_to_restore == false)) {

    /*
      common behavior:
      typically reads the file and shows its contents for monitoring purposes,
      but delete requests are also finalized here...
    */

    if ($request_to_delete == false) {

      if ($former_error == false) {

        $operation = ($qn)

          ? "$nfiles FILE(S) LISTED, $hfiles HIDDEN, NO FILE READ"
          : "FILE HAS BEEN READ";

        $opclass = "xg";

      }

      $blob = ($qn) ? "" : readFrom ("$folder/$file");

    }
    else {

      $operation = "FILE HAS BEEN DELETED";
      $opclass = "xy";

      lockSession ();
      safe_write_to ("$folder/$file", voidString);

      invalidateCache ();
      $blob = readFrom ("$folder/$file");

      /*
        delete requests aren't normally suppose to happen, so better log this event
      */

      logwr ("Raw deletion of database file: &quot;$folder/$file&quot;.", lw_persistent);

    }

  }
  else {

    /*
      write-back file:
      get posted contents, or restored copy contents
    */

    if ($request_to_save == true) {

      /*
        text comes from an explicit request to save file back via the HTTP form:
        that is, the user clicked "save changes"...
      */

      $text = wStripslashes ($_POST["content"]);

    }
    else {

      /*
        text will be read from an uploaded backup copy to restore this single file:
        if data is submitted via the web form, escaping "&amp;" tags into the text are
        automatically translated back by the browser upon submitting the form, but if
        data is read from a file, there's obviously no such backward-translation, and
        it's the responsibility of the following code to un-escape these 3 characters...
      */

      $text = str_replace (

        array (   "&amp;quot;",   "&amp;lt;",     "&amp;gt;"      ),
        array (   "&quot;",       "&lt;",         "&gt;"          ),

        file_get_contents ($copy_file)

      );

    }

    /*
      standardize CR+LF (Win), or CR (Mac), to single LF (Unix)
    */

    $text = trim ($text);
    $text = str_replace (array ("\r\n", "\r"), "\n", $text);

    /*
      split record boundaries:
      it won't be too picky about them, it only looks for the general appearence of a boundary,
      disregarding details that the user may have changed without noticing (such as added CR/LF
      sequences, and extra blanks); what prevents the boundary from being part of a record that
      has been saved to the database by other mean (throught the "fget" function of some field)
      is the fact that a boundary, to be valid, needs to be followed by at least 1 newline code
      which would be converted to Postline's internal marker "&;" if passed via a "fget"...
    */

    $text = preg_split ("/(\n*)\_\_\_(\s*)RECORD(\s*)\#(\d+)(\n+)/", $text);

    /*
      I know that before the first record boundary, there should be no effective text:
      so I'll unconditionally slice out the first element from the resulting array...
    */

    $text = array_slice ($text, 1);

    /*
      replace any newline codes found INSIDE a record (it's a mistake to have them there, but
      it may easily happen to strike "enter" while editing records spanning multiple lines in
      word-wrap) with blank spaces (which is what I suppose would be in the user's intent...)
      ---
      meanwhile, regenerate file stream:
      implode records using newline codes, append a final newline code to terminate last record.
    */

    unset ($blob);

    foreach ($text as $r) {

      $r = preg_replace ("/(\s*)\"(\s*)/", "", $r);     // replace back long-line-breakpoints
      $blob .= str_replace ("\n", chr (32), $r) . "\n"; // append to "binary" object

    }

    /*
      write file back:
      then, invalidate cache and verify the write by re-reading the file
    */

    if (empty ($blob)) {

      $operation = "NO RECORD BOUNDARIES IN SUBMITTED FILE";
      $opclass = "xr";

      $blob = ($qn) ? "" : readFrom ("$folder/$file");

    }
    else {

      $operation = "FILE HAS BEEN WRITTEN";
      $opclass = "xy";

      lockSession ();
      safe_write_to ("$folder/$file", $blob);

      invalidateCache ();
      $blob = readFrom ("$folder/$file");

      /*
        if the write was made in consequence of a restore request, it is evident that the user
        still has an updated backup, but "write_to" resets backup owners to signal the file has
        changed since last backup: so if it was a restore, be sure to list the current user
        again as the (actually only) owner of a backup copy of this file...
      */

      if ($request_to_restore == true) {

        setBackupOwner ("$folder/$file", $nick);

      }

    }

  }

}

/*
 *
 * generate file's informative report (prepended to first record)
 *
 */

if ($error == true) {

  /*
    if there was an error locating this file, here's the report
  */

  $report = ". NO FILE";

}
else {

  /*
    if there's a request to backup this file, which will be handled later,
    for now record this user as a backup owner, so its nick shows in the subsequent report
  */

  if ($request_to_backup == true) {

    setBackupOwner ("$folder/$file", $nick);

  }

  $owners = getBackupOwners ("$folder/$file");
  $owners = (count ($owners) == 0) ? array ("NOBODY") : $owners;
  $report = ". KNOWN BACKUP OWNERS SINCE LAST FILE WRITE:\n. " . implode ("\n. ", $owners);

}

/*
 *
 * [safety precaution, added June 8, 2006]
 *
 * the following line converts any unknown ASCII text formatters before showing file contents
 * in the text area, to the string "[?]"; well, there might be no way to insert such codes in
 * database files, but I had an occurrence of a NULL code (ASCII 0) in a file, being there
 * since long because of a missing check in an old script that took care of converting PL2005
 * flat-file databases to PL2006 SQL format; and well, having a NULL character into $text was
 * causing the form to truncate the file there either on reads (Opera) or writes (Firefox):
 * first of all the following precaution allowed me to save the file back in valid plain text
 * form, but I'm keeping the line here anyway to face possibly corrupted files in the future.
 *
 */

$blob = preg_replace ("/[\\x00-\\x08\\x0b\\x0c\\x0e-\\x1f]/", "[?]", $blob);

/*
 *
 * build "form-compatible" version of file contents, ie.
 *
 * 1) double-escape improper characters (eg. &quot; becomes &amp;quot;),
 * 2) remove any leading or trailing newline codes (they'd be mistakes),
 * 3) count and insert record boundaries in place of any real newline codes.
 *
 * and eventually,
 * due to Firefox NOT WRAPPING LONG LINES IN TEXT AREAS UNLESS SPACED (grr!)
 *
 * 4) break long unspaced records in fragments that fit the 72-character range
 *    using a combination of chr(34), an unescaped doublequote, and chr(32), a
 *    blank space; the doublequote is a signal for when this script saves back
 *    a file, indicating that the blank space that follows is NOT a real space
 *    but was only forced at that point of the record to solve Firefox problem.
 *
 */

$text = str_replace (

  array (       "&quot;",       "&lt;",         "&gt;"          ),
  array (       "&amp;quot;",   "&amp;lt;",     "&amp;gt;"      ),

  preg_replace ("/(^\n+)|(\n+$)/", "", $blob)

);

$recs = ($text == voidString) ? array () : explode ("\n", rtrim ($text));
$text = $report;

$n = count ($recs);

foreach ($recs as $r) {

  $text .= "\n\n___ RECORD #$n\n\n";
  $text .= ($fit_firefox == true) ? breakUnspaced ($r, $dbx_lines_size, chr (34) . chr (32)) : $r;

  $n --;

}

/*
 *
 * if file was located successfully, handle eventual backup requests
 *
 */

if ($error == false) {

  if ($request_to_backup == true) {

    header ("Content-type: text/plain");
    header ("Content-Disposition: attachment; filename={$folder}__$file.txt");

    unlockSession ();   // always, before leaving a Postline script
    die ($text);        // quit, producing a plain-text output from $text

  }

}

/*
 *
 * delete any temporary "restore" files:
 * if the request was valid, the uploaded copy was already read by above code...
 *
 */

if (($request_to_restore == true) || ($restore_backup_packet == true)) {

  @unlink ($copy_file);

}

/*
 *
 * build DB explorer form
 *
 */

$code = setcode ("pan");

$info = "\n"

        /*
          the following script is invoked to refresh the database explorer in consequence
          of a change in a combo box ("folder" or "file") or in the checkbox to toggle the
          listing of files that have been already backed up ("list files to backup")...
        */

      . "<script language=Javascript type=text/javascript>\n"
      . "<!--\n"
      . "function reload_explorer(){"
      .  "document.explore.content.value='';"
      .  "document.explore.submit();"
      . "}\n"
      . "//-->\n"
      . "</script>\n"

      /*
        open borderline tables
      */

      . "<table style=width:100%;height:100%>"
      .  "<td align=center>"

      /*
        help link
      */

      . "<table class=ct>"
      .  "<td class=cb align=center>"
      .   "[ <a href=xhelp.php target=pst style=color:#c22>click here for help</a> ]"
      .  "</td>"
      . "</table>"

      /*
        main table, holding status messages, file informations, and the file editing area,
        and also constituting the main explorer form (the form called "explore"), which
        natively holds the checkboxes to list only files to backup and quick navigation:
        that's why it doesn't hold "hidden" input controls echoing those values, like all
        of the subsequent forms do...
      */

      . "<table class=mt>"
      . "<form name=explore action=explorer.php enctype=multipart/form-data method=post>"
      . "<input type=hidden name=mpo value=\"\">"
      . "<input type=hidden name=code value=$code>"
      .  "<tr>"
      .   "<td class=cc align=right>"
      .    "folder:"
      .   "</td>"
      .   "<td class=cc>"
      .    $folderselect
      .   "</td>"
      .   "<td class=cc align=right>"
      .    "file:"
      .   "</td>"
      .   "<td class=cc>"
      .    $fileselect
      .   "</td>"
      .   "<td class=cc>"
      .    "<input class=rf name=refresh type=submit value=refresh>"
      .   "</td>"
      .   "<td width=1 class=cc align=right>"
      .     "<input name=show_files_to_backup type=checkbox onClick=reload_explorer()"
      .

     ((isset ($_POST["show_files_to_backup"]))

      ?     chr (32) . "checked"
      :     ""

      )

      .     ">"
      .   "</td>"
      .   "<td class=cc>"
      .    "files to backup"
      .   "</td>"
      .  "</tr>"
      .  "<tr>"
      .   "<td colspan=7 class=$opclass align=center>"
      .    "<table class=wt>"
      .     "<tr>"
      .      "<td class=wb>"
      .       "working in $dbname/$folder - $nfiles file(s) listed, $hfiles hidden"
      .      "</td>"
      .     "</tr>"
      .    "</table>"
      .    "<table class=wt>"
      .     "<tr>"
      .      "<td class=wb>"
      .       $operation
      .      "</td>"
      .     "</tr>"
      .    "</table>"
      .    "<table class=wt>"
      .     "<tr>"
      .      "<td class=wb>"
      .       $folder . "/" . $file
      .

    (($qn)

      ?       ""
      :       " - " . strlen ($blob) . " bytes in " . substr_count ($blob, "\n") . " records"

      )

      .      "</td>"
      .     "</tr>"
      .    "</table>"

      .    "<textarea name=content class=multilinecb wrap=soft"
      .    " cols=" . ($dbx_lines_size + 2)
      .    " rows=" .  $dbx_windowsize
      .

     (($is_admin == false)

      ?    chr (32) . "readonly"
      :    ""

      )

      .    ">"
      .     $text
      .    "</textarea>"
      .   "</td>"
      .  "</tr>"
      .  "<tr>"
      .   "<td colspan=6 height=1 class=cc nowrap=nowrap>"
      .    "<input type=checkbox name=qn"
      .

    (($qn)

      ?     chr (32) . "checked"
      :     ""

      )

      .    "> quick navigation (witheld reading the selected file)"
      .   "</td>"
      .   "<td class=cc align=right>"
      .    "<input class=rf name=request_to_save type=submit style=width:100px value=\"WRITE FILE\">"
      .   "</td>"
      .  "</tr>"
      . "</form>"
      . "</table>"

      /*
        file deletion control
      */

      . "<table class=ct>"
      .  "<tr>"
      .   "<form name=delfile action=explorer.php enctype=multipart/form-data method=post>"
      .   "<input type=hidden name=code value=$code>"
      .   "<input type=hidden name=folder value=\"$folder\">"
      .   "<input type=hidden name=file value=\"$file\">"
      .

     ((isset ($_POST["show_files_to_backup"]))

      ?   "<input type=hidden name=show_files_to_backup value=1>"
      :   ""

      )

      .

     ((isset ($_POST["qn"]))

      ?   "<input type=hidden name=qn value=1>"
      :   ""

      )

      .   "<td width=1 class=cb>"
      .    "<input type=hidden name=request_to_delete value=1>"
      .    "<input type=radio onClick=document.delfile.submit()>"
      .   "</td>"
      .   "<td colspan=3 class=cr>"
      .    "delete this file ($folder/$file) from the database"
      .   "</td>"
      .  "</tr>"
      .  "<tr>"
      .   "<td width=1 class=cb>"
      .    "<input type=checkbox name=delete_confirm_1>"
      .   "</td>"
      .   "<td class=cc>"
      .    "check all boxes on this line to confirm deletion"
      .   "</td>"
      .   "<td width=1 class=cb>"
      .    "<input type=checkbox name=delete_confirm_2>"
      .   "</td>"
      .   "<td width=1 class=cb>"
      .    "<input type=checkbox name=delete_confirm_3>"
      .   "</td>"
      .   "</form>"
      .  "</tr>"
      . "</table>"

      /*
        single-file backup control
      */

      . "<table class=ct>"
      .  "<tr>"
      .   "<form name=backup action=explorer.php enctype=multipart/form-data method=post>"
      .   "<input type=hidden name=code value=$code>"
      .   "<input type=hidden name=folder value=\"$folder\">"
      .   "<input type=hidden name=file value=\"$file\">"
      .

     ((isset ($_POST["show_files_to_backup"]))

      ?   "<input type=hidden name=show_files_to_backup value=1>"
      :   ""

      )

      .

     ((isset ($_POST["qn"]))

      ?   "<input type=hidden name=qn value=1>"
      :   ""

      )

      .   "<td width=1 class=cb>"
      .    "<input type=hidden name=request_to_backup value=1>"
      .    "<input type=radio onClick=document.backup.submit()"
      .

     ((in_array ($nick, getBackupOwners ("$folder/$file")))

      ?     chr (32) . "checked"
      :     ""

      )

      .    ">"
      .   "</td>"
      .   "<td colspan=3 class=cr>"
      .    "keep a backup copy of this file on your computer"
      .   "</td>"
      .   "</form>"
      .  "</tr>"

      /*
        single-file restore control
      */

      .  "<tr>"
      .   "<form name=restore action=explorer.php enctype=multipart/form-data method=post>"
      .   "<input type=hidden name=code value=$code>"
      .   "<input type=hidden name=mpo value=\"\">"
      .   "<input type=hidden name=folder value=\"$folder\">"
      .   "<input type=hidden name=file value=\"$file\">"
      .

     ((isset ($_POST["show_files_to_backup"]))

      ?   "<input type=hidden name=show_files_to_backup value=1>"
      :   ""

      )

      .

     ((isset ($_POST["qn"]))

      ?   "<input type=hidden name=qn value=1>"
      :   ""

      )

      .   "<td width=1 class=cb>"
      .    "<input type=hidden name=request_to_restore value=1>"
      .    "<input type=radio onClick=document.restore.submit()>"
      .   "</td>"
      .   "<td colspan=2 class=cc>"
      .    "overwrite this file with your backup copy"
      .   "</td>"
      .   "<td width=1 class=cb>"
      .    "<input type=file name=copy style=width:200px>"
      .   "</td>"
      .   "</form>"
      .  "</tr>"

      /*
        single-file re-creation control
      */

      .  "<tr>"
      .   "<form name=create action=explorer.php enctype=multipart/form-data method=post>"
      .   "<input type=hidden name=code value=$code>"
      .   "<input type=hidden name=folder value=\"$folder\">"
      .

     ((isset ($_POST["show_files_to_backup"]))

      ?   "<input type=hidden name=show_files_to_backup value=1>"
      :   ""

      )

      .

     ((isset ($_POST["qn"]))

      ?   "<input type=hidden name=qn value=1>"
      :   ""

      )

      .   "<td width=1 class=cb>"
      .    "<input type=hidden name=create_new value=1>"
      .    "<input type=hidden name=request_to_restore value=1>"
      .    "<input type=radio onClick=document.create.submit()>"
      .   "</td>"
      .   "<td class=cc>"
      .    "restore single file:"
      .   "</td>"
      .   "<td width=1 class=cb>"
      .    "<input type=text name=file style=width:120px;height:19px value=\"new file name\">"
      .   "</td>"
      .   "<td width=1 class=cb>"
      .    "<input type=file name=copy style=width:200px>"
      .   "</td>"
      .   "</form>"
      .  "</tr>"
      . "</table>"

      /*
        backup packet generation control
      */

      . "<table class=ct>"
      .  "<tr>"
      .   "<form name=backup_packet action=explorer.php enctype=multipart/form-data method=post>"
      .   "<input type=hidden name=code value=$code>"
      .   "<input type=hidden name=folder value=\"$folder\">"
      .

     ((isset ($_POST["show_files_to_backup"]))

      ?   "<input type=hidden name=show_files_to_backup value=1>"
      :   ""

      )

      .

     ((isset ($_POST["qn"]))

      ?   "<input type=hidden name=qn value=1>"
      :   ""

      )

      .   "<td width=1 class=cb>"
      .    "<input type=hidden name=create_backup_packet value=1>"
      .    "<input type=radio onClick=document.backup_packet.submit()>"
      .   "</td>"
      .   "<td class=cc>"
      .    "create folder backup packet"
      .   "</td>"
      .   "<td class=cc align=right>"
      .    "pkt. index:&nbsp;"
      .   "</td>"
      .   "<td width=1 class=cb align=center>"
      .    "<input type=text name=packet_index value=1 style=width:36px>"
      .   "</td>"
      .   "<td class=cc align=right>"
      .    "size:&nbsp;"
      .   "</td>"
      .   "<td width=1 class=cb align=center>"
      .    "<input type=text name=packet_size value=1200 style=width:36px>"
      .   "</td>"
      .   "<td class=sr>"
      .    "KB."
      .   "</td>"
      .   "</form>"
      .  "</tr>"
      . "</table>"

      /*
        backup packet restore control
      */

      . "<table class=ct>"
      .  "<tr>"
      .   "<form name=restore_packet action=explorer.php enctype=multipart/form-data method=post>"
      .   "<input type=hidden name=code value=$code>"
      .   "<input type=hidden name=mpo value=\"\">"
      .   "<input type=hidden name=folder value=\"$folder\">"
      .

     ((isset ($_POST["show_files_to_backup"]))

      ?   "<input type=hidden name=show_files_to_backup value=1>"
      :   ""

      )

      .

     ((isset ($_POST["qn"]))

      ?   "<input type=hidden name=qn value=1>"
      :   ""

      )

      .   "<td width=1 class=cb>"
      .    "<input type=hidden name=restore_backup_packet value=1>"
      .    "<input type=radio onClick=document.restore_packet.submit()>"
      .   "</td>"
      .   "<td class=cc>"
      .    "restore saved packet:"
      .   "</td>"
      .   "<td width=1 class=cb align=center>"
      .    "<input type=file name=copy style=width:180px>"
      .   "</td>"
      .   "<td class=cc align=right>"
      .    "<input type=checkbox name=except_newer checked>"
      .   "</td>"
      .   "<td class=sr>"
      .    "except newer"
      .   "</td>"
      .   "</form>"
      .  "</tr>"
      . "</table>"

      /*
        disclaim backups control:
        this form doesn't echo the eventual "true" value of "list files to backup",
        because after disclaiming any backups it would be rather useless to filter
        the list so that it holds only the files to backup; after disclaiming all
        backup copies of the files in a folder, all files are listed anyway...
      */

      . "<table class=ct>"
      .  "<tr>"
      .   "<form name=disclaim action=explorer.php enctype=multipart/form-data method=post>"
      .   "<input type=hidden name=code value=$code>"
      .   "<input type=hidden name=folder value=\"$folder\">"
      .

     ((isset ($_POST["qn"]))

      ?   "<input type=hidden name=qn value=1>"
      :   ""

      )

      .   "<td width=1 class=cb>"
      .    "<input type=hidden name=disclaim_backups value=1>"
      .    "<input type=radio onClick=document.disclaim.submit()>"
      .   "</td>"
      .   "<td class=sr>"
      .    "disclaim outdated backup copies of files in this folder"
      .   "</td>"
      .   "</form>"
      .  "</tr>"
      . "</table>"
      .

      /*
        management account protection override (MPO):
        on change of the "mpopass" field, the MPO password is echoed to all other forms
        which in some way allow to write files to the database, so that putting the MPO
        password here can properly disable all protections...
      */

     (($id == 1)

      ? "<table class=ct>"
      .  "<tr>"
      .   "<form name=mpoform action=#>"
      .   "<td width=1 class=cb>"
      .    "<input type=password name=mpopass style=width:200px onChange=\""
      .    "document.explore.mpo.value=document.mpoform.mpopass.value;"
      .    "document.restore.mpo.value=document.mpoform.mpopass.value;"
      .    "document.restore_packet.mpo.value=document.mpoform.mpopass.value;"
      .    "\">"
      .   "</td>"
      .   "<td class=sr>"
      .    "&lt; manager protection override (MPO)"
      .   "</td>"
      .   "</form>"
      .  "</tr>"
      . "</table>"
      : ""

      )

      .

      /*
        packet sequence reminder (on restore)
      */

     ((empty ($processed_packet))

      ? ""
      : "<table class=ct>"
      .  "<td class=cb align=center>"
      .   "PROCESSED PACKET: $processed_packet"
      .  "</td>"
      . "</table>"

      )

      /*
        close borderline tables
      */

      .  "</td>"
      . "</table>";

/*
 *
 * template initialization
 *
 */

$db_explorer = str_replace

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

    $db_explorer

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page upon showing or
 * hiding the chat frame, and ONLY for that: this URL is confidential and it's not tracked.
 *
 */

include ("setrfcookie.php");

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($db_explorer));



?>
