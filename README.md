# «Postline»
### BB and CMS of anynowhere.com

What follows is the original `installation.txt` from the author:

```




	um... installation, well...




	I'm not providing any facilities, in part because there might not be
	much interest in installing Postline somewhere else (its layout is
	so out of standard and somewhat messy that you might prefere something
	else), in part because I'd prefere to spend time doing something else.

	But in short, you may customize all scripts the way you like as long
	as you can follow what you're doing, and given the way HTML and CSS
	are integrated and randomly spread through them, you'll likely find
	it a bit difficult. The key script that enables access to a MySQL
	database is:

		bb/widgets/dbengine.php

	Other scripts you might really want to change to avoid simply
	duplicating anynowhere's logo, faq and other specific things, are:

		bb/settings.php
		bb/faq.php
		bb/intro.php

	The logo (the cat) might be somewhere in:

		bb/template.php

	The .htaccess file should be changed to point permalinks at your domain,
	otherwise they default to 'localhost'. Of course you should keep the
	.htaccess file in the folder which is the parent of the 'bb' folder,
	or however you rename the 'bb' folder that hosts Postline. You can then
	reflect the new path in bb/settings.php and in redirects of .htaccess,
	which, in turn, refer to "doors" in the bb/layout/html folder, where you
	might want to remove redirects for my links, to then alter the navigation
	bar on top to list your links, etc...

	Have fun...



	ps. also remember to distribute postline.zip in the "cd" folder, where
	the intro page expects it, otherwise you'll have a dead link on your
	intro page and disobey the GPL license cos you won't be providing the
	source code...



	new in version 10.11.28:

	- added a visual style ('44 Gj'), which means,

		- added its entry to 'bb/settings.php',
		- added its background to 'bb/layout/backgrounds',
		- shifted default style index (for 'optical') from 7 to 8;

	- added three maintenance scripts in the 'rollback synchronization' section,

		- 0_sync_forum_indices.php,
		- 0_sync_thread_counts.php,
		- 0_sync_threads.
    
    
```
Copyright ©2001-2010 by Alessandro Ghignola
