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

$em['invalid_request'] = 'YOUR REQUEST IS INVALID';
$ex['invalid_request'] =

        "Looks like the destionation of the uploaded file does not refer "
      . "to a path compatible with that of an user-uploaded file.";

/*
 *
 * initialize output accumulators
 *
 */

$form = "";             // form HTML output
$error = false;         // general error flag
$islost = false;        // true if no member account record found (and withelds form rendering)
$real_member = false;   // true if member account exists (and is not the server's name, for e.g.)

$dontrememberthisurl = false;   // flag: true on submits to avoid repeating the submit on reloads

/*
 *
 * get parameters
 *
 */

$submit = isset ($_POST["submit"]);             // profile form submission (a simple flag here)
$target_nick = fget ("nick", -1000, "");        // profile owner nickname

if ($target_nick == "me") {

  if ($login) {

    $target_nick = $nick;

  }
  else {

    $target_nick = "witheld";

  }

}

$encoded_target_nick = base62Encode ($target_nick);   // base62-encoded profile owner nickname

/*
 *
 * utility function to manage upload and delete of avatar and photographs:
 * management is identical, it's a matter of pictures anyway; $entity may be "avatar" or "photo"
 *
 */

$existing =

  array (

    // unset avatar entry
    // unset photograph entry

  );

function process_image_upload ($entity, $maxwidth, $maxheight, $maxbytes) {

  global $existing, $encoded_target_nick;       // array of existence flags/names, encoded nick
  global $profile, $form;                       // profile template may be added a header

  /*
    determining target file path
  */

  global $path_to_avatars;
  global $path_to_photos;

  $path_to_entity = ($entity == "avatar") ? $path_to_avatars : $path_to_photos;

  /*
    retrieve control switch,
    check if there's a request to delete the current picture
  */

  $sw = fget ("{$entity}_switch", 3, "");

  if ((isset ($existing[$entity])) && ($sw == "del")) {

    if (@unlink ($existing[$entity])) {

      /*
        image file deletion succeeded
      */

      unset ($existing[$entity]);

    }
    else {

      /*
        image file deletion failed: append warning
      */

      $form .= "//...but couldn't delete the {$entity}: "
            .  "it may be that the community manager didn't set appropriate "
            .  "access rights for the directory \"images/{$entity}s\" in order "
            .  "to grant the script permission to change/delete image files "
            .  "located in that directory.";

    }

  }

  /*
    check if there's a request to upload a new picture
  */

  elseif ($sw == "up") {

    /*
      get POST method file informations:
      - image_size holds the size in bytes, typically <= 2 Mb for HTTP POST security limits;
      - image_type holds the MIME type of the file, as reported by the client's browser;
      - image_file holds the location where file data was temporarily stored, server-side.
    */

    $image_size = (empty ($_FILES[$entity]["size"])) ? 0 : $_FILES[$entity]["size"];
    $image_type = (empty ($_FILES[$entity]["type"])) ? "" : $_FILES[$entity]["type"];
    $image_file = ($_FILES[$entity]["tmp_name"] == "none") ? "" : $_FILES[$entity]["tmp_name"];

    $file_given = (empty ($image_file)) ? false : true;
    $type_given = (empty ($image_type)) ? false : true;
    $size_given = ($image_size > 0) ? is_numeric ($image_size) : false;

    if (($file_given == false) || ($type_given == false) || ($size_given == false)) {

      /*
        this happens when the switch is turned to "upload",
        but then no file name was filled into the proper box...
      */

      $form .= "//...but no image file specified: please fill the file box under "
            .  "\"{$entity} control\", by browsing a file from your computer or by "
            .  "typing the full path to the image in the corresponding text "
            .  "field (the field to the left of the \"Browse...\" button).";

    }
    else {

      /*
        security check: "is_uploaded_file" returns TRUE if the file was uploaded via
        HTTP POST: this is useful to help ensure that a malicious user hasn't tried to
        trick this script into working on files upon which it shouldn't be working -
        for instance, /etc/passwd.
      */

      if (!is_uploaded_file ($image_file)) {

        unlockSession ();
        die (because ('invalid_request'));

      }

      if ($image_size > $maxbytes) {

        /*
          file is too big
        */

        $form .= "//...but the image file you specified as your {$entity} "
              .  "exceeds " . (int) ($maxbytes / 1024) . " Kbytes, "
              .  "which is the maximum allowed size. Try to compress it better, "
              .  "but remember only .GIF, .JPG or .PNG formats are allowed.";

      }
      else {

        /*
          check MIME type of uploaded file for a valid match
        */

        preg_match ("#image\/[x\-]*([a-z]+)#", $image_type, $image_type);
        $image_type = $image_type[1];

        $allowed_types =
          array (

            "jpg",
            "jpeg",
            "pjpeg",
            "gif",
            "png"

          );

        if (in_array ($image_type, $allowed_types)) {

          /*
            normalize known "synonyms" of a JPEG image
          */

          if (($image_type == "jpeg") || ($image_type == "pjpeg")) {

            $image_type = "jpg";

          }

          /*
            get image size in pixels and also ensure it appears well-formed
          */

          list ($image_width, $image_height, $type_code) = @getimagesize ($image_file);

          switch ($type_code) {

            case 1:
              $valid_image = ($image_type == "gif") ? true : false;
              break;

            case 2:
            case 9:
            case 10:
            case 11:
            case 12:
              $valid_image = ($image_type == "jpg") ? true : false;
              break;

            case 3:
              $valid_image = ($image_type == "png") ? true : false;
              break;

            default:
              $valid_image = false;

          }

          $image_area = $image_width * $image_height;
          $valid_image = ($image_area > 0) ? $valid_image : false;

          if ($valid_image == false) {

            $form .= "//...but the image file specified as your {$entity} seems "
                  .  "to be corrupted. It could mean that the file was classified "
                  .  "by your computer as an image, but the binary data within the "
                  .  "file is not consistent with its classification.";

          }
          else {

            /*
              check image size against maximum allowed dimensions:
              yes, it's always square...
            */

            if (($image_width <= $maxwidth) && ($image_height <= $maxheight)) {

              /*
                ok, the file is appropriate:
                before copying it over the eventually existing image file,
                delete the existing file and check for successful deletion.
              */

              if (isset ($existing[$entity])) {

                $deletion_check = @unlink ($existing[$entity]);

              }
              else {

                $deletion_check = true;

              }

              if ($deletion_check == true) {

                /*
                  assemble destination filename for copy
                */

                $destination = "$path_to_entity/$encoded_target_nick.$image_type";

                if (@copy ($image_file, $destination)) {

                  /*
                    copy succeeded:
                    also append a "refresh" tag to this page's "head" section,
                    so that the page will refresh (and all its images along),
                    after 10 seconds since the browser receives its HTML output.
                  */

                  $existing[$entity] = $destination;

                  if ($entity == "avatar") {

                    $profile = str_replace ("<head>", "<head><meta http-equiv=refresh content=10>", $profile);
                    $form .= "//This frame will update in 10 seconds to refresh the avatar image, in case the browser didn't cache it yet...";

                  }

                  if ($entity == "photo") {

                    $form .= "//...and if you still wonder where your photograph actually gets shown, "
                          .  "just click the button leading to your personal <u>introduction page</u>.";

                  }

                }
                else {

                  /*
                    copy failed
                  */

                  unset ($existing[$entity]);

                  $form .= "//...but couldn't copy the image image file as "
                        .  "\"$destination\". There may be troubles with "
                        .  "access rights for that directory or file, "
                        .  "or some other problems concerning files management "
                        .  "in the server's PHP environment.";

                }

              }
              else {

                $form .= "//...but couldn't delete the existing image before saving "
                      .  "the new one: probably, this means the community manager "
                      .  "didn't set appropriate access rights to directory &quot;"
                      .  "images/{$entity}s&quot; for this script to delete files from "
                      .  "that directory.";

              }

            }
            else {

              $form .= "//...but the image file specified as your {$entity} is larger "
                    .  "than {$maxwidth}x{$maxheight} pixels, which "
                    .  "are the maximum allowed dimensions on this board.";

            }

          }

        }
        else {

          $form .= "//...but the image file you specified as your {$entity} "
                .  "is not in .GIF, .JPG or .PNG format, which are the "
                .  "only possible formats for {$entity}s on this board.";

        }

      }

      /*
        remove temporary file
      */

      @unlink ($image_file);

    }

  }

}

/*
 *
 * utility function to effectively build a security code only once, even if called twice:
 * from the point of view of a moderator watching someone else's profile, this script could
 * be called to either submit profile data, or to ignore/unignore the profile's owner, which
 * has to be tied to the same code, even if the two forms are placed in different conditional
 * blocks.
 *
 */

$gcode = null;

function setcode_once () {

  global $gcode;

  $gcode = ($gcode === null) ? setcode ("pst") : $gcode;

  return $gcode;

}

/*
 *
 * check security code on submission of profile form
 *
 */

if ($submit) {

  $code = intval (fget ("code", 1000, ""));
  $submit = ($code == getcode ("pst")) ? true : false;

}

/*
 *
 * locate ID of profile owner's account,
 * by consulting the "bynick" reference list...
 *
 */

$target_entry = get ("members/bynick", "nick>$target_nick", "");
$target_id = intval (valueOf ($target_entry, "id"));

/*
 *
 * read and analyze member account informations, generate output
 *
 */

