=== PhotoSmash Galleries ===
Contributors: bennebw
Donate link: http://www.whypad.com/posts/photosmash-galleries-wordpress-plugin-released/507/#donate
Tags: images, photos, picture, gallery, social, community, posts, admin, pictures, media, galleries
Requires at least: 2.6
Tested up to: 2.8.4
Stable tag: 0.4.01
 
PhotoSmash - user contributable photo galleries for WordPress pages and posts with options.  Auto-add galleries or specify.

== Description ==

PhotoSmash Galleries makes it easy to create photo galleries in posts or pages that your users can upload images to.  Following are the features:

For support and more documentation, visit the plugin's new homepage: [PhotoSmash](http://smashly.net/photosmash-galleries/ "PhotoSmash Galleries on Smashly.net")

*   User contributable photo galleries
*	Add images to the WordPress Media Library so you can use them in blog posts, etc
*	Multiple simultaneous image uploads in Admin, using the WordPress Media Library, then import images to PhotoSmash!
*   AJAX photo uploads from within Posts and Pages
*	Star Ratings for images
*   Control who can upload images: admin only, authors & contributors (and higher), or registered users and higher
*   Moderate images uploaded by registered users (Admins and authors are automatically approved)
*   Receive email alerts for new images that need to be moderated
*   Options page for setting general defaults or specific gallery settings
*   Auto-adding of photo galleries
*   Multiple galleries per post, added using a simple tag system
*   Integrates with popular image viewing systems like Lightbox and Shadowbox
*   Tweak appearance through the included css file
*	Add Custom Fields to the upload form
*	Create Custom Upload forms using simple tags and HTML
*	Create Custom Layouts using simple tags and HTML

== Installation ==

