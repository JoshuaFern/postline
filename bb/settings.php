<?php if (!defined ('postline/settings.php')) {

/*************************************************************************************************

	    HSP Postline -- overall board settings

	    PHP bulletin board and community engine
	    version 2010.01.30 (10.1.30)

		Copyright (C) 2003-2010 by Alessandro Ghignola
		Copyright (C) 2003-2010 Home Sweet Pixel software

	    This program is free software; you can redistribute it and/or modify it under the
	    terms of the GNU General Public License as published by the Free Software Foundation;
	    either version 2 of the License, or (at your option) any later version.

	    This program is distributed in the hope that it will be useful, but WITHOUT ANY
	    WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
	    PARTICULAR PURPOSE. See the GNU General Public License for more details.

	    You should have received a copy of the GNU General Public License
	    along with this program; if not, write to the Free Software Foundation, Inc.,
	    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA

*************************************************************************************************/

/*
 *
 *	IMPORTANT!
 *
 *	avoid launching this and other similary dependand scripts alone,
 *	to prevent potential malfunctions and unexpected security flaws:
 *	the starting point of launchable scripts is defining 'going'
 *
 */

$proceed = (defined ('going')) ? true : die ('[ postline settings ]');

/*
 *
 *	postline scripts set version number:
 *	I normally place here the date of last change (of the overall package), as Y.M.D
 *
 */

$plversion = '10.11.28';

/*
 *
 *	names and paths to postline scripts, folders and parts, for this installation
 *
 *	      - the $sitename string might not include any non-alphanumeric characters for better
 *		safety, as well as NO blank spaces, because it's going to be used as a part of
 *		cookies' names; you may use underscores if you want, but keep it short and simple
 *
 *	      - the $sitedomain string MIGHT reflect your real domain, as a DNS knows it, because
 *		it's used to distinguish local and remote links, and if it's not corresponding it
 *		will cause highlighted links in the chat frame and in messages to always open in
 *		blank new windows or tabs: well, it's of course more comfortable to have links to,
 *		for example, 'posts.php', open in the 'pan' frame (the right part of the central
 *		frame) of your site's console; should you have a numerical IP in place of a domain
 *		(e.g. 123.45.67.8), type that as $sitedomain. If you don't have a domain or static
 *		IP but you're using an URL in the form "domain/username" type the path to your home
 *		page there; for better matching also append the full path to Postline's folder with
 *		respect to your home page; IN ANY CASES, ALWAYS PLACE A TERMINAL SLASH AT THE END!
 *
 *	      - $servername is purely cosmetic: it's the way your installation signs messages being
 *		reported by the server in the chat frame or in special messages (for example shared
 *		password warnings); AnywhereBB.com's server defaults to calling it 'Mary Lou'
 *
 *	      - $path_to_postline is absolute, meaning it must begin with a forward slash (from
 *		the documents root); also, it's assumed not to end by a terminal slash; the same
 *		considerations apply to other such paths to subdirectories of the postline folder,
 *		i.e. $path_to_avatars, $path_to_photos, and $feedbackfolder (those are relative)
 *
 */

$sitename		= 'AnyNowhere'; 		// short, and use only URL-safe characters
$servername		= 'Mary Lou';			// works like a regular member's nickname
$sitemailer		= 'anynowhere.com';		// appended to return address (tellme.php)
$sitedomain		= 'http://localhost/bb/';	// exact protocol and match is recommended
$path_to_postline	= '/bb';			// where these scripts are kept (absolute)
$path_to_avatars	= 'layout/images/avatars';	// relative to $path_to_postline
$path_to_photos 	= 'layout/images/photos';	// relative to $path_to_postline
$feedbackfolder 	= 'feedback';			// relative to $path_to_postline

/*
 *
 *	when the serverbot is active, placing these keywords into chat phrases is a way to attract
 *	the serverbot's attention, i.e. to address something specifically to the bot
 *
 */

$serverbot_reacts_to = array

  (

    'Mary'
  , 'Lou'
  , 'server'
  , 'ML'

  );

/*
 *
 *	permalink settings
 *
 *	permalinks should be enabled for the most important pages and for all forums, threads,
 *	messages, and c-disk files that are not pictures (e.g. ZIP archives), and then be used
 *	in remote sites; you can enable permalinks after you place some redirect rules in your
 *	server's documents root, by saving a .htaccess file there and by ensuring mod_Alias and
 *	mod_Rewrite are active in the online Apache server installation; otherwise, leave them
 *	disabled; the suggestion is to have them enabled, because they can be extremely useful
 *	to enter the board from remote links while causing such links to properly display the
 *	full frameset; the $perma_url must reflect the URL of your site's documents root, which
 *	is generally the URL of the site itself, and *must not* include a terminal slash, e.g.
 *	'http://www.mysite.com' would be the most common form of $perma_url; the $home_keyword,
 *	appended to $perma_url, will generate the permalink of the welcome page of this Postline
 *	installation: it might be left to 'home' if you run Postline as the content management
 *	system for your entire site; otherwise you may set it to something like 'forum', 'board',
 *	'community', 'postline', etc, if your main CMS is something other than Postline and you
 *	wish to leave the URI '/home' unused by Postline; similar considerations apply to other
 *	such '*_keyword' arguments following the declaration of $home_keyword
 *
 */

$enable_permalinks	= true; 			// true to enable permalinks (see above)
$perma_url		= 'http://localhost';		// URL of the documents root (see above)

/*
 *
 *	permalink keywords
 *
 *	      - CAUTION:
 *		permalinks live in a javascript alert box, use only alphanumeric characters!
 *
 */

$home_keyword		= 'home';			// welcome page redirect, see notes above
$help_keyword		= 'help';			// FAQ page redirect, see notes above
$members_keyword	= 'members';			// members list redirect, see notes above
$about_keyword		= 'about';			// introduction pages redirect, see notes
$forums_keyword 	= 'forums';			// forums index redirect, see notes above
$threads_keyword	= 'forum';			// forum threads index redirect, see notes
$posts_keyword		= 'thread';			// thread msg. index redirect, see notes
$message_keyword	= 'message';			// single msg. display redirect, see notes
$staffroster_keyword	= 'staff';			// staff roster redirect, see notes above
$chatlogs_keyword	= 'logs';			// chat logs redirect, see notes above
$feedback_keyword	= 'feedback';			// feedback form redirect, see notes above

/*
 *
 *	forum-specific permalinks,
 *	based on the forum ID and used for forums featured in the navigation bar
 *
 */

$forum_permas = array

  (

    13 => 'projects'

  );

/*
 *
 *	thread-specific permalinks,
 *	based on the thread ID and used for threads featured in the navigation bar
 *
 */

$thread_permas = array

  (

    408 => 'linoleum',
    409 => 'noctis',
    413 => 'links',
    414 => 'software'

  );

/*
 *
 *	Management account Protection Override
 *
 *	sometimes a manager may want to allow changes to his account by overriding the protection:
 *	this is normally done in consequence of a complete restore of a board from backup, starting
 *	with a completely void database; this password is also important because it's the only way
 *	to get the Database Explorer to change contents of the records that concern the management
 *	account; the (very aggressive) protection is there in the first place to avoid that, should
 *	someone guess the password to the management account, that sole password wouldn't suffice
 *	to make changes to the account itself, and providing the manager could later login back to
 *	his account, he would still have proper rights to recover any eventual damages from backups
 *
 */

$mpo_password = 'something';	// password for Management account Protection Override (see DBX)

/*
 *
 *	relative URI filter of 'index.php'
 *
 *	it only allows legit page URIs to be loaded through base62-encoded strings being fed to
 *	the 'index.php' script; this might be secure, so you couldn't for instance pass a 'src'
 *	argument to a frame in the form 'javascript: do_malicious_stuff_here'
 *
 *	$l_frames are the only allowed left frame URIs
 *	$r_frames are the only allowed right frame URIs
 *
 */

$l_frames = array

  (

    'cdisk.php'
  , 'defense.php'
  , 'delete.php'
  , 'edit.php'
  , 'egosearch.php'
  , 'inbox.php'
  , 'kprefs.php'
  , 'locked.php'
  , 'mstats.php'
  , 'onlines.php'
  , 'outbox.php'
  , 'postform.php'
  , 'profile.php'
  , 'recdisc.php'
  , 'retitle.php'
  , 'search.php'
  , 'sendpm.php'
  , 'side.php'
  , 'split.php'
  , 'styles.php'
  , 'xhelp.php'

  );

$r_frames = array

  (

    'authlist.php'
  , 'cdimage.php'
  , 'errlist.php'
  , 'explorer.php'
  , 'faq.php'
  , 'forums.php'
  , 'inspect.php'
  , 'intro.php'
  , 'logs.php'
  , 'members.php'
  , 'nslookup.php'
  , 'posts.php'
  , 'preview.php'
  , 'result.php'
  , 'tellme.php'
  , 'threads.php'
  , 'viewpm.php'

  );

/*
 *
 * about $time_offset:
 *
 *   I went into total confusion when I learned that server-side time is affected by the time zone
 *   of the server (not really UTC), and I have no precise idea how this kind of conversion should
 *   work, so here's an offset: I'm not sure what its value should be, so unless you know, I'd say
 *   set it to some multiple of 3600 (it's in seconds, and 1 hour = 3600 seconds), either negative
 *   or positive, until a search within the chat logs will match the times there. When your server
 *   changes time zone, or you change the server at all, you might need to also change this offset
 *   again, to obtain matches in such searchs: find this value before going public because it's in
 *   use for determining the epoch of a chat's message while its being indexed, so you'd get wrong
 *   indexing as long as this value wasn't correct. However, search script will inspect around the
 *   epoch of posts and logs matches, to work around this problem...
 *
 * about $ybase:
 *
 *   it's the first year for which your board has chat logs; if you installed Postline this year,
 *   it would need to be this year, unless your installation will inherit the data from past chat
 *   sessions which were recorded in past years... the minimum value for $ybase is 2003, coherent
 *   with the logs' timestamp corresponding to May 5, 2003 at 20:05 (minute #0 of the time stamp)
 *
 */

$time_offset = 0;
$ybase = 2003;

/*
 *
 *	letter identifying the actual version of prefs' data: it's provided as a signal that makes
 *	the preferences' cookie invalid when this letter changes, until new prefs are specified
 *	from scratch; change it when you change something in the preferences' cookie format, so
 *	old cookies won't interfere with the new format - changing this letter, however, makes all
 *	members lose their actual preferences (which is, practically, what we want in such cases)
 *
 */

$prefscfgversion = 'X';

/*
 *
 *	two alternative fragments to be used in the subsequent visual styles' array for
 *	replacement of the [BODY-OVERRIDING] argument; these are somewhat coupled with
 *	the templating script, but it's unlikely that they should be ever changed:
 *
 *	      - $override_plain uses plain-colored background;
 *	      - $override_texture uses an image, ideally large, spread over the central frameset
 *
 */

$override_plain = "<body style=\"[F-EXTRA]\"[BX]>";
$override_texture = "<body style=\"background-position: [FRAME-X]px [FRAME-Y]px; [F-EXTRA]\"[BX]>";

/*
 *
 *	index in styles array for default visual style, when preferences aren't set
 *
 */

$default_style = 8;	// 'Optical'

/*
 *
 *	visual styles array:
 *	these are supposed to apply to any conceivable templates anyway
 *
 */

$styles = array

  (

    0 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => '44 Gj'					// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-color: #370400;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => '44gj.jpg'				// background image name
      , '[MOST-TEXT-COLOR]' => 'ffffff' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => 'a01a1a' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => 'a00a0a' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => 'ff8000' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'fff000' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => 'ff8080' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => 'ffc800' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => 'ff0040' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => 'ff'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => false					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  , 1 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'After The Rain' 			// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-color: #dce8ff;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'aftertherain.jpg'			// background image name
      , '[MOST-TEXT-COLOR]' => '000000' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => 'ffffff' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => 'dce8ff' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => '800040' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'e80000' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '6e7a94' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => '123499' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => '7e8ab4' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => '00'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => true					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => false					// giving nav. bar a shadow

      )

  , 2 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Anniversary'				// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-repeat: no-repeat;'
	      . 'background-attachment: fixed;'
	      . 'background-color: #7f7973;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'anniversary.jpg'			// background image name
      , '[MOST-TEXT-COLOR]' => '000000' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => 'f1efeb' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => 'b1afab' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => '404040' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'ffffff' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '000000' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => '800000' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => '817f7b' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => '00'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => true					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  , 3 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Carbon' 				// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-color: #444;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'carbon.png'				// background image name
      , '[MOST-TEXT-COLOR]' => 'ffffff' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => '000000' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => '404040' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => 'fff000' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'ffa000' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '8080ff' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => '64ff64' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => '000000' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => 'ff'					// 00 if bright, ff if dark
      , '[APPLYREFLECTION]' => false					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  , 4 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Denim Blue'				// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-color: #464a84;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'denim.jpg'				// background image name
      , '[MOST-TEXT-COLOR]' => 'ffffff' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => '5b5da4' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => '464a84' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => '80ff80' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'c0ffc0' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '101820' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => 'ffc060' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => '8b8dc4' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => 'ff'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => false					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  , 5 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Garden' 				// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-color: #050;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'garden.jpg'				// background image name
      , '[MOST-TEXT-COLOR]' => 'ffffff' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => 'a1595b' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => 'a1a1a1' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => '80ff80' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'c0ffc0' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => 'fff000' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => 'ffc060' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => '000000' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => 'ff'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => false					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  , 6 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Litter Box'				// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-color: #f1f1f3;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'litter.jpg'				// background image name
      , '[MOST-TEXT-COLOR]' => '000000' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => 'f1f1f3' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => 'f1f1f3' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => '2040ff' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'dc2020' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '646464' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => '990000' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => '6464c8' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => '00'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => false					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  , 7 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Mahogany'				// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-color: #632d13;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'mahogany.jpg'				// background image name
      , '[MOST-TEXT-COLOR]' => 'ffffff' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => '805030' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => '632d13' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => 'ffa050' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'ffff50' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => 'ffc298' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => 'fff080' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => 'c08252' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => 'ff'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => true					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  , 8 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Optical'				// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-color: #e8e8e8;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'optical.png'				// background image name
      , '[MOST-TEXT-COLOR]' => '002080' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => 'ffffff' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => 'f5f5f5' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => '008000' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'c80000' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '000000' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => '0000ff' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => '8080c0' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => '00'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => false					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  , 9 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Remix'					// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-repeat: no-repeat;'
	      . 'background-attachment: fixed;'
	      . 'background-color: #fff;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'remix.jpg'				// background image name
      , '[MOST-TEXT-COLOR]' => '000000' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => 'ffffff' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => 'f5f5f5' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => '006400' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'c80000' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '000000' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => '002080' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => 'ff2080' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => '00'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => false					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  ,10 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Security Blanket'			// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-color: #4b85c2;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'blanket.jpg'				// background image name
      , '[MOST-TEXT-COLOR]' => '000040' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => 'fff8f0' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => '4b85c2' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => '660000' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'c80000' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '000066' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => '006600' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => '000000' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => '00'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => false					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  ,11 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Shadows'				// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-repeat: no-repeat;'
	      . 'background-attachment: fixed;'
	      . 'background-color: #c3d3dd;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'shadows.jpg'				// background image name
      , '[MOST-TEXT-COLOR]' => '30404c' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => 'ffffff' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => 'c3d3dd' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => '000000' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'c00000' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '32434c' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => '800000' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => '808088' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => '00'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => true					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  ,12 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Then Comes The Sun'			// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-repeat: no-repeat;'
	      . 'background-attachment: fixed;'
	      . 'background-color: #000;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'thencomesthesun.jpg'			// background image name
      , '[MOST-TEXT-COLOR]' => 'ffffff' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => '000000' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => 'a4a0a8' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => 'fff000' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'ffa000' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '64c8ff' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => '58ffff' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => 'ffffff' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => 'ff'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => true					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => false					// giving nav. bar a shadow

      )

  ,13 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Ultraviolet'				// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-color: #000;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'ultraviolet.png'			// background image name
      , '[MOST-TEXT-COLOR]' => 'bbbbff' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => '700090' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => 'af00f4' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => 'ff00ff' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'ffffff' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => 'cf00ff' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => 'b030ff' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => '7f00ff' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => 'ff'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => false					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  ,14 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'When It Rains'				// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-color: #191c33;'
	      . 'background-attachment: fixed;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'whenitrains.jpg'			// background image name
      , '[MOST-TEXT-COLOR]' => '999cd9' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => '2a2c4f' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => '191c33' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '484888' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => 'c8ffff' 				// color of links
      , '[HOV-LINKS-COLOR]' => '007fff' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '898cc3' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => 'ffffff' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => '696c99' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => 'ff'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => true					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  ,15 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'White'					// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-color: #fff;'
	      . ((defined ('left_frame')) ? '' : 'border-left: 1px solid #000;')

      , '[BODY-OVERRIDING]' => $override_plain				// body tag model to use
      , '[TRUE-BACKGROUND]' => 'none'					// background image name
      , '[MOST-TEXT-COLOR]' => '000000' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => 'ffffff' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => 'ffffff' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => 'b00000' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'f00000' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '6464c8' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => '2040d0' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => 'a4a0a8' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => '00'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => false					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  ,16 => array								// progressive index from 0

      (

	'STYLE_DESCRIPTION' => 'Wrapped in the Attic'			// appears in settings form
      , '[BODY-BACKGROUND]' =>						// background control tags

		'background-image: url("[TRUE-BACKGROUND]");'
	      . 'background-color: #000;'

      , '[BODY-OVERRIDING]' => $override_texture			// body tag model to use
      , '[TRUE-BACKGROUND]' => 'wrapped.jpg'				// background image name
      , '[MOST-TEXT-COLOR]' => '908080' 				// color of normal text
      , '[MOST-TEXT-BKGND]' => '807060' 				// color of text frames
      , '[SCROLL-BAR-TRCK]' => '988070' 				// color of scroller tracks
      , '[SCROLL-BAR-FACE]' => '000000' 				// color of scroller bars
      , '[NRM-LINKS-COLOR]' => 'C8C080' 				// color of links
      , '[HOV-LINKS-COLOR]' => 'EEEEEE' 				// color of hovered links
      , '[SERVERLOADCOLOR]' => '8080A0' 				// color of load indication
      , '[FRAMEBOUNDCOLOR]' => '9090C0' 				// color of lines and edges
      , '[INNERFRAMECOLOR]' => '605040' 				// mostly, emphasized text
      , '[GRAPHICSVARIANT]' => 'ff'					// 00 if bright, FF if dark
      , '[APPLYREFLECTION]' => false					// reflects navigation bar
      , '[APPLYNAVBSHADOW]' => true					// giving nav. bar a shadow

      )

  );