if ((empty ($target_id)) || ($target_nick == "witheld")) {

  /*
    if no valid ID can be located for this member (an integer non-zero ID),
    it means there's no such nickname in the reference list ("members/bynick")
  */

  $error = true;
  $islost = true;

  switch ($target_nick) {

    case $servername:
    case "bulletin_board_system_server":

      /*
        pfft... this nickname appears for eg. in preview pages when you try to see
        someone else's previewed message (which is not tracked): just in case some
        curious user wanted to know who's this "bulletin_board_system_server"...
      */

      $form = "Heh, no, sorry...//"
            . "The server is not a real member: the server is the machine that keeps "
            . "this bulletin board system going.";

      break;

    case "hidden recipients":

      /*
        this is a placeholder string for private messages sent to undisclosed recipients
      */

      $form = "Hidden recipients...//"
            . "For some kind of security reason, the recipients of this message have been "
            . "not disclosed; if the message you're seeing is a warning about a shared "
            . "password, this is done automatically.";

      break;

    case "witheld":

      /*
        trying to access the profile corresponding to the "me" keyword while not logged in
      */

      $form = "You are not logged in.//<a href=mstats.php>go to login panel</a>";

      break;

    default:

      $form = "Can't find member \"$target_nick\"//"
            . "This account may have been:"
            . "<ol>"
            .  "<li>deleted by its owner</li>"
            .  "<li>deleted by an admin</li>"
            .  "<li>pruned for inactivity</li>"
            . "</ol>"
            . "Alternatively, there's something wrong in archive "
            . "file called \"members/bynick\": in this case "
            . "you may report that to the staff.";

  }

}
else {

  /*
    valid ID found: read account record
  */

  $real_member = true;
  $target_db = "members" . intervalOf ($target_id, $bs_members);
  $account_record = get ($target_db, "id>$target_id", "");

  if (empty ($account_record)) {

    /*
      if record appears void, it's an inconsistence in the database:
      there's a nickname registered, but no account record?
    */

    $error = true;
    $islost = true;

    $form = "Can't get info for \"$target_nick\"//"
          . "Something sounds wrong with the members' database, given that "
          . "this member was found associated to an ID, but then there was "
          . "no readable record corresponding to that ID. "
          . "Please report this problem.";

  }
  else {

    /*
      find name of existing avatar image (if any):
      it may be a .gif, .jpg, or .png image file, located in layout/images/avatars,
      and the file name is given by the base-62 encoded version of the member's nickname
    */

    unset ($existing["avatar"]);

    if     (@file_exists ("$path_to_avatars/$encoded_target_nick.gif")) $existing["avatar"] = "$path_to_avatars/$encoded_target_nick.gif";
    elseif (@file_exists ("$path_to_avatars/$encoded_target_nick.jpg")) $existing["avatar"] = "$path_to_avatars/$encoded_target_nick.jpg";
    elseif (@file_exists ("$path_to_avatars/$encoded_target_nick.png")) $existing["avatar"] = "$path_to_avatars/$encoded_target_nick.png";

    /*
      find name of existing photograph image (if any):
      it may be a .gif, .jpg, or .png image file, located in images/photos,
      and the file name is given by the base-62 encoded version of the member's nickname
    */

    unset ($existing["photo"]);

    if     (@file_exists ("$path_to_photos/$encoded_target_nick.gif")) $existing["photo"] = "$path_to_photos/$encoded_target_nick.gif";
    elseif (@file_exists ("$path_to_photos/$encoded_target_nick.jpg")) $existing["photo"] = "$path_to_photos/$encoded_target_nick.jpg";
    elseif (@file_exists ("$path_to_photos/$encoded_target_nick.png")) $existing["photo"] = "$path_to_photos/$encoded_target_nick.png";

    /*
      is the viewer of this page also the profile owner?
      this flag will be important in later checks...
    */

    $is_owner = (($login == true) && ($nick == $target_nick)) ? true : false;

    /*
      read authorization level (rank) from account record, determining the following:
      - if profile owner is also the viewer, allow him/her to change his/her profile;
      - if profile owner is a moderator, allow changes to moderators and administrators;
      - if profile owner is an administrator, allow changes only to other administrators.
    */

    $actual_rank = valueOf ($account_record, "auth");

    $is_target_admin = in_array ($actual_rank, $admin_ranks);
    $is_target_mod = in_array ($actual_rank, $mod_ranks);

    $allow_change = false;

    if (($is_admin == true) || ($is_owner == true)) {

      $allow_change = true;

    }

    if (($is_mod == true) && ($is_target_admin == false)) {

      $allow_change = true;

    }

    /*
      major age check:
      sets a flag for later conditions...
    */

    $mms = (valueOf ($account_record, "major") == "yes") ? true : false;

    /*
      profile data submission
    */

    if ($submit == true) {

      /*
        on submits, discarding URL tracking feature on bottom of script
        avoids involountarily repeating the submission on reloading this page
      */

      $dontrememberthisurl = true;

      if ($login == false) {

        /*
          you obviously need to be logged in to submit changes to a profile:
          depending on your authorization level, you will or will not be allowed to change it
        */

        $error = true;
        $form = "You are not logged in.//<a class=pk href=mstats.php>go to login panel</a>";

      }
      else {

        /*
          there's no "submit changes" button showed in the form if you cannot change the profile,
          so a missing authorization error message has no praticular reason to be presented: it
          therefore only bypasses all the code that changes the account record... just preventing
          lame attempts to add a button there in saved versions of someone else's profile form...
        */

        if ($allow_change == true) {

          /*
            obtain exclusive write access:
            database manipulation ahead...
          */

          lockSession ();

          /*
            now in a locked session frame, all cached informations from the database having been
            invalidated, read the record again to ensure it still exists and bring it up to date
          */

          $target_entry = get ("members/bynick", "nick>$target_nick", "");
          $target_id = intval (valueOf ($target_entry, "id"));

          if (empty ($target_id)) {

            $account_record = "";

          }
          else {

            $target_db = "members" . intervalOf ($target_id, $bs_members);
            $account_record = get ($target_db, "id>$target_id", "");

          }

          if (empty ($account_record)) {

            $error = true;
            $islost = true;

            $form = "Error - lost entry!//"
                  . "This entry of the members' database appears to no longer exist. "
                  . "It may be that the member resigned, or this account was deleted, "
                  . "while this script was attempting to update its associated profile.";

          }
          else {

            /*
              record still exists, but what if someone changed its rank before this script
              started executing in its locked frame? check again if changes are still allowed...
            */

            $actual_rank = valueOf ($account_record, "auth");

            $is_target_admin = in_array ($actual_rank, $admin_ranks);
            $is_target_mod = in_array ($actual_rank, $mod_ranks);

            $allow_change = false;

            if (($is_admin == true) || ($is_owner == true)) {

              $allow_change = true;

            }

            if (($is_mod == true) && ($is_target_admin == false)) {

              $allow_change = true;

            }

            /*
              also update major age status flag (may have been promoted in the meantime)
            */

            if (valueOf ($account_record, "major") == "yes") {

              $mms = true;

            }

            if ($allow_change == true) {

              /*
                ok, after locking the session, this viewer can still make changes to the profile:
                begin analyzing submitted fields, starting with the "rank" field, which determines
                a member's access rights, and is subject to special protection...
              */

              $rank_changed = false;    // will output a warning message after changing rank

              if (($is_admin == true) && ($target_id != 1)) {

                /*
                  allowing changes to "rank" only to administrators,
                  and never, in any cases, for the rank of the first account (the manager)
                */

                $new_rank = strtolower (fget ("auth", $p_auth_size, ""));

                if ($new_rank != $actual_rank) {

                  $actual_rank = $new_rank;
                  $rank_changed = true;

                  $account_record = fieldSet ($account_record, "auth", $new_rank);

                }

              }

              /*
                update other fields (that are not subject to special protection)
              */

              $account_record = fieldSet ($account_record, "species", fget ("species", $p_species_size, ""));
              $account_record = fieldSet ($account_record, "gender", fget ("gender", $p_gender_size, ""));
              $account_record = fieldSet ($account_record, "aka", fget ("aka", $p_aka_size, ""));
              $account_record = fieldSet ($account_record, "born", fget ("born", $p_born_size, ""));
              $account_record = fieldSet ($account_record, "habitat", fget ("habitat", $p_habitat_size, ""));

              /*
                update "bit about" (which is multiline and needs line breaks converted to "&;")
                update integer numeric fields (nickname and speech bubble color index)
              */

              $account_record = fieldSet ($account_record, "about", fget ("about", $p_about_size, "&;"));

              /*
                update nickname color
              */

              $new_ntint = fget ("ntint", 10, "");
              $new_ntint = ($new_ntint === "") ? 0 : intval ($new_ntint);
              $new_ntint = ($new_ntint < 0) ? 0 : $new_ntint;
              $new_ntint = ($new_ntint < count ($ntints)) ? $new_ntint : (count ($ntints) - 1);

              $account_record = fieldSet ($account_record, "ntint", strval ($new_ntint));

              /*
                update speech balloon color
              */

              $new_bcol = fget ("bcol", 10, "");
              $new_bcol = ($new_bcol === "") ? $default_bcol : intval ($new_bcol);
              $new_bcol = ($new_bcol < -1) ? -1 : $new_bcol;
              $new_bcol = ($new_bcol < count ($bcols) - 1) ? $new_bcol : (count ($bcols) - 2);

              $account_record = fieldSet ($account_record, "bcol", strval ($new_bcol));

              /*
                update private email address:
                it's only requested to staff members (moderators and administrators),
                it's never showed or gathered to/from anybody but the profile owner;
                it may be provided by regular members to allow Postline to send passwords
                back to members that may have forgotten the password...
              */

              if ($is_owner == true) {

                $account_record = fieldSet ($account_record, "email", fget ("email", $p_email_size, ""));

              }

              /*
                update fields that require moderation access rights to be changed
              */

              if (($is_admin == true) || ($is_mod == true)) {

                /*
                  user title flag (allows member to always choose a user title)
                */

                if (($is_target_admin == false) && ($is_target_mod == false)) {

                  $flag = (isset ($_POST["ut"])) ? "yes" : "";
                  $account_record = fieldSet ($account_record, "user_title", $flag);

                }

                /*
                  chat mute flag (disallows member to chat)
                */

                if (($is_target_admin == false) && ($is_target_mod == false)) {

                  $flag = (isset ($_POST["mu"])) ? "yes" : "";
                  $account_record = fieldSet ($account_record, "muted", $flag);

                  if ($flag == "yes") {

                    logwr ("&quot;{$target_nick}&quot; has been gagged.", lw_persistent);

                  }

                }

                /*
                  force major member status (unless member is already so):
                  this is a one-shot flag - once it's set, it can't be unset
                */

                if ($mms == false) {

                  if (isset ($_POST["fmms"])) {

                    $mms = true;

                    /*
                      note that major age promotion clears the ignorelist
                    */

                    $account_record = fieldSet ($account_record, "major", "yes");
                    $account_record = fieldSet ($account_record, "ignorelist", "");

                    logwr ("&quot;{$target_nick}&quot; has been promoted major member.", lw_persistent);

                  }

                }

              }

              /*
                update account record in database
              */

              set ($target_db, "id>$target_id", "", $account_record);

              /*
                nickname's color might be also echoed into current session record:
                this way, it will immediately influence the status line...
              */

              $session_table_record = get ("stats/sessions", "id>$target_id", "");
              $is_logged_in = (empty ($session_table_record)) ? false : true;

              if ($is_logged_in == true) {

                set ("stats/sessions", "id>$target_id", "ntint", valueOf ($account_record, "ntint"));

              }

              /*
                confirm that the profile was successfully updated:
                error flag is asserted to show the confirmation message, not because of an error
              */

              $error = true;
              $form = "Profile updated...";

              /*
                append a warning for administrators to know what happens when ranks are changed
              */

              if ($rank_changed == true) {

                logwr ("Warning: rank change for &quot;{$target_nick}&quot;. New rank: {$new_rank}.", lw_persistent);

                $list1 = implode (", ", $admin_ranks);
                $list2 = implode (", ", $mod_ranks);

                $form .= "//P.S. you have changed this member's rank. "
                      .  "Note that ranks such as <u>$list1</u> have "
                      .  "administration rights, while ranks such as "
                      .  "<u>$list2</u> have moderation rights. If you "
                      .  "are an admin and this is your profile, please "
                      .  "pay attention: if your new rank is NOT appearing "
                      .  "in the corresponding list above, you NO LONGER "
                      .  "have administration rights, and you will not even "
                      .  "be able to change your rank again. If you made "
                      .  "this kind of mistake, you may ask another admin "
                      .  "or the community manager to restore your rank.";

              }

              /*
                retrieve avatar and photograph control switches,
                check if there's a request to delete the current avatar and/or photograph
              */

              process_image_upload ("avatar", $avatarpixelsize, $avatarpixelsize, $avatarbytesize);
              process_image_upload ("photo", $maxphotowidth, $maxphotoheight, $photobytesize);

              /*
                preparing to change accessory fields in "members/bynick" entry:
                these fields control aspects of an account record that need to be
                visualized in posts; because the board will not access accunt records
                when simply listing posts in a thread (this saves A LOT of queries),
                a few options can be stored in "members/bynick", which is queried only
                once per run of the "posts.php" script, and is cached by "message.php"
              */

              $entry_changed = false;

              /*
                this is also part of the "avatar control" panel of the profile form:
                it's the switch to turn the black frame around the avatar on or off,
                and the requested state of that switch is checked after any eventual
                requests to delete the avatar (if no avatar, switch is off)
              */

              $old_frame_state = valueOf ($target_entry, "nf");

              if (isset ($existing["avatar"])) {

                $new_frame_state = (isset ($_POST["nf"])) ? "y" : "";

              }
              else {

                $new_frame_state = "";

              }

              if ($old_frame_state != $new_frame_state) {

                $target_entry = fieldSet ($target_entry, "nf", $new_frame_state);
                $entry_changed = true;

              }

              /*
                this is also part of the "avatar control" panel of the profile form:
                it's the "vertical displacement", a value given in pixels to adjust the
                position of specially-crafted avatars that may have parts that "drop"
                below the user title, or the bottom of the avatar frame; large displacement
                values coupled with large avatars may "encumber" a post, but the feature
                is normalized to a maximum, and could still be moderated if abuses are spotted.
              */

              $old_displacement = valueOf ($target_entry, "ad");

              if (isset ($existing["avatar"])) {

                $new_displacement = intval (fget ("avatar_displacement", 1000, ""));
                $d_max = $avatarpixelsize - 1;

                $new_displacement = ($new_displacement < 0) ? 0 : $new_displacement;
                $new_displacement = ($new_displacement > $d_max) ? $d_max : $new_displacement;

              }
              else {

                $new_displacement = 0;

              }

              if ($old_displacement != $new_displacement) {

                $target_entry = fieldSet ($target_entry, "ad", strval ($new_displacement));
                $entry_changed = true;

              }

              /*
                update user title, which is allowed to:
                - moderators and administrators (always);
                - users that have been allowed to pick a title (always);
                - all users (only if $freetitles = true in "settings.php").
                user title is pratically also part of the avatar, because it won't show
                if there's no avatar, although it's preserved as an independent field...
                it's typically considered to be some "special acknowledgement", so it's
                rather important that it wouldn't go away, should the avatar be deleted...
              */

              $can_pick_title = (valueOf ($account_record, "user_title") == "yes") ? true : false;

              if (

                ($is_admin == true) ||          // administrator
                ($is_mod == true) ||            // moderator
                ($can_pick_title == true) ||    // regular member who can choose a title
                ($freetitles == true)           // board is told to allow this to everyone

              )

              {

                $old_title = valueOf ($target_entry, "ut");
                $new_title = strtolower (fget ("title", $p_title_size, ""));

                if ($old_title != $new_title) {

                  $target_entry = fieldSet ($target_entry, "ut", $new_title);
                  $entry_changed = true;

                }

              }

              /*
                update entry in "members/bynick" if changes were made to its record:
                members/bynick can become a large file, so it's better to "centralize" its
                updates in a single attempt to write to that file, for better performance...
              */

              if ($entry_changed == true) {

                set ("members/bynick", "nick>$target_nick", "", $target_entry);

              }

              /*
                finally, the possibility to resign:
                accounts can be deleted either by their owners, or by administrators
              */

              if (($is_owner == true) || ($is_admin == true)) {

                /*
                  all three boxes must be checked for the resign request to be valid:
                  it's rather difficult to check three boxes by mistake, you see...
                */

                if (isset ($_POST["resign_1"])
                 && isset ($_POST["resign_2"])
                 && isset ($_POST["resign_3"])) {

                  /*
                    special care for account #1: the community manager,
                    which is absolutely protected against deletion...
                  */

                  if ($target_id != 1) {

                    /*
                      begin by deleting the session record from logged-in members sessions table:
                      it wouldn't matter if there was no such record, it'd just be a "try"...
                    */

                    set ("stats/sessions", "id>$target_id", "", "");

                    /*
                      read inbox and outbox lists, and merge them in an array of positive
                      message IDs (negative IDs may be in the inbox for messages marked as
                      "read"), then ensure the array doesn't contain duplicated IDs (case
                      of messages sent from this member to... him/herself, or sent back as
                      carbon copys): there wouldn't be problems deleting a message twice,
                      but sure it'd be a waste of time...
                    */

                    $_inbox = valueOf ($account_record, "inbox");
                    $_inbox = wExplode (";", $_inbox);

                    $outbox = valueOf ($account_record, "outbox");
                    $outbox = wExplode (";", $outbox);

                    $inbox = array ();

                    foreach ($_inbox as $message_id) {

                      $inbox[] = abs ($message_id);

                    }

                    $private_message = wArrayUnique (array_merge ($inbox, $outbox));

                    /*
                      now delete ALL private messages sent or received by this member,
                      and grouped in the above array: "delete_pm" will physically delete
                      the message entry, though, only if there's no more recipients for
                      the message (ie. typically, if all message recipients also deleted
                      the message from their inbox)
                    */

                    foreach ($private_message as $message_id) {

                      delete_pm ($target_nick, $message_id);

                    }

                    /*
                      clear major member's personal ignorelist:
                      the function will however check if the indicated member is a major member,
                      and eventually decrease "ignore counts" for ignored members in the list...
                    */

                    clear_mm_ignorelist ($target_id);

                    /*
                      finally, delete member account:
                      - from the database file holding its record;
                      - from the nicknames reference list ("members/bynick").
                    */

                    set ($target_db, "id>$target_id", "", "");
                    set ("members/bynick", "nick>$target_nick", "", "");

                    /*
                      update resignations counter in board statistics:
                      it's important, because... members in hold = signups - resigns
                    */

                    $rc = intval (get ("stats/counters", "counter>resigns", "count")) + 1;
                    set ("stats/counters", "counter>resigns", "", "<counter>resigns<count>$rc");

                    /*
                      delete avatar, if an avatar is present
                    */

                    if (isset ($existing["avatar"])) {

                      $avatar_deletion_check = @unlink ($existing["avatar"]);

                    }
                    else {

                      $avatar_deletion_check = true;

                    }

                    /*
                      delete photograph, if a photograph is present
                    */

                    if (isset ($existing["photo"])) {

                      $photo_deletion_check = @unlink ($existing["photo"]);

                    }
                    else {

                      $photo_deletion_check = true;

                    }

                    /*
                      confirm successful resignation,
                      report eventual troubles deleting the avatar...
                    */

                    if ($is_owner == true) {

                      logwr ("Farewell to &quot;$target_nick&quot;, leaving on intention.", lw_persistent);

                    }
                    else {

                      logwr ("Apologizing with &quot;$target_nick&quot; (forceful deletion).", lw_persistent);

                    }

                    if (($avatar_deletion_check == true) && ($photo_deletion_check == true)) {

                      unset ($existing["avatar"]);
                      unset ($existing["photo"]);

                      $form = "Ok, member deleted.//"
                            . "The account record, the nickname from members list, the avatar "
                            . "image file, and all private messages sent or received by this "
                            . "member, were successfully deleted from the database. However, "
                            . "please note that formerly posted messages and threads will be "
                            . "kept in the database forever. C-disk files uploaded by deleted "
                            . "members are not immediately removed: they will be grouped into "
                            . "a special folder of the community disk, called \"orphans\". If "
                            . "moderators and administrators will deem orphan files no longer "
                            . "important, the said files will also be deleted.";

                      if ($is_owner == true) {

                        $form .= "<hr>"
                              .  "Thank you for the time you've been with us. "
                              .  "As I write this piece of code, I would like to add my personal "
                              .  "attempt to apologize with you for any eventual troubles that "
                              .  "may have lead to your decision of leaving the community.";

                      }

                    }
                    else {

                      if ($avatar_deletion_check == false) {

                        $form = "Account deleted, but...//"
                              . "...could not delete member's avatar, the file called \""
                              . $existing["avatar"] . "\". If you are the community manager, please "
                              . "check directory's access rights and try again, otherwise "
                              . "report this problem to the manager. The member was deleted "
                              . "from the database and its index, but that image file is now "
                              . "completely useless. Please take note of the file and report it!";

                        logwr ("Warning: failed to delete &quot;" . $existing["avatar"] . "&quot;.", lw_persistent);

                      }

                      if ($photo_deletion_check == false) {

                        $form = "Account deleted, but...//"
                              . "...could not delete member's photograph, the file called \""
                              . $existing["photo"] . "\". If you are the community manager, please "
                              . "check directory's access rights and try again, otherwise "
                              . "report this problem to the manager. The member was deleted "
                              . "from the database and its index, but that image file is now "
                              . "completely useless. Please take note of the file and report it!";

                        logwr ("Warning: failed to delete &quot;" . $existing["photo"] . "&quot;.", lw_persistent);

                      }

                    }

                    /*
                      set $islost to witheld useless form rendering for a now-deleted member
                    */

                    $islost = true;

                  }
                  else {

                    $form = "Not permitted!//"
                          . "You cannot delete the first member of the board! "
                          . "The first member is the original community manager, "
                          . "and the corresponding account can't be deleted via "
                          . "this control form, not even if this is your profile "
                          . "and you're the community manager in person.";

                  }

                }

              }

            }

          }

        }

      }

    }

    /*
       ignore lists and majority ban control:
       - members can't ignore themselves (duh!)
       - moderators and administrators cannot be ignored
    */

    $ignored = in_array ($target_nick, $ilist);

    if ($is_owner == false) {

      $code = intval (fget ("code", 1000, ""));
      $code_match = ($code == getcode ("pst")) ? true : false;

      if ($ignored == true) {

        /*
          profile owner's nickname is in viewer's ignore list:
          - is there a request to un-ignore (listen) the profile owner?
        */

        $listen = ($code_match) ? fget ("listen", 1, "") : "";

        if ($listen == "y") {

          /*
            obtain exclusive write access to the database,
            then re-check all conditions that made the "listen" request valid
            - profile owner record must still exist;
            - viewer record must still exist;
            - profile owner nickname must still be inside viewer's ignore list.
          */

          lockSession ();

          $viewer_db = "members" . intervalOf ($id, $bs_members);

          $account_record = get ($target_db, "id>$target_id", "");
          $viewer_record = get ($viewer_db, "id>$id", "");

          $ilist = valueOf ($viewer_record, "ignorelist");
          $ilist = wExplode (";", $ilist);

          $ignored = in_array ($target_nick, $ilist);

          if ((!empty ($account_record)) && (!empty ($viewer_record)) && ($ignored == true)) {

            /*
              remove profile owner's nickname from viewer's ignore list
            */

            $ilist = wArrayDiff ($ilist, array ($target_nick));
            $ignored = false;

            /*
              if viewer is a major member, decrease ignore count for target
            */

            if ($is_major == true) {

              $ic = intval (get ($target_db, "id>$target_id", "ignorecount")) - 1;
              if ($ic < 0) $ic = 0; // might never happen, but better be sure...

              $account_record = fieldSet ($account_record, "ignorecount", strval ($ic));

              set ($target_db, "id>$target_id", "", $account_record);

            }

            /*
              update viewer's ignorelist
            */

            set ($viewer_db, "id>$id", "ignorelist", implode (";", $ilist));

            /*
              if ignorelist is now empty,
              remove viewer's nickname from list of members ignoring at least someone
            */

            if (count ($ilist) == 0) {

              set ("stats/ignorers", "nick>$nick", "", "");

            }

          }

        }

      }
      else {

        /*
          profile owner's nickname is NOT in viewer's ignore list:
          - is there a request to ignore the profile owner?
        */

        $ignore = ($code_match) ? fget ("ignore", 1, "") : "";

        if ($ignore == "y") {

          /*
            obtain exclusive write access to the database,
            then re-check all conditions that made the "ignore" request valid:
            - profile owner record must still exist;
            - viewer record must still exist;
            - profile owner must NOT be the viewer (you can't ignore yourself);
            - profile owner nickname must still be missing from viewer's ignore list.
            note: it may seem a bunch of useless complications, but ignore lists may
            have the power to ban members (due to the "majority ban" feature). These
            checks should make it impossible to "ignore a member twice", by starting
            two sessions with different browsers and attempting to press the "ignore"
            button at the same time. If it wasn't for these checks, it'd be possible,
            at least in theory, to trigger this script twice, relying on the outdated
            information of $ilist (loaded by "suitcase.php" much before getting here)
            which wouldn't (yet) include the target's nickname. In many other points,
            such checks for multiple access from a same account aren't necessary, but
            in this particular case they become rather important.
          */

          lockSession ();

          $viewer_db = "members" . intervalOf ($id, $bs_members);

          $account_record = get ($target_db, "id>$target_id", "");
          $viewer_record = get ($viewer_db, "id>$id", "");

          $ilist = valueOf ($viewer_record, "ignorelist");
          $ilist = wExplode (";", $ilist);

          $ignored = in_array ($target_nick, $ilist);

          if ((!empty ($account_record)) && (!empty ($viewer_record)) && ($ignored == false) && ($is_owner == false)) {

            $actual_rank = valueOf ($account_record, "auth");

            $is_target_admin = in_array ($actual_rank, $admin_ranks);
            $is_target_mod = in_array ($actual_rank, $mod_ranks);

            if (($is_target_admin == false) && ($is_target_mod == false)) {

              /*
                add target nickname to viewer's ignore list
              */

              $ilist = array_merge ($ilist, array ($target_nick));
              $ignored = true;

              /*
                add viewer's nickname to list of members ignoring at least someone
              */

              set ("stats/ignorers", "nick>$nick", "", "<nick>$nick");

              /*
                if viewer is a major member, increase ignore count for target
              */

              if ($is_major == true) {

                $ic = intval (valueOf ($account_record, "ignorecount")) + 1;
                $account_record = fieldSet ($account_record, "ignorecount", strval ($ic));

                set ($target_db, "id>$target_id", "", $account_record);

                /*
                  if ignore count is enough, and majority ban is active, apply majority ban
                */

                $majority_ban_state = get ("stats/counters", "counter>majorityban", "state");
                $majority_ban_active = ($majority_ban_state == "on") ? true : false;

                $majority_ban_notify = get ("stats/counters", "counter>mbnotify", "state");
                $majority_ban_notify = ($majority_ban_notify == "on") ? true : false;

                if (($ic >= $majorityban) && ($majority_ban_active == true)) {

                  if ($majority_ban_notify == true) {

                    /*
                      not really banning, only requested to notify staff members
                    */

                    $no_mbn_since = $epc - intval (valueOf ($account_record, "mbnlatest"));
                    $do_notify = ($no_mbn_since > 86400) ? true : false;

                    if ($do_notify) {

                      set ($target_db, "id>$target_id", "mbnlatest", strval ($epc));

                      /*
                        scan the members' database looking for where the target nickname appears
                        in who's ignorelist
                      */

                      $allignorers = all ("stats/ignorers", makeProper);
                      $ignorers = array ();

                      foreach ($allignorers as $nickrecord) {

                        $ref_nn = valueOf ($nickrecord, "nick");
                        $ref_id = intval (get ("members/bynick", "nick>$ref_nn", "id"));
                        $ref_db = "members" . intervalOf ($ref_id, $bs_members);
                        $ref_il = wExplode (";", get ($ref_db, "id>$ref_id", "ignorelist"));

                        if (in_array ($target_nick, $ref_il)) {

                          $ignorers[] = valueOf ($nickrecord, "nick");

                        }

                      }

                      $ignorers[] = $nick;
                      $ignorers = array_unique ($ignorers);

                      /*
                        read private messages ID counter, check if next ID is free:
                        if it's not, it's obviously a problem concerning the counter...
                      */

                      $pmid = intval (get ("stats/counters", "counter>newpms", "count")) + 1;
                      $pmdb = "pms" . intervalOf ($pmid, $bs_pms);

                      /*
                        create array of recipients:
                        it will hold nicknames, initially not checked for existence and validity;
                        proceeds to add administrators and moderators from the corresponding lists
                      */

                      $manager = get ("members" . intervalOf (1, $bs_members), "id>1", "nick");
                      $administrators = all ("members/adm_list", asIs);
                      $moderators = all ("members/mod_list", asIs);

                      $rl = array ($manager);
                      $rl = array_merge ($rl, $administrators);
                      $rl = array_merge ($rl, $moderators);

                      /*
                        deliver message to all recipients
                      */

                      foreach ($rl as $n) {

                        /*
                          retrieve recipient's ID ($ci): non-zero if nickname exists;
                          locate database file holding corresponding account record ($db);
                          retrieve recipient's record ($mr): non-empty if record exists.
                        */

                        $_ci = intval (get ("members/bynick", "nick>$n", "id"));
                        $_db = "members" . intervalOf ($_ci, $bs_members);
                        $_mr = get ($_db, "id>$_ci", "");
                        $ok_to_deliver = (empty ($_mr)) ? false : true;

                        if ($ok_to_deliver) {

                          /*
                            add the ID of this PM to recipient's inbox and set
                            the recipient's "you have mail" flag ("newpm" = "yes")
                          */

                          $rcpt_inbox = wExplode (";", valueOf ($_mr, "inbox"));
                          $rcpt_inbox[] = $pmid;

                          $_mr = fieldSet ($_mr, "inbox", implode (";", $rcpt_inbox));
                          $_mr = fieldSet ($_mr, "newpm", "yes");

                          set ($_db, "id>$_ci", "", $_mr);

                        }

                      }

                      /*
                        update general PMs counter, write message record
                      */

                      $record = "<id>$pmid"
                              . "<to>"
                              . "hidden recipients"
                              . "<hr>" . implode (";", $rl)             // hidden recipients list
                              . "<os>" . $servername                    // hidden original sender
                              . "<author>$servername"
                              . "<date>$epc"
                              . "<title>Ignore count threshold reached!"
                              . "<message>"
                              . "&lt;a target=pst href=&quot;./profile.php?"
                              . "nick=" . base62Encode ($target_nick)
                              . "&amp;at=$epc&quot;&gt;$target_nick&lt;/a&gt; is being currently "
                              . "ignored by $majorityban or more major members. The majority "
                              . "ban feature has been set to only send this kind of report to "
                              . "moderators and administrators. In practice, this message "
                              . "means a significant part of the community decided to "
                              . "ignore the member in question ($target_nick), implying "
                              . "that member is somewhat upsetting people around the forum."
                              . "&lt;h&gt;"
                              . "Currently, $target_nick is being ignored by the following members:"
                              . "&lt;/h&gt;"
                              . "&lt;ol&gt;&lt;li&gt;"
                              . implode ("&lt;/li&gt;&lt;li&gt;", $ignorers)
                              . "&lt;/li&gt;&lt;/ol&gt;"
                              . "<rcpt>" . implode (";", $rl);

                      set ("stats/counters", "counter>newpms", "", "<counter>newpms<count>$pmid");
                      set ($pmdb, "", "", $record);

                      /*
                        log this event, store in persistent chat logs
                      */

                      logwr ("Warning: &quot;{$target_nick}&quot; reached ignore count threshold and is being reported to the staff.", lw_temporary);

                    }

                  }
                  else {

                    /*
                      really banning
                    */

                    /*
                      force logout (destroy session)
                    */

                    set ("stats/sessions", "id>$target_id", "", "");

                    /*
                      block IP from re-registering:
                      refer to login IP first; if void, take registration IP.
                      login IP would be unknown if profile owner had never logged in so far:
                      I would wonder what'd be the point in ignoring someone who never logged in,
                      but you never know... maybe some troublemaker returned with the same nick?
                    */

                    $target_ip = valueOf ($account_record, "logip");

                    if (empty ($target_ip)) {

                      $target_ip = valueOf ($account_record, "ri");

                    }

                    /*
                      in older versions of Postline, there wasn't a registration IP ("ri" field):
                      in theory, newer Postlines should never come here with a void $target_ip,
                      so this last check is simply a backward-compatibility issue...
                    */

                    if (!empty ($target_ip)) {

                      kick ($target_ip);

                    }

                    /*
                      set permanent ban on target account, by adding the nickname to the banlist:
                      - $bml is read from "stats/bml", which is an archive in the form of a
                        simple list of nicknames; newline codes (\n) separate each nickname;
                        to avoid letters case to mismatch, convert both the target nickname and
                        the names in the BML to lowercase; to avoid leaving blank lines in the
                        BML due to trailing line breaks, trim formatters from $bml.
                    */

                    $ncn = strtolower ($target_nick);

                    $bml = trim (strtolower (readFrom ("stats/bml")));
                    $bml = wExplode ("\n", $bml);

                    if (!in_array ($ncn, $bml)) {

                      $bml[] = $target_nick;
                      writeTo ("stats/bml", implode ("\n", $bml) . "\n");

                    }

                    /*
                      clear banned member's own ignorelist and decrease corresponding ignorecounts
                    */

                    clear_mm_ignorelist ($target_id);

                    /*
                      log this event, store in persistent chat logs
                    */

                    logwr ("Warning: &quot;{$target_nick}&quot; has been majority-banned.", lw_persistent);

                  }

                }

              }

              /*
                update viewer's ignorelist
              */

              set ($viewer_db, "id>$id", "ignorelist", implode (";", $ilist));

            }

          }

        }

      }

    }

  }

}