1. Upload the plugin folder, bwb-photosmash, to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. To automatically add a new gallery to a post, put this shortcode in the body of the post where you want it to show up: [photosmash]
1. To add a specific existing gallery to a post, use this shortcode (replacing ## with the gallery's id): [photosmash id=##] 
1. Alternatively, you can set PhotoSmash to "auto-add" galleries to all post by updating the settings in the PhotoSmash options page in the Settings Admin menu.

== Frequently Asked Questions ==

= Is PhotoSmash free? =

Yes...it's licensed under the GPL.

= How many galleries can a Post have? =

Unlimited.  Just add as many [photosmash=%id%] tags as you like...But performance gets to be an issue at some point.

= Who can upload images to a gallery? =

You control this on a gallery-by-gallery basis (you can also set a default for PhotoSmash that all new galleries will inherit).  Your options are Admins only; Admins, Authors, and Contributors (anyone with level_1 or higher roles); or Register Users (level_0 roles).

= Can unregistered users upload images? =

No...not at this time.

= How can I upload images to a gallery before adding to a Post? =

You can't.

= How do I change the appearance of my galleries? =

In the bwb-photosmash plugin folder, there is a css file:  bwbps.css
It should be relatively straight forward to change the look and feel through this file.

== Screenshots ==

1.  Sample PhotoSmash Gallery with default css
2.  Top portion of the PhotoSmash options page
3.  Sample of the upload page

== Changelog ==

Visit the [Changelog on Smashly.net](http://smashly.net/photosmash-galleries/ "Changelog on Smashly.net") to see what is currently in development.
= 0.4.01 – 09/04/2009 =

    * Added ability to Import images to PhotoSmash galleries from the WordPress Media Library.  This lets you use the WP Media uploader (multiple simultaneous uploads) in Admin, then import them into galleries.
    * Changed the default delete from deleting the Media Library images to be on-demand in Photo Manager.  Deleting a gallery does not delete Media Library images now.  Too much risk.
    * Fixed a javascript bug - when uploading images with the new (0.4.00) WP upload functionality, the link to the image was broken until you reloaded the page.

= 0.4.00 – 09/01/2009 =

    * This gets a version bump! Added option [is default for new installs] to use WordPress upload functionality. Can optionally add uploaded images to the WP Media Library. Set these options in PhotoSmash Settings…top of the Uploading tab. This is in preparation for the upcoming new WordPress 2.9 media features. By adding these images to the Media Library, you should be able to utilize new features that WordPress builds in. The new WP 2.9 feature set hasn’t been officially announced yet, but stay tuned!!! This should also solve upload issues where people have trouble with folder permissions. I could be wrong, but I think this is pretty big :P
    * Fixed a couple of annoying ThickBox images that weren’t loading. You have to set the variables in the page footer…FYI.


= 0.3.07 - 08/27/2009 =

    * Fixed database update message - was displaying in error for MySQL 4 users. MySQL 4 doesn't allow WHERE in the SHOW COLUMNS statement-have to use LIKE. MySQL 5 users were not affected by this.

= 0.3.06 - 08/27/2009 =

    * Changed pagination to show only 5 pages at a time. Added First, Last, and ellipses.
    * Fixed the situation when showing Ratings beneath the Caption-rating wasn't showing when there was no caption
    * Fixed the code that verifies if the database tables are up to date. Now using SHOW COLUMNS sql. Wasn't getting anything when table was empty.
    * Changed moderation rules so that users with the Contributor role now receive moderation when moderation is turned on. Notes:
          o Useful for setups where users create a new WordPress post by uploading an image through PhotoSmash (this functionality is coming to the PhotoSmash Extend plugin)
          o Roles that get moderated when moderation is turned on in a gallery: Anybody (not logged in), Subscriber, Contributor
          o Roes that don't get moderated even when moderation is on: Authors and Admins
    * Vote Up/Vote Down - will work similarly to Star Ratings, except-it's voting up or down
    * Added code to bwb-photosmash.php to give a way to collect and insert Javascript code into the footer. This will save a lot of script tags and jQuery(document).ready() functions, and will collect JS nice and neatly in the footer. If you do a global on the $bwbPS object in PHP, you can easily add javascript to the footer using these 2 functions: $bwbPS->addFooterJS($js); or $bwbPS->addFooterReady($js); PhotoSmash takes care of putting in new lines to separate multiple JS calls, as well as takes care of the Script tags and the document.ready function-it's easy ;-)


= 0.3.05 - 08/19/2009 =

    * Removed code in Database Update that was removing duplicate indices - this was causing users with certain SQL Mode settings to experience errors.
    * Note - there may be a problem with star ratings with IE 6.  Further testing will ensue.  If you experience problems with Star Ratings, please report them.  Thanks!


= 0.3.04 - 08/19/2009 =

    * Fixed a conflict with Contact Form 7 where duplicate creation of esc_attr functions was occuring 
    * Added template tags:  
          o show_photosmash_gallery(optional $attr);  - echoes a gallery - the $attr param can be a gallery ID or an array of parameters that you can also use in shortcodes. 
          o get_photosmash_gallery(optional $attr);  -  same as show except returns a gallery as a string that you can use in PHP 

= 0.3.03 - 08/19/2009 =

    * Added Star Rating system - thanks to GD Star Ratings for use of the star set (used by permission).  2 placement options (beneath caption or overlay image [default]).  Design of star rating system enables extensions.   
    * Improved the Admin messaging - database message now contains a link that updates the database when clicked.

= 0.3.02 - 08/01/2009 =

    * Fixed Pagination when multiple galleries are on the same Post...now it remembers what page each gallery was on and paging links reflect proper paging for all other galleries.
    * Added message to JSON return on upload for images that are to be moderated.  Uploading user is now presented with message:  Upload Successful!  Image is awaiting moderation.
    * Added a hook to ajax_upload.php - hook:  bwbps_upload_done.  Fires after the Image is saved to the database, and provides an array containing the image's database values to the receiving function.  Useful if you're going to want to do some fun stuff after an image get uploaded.  A use case:  you have a business review site where the initial business record is created using a PhotoSmash upload.  The image in the upload should be the logo. If no image is supplied, that's ok, show a placeholder image. There is another gallery in the post you created for the business where users can upload their images.  When the an image is uploaded to this secondary gallery, you want to use that for the logo.  You can use this hook to update the blank image's file_name with the new image's file name.
          o Call this hook in your code by:   add_action('bwbps_upload_done', 'your_function_name');
          o Your function should accept an array as its first argument, all other arguments (if any) must be optional.
    * Added Gallery-level option for allowing uploads with no image file attached - this will let you do some CMS type stuff
    * Added Gallery-level option for suppressing 'no image' records in your gallery.  The can be accessed using the [psmash id=IMAGE-ID] shortcode. You can specify a layout to use or a field to display.
    * Added Gallery-level default image option where you can specify the name of an image that is in the PhotoSmash images folder structure. This image will be used for 'no image' records if you don't Suppress.
    * Fix - set contributor gallery so that it doesn't show any comments, and comments are closed.
    * Fix - Link for post name in contributor gallery should link back to itself
    * Fix - got rid of Video options in the Gallery Type setting.  YouTube options still remain, and will remain.  I'm not ready for uploading video yet. Worried about security issues.


= 0.3.01 - 7/25/2009 =

    * Added Contributor Gallery - a special gallery that can be shown in the Author page.  Turn it on in PhotoSmash Setting > Special Galleries.  It can also suppress all other posts in the Author page.  Can also use custom layouts.
    * Added Caption types to display link to Author page for Contributor.
    * Added ability to set CSS class for pagination DIV in Custom Layouts - so you can style it like you want
    * Bug - pagination wasn't showing up in Custom Layouts
    * Bug - [user_link] does not show non-Admin user links in custom layouts
    * Added ability to get notifications on all uploads (not just moderation)
    * Add option for getting notifications on uploads Immediately
    * Bug - fixed Radio buttons for gallery type:  Mixed Images + YouTube.  Browse for File and YouTube radio buttons were both being checked


= 0.3.00 =
* This is a huge re-release of PhotoSmash, dozens of changes from custom forms/fields/layouts to sorting
* You should not lose any of your prior galleries or work if you're upgrading, and they should work the same without any tweaking from you...All the same, BACKUP your PhotoSmash tables just in case.  Please!
* Make sure you visit the PhotoSmash Admin pages after upgrading.  If you see a message concerning the database, please follow the instructions for upgrading it.

== Usage ==

Using PhotoSmash is extremely simple:

1. Download PhotoSmash and unzip?you should wind up with a folder named: bwb-photosmash
1. Upload the bwb-photosmash plugin folder to your /wp-content/plugins/ folder
1. In the Plugins page of your WordPress Admin, activate PhotoSmash
1. There are 3 ways to add new galleries to your posts:
         --1--   Under settings, go to the PhotoSmash options page and turn on Auto-adding of galleries.  You can auto-add galleries to the top of each post or the bottom of each post by changing the drop down to the correct selection.  Click Update Defaults button to save changes
         --2--   Also in the PhotoSmash options page, scroll down below the PhotoSmash defaults section and select New in the gallery drop down.  Fill in the details you want to use for the new gallery, and click the Save Gallery button to create the new gallery.  After the save is complete, select your new gallery from the Gallery drop down and click the Edit button to retrieve it.  The code (like [photosmash=1] )for adding this specific gallery to any post or page will be in red beneath the Gallery drop down.  Cut and past the code anywhere you like in your posts or pages.  You can also specify multiple specific galleries within a single post or page by putting the tags with their ids in as needed.
         --3--   PhotoSmash can also create galleries on the fly for specific posts.  Simply enter the following code anywhere you like in posts or pages and a gallery will be automatically created:   [photosmash=] The code should include everything in red, including the braces and the = sign.
1.  To prevent a post or page from receiving a gallery when Auto-add is activated, insert the following tag anywhere in your post or page:  [ps-skip]
1.  To add photos to your galleries, go to the post or page and click Add Photos link.  I?m not sure what the size limit is right now.  It may vary based on your php.ini settings.
1.  If you choose to let Registered users upload photos, their photos will be visible to Admins and the themselves only.  Admins will be presented with buttons for Approve or Bury.  Approve is self explanatory.  Bury simply deletes the record from the database and deletes (unlinks in PHP terms) the files from the bwbps and bwbps/thumbs/ folders in the wp-content/uploads/ folder
1.  You will receive an email alert for photos requiring moderation.  These alerts use a pseudo-cron like scheduling scheme that is triggered whenever someone views one of your blog?s pages.  You can set the alert program to check every 10 minutes, 1 hour, or 1  day, or not at all.
1.  To edit a photo?s caption, go to the PhotoSmash options page in wp-admin.  Select the desired gallery from the drop down and click Edit.  When the page comes back, the images for that gallery will show up at the bottom of the page.  There will be text boxes beneath image allowing you to edit captions.  Click save to save caption edits.  Approve buttons will be present for images needing moderation.  Delete will be available for all images.
1.  To integrate with Lightbox or Shadowbox, simply include the correct ?rel? information in the Gallery specific options on the PhotoSmash options page.  You can set your general PhotoSmash default rel in PhotoSmash Defaults section so that any newly created galleries will automatically get the rel.   For Lightbox, set the rel to lightbox.  Shadowbox can use lightbox or shadowbox.  To group a galleries images together as an album for Shadowbox, use something like:  shadowbox[album] as the gallery?s rel.

== Acknowledgements ==

PhotoSmash, like most open source applications, has benefitted from the millions of hours of development and bug crushing that the open source world has put in.  Here are a few of the many projects that have influenced, informed, or otherwise enabled PhotoSmash. Thanks to you all!

*	Colin Verot - Upload Class - PhotoSmash uses this php class for handling image uploads - [class.upload](http://www.verot.net/php_class_upload.htm "Verot.net")
*	Alex Rabe - NextGEN Gallery - the heavyweight champ of photo galleries in WordPress (an excellent choice if you don't need the features of PhotoSmash) - PhotoSmash borrows several ideas and a little code - [NextGen Gallery](http://alexrabe.boelinger.com/wordpress-plugins/nextgen-gallery/ "NextGEN Gallery")
*	Milan Petrovic - GD Star Ratings - Milan granted PhotoSmash permission to use one of his star sets.  He informs me that he plans to enable GDSR to rate images and links in the near future (so check it out to see if it's in there now!).  I plan to add support for GDSR when this occurs. - [GD Star Rating](http://www.gdstarrating.com/ "Star Rating System for WordPress)