/*
 *
 *	default colors of Internal Frespych Chat
 *
 *	matching their equivalents from 'chatpage.html', they may be re-declared in 'template.php'
 *	to override their defaults and match some different color scheme and background image used
 *	in the chat frame
 *
 */

$ifc_server	= '546880';	// color of server messages (and of the sole manager nickname)
$ifc_admin	= '188088';	// color of administrators' nicknames (all purposes)
$ifc_mod	= '4080ff';	// color of moderators' nicknames (all purposes)
$ifc_member	= '000000';	// color of members' nicknames (all purposes)
$ifc_typo	= 'a06000';	// color of typo corrections ("someone meant: ...")
$ifc_yelling	= '0000ff';	// color of yelled phrase ("someone yells: ...")
$ifc_whispering = '8498b0';	// color of whispered phrase ("someone whispers: ...")

/*
 *
 *	array of possible colors to be used for the nicknames
 *
 *	they really stand out best on white or a very bright color, and should not be redeclared
 *	by 'template.php', as they're something concerning the member's preference; some color
 *	names were requested by long-time AnywhereBB members, feel free to change them...
 *
 *	      - the 'default of your rank' entry determines the default color of regular member
 *		names only: if the default is chosen, the manager will be given the color of
 *		messages logged by the server, admins will be given the color specified by the
 *		above $ifc_admin setting, and mods will be given the $ifc_mod setting above
 *
 */

