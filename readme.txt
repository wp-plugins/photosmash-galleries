=== PhotoSmash Galleries ===
Contributors: bennebw
Donate link: http://www.whypad.com/posts/photosmash-galleries-wordpress-plugin-released/507/#donate
Tags: images, photos, picture, gallery, social, community, posts, admin, pictures, media, galleries
Requires at least: 2.6
Tested up to: 2.7.1
Stable tag: 0.1.98

PhotoSmash - user contributable photo galleries for WordPress pages and posts with options.  Auto-add galleries or specify.

== Description ==

PhotoSmash Galleries makes it easy to create photo galleries in posts or pages that your users can upload images to.  Following are the features:

*   User contributable photo galleries
*   AJAX photo uploads
*   Control who can upload images: admin only, authors & contributors (and higher), or registered users and higher
*   Moderate images uploaded by registered users (Admins and authors are automatically approved)
*   Receive email alerts for new images that need to be moderated
*   Options page for setting general defaults or specific gallery settings
*   Auto-adding of photo galleries
*   Multiple galleries per post, added using a simple tag system
*   Integrates with popular image viewing systems like Lightbox and Shadowbox
*   Tweak appearance through the included css file

== Installation ==

1. Upload the plugin folder, bwb-photosmash, to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place [photosmash=] tag in your posts or pages.  You can add a gallery id after the '='.  Leaving blank will create a new gallery that will be linked to your post.  Alternatively, you can set PhotoSmash to "auto-add" galleries to all post by updating the settings in the PhotoSmash options page in the Settings Admin menu.

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