/*
 *
 * generate profile form:
 * yeah, this is very long and complicated...
 *
 */

if ($islost == true) {

  /*
    in case no account record could be found, or in case member just resigned,
    report the error message which will say there's no such member, or that
    accound deletiong succeeded
  */

  list ($frame_title, $frame_contents) = explode ("//", $form);

  $form =

        makeframe (

          $frame_title,
          $frame_contents,

          true

        );

}
else {

  /*
    setup a few accessories for the profile form
  */

  $img =                        // HTML of avatar slot

     ((isset ($existing["avatar"]))

       ? $opcart
       .  "<td class=avatar align=center>"
       .   "<img width=$avatarpixelsize height=$avatarpixelsize src=" . $existing["avatar"] . ">"
       .  "</td>"
       . $clcart
       . $inset_shadow

       : ""

     );

  $fw = $iw - 64;               // width of text input fields

  $sep = $clcart                // accessory list separator
       . $inset_bridge
       . $opcart
       .  "<td class=ls>";

  $pwl = intval ($pw / 2) - 1;  // width of left-side button ("save")
  $pwr = $pw - $pwl - 3;        // width of right-side button ("cancel")

  /*
    define "subject" in sentences:
    depending on $is_owner, "Your profile" or "<Someone else>'s profile"
  */

  $mpers = ($is_owner == true) ? "Your" : "$target_nick's";

  /*
    $ics      third-person "s" appended to verbs
    $icgend   "you", "he" or "she"
    $icpers   "you", "him" or "her"
    $icposs   "your", "his" or "her"
  */

  if ($is_owner == true) {

    $ics = "";

    $icpers = "you";
    $icgend = "you";
    $icposs = "your";

  }
  else {

    $ics = "s";

    if (strtolower (substr (valueOf ($account_record, "gender"), 0, 3)) == "fem") {

      $icgend = "she";
      $icpers = "her";
      $icposs = "her";

    }
    else {

      $icgend = "he";
      $icpers = "him";
      $icposs = "his";

    }

  }

  /*
    major member status message
  */

  if ($mms == true) {

    $mmsline = "<tr><td>" . maketopper ("Major Member") . "</td></tr>";

  }

  /*
    restrictions check (permament ban, kickout delay)
  */

  $ncn = strtolower ($target_nick);

  $bml = trim (strtolower (readFrom ("stats/bml")));
  $bml = wExplode ("\n", $bml);

  if ((in_array ($ncn, $bml)) && ($is_target_admin == false)) {

    $mmsline = "<tr>"
             .  "<td class=alert align=center>"
             .   "PERMANENTLY BANNED"
             .  "</td>"
             . "</tr>"
             . $bridge;

  }
  else {

    $kd = intval (valueOf ($account_record, "kicked_on"))
        + intval (valueOf ($account_record, "kicked_for"));

    if ($kd >= $epc) {

    $mmsline = "<tr>"
             .  "<td class=alert align=center>"
             .   "UNDER KICKOUT DELAY"
             .  "</td>"
             . "</tr>"
             . $bridge;

    }

  }

  /*
    enable or disable text input in "title" field:
    assuming the viewer has the right to change this profile, title may be changed if:
    - member is an administrator or a moderator (always);
    - member has been allowed to pick a user title ($can_pick_title);
    - the board has been set to allow everyone to pick titles ($freetitles).
  */

  $can_pick_title = (valueOf ($account_record, "user_title") == "yes") ? true : false;
  $can_change_title = $is_admin | $is_mod | $can_pick_title | $freetitles;

  if ($allow_change == true) {

    $title_class = "mf";
    $title_disable = "";

  }
  else {

    $title_class = "sf";
    $title_disable = chr (32) . "readonly=readonly";

  }

  /*
    enable or disable text input in "auth" field (the rank, which determines access rights):
    changes are allowed only if member is an administrator, and the rank is not that of the
    first member of the database (logical ID 1, the manager)...
  */

  if (($is_admin == true) && ($target_id != 1)) {

    $auth_class = "mf";
    $auth_disable = "";

  }
  else {

    $auth_class = "sf";
    $auth_disable = chr (32) . "readonly=readonly";

  }

  /*
    enable or disable text input in all other text fields:
    it simply, depends on $allow_change (see code a few hundred lines above)
  */

  if ($allow_change == true) {

    $gen_class = "mf";
    $gen_disable = "";

  }
  else {

    $gen_class = "sf";
    $gen_disable = chr (32) . "readonly=readonly";

  }

  /*
    retrieve saved fields from account record,
    post-processing where appropriate...
  */

  $form_auth    = valueOf ($account_record, "auth");
  $form_title   = get ("members/bynick", "nick>$target_nick", "ut");
  $form_species = valueOf ($account_record, "species");
  $form_gender  = valueOf ($account_record, "gender");
  $form_aka     = valueOf ($account_record, "aka");
  $form_born    = valueOf ($account_record, "born");
  $form_habitat = valueOf ($account_record, "habitat");
  $form_email   = valueOf ($account_record, "email");
  $form_about   = valueOf ($account_record, "about");

  $form_about = str_replace ("&;", "\n", $form_about);
  $regdate = gmdate ("M d, y H:i", valueOf ($account_record, "reg"));

  /*
    retrieve logins count
  */

  $login_count = intval (valueOf ($account_record, "logins"));
  $logins = ($login_count == 0) ? "none" : "$login_count (so far)";

  /*
    compile date of last login (Un*x epoch)
  */

  $last_login = intval (valueOf ($account_record, "lastlogin"));
  $lastlogin = ($last_login == 0) ? "never logged in" : gmdate ("M d, y H:i", $last_login);

  /*
    is member actually logged in?
    - if greater than actual epoch ($epc), session end time is in the future,
      which means session is still valid, and ultimately that member is logged in.
  */

  $session_end_time = intval (get ("stats/sessions", "id>$target_id", "beg")) + $sessionexpiry;
  $in_out = ($session_end_time > $epc) ? "logged in" : "not logged in";

  /*
    compile public general informations ($geninfo, a text box at the bottom of the profile)
  */

  $thread_count = intval (valueOf ($account_record, "threads"));
  $post_count = intval (valueOf ($account_record, "posts"));

  $thread_count = ($thread_count == 0) ? "no" : $thread_count;
  $post_count = ($post_count == 0) ? "no" : $post_count;

  $geninfo = "$icgend started $thread_count threads"
           . "<br>$icgend wrote $post_count messages";

  if (($is_admin == true) || ($is_mod == true) || ($is_owner == true)) {

    /*
      append private message and carbon copys counts only if viewer is
      at least a moderator, of if it's the profile owner...
    */

    $pms = intval (valueOf ($account_record, "pms"));
    $ccs = intval (valueOf ($account_record, "ccs"));

    $geninfo .= "<br>$icgend sent $pms PMs and $ccs CCs";

  }

  /*
    ignore list statistics and utilities:
    showen only to profile owner, moderator, or administrator
  */

  if (($is_admin == true) || ($is_mod == true) || ($is_owner == true)) {

    $target_ilist = valueOf ($account_record, "ignorelist");
    $target_ilist = wExplode (";", $target_ilist);

    /*
      it isn't really possible to ignore mods and admins, but there's the case
      where a formerly regular member was ignored, then became a mod/admin, and
      couldn't be ignored anymore, so its entry in a personal ignore list would
      no longer be valid anyway...
    */

    $filtered_ilist = array ();

    foreach ($target_ilist as $n) {

      $i_id = intval (get ("members/bynick", "nick>$n", "id"));
      $i_db = "members" . intervalOf ($i_id, $bs_members);
      $i_auth = get ($i_db, "id>$i_id", "auth");

      $is_ignored_entry_admin = in_array ($i_auth, $admin_ranks);
      $is_ignored_entry_mod = in_array ($i_auth, $mod_ranks);

      if (($is_ignored_entry_admin == false) && ($is_ignored_entry_mod == false)) {

        $filtered_ilist[] = ((($is_admin == true) || ($is_mod == true) || ($is_owner == true))

          ? "<a href=profile.php?nick=" . base62Encode ($n) . "&amp;listen=y>"
          .  $n
          . "</a>"

          : ""

        );

      }

    }

    if (count ($filtered_ilist) == 0) {

      $geninfo .= $sep . "$icgend ignore$ics nobody<br>";

    }
    else {

      $geninfo .= $sep . "$icgend ignore$ics:<ol><li>" . implode ("<li>", $filtered_ilist) . "</ol>";

    }

    $icount = intval (valueOf ($account_record, "ignorecount"));

    if ($icount == 0) {

      $geninfo .= "no major members ignore $icpers";

    }
    else {

      $s1 = ($icount > 1) ? "s" : "";
      $s2 = ($icount > 1) ? "" : "s";

      $geninfo .= "$icount major member$s1 ignore$s2 $icpers";

    }

  }

  /*
    load actual speech ballon and nickname color from account record:
    even if there could be no selectors for these entities (see below) they will need to be
    posted as they are (as hidden fields) along with the rest of the profile's informations.
  */

  $target_bcol = valueOf ($account_record, "bcol");
  $target_ntint = intval (valueOf ($account_record, "ntint"));

  /*
    if appropriate, create speech balloon and nickname color selection tables:
    they only show to profile owner, there's no point in having them encumber the form otherwise.
  */

  if ($is_owner == true) {

    /*
      nickname color select
    */

    if ($is_admin == true) {

      if ($id > 1) {

        $default_color = $ifc_admin;

      }
      else {

        $default_color = $ifc_server;

      }

    }
    elseif ($is_mod == true) {

      $default_color = $ifc_mod;

    }
    else {

      $default_color = $ifc_member;

    }

    $ntintselect =   "<table width=$iw style=margin:1px>"
                 .    "<tr>"
                 .     "<td style=padding:2px width=1 bgcolor=E4E0E8>"
                 .      "<input type=radio name=ntint value=0" . (($target_ntint == 0) ? chr (32) . "checked" : "") . ">"
                 .     "</td>"
                 .     "<td width=100% height=20 bgcolor=E4E0E8 style=color:$default_color;padding:2px>"
                 .      "&nbsp;" . $ntints[0]["LABEL"]
                 .     "</td>"
                 .    "</tr>";

    for ($x = 1; $x < count ($ntints); ++ $x) {

      $ntintselect .= "<tr>"
                   .   "<td style=padding:2px width=1 bgcolor=E4E0E8>"
                   .    "<input type=radio name=ntint value=$x" . (($target_ntint == $x) ? chr (32) . "checked" : "") . ">"
                   .   "</td>"
                   .   "<td width=100% height=20 bgcolor=E4E0E8 style=color:" . $ntints[$x]["COLOR"] . ";padding:2px>"
                   .    "&nbsp;" . $ntints[$x]["LABEL"]
                   .   "</td>"
                   .  "</tr>";

    }

    $ntintselect .=  "</table>";

    /*
      speech bubble color select
    */

    $bcolselect  =  "<table width=$iw style=\"margin:1px 1px 0 1px\">"
                 .   "<tr>"
                 .    "<td style=padding:2px width=1 bgcolor=" . $bcols[$default_bcol]["COLOR"] . ">"
                 .     "<input onClick=document.prevform.bcol.value='' type=radio name=bcol value=''" . (($target_bcol == $default_bcol) ? chr (32) . "checked" : "") . ">"
                 .    "</td>"
                 .    "<td style=padding:2px;color:black width=100% height=20 bgcolor=" . $bcols[$default_bcol]["COLOR"] . ">"
                 .     "&nbsp;Default (" . strtolower ($bcols[$default_bcol]["LABEL"]) . ")"
                 .    "</td>"
                 .   "</tr>"
                 .  "</table>"
                 .  "<table width=$iw style=\"margin:0 1px 1px 1px\">";

    for ($x = 0; $x < count ($bcols) - 1; ++ $x) {

      $bcolselect .= ($x == $default_bcol)

                  ?  ""
                  :  "<tr>"
                  .   "<td style=padding:2px width=1 bgcolor=" . $bcols[$x]["COLOR"] . ">"
                  .    "<input type=radio name=bcol value=$x" . (($x == $target_bcol) ? chr (32) . "checked" : "") . ">"
                  .   "</td>"
                  .   "<td style=padding:2px;color:black width=100% height=20 bgcolor=" . $bcols[$x]["COLOR"] . chr (32) . ">"
                  .    "&nbsp;" . $bcols[$x]["LABEL"]
                  .   "</td>"
                  .  "</tr>";

    }

    $bcolselect  .= "</table>"
                 .

               (($default_bcol == -1)

                 ?  ""
                 :  "<table width=$iw style=\"margin:0 1px 1px 1px\">"
                 .   "<tr>"
                 .    "<td width=1 bgcolor=E4E0E8>"
                 .     "<input type=radio name=bcol value=-1" . (($target_bcol == -1) ? chr (32) . "checked" : "") . ">"
                 .    "</td>"
                 .    "<td width=100% height=19 bgcolor=E4E0E8 style=color:black>"
                 .     "&nbsp;Pick it randomly!"
                 .    "</td>"
                 .   "</tr>"
                 .  "</table>"

                 );

  }

  /*
    retrieve state of accessory rendering fields from nicknames reference list
  */

  $avatar_displacement = valueOf ($target_entry, "ad");
  $nf_switch = valueOf ($target_entry, "nf");
  $nf_checked = ($nf_switch == "y") ? chr (32) . "checked" : "";

  /*
    assemble moderation controls
  */

  $ut_checked = (valueOf ($account_record, "user_title") == "yes") ? chr (32) . "checked" : "";
  $mu_checked = (valueOf ($account_record, "muted") == "yes") ? chr (32) . "checked" : "";

  $modctr =

        (($mms == false)

          ?  "<span title=\"force major status: can't be undone, and will clear this member's ignorelist\">"
          .   "&nbsp;<input type=checkbox name=fmms>&nbsp;set major member status"
          .  "</span>"
          .  "<br>"

          :  ""

        )

          .

        ((($freetitles == false) && ($is_target_admin == false) && ($is_target_mod == false))

          ?  "<span title=\"check this to allow this member to choose and display his/her own title\">"
          .   "&nbsp;<input type=checkbox name=ut{$ut_checked}>&nbsp;allow $icpers to pick a title"
          .  "</span>"
          .  "<br>"

          :  ""

        )

          .

        ((($is_target_admin == false) && ($is_target_mod == false))

          ?  "<span title=\"check this to disallow this member to write in the chat frame\">"
          .   "&nbsp;<input type=checkbox name=mu$mu_checked>&nbsp;disallow $icpers to chat"
          .  "</span>"

          :  ""

        );

  /*
    build HTML for utility links:
    note these are FORMS, so they might not be placed in the middle of the "real" profile
    form; suitable placements only include the top or the bottom of the profile page...
    they'd be easier to be given as proper "href" links, but they stand out better as buttons.
  */

  $utils =

        // find recent posts by this member

       (($is_owner == false)

         ? $bridge
         . "<form action=recdisc.php method=get>"
         . "<input type=hidden name=n value=$encoded_target_nick>"
         . "<tr>"
         .  "<td colspan=2>"
         .   "<input class=ky type=submit value=\"find $icposs recent posts\" style=width:{$pw}px>"
         .  "</td>"
         . "</tr>"
         . "</form>"

         : ""

       )

        // find all threads by this member

         . $bridge
         . "<form action=egosearch.php method=get>"
         . "<input type=hidden name=n value=$encoded_target_nick>"
         . "<tr>"
         .  "<td colspan=2>"
         .   "<input class=ky type=submit value=\"find all $icposs threads\" style=width:{$pw}px>"
         .  "</td>"
         . "</tr>"
         . "</form>"

        // list profile owner's c-disk files

         . $bridge
         . "<form action=cdisk.php method=get>"
         . "<input type=hidden name=sub value=$encoded_target_nick>"
         . "<tr>"
         .  "<td colspan=2>"
         .   "<input class=ky type=submit value=\"list all $icposs c-disk files\" style=width:{$pw}px>"
         .  "</td>"
         . "</tr>"
         . "</form>"
         .

        // link to send a private message to profile owner

       ((($login == true) && ($is_owner == false))

         ? $bridge
         . "<form action=sendpm.php method=get>"
         . "<input type=hidden name=to value=$encoded_target_nick>"
         . "<tr>"
         .  "<td colspan=2>"
         .   "<input class=ky type=submit value=\"send $icpers a private message\" style=width:{$pw}px>"
         .  "</td>"
         . "</tr>"
         . "</form>"

         : ""

       )

         .

        // button to ignore profile owner

       ((($login == true) && ($is_target_admin == false) && ($is_target_mod == false) && ($ignored == false) && ($is_owner == false))

         ? $bridge
         . "<form action=profile.php method=get>"
         . "<input type=hidden name=ignore value=y>"
         . "<input type=hidden name=code value=" . setcode_once () . ">"
         . "<input type=hidden name=nick value=$encoded_target_nick>"
         . "<tr>"
         .  "<td colspan=2>"
         .   "<input class=ky type=submit value=\"ignore what $icgend writes\" style=\"width:{$pw}px\">"
         .  "</td>"
         . "</tr>"
         . "</form>"

         : ""

       )

         .

       // button to un-ignore profile owner

       ((($login == true) && ($is_target_admin == false) && ($is_target_mod == false) && ($ignored == true) && ($is_owner == false))

         ? $bridge
         . "<form action=profile.php method=get>"
         . "<input type=hidden name=listen value=y>"
         . "<input type=hidden name=code value=" . setcode_once () . ">"
         . "<input type=hidden name=nick value=$encoded_target_nick>"
         . "<tr>"
         .  "<td colspan=2>"
         .   "<input class=su type=submit value=\"stop ignoring $icposs messages\" style=\"width:{$pw}px\">"
         .  "</td>"
         . "</tr>"
         . "</form>"

         : ""

       );

  /*
    begin assembling HTML layout of profile form (as $form)
  */

  if ($error == true) {

    /*
      if there's an error to report,
      it's showed on top of the profile form
    */

    list ($frame_title, $frame_contents) = explode ("//", $form);

    $form =

          makeframe (

            $frame_title,
            $frame_contents,

            true

          );

  }
  else {

    /*
      no errors to report, but still a warning may be reported if the profile form of
      this member was accessed by clicking the nickname of the author of a certain post:
      if registration date of this member (the owner of the profile) is past the date
      of the post given with the refering link ("at" argument), display warning...
    */

    $at = intval (fget ("at", 1000, ""));

    $warning =

           ((($at > 0) && (intval (valueOf ($account_record, "reg")) > $at))

             ? "WARNING: you are accessing this profile from a message that was posted "
             . "<u>before</u> this member's registration date. This might mean that "
             . "the message you've been reading has NOT been posted by this member, but "
             . "by some other member who had registered with <u>that same name</u>, and "
             . "then resigned."

             : false

           );

    $form =

          makeframe (

            "$mpers profile:",
            $warning,

            true

          );

  }

  /*
    continue assembling HTML for profile form...
  */

  $form .=

        // button to "introduce" profile owner, ie search members' list, display photograph etc...

           "<table width=$pw>"
         . "<form action=members.php method=get target=pan>"
         . "<input type=hidden name=present value=$encoded_target_nick>"
         . "<tr>"
         .  "<td colspan=2>"
         .   "<input style=\"width:{$pw}px\" class=ky type=submit "
         .   "value=\"" . (($is_owner) ? "review introduction page" : "introduce $icpers") . " ...\""
         .   ">"
         .  "</td>"
         . "</tr>"
         . "</form>"
         . "</table>"
         . $inset_shadow

        // table for avatar image slot and "major member" status line

        .  $img
        .  "<table width=$pw>"
        .   $mmsline
        .  "</table>"
        .

        // inspector link (helps spotting duplicated accounts)

      ((($is_admin == true) || ($is_mod == true))

        ?  $opcart
        .   "<form name=inspect target=pan action=inspect.php enctype=multipart/form-data method=get>"
        .   "<input type=hidden name=nick value=$encoded_target_nick>"
        .   "<td class=ls>"
        .    "<input type=checkbox name=igip> ignore IP<br>"
        .    "<input type=checkbox name=fuzy> use fuzzy logic"
        .   "</td>"
        .   "<td class=ls align=right>"
        .    "<a href=# onClick=document.inspect.submit()>"
        .     "<img src=layout/images/inspect.png width=48 height=48 border=0>"
        .    "</a>"
        .   "</td>"
        .   "</form>"
        .  $clcart
        .  $inset_bridge

        :  ""

      )

        // statistics (registration date, no. of logins, date/time of last login...)

        .  $opcart
        .   $fspace
        .   "<tr><td width=64 class=in align=right>joined:&nbsp;</td><td class=in>&nbsp;$regdate</td></tr>"
        .   "<tr><td class=in align=right>logins:&nbsp;</td><td class=in>&nbsp;$logins</td></tr>"
        .   "<tr><td class=in align=right>status:&nbsp;</td><td class=in>&nbsp;$in_out</td></tr>"
        .

      (($in_out == "not logged in")

        ?   "<tr><td class=in align=right>last seen:&nbsp;</td><td class=in>&nbsp;$lastlogin</td></tr>"
        :   ""

      )

        .   $fspace
        .  $clcart
        .  $inset_bridge

        // profile form open (<form>),
        // ubiquitus text fields (rank, title, species, gender, aka, born, habitat)

        .  $opcart
        .   $fspace
        .   "<form action=profile.php?nick=$encoded_target_nick enctype=multipart/form-data method=post>"
        .   "<tr>"
        .    "<td width=64 class=in align=right>rank:&nbsp;</td>"
        .    "<td class=in><input class=$auth_class style=width:{$fw}px type=text name=auth value=\"$form_auth\" maxlength=$p_auth_size{$auth_disable}></td>"
        .   "</tr>"
        .   "<tr>"
        .    "<td class=in align=right>title:&nbsp;</td>"
        .    "<td class=in><input class=$title_class style=width:{$fw}px type=text name=title value=\"$form_title\" maxlength=$p_title_size{$title_disable}></td>"
        .   "</tr>"
        .   "<tr>"
        .    "<td class=in align=right>species:&nbsp;</td>"
        .    "<td class=in><input class=$gen_class style=width:{$fw}px type=text name=species value=\"$form_species\" maxlength=$p_species_size{$gen_disable}></td>"
        .   "</tr>"
        .   "<tr>"
        .    "<td class=in align=right>gender:&nbsp;</td>"
        .    "<td class=in><input class=$gen_class style=width:{$fw}px type=text name=gender value=\"$form_gender\" maxlength=$p_gender_size{$gen_disable}></td>"
        .   "</tr>"
        .   "<tr>"
        .    "<td class=in align=right>a.k.a.&nbsp;</td>"
        .    "<td class=in><input class=$gen_class style=width:{$fw}px type=text name=aka value=\"$form_aka\" maxlength=$p_aka_size{$gen_disable}></td>"
        .   "</tr>"
        .   "<tr>"
        .    "<td class=in align=right>born:&nbsp;</td>"
        .    "<td class=in><input class=$gen_class style=width:{$fw}px type=text name=born value=\"$form_born\" maxlength=$p_born_size{$gen_disable}></td>"
        .   "</tr>"
        .   "<tr>"
        .    "<td class=in align=right>habitat:&nbsp;</td>"
        .    "<td class=in><input class=$gen_class style=width:{$fw}px type=text name=habitat value=\"$form_habitat\" maxlength=$p_habitat_size{$gen_disable}></td>"
        .   "</tr>"
        .   $fspace
        .  $clcart
        .  $inset_shadow
        .

        // feedback email address (only shown to profile owner)

      (($is_owner == true)

        ?  "<table width=$pw>"
        .   "<td height=40 class=inv align=center>"
        .    "send" . chr (32) . ((($is_admin) || ($is_mod)) ? "feedback" : "temp. password") . chr (32) . "to...<br>"
        .    "<small>(your email will be kept private)</small>"
        .   "</td>"
        .  "</table>"
        .  $inset_bridge
        .  $opcart
        .   $fspace
        .   "<tr>"
        .    "<td width=64 class=in align=right>email:&nbsp;</td>"
        .    "<td class=in><input class=mf style=width:{$fw}px type=text name=email value=\"$form_email\" maxlength=$p_email_size></td>"
        .   "</tr>"
        .   $fspace
        .  $clcart
        .  $inset_shadow

        :  ""

      )

        // "bit about" field (which is a text area)

        .  "<table width=$pw>"
        .   "<td height=20 class=inv align=center>"
        .    "bit about ($p_about_size char):"
        .   "</td>"
        .  "</table>"
        .  $inset_bridge
        .  $opcart
        .   "<td>"
        .    "<textarea style=width:{$iw}px;height:188px name=about{$gen_disable}>"
        .     $form_about
        .    "</textarea>"
        .   "</td>"
        .  $clcart
        .  $inset_shadow

        // general informations pad:
        // number of threads, posts, private messages, ignore list, ignore count

        .  "<table width=$pw>"
        .   "<td height=20 class=inv align=center>"
        .    "general informations:"
        .   "</td>"
        .  "</table>"
        .  $inset_bridge
        .  $opcart
        .   "<tr>" // <tr> here because $geninfo may add rows to the table trought $sep elements
        .    "<td class=ls>"
        .     $geninfo
        .    "</td>"
        .   "</tr>"
        .  $clcart
        .  $inset_shadow
        .

        // nickname and speech bubble color selectors,
        // given as hidden fields to whoever doesn't own this profile,
        // but in case, as mod/admin, could submit changes...

      ((($allow_change == true) && ($is_owner == true))

        ?  "<table width=$pw>"
        .   "<tr>"
        .    "<td height=20 class=inv align=center>"
        .     "nickname's color:"
        .    "</td>"
        .   "</tr>"
        .   $bridge
        .   "<tr>"
        .    "<td class=tb>"
        .     $ntintselect
        .    "</td>"
        .   "</tr>"
        .   $shadow
        .   "<tr>"
        .    "<td height=40 class=inv align=center>"
        .     "speech balloon color:<br>"
        .     "<small>(applies to future posts)</small>"
        .    "</td>"
        .   "</tr>"
        .   $bridge
        .   "<tr>"
        .    "<td class=tb>"
        .     $bcolselect
        .    "</td>"
        .   "</tr>"
        .   $shadow
        .  "</table>"

        :  "<input type=hidden name=bcol value=\"$target_bcol\">"
        .  "<input type=hidden name=ntint value=$target_ntint>"

      )

        .

        // avatar control panel

      (($allow_change == true)

        ?  "<table width=$pw>"
        .   "<td height=40 class=inv align=center>"
        .    "avatar control:<br>"
        .    "<small>{$avatarpixelsize}x{$avatarpixelsize}, MAX " . ((int) ($avatarbytesize / 1024)) . " Kb, GIF, JPG, PNG</small>"
        .   "</td>"
        .  "</table>"
        .  $inset_bridge
        .  $opcart
        .   "<td height=32 class=in align=center>"
        .    "<span title=\"check this button to UPLOAD the image specified BELOW as your avatar\">"
        .     "<input type=radio name=avatar_switch value=up>&nbsp;Upload&nbsp;&nbsp;"
        .    "</span>"
        .    "<span title=\"check this button to DELETE the existing avatar, if any\">"
        .     "<input type=radio name=avatar_switch value=del>&nbsp;Delete"
        .    "</span>"
        .   "</td>"
        .  $clcart
        .  $inset_bridge
        .  $opcart
        .   "<td class=fs>"
        .    "<span title=\"avatar IMAGE FILE NAME: pick an image from your PC "
        .    "({$avatarpixelsize}x{$avatarpixelsize} pixels, upto " . ((int) ($avatarbytesize / 1024)) . " Kb)\">"
        .     "<input type=hidden name=MAX_FILE_SIZE value=$avatarbytesize>"
        .     "<input style=width:" . ($iw - 2) . "px size=" . (intval ($pw / 11)) . " type=file name=avatar>"
        .    "</span>"
        .   "</td>"
        .  $clcart
        .  $inset_bridge
        .  $opcart
        .   "<td class=in align=center style=padding:4px>"
        .    "<span title=\"removes the black frame around the avatar - for use whenever it looks bad\">"
        .     "&nbsp;<input type=checkbox name=nf{$nf_checked}>&nbsp;do not frame my avatar"
        .    "</span>"
        .   "</td>"
        .  $clcart
        .  $inset_bridge
        .  $opcart
        .   $fspace
        .   "<tr>"
        .    "<td class=in align=center>"
        .     "displace avatar by: &nbsp;"
        .     "<input class=mf style=width:30px type=text name=avatar_displacement value=$avatar_displacement>"
        .     "&nbsp; px"
        .    "</td>"
        .   "</tr>"
        .   $fspace
        .  $clcart
        .  $inset_shadow

        :  ""

      )

        .

        // photograph control panel

      (($allow_change == true)

        ?  "<table width=$pw>"
        .   "<td height=40 class=inv align=center>"
        .    "photograph control:<br>"
        .    "<small>MAX {$maxphotowidth}x{$maxphotoheight}x" . ((int) ($photobytesize / 1024)) . "Kb, GIF, JPG, PNG</small>"
        .   "</td>"
        .  "</table>"
        .  $inset_bridge
        .  $opcart
        .   "<td height=32 class=in align=center>"
        .    "<span title=\"check this button to UPLOAD the image specified BELOW as your photograph\">"
        .     "<input type=radio name=photo_switch value=up>&nbsp;Upload&nbsp;&nbsp;"
        .    "</span>"
        .    "<span title=\"check this button to DELETE the existing photograph, if any\">"
        .     "<input type=radio name=photo_switch value=del>&nbsp;Delete"
        .    "</span>"
        .   "</td>"
        .  $clcart
        .  $inset_bridge
        .  $opcart
        .   "<td class=fs>"
        .    "<span title=\"photograph IMAGE FILE NAME: pick an image from your PC "
        .    "({$maxphotowidth}x{$maxphotoheight} pixels, upto " . ((int) ($photobytesize / 1024)) . " Kb)\">"
        .     "<input type=hidden name=MAX_FILE_SIZE value=$photobytesize>"
        .     "<input style=width:" . ($iw - 2) . "px size=" . (intval ($pw / 11)) . " type=file name=photo>"
        .    "</span>"
        .   "</td>"
        .  $clcart
        .  $inset_shadow

        :  ""

      )

        .

        // moderation controls

      (((($is_admin == true) || ($is_mod == true)) && (!empty ($modctr)))

        ?  "<table width=$pw>"
        .   "<td height=20 class=inv align=center>"
        .    "moderation controls:"
        .   "</td>"
        .  "</table>"
        .  $inset_bridge
        .  $opcart
        .   "<td class=in style=padding:4px>"
        .    $modctr
        .   "</td>"
        .  $clcart
        .  $inset_shadow

        :  ""

      )

        .

        // resign request (three checkboxes for confirmation):
        // apart from the owner of the profile, only admins may force members to resign.

      ((($target_id != 1) && (($is_admin == true) || ($is_owner == true)))

        ?  "<table width=$pw>"
        .   "<td height=40 class=alert align=center>"
        .    "RESIGN TRIGGER<br>"
        .    "<small>(delete this account)</small>"
        .   "</td>"
        .  "</table>"
        .  $inset_bridge
        .  $opcart
        .   "<td class=in align=center style=padding:4px>"
        .    "<span title=\"check ALL THE THREE BOXES if you really want it - this CANNOT BE UNDONE\">"
        .     "<input type=checkbox name=resign_1>&nbsp;Yes&nbsp;&nbsp;"
        .     "<input type=checkbox name=resign_2>&nbsp;Yes&nbsp;&nbsp;"
        .     "<input type=checkbox name=resign_3>&nbsp;Yes"
        .    "</span>"
        .   "</td>"
        .  $clcart
        .  $inset_shadow

        :  ""

      )

        .

        // form submission ("save" and "cancel" buttons)

      (($allow_change == true)

        ?  "<table width=$pw>"
        .   "<tr>"
        .    "<td height=20 class=inv align=center>"
        .     "update profile data:"
        .    "</td>"
        .   "</tr>"
        .   $bridge
        .   "<tr>"
        .    "<td>"
        .     "<table width=$pw>"
        .      "<td>"
        .       "<span title=\"APPLY CHANGES TO PROFILE\">"
        .        "<input type=hidden name=code value=" . setcode_once () . ">"
        .        "<input class=su style=width:{$pwl}px type=submit name=submit value=save>"
        .       "</span>"
        .      "</td>"
        .  "</form>"
        .  "<form action=mstats.php>"
        .      "<td align=right>"
        .       "<span title=\"RETURN TO LOGIN PANEL\">"
        .        "<input class=ky style=width:{$pwr}px type=submit value=cancel>"
        .       "</span>"
        .      "</td>"
        .  "</form>"
        .     "</table>"
        .    "</td>"
        .   "</tr>"
        .  "</table>"
        .  $inset_shadow

        :  "</form>"

      )

        // utilities (past the main profile form because these ARE other small forms)

        .  "<table width=$pw>"
        .   "<tr>"
        .    "<td height=20 class=inv align=center>"
        .     "utilities:"
        .    "</td>"
        .   "</tr>"
        .   $utils
        .  "</table>"
        .  $inset_shadow;

  /*
    set permalink
  */

  if (($enable_permalinks) && ($real_member)) {

    $permalink = $perma_url . "/" . $about_keyword . "/" . $encoded_target_nick;

  }

}

/*
 *
 * template initialization
 *
 */

$permalink = (isset ($permalink))

  ? str_replace

      (

        "[PERMALINK]", $permalink, $permalink_model

      )

  : "";

$profile = str_replace

  (

    array (

      "[FORM]",
      "[PERMALINK]"

    ),

    array (

      $form,
      $permalink

    ),

    $profile

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page upon showing or
 * hiding the chat frame, and for the online members list links (unless no-spy flag checked).
 *
 */

if ($dontrememberthisurl == false) {

  include ("setlfcookie.php");

}

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($profile));



?>