$ntints = array

  (

    0 => array					// progressive index from 0

      (

	'LABEL' => 'Default of your rank',
	'COLOR' => $ifc_member

      )

  , 1 => array

      (

	'LABEL' => 'Black',
	'COLOR' => '000000'

      )

  , 2 => array

      (

	'LABEL' => 'Brown',
	'COLOR' => '996600'

      )

  , 3 => array

      (

	'LABEL' => 'Red',
	'COLOR' => 'e40000'

      )

  , 4 => array

      (

	'LABEL' => 'Green',
	'COLOR' => '008000'

      )

  , 5 => array

      (

	'LABEL' => 'Pink',
	'COLOR' => 'd020e0'

      )

  , 6 => array

      (

	'LABEL' => 'Light blue',
	'COLOR' => '2f2fff'

      )

  , 7 => array

      (

	'LABEL' => 'Dark blue',
	'COLOR' => '202880'

      )

  , 8 => array

      (

	'LABEL' => 'Violet',
	'COLOR' => '9212c2'

      )

  , 9 => array

      (

	'LABEL' => 'Orangetilt',		// added by Overtilt, now known as Magnulus
	'COLOR' => 'ed6a00'

      )

  , 10 => array

      (

	'LABEL' => 'Purpleclaw',		// added by Shadowclaw, now known as Xenomorph
	'COLOR' => '990099'

      )

  , 11 => array

      (

	'LABEL' => 'Whohooviolet',		// added by... Leniad, I think
	'COLOR' => '6e0472'

      )

  , 12 => array

      (

	'LABEL' => 'Lightningblue',		// added by Lightning4
	'COLOR' => '6c77e4'

      )

  );

/*
 *
 *	default frame color, and array of possible colors to be used for the message frames:
 *	you must provide a corresponding graphics set for each frame color!
 *
 *	note on "bgcol/MSIE": it may be used to replace background colors that couldn't match
 *	IE's interpretation of PNG images (IE6 and older were probably missing alpha exponent
 *	calculation when decoding PNG images) where colors may be different if PNG images are
 *	used for the edges of message frames (or 'balloons'); currently they are GIFs because
 *	no alpha blending is needed, but I preferred to hold the parts to manage "bgcol/MSIE"
 *	in the new template because they may be useful again, should templates be changed for
 *	use of frames having non-rectangular shapes
 *
 *	      - $legacy_bcol is used for rendering of messages which don't have the 'bcol' field
 *		at all in their records; under most circumstances, this applies only to messages
 *		imported from Postline installations predating some point in year 2005, when the
 *		message frame color was made selectable; in other circumstances, it would render
 *		messages that had their 'bcol' field somewhat removed (but that should't happen,
 *		unless it was done on purpose from the Database Explorer); for AnywhereBB, white
 *		is used as $legacy_bcol on the ground that it's the most readable background and
 *		despite the historical fact that the real legacy color was the actual 'Sky blue'
 *
 */

$legacy_bcol	= 1;	// applied to archived messages not having a 'bcol' entry from 2003-2005
$default_bcol	= -1;	// here -1 means "pick it randomly", but the default can be otherwise...

$bcols = array

  (

   -1 => array					// special entry (mandatory, do not remove)

      (

	'LABEL'   => 'Random',			// displayed name
	'COLOR'   => 'e4e0e8',			// background color
	'IECOLOR' => 'e4e0e8',			// background color matching MSIE 6 glitch
	'FOLDER'  => 'not/applicable'		// graphics set folder

      )

  , 0 => array					// progressive index from 0

      (

	'LABEL'   => 'Sky blue',		// displayed name
	'COLOR'   => 'ccdde8',			// background color
	'IECOLOR' => 'ccdde8',			// background color matching MSIE 6 glitch
	'FOLDER'  => 'layout/images/frames/0'	// graphics set folder

      )

  , 1 => array

      (

	'LABEL'   => 'White',
	'COLOR'   => 'ffffff',
	'IECOLOR' => 'ffffff',
	'FOLDER'  => 'layout/images/frames/7'

      )

  , 2 => array

      (

	'LABEL'   => 'Rose',
	'COLOR'   => 'ead1e0',
	'IECOLOR' => 'ead1e0',
	'FOLDER'  => 'layout/images/frames/5'

      )

  , 3 => array

      (

	'LABEL'   => 'Greenish',
	'COLOR'   => 'd2ebc8',
	'IECOLOR' => 'd2ebc8',
	'FOLDER'  => 'layout/images/frames/2'

      )

  , 4 => array

      (

	'LABEL'   => 'Post-it',
	'COLOR'   => 'ffeab5',
	'IECOLOR' => 'ffeab5',
	'FOLDER'  => 'layout/images/frames/4'

      )

  , 5 => array

      (

	'LABEL'   => 'Violet',
	'COLOR'   => 'd3d1f2',
	'IECOLOR' => 'd3d1f2',
	'FOLDER'  => 'layout/images/frames/6'

      )

  , 6 => array

      (

	'LABEL'   => 'Aqua blue',
	'COLOR'   => 'a0ccff',
	'IECOLOR' => 'a0ccff',
	'FOLDER'  => 'layout/images/frames/1'

      )

  , 7 => array

      (

	'LABEL'   => 'Heating up',
	'COLOR'   => 'ffd2b5',
	'IECOLOR' => 'ffd2b5',
	'FOLDER'  => 'layout/images/frames/3'

      )

  );

/*
 *
 *	default CSS fonts table:
 *	despite foreseeable template constraints, these might still be user-selectable
 *
 */

$default_kfont0 = '13px century gothic, sans-serif';		// title: thread titles, headings
$default_kfont1 = '13px trebuchet ms, sans-serif';		// post: paragraphs, messages...
$default_kfont2 = '13px microsoft sans serif, sans-serif';	// main: all panels and frames
$default_kfont3 = '13px georgia, serif';			// alt: alternate text in messages
$default_kfont4 = '12px courier new, monospace';		// code: code fragments (monospace)
$default_kfont5 = '12px microsoft sans serif, sans-serif';	// snip: snippet, small text parts

/*
 *
 *	settings concerning 'faq.php':
 *	numbers of certain 'key questions' tied to help links; change if you reorganize 'faq.php'
 *
 */

$chpass_faq_number	= 10;	// 'inspect.php', about changing a member's own password
$cdp_faq_number 	= 18;	// 'defense.php', Community Defense Panel help
$sidebar_faq_number	= 19;	// 'sidebar.php', sidebar construction help

/*
 *
 *	disallowed nicknames:
 *	capitalization and general letter case doesn't matter
 *
 */

$disallowednicks = array

  (

    $servername 		// might be always disallowed to prevent impersonating the server!

  , 'Admin'
  , 'Administrator'
  , 'Manager'
  , 'Mod'
  , 'Moderator'

  , 'Axle'			// my akas, to prevent impersonating me... or at least the easy way
  , 'Alex T. Great'
  , 'Alex.tg'
  , 'Fottifoh'

  );

/*
 *
 *	the following determine which ranks correspond to administration or moderation rights:
 *	they are VERY important because they are the ONLY thing that establish which powers are
 *	granted to staff members of the two kinds, and ranks here must be given in all lowercase
 *
 */

$admin_ranks = array

  (

    'admin'
  , 'administrator'
  , 'manager'		// note that the manager isn't by default an admin so it *must* be here!

  );

$mod_ranks = array

  (

    'mod'
  , 'moderator'
  , 'powercat'		// 'powercat' is specific of AnywhereBB.com, others may want to remove this

  );

/*
 *
 *	be very careful below: allowing tags such as the "table, th, tr, td" set may compromise the
 *	layout of a whole page where someone posted a message containing a table BUT didn't close
 *	one or more lines/colums/the table itself; on another completely different matter, allowing
 *	for tags like "iframe" means opening security holes: malicious use of <iframe>s can lead to
 *	your forum displaying a page containing javascript viruses, worms, annoying popups etc, the
 *	same also applies to <script> (embedded javascript, possibly malicious) and <?php> (so much
 *	worse, because would allow to perform server-side operations, it'd be pratically suicidal);
 *	and one more thing to consider: several Postline's "paragraph control tags" could conflict
 *	with common HTML tags, if the corresponding HTML tags were allowed in the following array:
 *	see help notes below the preview of a message to find out Postline's paragraph control tags
 *
 *	      - associated to each tag is a brief description, outputted in the help notes upon
 *		doing a message's preview: those can be changed freely
 *
 *	      - misuse of HTML in messages is ideally guarded by filters as of $disallowed_strings,
 *		but there's so many ways of injecting XSS that I can't absolutely guarantee it will
 *		be practically impossible to get a javascript to execute; if you want to be safer,
 *		you may disallow every tag (the very harmless ones that will be forced to have no
 *		properties, like <b> and <i>, will remain among paragraph control tags), but still,
 *		you might never rely on filters enough to feel 100% safe: even if you cut out HTML
 *		entirely, unforeseen entry points for XSS could strike in other entirely unrelated
 *		parts of user-submitted content in general, and so my suggestion is that you should
 *		never 'give up surveillance' to rely on something you presumed to be invulnerable!
 *
 */

$allowedhtmltags = array

  (

    'a' 	=> 'anchor or link, e.g. &lt;a href=&quot;http://site.com&quot;&gt;...&lt;/a&gt;'
  , 'p' 	=> 'paragraph, e.g. &lt;p align=&quot;justify&quot;&gt;...&lt;/p&gt;'
  , 'ol'	=> 'begin/end of ordered list'
  , 'ul'	=> 'begin/end of unordered list'
  , 'div'	=> 'divider, e.g. &lt;div class=&quot;pc_h&quot;&gt;...&lt;/div&gt;'
  , 'img'	=> 'embed remote image, e.g. &lt;img src=&quot;http://site.com/image.png&quot;&gt;'
  , 'sup'	=> 'you can use &lt;sup&gt;...&lt;/sup&gt; to mark exponents'
  , 'font'	=> 'set font properties *'
  , 'span'	=> 'set span properties *'
  , 'strike'	=> '&lt;strike&gt;mmm&lt;/strike&gt; produces: <strike>mmm</strike>'
  , 'marquee'	=> 'scrolling marquee, likely supported but not standard'

  );

/*
 *
 *	strings that trigger immediate fatal errors if found in anything that users may submit:
 *	this includes any text fields read through 'fget', PLUS any image or file data streams;
 *	the intended goal is to disable cookie theft via cross-site-scripting (XSS) by blocking
 *	any known ways to inject javascripts into messages, files and any other user inputs; an
 *	option to check for html
 *
 *	references:
 *
 *	      - http://www.w3.org/TR/REC-html40/interact/scripts.html
 *	      - http://devedge-temp.mozilla.org/library/manuals/2000/javascript/1.3/guide/evnt.html
 *	      - http://www.w3schools.com/vbscript/vbscript_howto.asp
 *	      - http://ha.ckers.org/xss.html (most comprehensive list of solutions I've ever seen)
 *
 *	as a personal comment, I encourage you to examine the long list below and visit the last
 *	reference webpage mentioned above, and report you the following sentence from that page:
 *
 *		"why browsers allow this, I'll never know"
 *
 *	that was particularly referring to mixing up different, even incorrect and non-standard,
 *	html entity encodings which, surprisingly, *ALL WORK* for triggering things as dangerous
 *	as javascripts; I will join the author: why? it's a very good question; the answer isn't
 *	easy to figure, and while I can understand that backward compatibility requires browsers
 *	to bear traces of formerly wide-spread event handlers, as of the following long list, it
 *	STILL SOUNDS COMPLETELY ABSURD that the string 'javascript' encoded mixing different and
 *	evidently misleading techniques might keep working in place of a protocol in hyperlinks;
 *	the moral of this story, which I wanted to point out, is that next time you hear someone
 *	screaming 'security, security, security', be aware it will likely be smoke in your eyes!
 *
 */

$disallowed_strings = array

  (

 /*
  *
  *	blocked 		filtered construct:
  *	string			for complete notes see http://ha.ckers.org/xss.html
  *
  */

    'javascript:'		// HTML tag property 'javascript: ...' (protocol alias)
  , 'vbscript:' 		// HTML tag property 'vbscript: ...' (protocol alias)
  , 'mocha:'			// Netscape HTML tag property 'mocha: ...' (obsolete)
  , 'livescript:'		// Netscape HTML tag property 'livescript: ...' (obsolete)
  , 'expression('		// MSIE CSS property, e.g. style = 'width: expression(...)'
  , 'fscommand' 		// executed from within an embedded Flash object (archives)
  , 'onabort'			// when user aborts the loading of an image
  , 'onactivate'		// when object is set as the active element
  , 'onafterprint'		// activates after user prints or previews print job
  , 'onafterupdate'		// activates on data object aft updating data in source object
  , 'onbeforeactivate'		// fires before the object is set as the active element
  , 'onbeforecopy'		// right before a selection is copied to the clipboard
  , 'onbeforecut'		// right before a selection is cut
  , 'onbeforedeactivate'	// right after activeElement is changed from current object
  , 'onbeforeeditfocus' 	// before an object contained in an editable element [...]
  , 'onbeforepaste'		// user needs to be tricked into pasting or be forced [...]
  , 'onbeforeprint'		// user needs to be tricked into printing or print() or [...]
  , 'onbeforeunload'		// user needs to be tricked into closing the browser [...]
  , 'onbegin'			// fires immediately when the element's timeline begins
  , 'onblur'			// where another popup is loaded and window looses focus
  , 'onbounce'			// when the behavior property of the marquee object is [...]
  , 'oncellchange'		// fires when data changes in the data provider
  , 'onchange'			// select, text, or TEXTAREA field loses focus and its [...]
  , 'onclick'			// someone clicks on a form
  , 'oncontextmenu'		// user would need to right click on attack area
  , 'oncontrolselect'		// when the user is about to make a control selection of [...]
  , 'oncopy'			// user needs to copy something or it can be exploited [...]
  , 'oncut'			// user needs to cut something or it can be exploited by [...]
  , 'ondataavailable'		// user needs to change data in an element, or attacker [...]
  , 'ondatasetchanged'		// when the data set exposed by a data source object changes
  , 'ondatasetcomplete' 	// fires to indicate that all data is available from the [...]
  , 'ondblclick'		// user double-clicks a form element or a link
  , 'ondeactivate'		// fires when the activeElement is changed from the [...]
  , 'ondrag'			// requires that the user drags an object
  , 'ondragend' 		// requires that the user drags an object
  , 'ondragleave'		// requires that the user drags an object off a valid location
  , 'ondragenter'		// requires that the user drags an object into valid location
  , 'ondragover'		// requires that the user drags an object into valid location
  , 'ondragdrop'		// user drops an object (e.g. file) onto the browser window
  , 'ondrop'			// user drops an object (e.g. file) onto the browser window
  , 'onend'			// the onEnd event fires when the timeline ends [...]
  , 'onerror'			// loading of a document or image causes an error
  , 'onerrorupdate'		// fires on a databound object when an error occurs [...]
  , 'onfilterchange'		// fires when a visual filter completes state change
  , 'onfinish'			// attacker can create the exploit when marquee is [...]
  , 'onfocus'			// attacker executes the attack string when window gets focus
  , 'onfocusin' 		// attacker executes the attack string when window gets focus
  , 'onfocusout'		// [...] executes attack string when window looses focus
  , 'onhelp'			// [...] executes the attack string when users hits F1 [...]
  , 'onkeydown' 		// user depresses a key
  , 'onkeypress'		// user presses or holds down a key
  , 'onkeyup'			// user releases a key
  , 'onlayoutcomplete'		// user would have to print or print preview
  , 'onload'			// attacker executes the attack string after the window loads
  , 'onlosecapture'		// can be exploited by the releaseCapture method
  , 'onmediacomplete'		// when a streaming media file is used, this event could [...]
  , 'onmediaerror'		// user opens a page in browser that contains a media [...]
  , 'onmousedown'		// attacker would need to get the user to click on an image
  , 'onmouseenter'		// cursor moves over an object or area
  , 'onmouseleave'		// the attacker would need to get the user to mouse over [...]
  , 'onmousemove'		// the attacker would need to get the user to mouse over [...]
  , 'onmouseout'		// the attacker would need to get the user to mouse over [...]
  , 'onmouseover'		// cursor moves over an object or area
  , 'onmouseup' 		// attacker would need to get the user to click on an image
  , 'onmousewheel'		// attacker would need to get the user to use the mouse wheel
  , 'onmove'			// user or attacker would move the page
  , 'onmoveend' 		// user or attacker would move the page
  , 'onmovestart'		// user or attacker would move the page
  , 'onoutofsync'		// interrupt the element's ability to play its media as [...]
  , 'onpaste'			// user would need to paste or attacker could use the [...]
  , 'onpause'			// the onpause event fires on every element that is [...]
  , 'onprogress'		// attacker would use this as a flash movie was loading
  , 'onpropertychange'		// user or attacker would need to change an element property
  , 'onreadystatechange'	// user or attacker would need to change an element property
  , 'onrepeat'			// the event fires once for each repetition of the [...]
  , 'onreset'			// user or attacker resets a form
  , 'onresize'			// user would resize the window; attacker could auto [...]
  , 'onresizeend'		// user would resize the window; attacker could auto [...]
  , 'onresizestart'		// user would resize the window; attacker could auto [...]
  , 'onresume'			// event fires on every element that becomes active when [...]
  , 'onreverse' 		// if the element has a repeatCount greater than one, [...]
  , 'onrowsenter'		// user or attacker would need to change a row in a data [...]
  , 'onrowexit' 		// user or attacker would need to change a row in a data [...]
  , 'onrowdelete'		// user or attacker would need to delete a row in a data [...]
  , 'onrowinserted'		// user or attacker would need to insert a row in a data [...]
  , 'onscroll'			// user would need to scroll, or attacker could use the [...]
  , 'onseek'			// when timeline is set to play in any direction other [...]
  , 'onselect'			// user needs to select some text - attacker could auto [...]
  , 'onselectionchange' 	// user needs to select some text - attacker could auto [...]
  , 'onselectstart'		// user needs to select some text - attacker could auto [...]
  , 'onstart'			// fires at the beginning of each marquee loop
  , 'onstop'			// user would need to press the stop button or leave the [...]
  , 'onsyncrestored'		// user interrupts the element's ability to play its [...]
  , 'onsubmit'			// requires attacker or user submits a form
  , 'ontimeerror'		// user or attacker sets a time property, such as dur, [...]
  , 'ontrackchange'		// user or attacker changes track in a playList
  , 'onunload'			// the user clicks any link or presses the back button [...]
  , 'onurlflip' 		// fires when an Advanced Streaming Format (ASF) file, [...]
  , 'seeksegmenttime'		// this is a method that locates the specified point on [...]

  );

/*
 *
 *	embedded CSS styling restrictions
 *
 *	some of the properties of an embedded CSS specification (eg. "<p syle=xxx>...") could
 *	be used to tweak the layout of the page and place pieces of text outside the message's
 *	visible bounds, possibly giving a chance to write stuff OVER others' posts; for this to
 *	be avoided, disallowing use of the "position" attribute is rather important; the flag
 *	following the array of disallowed words in HTML tags' attributes, if set to true, tells
 *	Postline that the tag holding forbidden words is to be completely disabled, and escaped
 *	with "&lt;" and "&gt;" instead or real less than/greater than signs; if it's false, the
 *	tag is allowed BUT a warning sign is displayed along with the message, so people could
 *	inspect the message to find out possible tricks like the one exactly mentioned above...
 *
 */

$disallowedprops = array ('position');
$disallow_styles = false;

/*
 *
 *	changing the intended size of the left frame will add or remove room for it,
 *	but, aside of backgrounds, there's something you might unfortunately redraw:
 *
 *		layout/images/bridge.png
 *		layout/images/bridgew.png
 *
 *	...which are the standard (black) and white versions of the separators used to
 *	indicate fields and cartridges within left frames; they are size-dependent and
 *	they might be, for best visualization, of exactly:
 *
 *		$leftframewidth - $scrollbarwidth pixels wide, 7 pixels tall
 *
 *	if they're different they'll get stretched to that size by the browser,
 *	which often aggravates the computational overhead for rendering, on the
 *	visitor's computer
 *
 */

$leftframewidth = 227;	// default width of left frame (unless collapsed, of course)
$scrollbarwidth = 27;	// room to leave for a scrollbar in left frames (pixels)

/*
 *
 *	by default of template changes, chat lines are 16 pixels tall each,
 *	so if you change this, you'll have to change the height of the chat
 *	frame in 'layout/html/ctc.html', using the following formula:
 *
 *		new frame height = $trimlinesafter * 16
 *
 *	the exact line to change in 'layout/html/ctc.html' is actually line #98, which reads:
 *
 *		r = '47,96,23,15,*,48';
 *			^
 *	where the value to replace would be the second field of the frameset 'rows' property,
 *	which is actually 6 * 16 = 96 (pixels); the second row is in fact the one of the chat
 *	frame, right below the 47-pixel navigation bar dedicated to featured content
 *
 */

$trimlinesafter = 12;	// max lines in chat frame, before they're sent to the logs

/*
 *
 *	anti-flood limits (given in seconds)
 *
 *	used to reduce possible spamming by users that may be filling the board with pointless
 *	phrases, posts, threads and files for the only purpose of taking up a lot of space and
 *	disturbing the others - although such behaviors might get moderated, these limits will
 *	help reduce such 'damage' while all moderators may eventually be distracted; specially
 *	the first entry, $reg_interval, if it's set high enough will help keep the system less
 *	vulnerable to spambot attacks (it has a simple captcha, but no captcha is unbreakable)
 *
 */

$antiuserflood = 900;	// wait 900 seconds between each registration of new members
$antichatflood = 1;	// wait 1 second before accepting phrases from the same user
$antithrdflood = 60;	// wait 1 minute before accepting threads from the same user
$antipostflood = 30;	// wait 30 seconds before accepting posts from the same user
$antifileflood = 30;	// wait 30 seconds before accepting files from the same user

/*
 *
 *	session behavior and timers:
 *	all timers are in seconds
 *
 */

$track_guests		= true; // if true, tracks guest sessions: false may release some load
$sessionupdate		= 600;	// seconds before session start time is updated on action
$sessionexpiry		= 900;	// seconds before session expires in case nothing is done
$expirycheck		= 930;	// seconds between checks for expired sessions removal
$g_sessionexpiry	= 300;	// seconds before guest session expires in case guest goes away
$g_expirycheck		= 330;	// seconds between checks for expired guest sessions removal

/*
 *
 *	miscellaneous timers:
 *	all timers are in seconds
 *
 */

$pruningcheck	= 300;		// seconds between account pruning checks (0 = no pruning)
$pruneolderthan = 2592000;	// seconds since last login for pruning accounts
$maxkickdelay	= 86400;	// max seconds (in /kick command) before kicked people can re-login
$ipkickdelay	= 86400;	// seconds before corresponding kicked ip addresses can re-register
$majoragedays	= 100;		// required days of permanence before member becomes "major"
$refreshtime	= 5;		// seconds between each page refresh in chat frame
$dlrefreshtime	= 60;		// seconds between each refresh of the status line (downline)
$posteditdelay	= 0;		// time for regular members to delete/edit posts (0 = infinite)
$mercifuledit	= 900;		// time for editing without triggering a "last edit" notice
$avg_load_reset = 300;		// seconds between every reset of the average load ticker

/*
 *
 *	miscellaneous quantities
 *
 *	note: the $membersperpage is somewhat of a legacy setting; it is still possible to display
 *	more than one entry per page, but the 'members.php' script is since Postline 2009 designed
 *	to work as an 'introduction page' presenting members individually and as such, it's rather
 *	cumbersome, and confusing, to have more than one profile displayed per page...
 *
 */

$maxkickedips		= 10;	// max IPs to be blocked from registering because recently kicked
$majorityban		= 10;	// major members required to ignore given member to trigger ban
$majorageposts		= 100;	// required posts to submit before member becomes "major" member
$threadsperpage 	= 25;	// how many threads are listed per page in a forum's index
$postsperpage		= 15;	// how many posts are listed per page in a thread's index
$recdiscrecords 	= 300;	// how many records to preserve in "recent discussions" archive
$membersperpage 	= 1;	// how many members per page in members' list
$recdiscsperpage	= 4;	// how many records per page of the "recent discussions" archive
$recdiscsonlarge	= 7;	// same as above, but in case the "large screen" option is active
$maxpostsinchild	= 100;	// max replies that may follow a post to allow splicing its thread
$userslistsize		= 25;	// list upto this many members in forums/threads' tracking lists
$maxdlnames		= 10;	// maximum number of nicknames to be shown in the status line
$errorspercode		= 100;	// maximum number of requests tracked for each HTTP error code

/*
 *
 *	the following affects working installations, and if changed, the manager should run the
 *	maintenance script labeled 'recent discussions collector' in order to build an updated
 *	list holding the newly given number of entries; since it's based on $recdiscrecords, if
 *	a change to the above $recdiscrecords occurs, that would ALSO require maintenance to be
 *	performed on that list in the same way
 *
 */

$vrdtablerecords =
  $recdiscrecords + 100;	// VRD table records (= $recdiscrecords + possible deletions)

/*
 *
 *	miscellaneous string length limits:
 *	note 2E4 means 20000, written that way to better format the code
 *
 */

$maxcharsperpost	= 300;	// max characters per single CLI/chat message
$maxcharsperfrag	= 74;	// unspaced CLI/chat fragments' maximum length (0 = no limit)
$hintlength		= 32;	// length of hints for message's extracts in recdisc/searchs
$maxtitlelen		= 60;	// max characters in a thread's or a message's title
$maxmessagelen		= 2E4;	// max characters in a message's body (the effective text)
$maxpolllen		= 2E4;	// max characters in a poll's options list (incl. semicolons)
$maxkeywordlen		= 20;	// how long a smiley keyword could be (characters)
$p_auth_size		= 20;	// size of profile's "rank" field
$p_species_size 	= 20;	// size of profile's "species" field
$p_gender_size		= 20;	// size of profile's "gender" field
$p_aka_size		= 80;	// size of profile's "a.k.a" field
$p_born_size		= 80;	// size of profile's "born" field
$p_habitat_size 	= 80;	// size of profile's "habitat" field
$p_email_size		= 80;	// size of profile's "email" field for staff members (admins, mods)
$p_about_size		= 5000; // size of profile's "bit about" field
$p_title_size		= 32;	// size of user titles
$p_title_split		= 16;	// where to split user titles that may be too wide to fit one line

/*
 *
 *	avatar display settings
 *
 */

$freetitles		= false;	// if true, avatar user titles may be chosen by all members
$avatarpixelsize	= 100;		// how big avatars could be (a square picture of that size)
$avatarbytesize 	= 32768;	// how big avatar image files could be (in bytes)

/*
 *
 *	photograph display settings
 *
 *	note that if you change the maximum dimensions of the photograph, you might as well
 *	take care of drawing a different holder, as the background upon which photographs are
 *	kept in user presentation pages; the holder is 'images/photos/ancillary/holder.png',
 *	and must be sized to:
 *
 *		$maxphotowidth + 20
 *		$maxphotoheight + 20
 *
 *	because there is a fixed 10-pixel margin left on each side of the photograph; also
 *	note that, unless you also want to have to re-design parts of the template and the
 *	various signals that get superimposed to photographs, there are minimum dimensions to
 *	consider as a limit for the following settings: I don't recommend reducing the default
 *
 */

$maxphotowidth	= 180;		// how wide photos could be (in pixels)
$maxphotoheight = 240;		// how tall photos could be (in pixels)
$photobytesize	= 102400;	// how big photograph image files could be (in bytes)

/*
 *
 *	smiley limits
 *
 */

$allowedsmileys 	= 120;		// how many smileys could be registered complessively
$smileypixelsize	= 32;		// how big smileys could be (a square picture of that size)
$smileybytesize 	= 20480;	// how big smileys image files could be (in bytes)

/*
 *
 *	'tellme.php' user feedback settings:
 *	$clear_fb_iplist determines that only one message may be sent per day by a same IP address
 *
 */

$feedback_space 	= 10485760;	// max. disk space (bytes) kept for users feedback folder
$feedbackmaxmsg 	= 32768;	// max. size of a feedback message
$feedbackmaxfile	= 2097152;	// max. size of files to be attached to a feedback message
$clear_fb_iplist	= 86400;	// how often the IP list for anti-spam checks is cleared

/*
 *
 *	community disk settings
 *
 *	the cluster size of the community disk means the size to which a file's size is rounded
 *	while computing the total used space; several small files don't normally mean, to the
 *	server's filesystem, that the used quota is the sum of all the files' sizes, because in
 *	effects you have to consider that server's hard drives are normally divided in clusters,
 *	also known as "allocation units"; the default cluster size, 32KiB, is quite "generous",
 *	anyway to avoid those problems I recommend to avoid setting that value below that, even
 *	because this somewhat limits the number of files that could be stored inside the c-disk:
 *	the max no. of files with a setting of, for example, 50MiB made of 32KiB clusters applies
 *	a limit of 1600 files complessively; WARNING: the cluster size of the c-disk shouldn't be
 *	changed while the c-disk holds files, otherwise its counters no longer say the truth and
 *	maintenance script 'C-disk folders builder' would need to be called after having deleted
 *	all the 'dir_*' files from the 'cd' folder through the Database Explorer, such that the
 *	'balance' file would be rebuilt and reflect the new cluster size; while the said script
 *	makes changing cluster size possible, I wouldn't say it's an easy thing to do...
 *
 *	      - careful with allowed filetypes, for each of which a folder will also have to be
 *		built: including extensions like ".bin", ".exe", ".php", any other executables,
 *		might be SUICIDAL because files would be possibly executable on the server!
 *
 */

$cd_maxfilesize 	= 2097152;	// max size of a file in community disk, in bytes
$cd_disksize		= 81920;	// size of community disk (IN CLUSTERS)
$cd_clustersize 	= 32768;	// size of a cluster of the community disk (default=32Kb)
$charsperfilename	= 40;		// how many characters, as a maximum, in c-disk file names
$charspernameline	= 12;		// how many characters per line below c-disk file icons

$cd_filetypes = array	// allowed file types (each extension must be given a folder)

  (

    'image/gif'
  , 'image/jpg'
  , 'image/png'
  , 'file/zip'
  , 'file/gz'
  , 'file/rar'
  , 'file/7z'

  );

$cd_mimetypes = array	// MIME types of allowed file types (affect download requests)

  (

    'gif' => 'image/gif'
  , 'jpg' => 'image/jpeg'
  , 'png' => 'image/png'
  , 'zip' => 'application/x-zip-compressed'
  , 'gz'  => 'application/x-gz-compressed'
  , 'rar' => 'application/x-rar-compressed'
  , '7z'  => 'application/octet-stream'

  );

/*
 *
 *	search indexing 'on-the-fly' settings:
 *	these can be changed in a working installation
 *
 */

$search_disable 	= false;	// if true, disables word indexing and search completely
$maxsearchlen		= 80;		// max characters in a search query
$resultsperpage 	= 8;		// how many records per page in search results
$resultsonlarge 	= 12;		// same as above but if the "large screen" option is active
$search_lifo_jpp	= 100;		// jobs per request (search indexing background processing)

/*
 *
 *	search indexing 'bulk' parameters:
 *	these CANNOT be changed at any time without possibly needing complete re-indexing
 *
 *	      - if you changed something below you'd need to re-index the wordsearch dictionary,
 *		but I'm currently not yet providing any maintenance scripts to do that, so you'd
 *		just better not change these parameters after having installed Postline
 *
 *	      - $maxresults determines the length of a word record, holding all matches for that
 *		word: therefore, it directly determines the filesize of dictionary and index parts,
 *		and it couldn't be an aritrarily large value or you'd end up wasting lots of server
 *		space, and CPU time, to hold the search database alone: after all, in a nutshell,
 *		you're not supposed to imitate Google here; $maxresults can be only raised without
 *		reindexing: if so, index files will begin to accumulate more occurences as they get
 *		posted; if it's diminished, it will have no effect until all indexed records are
 *		touched, and so, diminishing this setting will not release disk space on the server
 *
 *	      - $bs_search is how many files will form the dictionary and the index: words will be
 *		randomly (and hopefully as uniformly as possible) spread between the said number of
 *		parts, but although raising that value would shorten single parts and make indexing
 *		jobs a bit faster, you can't go too high, because background processing operates on
 *		one file at a time, so the more files you choose to split the search database, the
 *		more page requests will be needed to fully index (or unindex, or update) a single
 *		message; with the default setting of 10 pages I'm choosing there should be, for
 *		every operation such as posting/editing/deleting a message, at least another 9 page
 *		requests on average, in which people may not post; ah, and it's 9 (10 - 1) because
 *		the action of posting involves a page request, so one cluster of words gets always
 *		processed on the fly; if the setting turned out wrong, and the value of $bs_search
 *		was too high, the ugly consequence would be that the LIFO list may grow bigger and
 *		bigger, because there'd be too much input (too many words to index) and too little
 *		output (page requests)
 *
 *	      - the array of common words that are not supposed to get indexed might not really
 *		contain all of the possible common words based on the board's spoken language:
 *		to compile that array, consider that words shorter than 3 characters or longer than
 *		14 characters are already filtered out, that only decimal numbers between 1-31 and
 *		1970-2070 (inclusive) aren't filtered (mostly for the sake of allowing date-time
 *		searchs), and that a 'word' is defined as a strict sequence of lowercase alphabetic
 *		characters, not interrupted by any signs: because signs include the single quote,
 *		you'll see things like 'isn' in the array of common words to account for the split
 *		of - for this instance - "isn't"; as a final suggestion, try not to be excessively
 *		picky compiling the array: the longer it is, the longer it will take for the
 *		'wordlist_of' function of 'suitcase.php' to make out the wordlist of a message,
 *		because of all the common words it would have to filter; and also, consider that
 *		words can be combined in searchs: 'of course' is common, but if you included the
 *		word 'course', it wouldn't match 'crash course' anymore, for example; I suppose
 *		you might mostly concentrate on words used to link sentences together, but which,
 *		on themselves, add little or no information to what's being said
 *
 */

$maxresults		= 50;	// max unique messages outputted by search queries
$bs_search_posts	= 10;	// forum messages' search index: split index in 10 parts
$bs_search_logs 	= 6;	// as above, for when you're indexing chat logs 'on the fly'

$common_words = array

  (

    // conjunctions and similars

    'the', 'and', 'not', 'but', 'for', 'with', 'also',

    // pronouns and possessives

    'you', 'your', 'she', 'him', 'her', 'his', 'its', 'they', 'them', 'their', 'our', 'own',

    // common verbs and related accessories

    'are', 'has', 'have', 'had', 'was', 'went', 'were', 'will', 'must',
    'can', 'going', 'let', 'get', 'got', 'did', 'does', 'make', 'made',

    // qualifiers

    'what', 'how', 'why', 'who', 'when', 'where', 'which',

    // quantifiers and comparatives

    'than', 'few', 'some', 'same', 'just', 'only', 'once',
    'like', 'more', 'much', 'many', 'one', 'all', 'too',

    // determiners

    'this', 'that', 'these', 'those',

    // conditionals

    'could', 'should', 'would', 'may', 'might', 'ought',

    // concerning time

    'while', 'still', 'such', 'yet', 'now', 'then', 'ago',

    // miscellaneous

    'well', 'yes', 'yeah', 'out', 'right',

    // split fragments

    'isn',	// isn't
    'aren',	// aren't
    'hasn',	// hasn't
    'haven',	// haven't
    'hadn',	// hadn't
    'wasn',	// wasn't
    'weren',	// weren't
    'won',	// won't
    'don',	// don't
    'didn',	// didn't
    'doesn',	// doesn't
    'couldn',	// couldn't
    'shouldn',	// shouldn't
    'wouldn',	// wouldn't

    // html entities that could often be found in messages (but useless to index)

    'apos',	// apostrophe, single quote (&apos;)
    'quot'	// doublequotes (&quot;)

  );

/*
 *
 *	characters that may terminate an HTML link: however, the two cases ? and & will be checked
 *	for eventual arguments passed through the URL (in "GET" method): they will only terminate
 *	the link if no arguments are given, e.g. "Did you ever visit http://www.anywherebb.com?"
 *
 */

$link_terminators = array

  (

    chr (32), ',', ')', '!', '?', '&', '<', '>', '"'

  );

/*
 *
 *	if a block of arguments is effectively included, it may be terminated only by one of the
 *	following characters; well, of course most of these are legal within a GET URL, but it's
 *	unlikely that such an URL would contain them: much more probably they're punctuation
 *
 */

$parblock_terminators = array

  (

    chr (32), '<', '>', '"'

  );

/*
 *
 *	characters that may terminate an email address:
 *	the @ will of course count only if it's a secondary @ sign
 *
 */

$email_terminators = array

  (

    chr (32), '@', '!', '?', ',', ';', ':', '(', ')', '[',
    ']', '{', '}', '&', '=', '*', '"', "'", '|', '<', '>'

  );

/*************************************************************************************************

	SIZE OF DATABASE INTERVALS:
	DO NOT TOUCH THE VALUES BELOW UNLESS YOU REALLY KNOW WHAT YOU'RE DOING!

	These are absolutely not intended to change while the database is being built, that is,
	while day by day, members sign up and post threads and messages in the forums. They may
	be changed *only* before the board is installed and made public: what they mean is that
	the database will use a single file to hold the given number of records whereas records
	are for members' profiles ($bs_members), for threads' directories ($bs_threads) and for
	the database holding the messages ($bs_posts).

	Changing these values WHILE the forums are being used may result in a complete crash of
	the database, so you have to choose which values to set these parameters before putting
	your community at work, you cannot get back easily from these choices! Considering real
	life medium-large communities, using the default values will realistically result in:

	      - 2000 members at 50 members per block give 40 files,
		at an average of about 2 kBytes per member record, each file will be ~100 kB;

	      - 120000 threads at 300 threads per block give 400 files,
		at an average of about 350 bytes per thread record, each file will be ~105 kB;

	      - 1000000 posts at 150 posts per block give 6667 files,
		at an average of about 1.5 kBytes per post record, each file will be ~225 kB.

	The way to choose these values might be targeted to the creation of files around 100 kB
	in size, eventually up to a very few hundred kBs to avoid overloading the server and/or
	slowing down the engine, but another factor to consider may be overall SQL performance:
	it could sensibly decrease when confronting tables holding several thousand files while
	reads from the posts' database get cached (by the caching mechanism in 'suitcase.php').
	This is why the default settings, as an exception, determine the creation of files with
	sizes of over 200kB to archive posts: they're often contiguous in a file and are helped
	significantly by the caching mechanism, and by allowing them to double the average file
	size they shouldn't get to be too many.

*************************************************************************************************/

$bs_members	= 50;	// 'members': allow 50 members per file
$bs_threads	= 300;	// 'forums': allow 300 threads per file
$bs_posts	= 150;	// 'posts': allow 150 posts per file
$bs_pms 	= 150;	// 'pms' : allow 150 private messages per file

/*
 *
 *	the following are rarely intended to be changed anyway, but $cache_ix could be increased
 *	upon, eventually, noticing that pages often require a number of queries greater than the
 *	value of $cache_ix (an exact number of queries is always shown at the bottom of pages in
 *	the left and right central frames): in such cases raising $cache_ix could slightly boost
 *	the board's overall performance; in other cases, raising it would be useless and if it's
 *	way too much, it could even SLOW DOWN the boards, due to larger arrays that'd have to be
 *	managed when the caching mechanism reads or writes files, up to rendering the boards non
 *	functional in consequence of colliding with memory usage limits per PHP script
 *
 */

$epc = time (); // used throughout all dependent scripts as the general epoch of a page request
$cache_ix = 50; // size of file R-W cache circular buffer (number of file slots held in memory)

/*
 *
 *	declaring defaults for session parameters
 *
 *	these are not settings, they're just common initial values to ensure they're defined,
 *	when any scripts will refer to them, even if their initial values reflect the status
 *	of a guest session... oh, well, nothing should be changed among the following values,
 *	especially beware NOT to set $is_admin or $is_mod to true, or any members and guests
 *	would be given the relative privileges; generally, keep these values as they are and
 *	be careful NEVER DELETING any of the statements below (in general it's always better
 *	to declare global variables, and set them to an intended value before referring them;
 *	otherwise, server-side PHP configurations may allow maliciously rewriting them)
 *
 */

/*
 *
 *	session booleans (flags)
 *
 */

$login		= false;	// login flag: true if visitor is logged in as a member
$is_major	= false;	// major member status flag: true or false
$is_admin	= false;	// administration access rights: true or false
$is_mod 	= false;	// moderation access rights: true or false
$is_muted	= false;	// if true, member is forbidden to chat
$all_off	= false;	// maintenance lock (true while manager's accessing a locked board)
$manager	= false;	// management access rights: also, overrides the effect of $all_off

/*
 *
 *	session integers
 *
 */

$id		= 0;		// logged member's ID (zero - and invalid - if not logged in)
$ntint		= 0;		// nickname color (index in colors array of 'template.php')
$bcol		= 0;		// speech bubble color (index in colors array of 'template.php')
$jtime		= 2147483647;	// logged member's join time (defaults to highest Unix epoch)
$intercom	= 0;		// intercom call message ID (moderators and administrators only)

/*
 *
 *	session strings
 *
 */

$nick		= '';		// logged member's nickname
$auth		= '';		// logged member's rank (authorization level)
$newpm		= '';		// new private message signal: void string, or "yes"
$ip		= 'ffffffff';	// dummy IP address
$cflag		= 'off';	// chat frame state ("on" = in show, "off" = hidden)

/*
 *
 *	session arrays
 *
 */

$ilist = array ();		// ignorelist: a void array, or an array of ignored nicknames

/*
 *
 *	template variables
 *
 */

$frame_x	= '';	// X coordinate of given page's frame, for backgrounds' positioning
$frame_y	= '';	// Y coordinate of given page's frame, for backgrounds' positioning
$f_extra	= '';	// page frame's extra snippets to be added to the 'body' tag
$login_check	= '';	// eventually contains a javascript giving a warning ($in_page)
$security_code	= '';	// added to all scripts operating on behalf of a logged-in member
$form		= '';	// often the HTML of a form to be submitted, referred in templates
$modcp		= '';	// moderators-only control panel, referred by templates using it
$list		= '';	// used for holding most of the page's contents in many templates
$execution_time = '';	// the line holding number of database queries and execution time

unset ($permalink);	// requested page's permalink (set by specific code, else left "unset")

/*
 *
 *	variables concerning preferences:
 *	in lack of processing by 'sessions.php', they reflect defaults
 *
 */

$kflags 		= 'nnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnn';
$style			= $default_style;
$backgr 		= '';
$kfonts 		= '';
$kfont0 		= $default_kfont0;
$kfont1 		= $default_kfont1;
$kfont2 		= $default_kfont2;
$kfont3 		= $default_kfont3;
$kfont4 		= $default_kfont4;
$kfont5 		= $default_kfont5;
$mlfr			= '';
$aclf			= '';
$ofie			= '';
$dusc			= '';
$bigs			= '';
$dfwl			= '';
$hcb			= '';
$hii			= '';
$csn			= '';
$cnb			= '';
$cdl			= '';
$sb1			= '';
$sb2			= '';
$sbi			= '';
$lffn			= '';
$hhip			= '';
$nspy			= '';
$smmn			= '';
$true_background	= '';
$body_background	= $styles[$default_style]['[BODY-BACKGROUND]'];
$most_text_color	= $styles[$default_style]['[MOST-TEXT-COLOR]'];
$most_text_bkgnd	= $styles[$default_style]['[MOST-TEXT-BKGND]'];
$nrm_links_color	= $styles[$default_style]['[NRM-LINKS-COLOR]'];
$hov_links_color	= $styles[$default_style]['[HOV-LINKS-COLOR]'];
$serverloadcolor	= $styles[$default_style]['[SERVERLOADCOLOR]'];
$frameboundcolor	= $styles[$default_style]['[FRAMEBOUNDCOLOR]'];
$innerframecolor	= $styles[$default_style]['[INNERFRAMECOLOR]'];
$graphicsvariant	= $styles[$default_style]['[GRAPHICSVARIANT]'];
$v_high_contrast	= $graphicsvariant . $graphicsvariant . $graphicsvariant;
$font_variant		= '';
$ph_top_padding 	= '2';

/*
 *
 *	preference variables needing immediate processing to be set to the intended default value,
 *	and also leaving these flags for the template script
 *
 */

$on_ms_ie = (strstr ($_SERVER['HTTP_USER_AGENT'], 'MSIE') === false) ? false : true;
$on_opera = (strstr ($_SERVER['HTTP_USER_AGENT'], 'Opera') === false) ? false : true;
$on_frfox = (strstr ($_SERVER['HTTP_USER_AGENT'], 'Firefox') === false) ? false : true;

$kflags[3] = ($on_ms_ie) ? 'y' : 'n';			// on IE, activate optimizations for MSIE
$kflags[16] = (($on_ms_ie) || ($on_opera)) ? 'n' : 'y'; // no color scrollers where not supported

/*
 *
 *	variables concerning 'session.php' processing
 *
 */

$login_cookie_given	= false;	// login cookie was found and possibly valid
$fields_given		= false;	// login cookie fields were given and possibly valid
$c_id			= 0;		// login cookie's claimed (but not verified) account's ID
$c_pass 		= '';		// login cookie's claimed (not verified) account password
$db			= '';		// database file holding member record according to cookie
$mr			= '';		// member record according to indications of login cookie
$ok			= false;	// member record presence flag (does not indicate validity)
$c_auth 		= '';		// rank field as read from member record (not convalidated)
$c_is_mod		= false;	// moderator status as reflected by $c_auth (not confirmed)
$c_is_admin		= false;	// administrator status, implied by $c_auth (not confirmed)
$allow_login		= false;	// login allowed (no bans or kicks) but yet to be confirmed
$c_nick 		= '';		// lowercase version of claimed nickname (as of $mr)
$banlist		= array ();	// banned nicknames array (will be read in 'session.php')
$t_lkick		= 0;		// time of last kick (then extracted from $mr)
$t_delay		= 0;		// delay of last kick (then extracted from $mr)
$man_pass		= false;	// login was authenticated via management password
$md5_pass		= false;	// login was authenticated via regular hash of password
$t_passwd		= '';		// temporary password (then extracted from $mr)
$t_expire		= 0;		// temporary password expiration epoch (extracted from $mr)
$tmp_pass		= false;	// login was authenticated via temporary password
$prefs_token_given	= false;	// preferences cookie held correct version letter

/*
 *
 *	defining cookies' layout and retrieving cookies:
 *
 *	these will be mostly processed by 'session.php', overriding the above default values;
 *	this also concludes definition of all variables used or re-declared in 'session.php',
 *	allowing other scripts to refer to them even though they don't include 'session.php';
 *	also note that some cookies are directly processed by javascripts in 'frameset.html'!
 *
 */

$login_cookie_name = $sitename . '_postline_login';
$prefs_cookie_name = $sitename . '_postline_prefs';
$cflag_cookie_name = $sitename . '_postline_cflag';
$frame_cookie_name = $sitename . '_postline_frame';
$login_cookie_text = "created_by_{$sitename}_to_authenticate_your_access_to_your_account____";
$prefs_cookie_text = "created_by_{$sitename}_to_remember_layout_and_behavior_preferences____";
$cflag_cookie_text = "created_by_{$sitename}_to_take_note_of_your_chat_frame_preferences____";
$frame_cookie_text = "created_by_{$sitename}_to_provide_you_with_your_last_visited_pages____";
$login_cookie_hold = isset ($_COOKIE[$login_cookie_name]) ? $_COOKIE[$login_cookie_name] : '';
$prefs_cookie_hold = isset ($_COOKIE[$prefs_cookie_name]) ? $_COOKIE[$prefs_cookie_name] : '';
$cflag_cookie_hold = isset ($_COOKIE[$cflag_cookie_name]) ? $_COOKIE[$cflag_cookie_name] : '';
$frame_cookie_hold = isset ($_COOKIE[$frame_cookie_name]) ? $_COOKIE[$frame_cookie_name] : '';
$login_cookie_hold = substr ($login_cookie_hold, strlen ($login_cookie_text));
$prefs_cookie_hold = substr ($prefs_cookie_hold, strlen ($prefs_cookie_text));
$cflag_cookie_hold = substr ($cflag_cookie_hold, strlen ($cflag_cookie_text));
$frame_cookie_hold = substr ($frame_cookie_hold, strlen ($frame_cookie_text));

define ('postline/settings.php', true); } ?>
