=== PhotoSmash Galleries ===
Contributors: bennebw
Donate link: http://smashly.net/photosmash-galleries/#donate
Tags: images, photos, photo, picture, gallery, social, community, posts, admin, pictures, media, galleries
Requires at least: 3.0
Tested up to: 3.2
Stable tag: 1.0.7

ATTENTION! A 'farewell' for PhotoSmash:

It has been my pleasure to be part of the WordPress community over the past couple of years by providing the PhotoSmash plugin and supporting its users.  However, like many developers before me, I find that as PhotoSmash's install base grows, I can no longer provide a level of support that meets my personal standards.  So, it is with a mixture of sadness and relief that I move on to my next adventure. To all of you PhotoSmashers, best wishes and...Cheers! Byron 

PhotoSmash - photo gallery plugin that integrates with built-in WordPress gallery functionality and lets you allow your users to upload images.

== Description ==

PhotoSmash Galleries makes it easy to create photo galleries in posts or pages that your users can upload images to.  PhotoSmash has incredibly flexible and simple models for designing custom photo gallery and form layouts, utilizing your own custom database fields, html, and css, or just use the standard.

= Links =

* <a href="http://wordpress.org/tags/photosmash-galleries?forum_id=10" title="WP Forum Page">WordPress Help Forum</a>
* <a href="http://smashly.net/community/forum/photosmash-help/" title="Forums page">Help Forum (deprecated)</a>
* <a href="http://smashly.net/photosmash-galleries/tutorials/" title="Help Videos">Help Videos</a>
* <a href="http://smashly.net/photosmash-galleries/photosmash-demo/" title="Demo Gallery">Demo Gallery</a>
* <a href="http://www.itunes.com/apps/photosmash/" title="PhotoSmash iPhone App">PhotoSmash iPhone App</a> on the App Store


= Features =

For support and more documentation, visit the plugin's new homepage: [PhotoSmash](http://smashly.net/photosmash-galleries/ "PhotoSmash Galleries on Smashly.net")

*   PhotoSmash iPhone App - <a href='http://www.itunes.com/apps/photosmash/'>Available on the App Store!</a>  Lets you and your site's users browse your images and upload images to galleries using your custom fields, tags, etc
*   User contributable photo galleries - allow your users to upload images to galleries
*   Map your images using Google Maps API V.3 - simple to use (see Changelog for details)
*	Media RSS is enabled, supports PicLens (activated by including piclens=true in the shortcode)
*	Link gallery thumbnails to various destinations including full-size image, related post, and WP Attachment Page when using Media Library integration
*	Add images to the WordPress Media Library so you can use them in blog posts and even the Standard WordPress Image Gallery features
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
*	Add Custom Fields to tile
*	Add Custom Fields to the upload form
*	Create Custom Upload forms using simple tags and HTML
*	Create Custom Layouts using simple tags and HTML

== Installation ==

1. Upload the plugin folder, bwb-photosmash, to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. To automatically add a new gallery to a post, put this shortcode in the body of the post where you want it to show up: [photosmash]
1. To add a specific existing gallery to a post, use this shortcode (replacing ## with the gallery's id): [photosmash id=##] 
1. Alternatively, you can set PhotoSmash to "auto-add" galleries to all post by updating the settings in the PhotoSmash options page in the Settings Admin menu.

== Frequently Asked Questions ==

= Is PhotoSmash free? =

Yes...and it's licensed under the GPL.

= How many galleries can a Post have? =

Unlimited.  Just add as many [photosmash=%id%] tags as you like...But performance gets to be an issue at some point.

= Who can upload images to a gallery? =

You control this on a gallery-by-gallery basis (you can also set a default for PhotoSmash that all new galleries will inherit).  Your options are Admins only; Admins, Authors, and Contributors (anyone with level_1 or higher roles); or Register Users (level_0 roles).

= Can unregistered users upload images? =

Yes...if you assign the minimum role for uploading to 'Anyone'

= How can I upload images to a gallery before adding to a Post? =

Create your gallery in Gallery Settings. Go to Photo Manager, select and view your gallery.  There will be an Add Images button.

= How do I change the appearance of my galleries? =

In the bwb-photosmash plugin folder, there is a css file:  bwbps.css
It should be relatively straight forward to change the look and feel through this file.
You can also exclude the standard css file and include your own through options in the Advanced tab of PhotoSmash Settings.

== Screenshots ==

1.  Example gallery
2.  Widgets - random, recent, random tag, highest rated
3.	Menu pages for PhotoSmash and Extend

== Changelog ==

= 1.0.7 – 7/4/2011

    * Removed an extra /DIV that was causing problems with the absolutely positions footer in Admin > Photo Manager 
    * NOTE:  Users who are displaying their images with the Thickbox Popup...for some reason, Thickbox now doesn't like when the REL attribute contains something like:  lightbox[album].  It causes the Thickbox loading screen to pop up, but nothing happens.  Here are your options: 1) try using either the Shadowbox JS or Fancybox for Wordpress plugins (Thicbox stopped being developed back in 2009, and I believe that time and the new jQuery have caught up with it), or 2) if you have lightbox[album] in you REL parameter (first page of the Gallery Settings screen after you select your gallery and click Edit), try changing the [album] to [gallery].  I added some extra code to deal with that.  But NOTICE, if you switch to Shadowbox JS or Fancybox, be sure to switch the [gallery] back to [album].

= 1.0.5 – 2/22/2011

    * Fixed XSS Vulnerability – an XSS vulnerability was discovered by High-Tech Bridge SA. It was reported as a “Medium” risk level. It was fixed the same day we were notified. The vulnerability was leftover code from testing and has been completely removed. All PhotoSmash users should upgrade to this release.
    * Added Link to Post to PhotoSmash Settings / Caption Style
    * Added ability to permanently hide Farewell notification

= 1.0.2 – 2/19/2011

    * (Extend) Fix alternate titles for new posts – the Alternate Layout was not working for the Title when creating a new Post.
    
= 1.0.1 – February 2011

    * Farewell notification

= 1.0.0 – 2/8/2011 =
    * (Extend) Added Post Meta Mapping - for PhotoSmash Extend, New Posts on Upload, you can now map PhotoSmash fields to Custom Fields in your posts. Go to PSExtend Settings / Post on Upload settings, then add your fields to the Post Meta Field Mapping (more details are given there)
    * iPhone/Mobile API tweaked
    * Added ability to let Users Delete Their Approved Images - previously, they could only delete images that were awaiting moderation. Now you can turn on ability to delete approved images. Go to PhotoSmash Settings, look for option near bottom of first tab
    * Added - Post Excerpt (Caption) is set to Image Caption when Attachment is Inserted - the WP Attachments use the Post Excerpt field as the caption for the standard galleries. Now when PhotoSmash inserts an Attachment, it is populating the Post Excerpt with the Caption.
    * Added - ability to select what the Gallery Viewer gallery ID slug will be - in PhotoSmash Settings (first tab), this slug will default to psmash-gallery. It is used with the Gallery Viewer when a gallery is clicked.
    * Fixed - Extend will now properly create new posts on Mobile uploads
    * Fixed - Extend Nav Search doesn't use paging...all result images are shown
    * Fixed - Function call was expecting too many arguments in bwbps-widget.php
    * Fixed - Static modifier on function broke PHP4 compatibility - in the pxx-helpers.php file, a STATIC modifier to a function broke PHP4
    * Added Gallery Viewer Shortcode Attributes - gallery_ids='1,3,4,##' will include only the gallery ids you enter (replace 1,3,4,## with your own ids. exclude_galleries='1,3,4,##' - you get the picture. So now you have multiple Gallery Viewers with different galleries listed. Here's a full shortcode (remove the space after the '['): [ photosmash gallery_viewer=true gallery_ids='1,3,4,12']
    * Added length attribute to custom fields in Layouts - so, now you can limit how many characters will display from your custom fields in your custom layouts. Say you have a field called Description. You can put that field in a custom layout and say you only want the first 30 characters like this: [description length=30]
    * Added nav_search_term field for Extended Navigation (Extend use only) - This allows you show the something like: Search results for: my search term. You can use the conditionals 'if_before' and 'if_after' to add styling and the 'Search results for:' text that will only appear if a search term exists. So the whole thing would be like: [nav_search_term if_before='<h3>Results for: ' if_after='</h3>']
    * Fixed - datepicker.js does not to load if you don't have a Custom Field (type date)
    * Fixed Google Maps code to only load when needed
    * Added Google Map checkbox to PhotoSmash Widget - you can now have your PhotoSmash image widgets mapped in a PhotoSmash Map widget. Look at the bottom of the PhotoSmash Widget for the "Show in Map Widget" setting.

= 0.9.02 – 1/11/2011 =

    * iPhone/Mobile API completed
    * Added ability to turn off ‘Toggle Ratings’ link
    * Added [gallery_description] to Gallery Settings and Custom Layouts
    * Added ability to turn off Pagination in Widgets

= 0.9.00 - 11/8/2010 =

    * Added ability to run a Shortcode in Text Inserts (PhotoSmash Extend) - here is an example of running the photosmash shortcode as a text insert: [shortcode text='photosmash id=2 no_gallery=true form=std']  That will essentially show the PhotoSmash upload form as the insert, uploading to gallery 2.
    * Fixed display of checkbox custom fields values in Photo Manager. Will no properly show as checked or unchecked.
    * Fixed some logic in the displaying of PhotoSmash Extend Ad Inserts - there was a logic problem in bwbps-layout.php.
    * Added ability to limit # images a user can upload - go to Gallery Settings, Edit your desired gallery, then go to the Uploading tab to set the  Maximum number of images you want a user to be able to upload to the gallery (set to 0 for unlimited uploads by user), and set the time frame that the restriction applies to: forever (ie. the user can only upload the max # images ever), per hour (ie. they can upload X number of images per hour), per day, and per week.  Note that these times are calculated by taking the number of hours specified and subtracting from the current time - so this means that max per day is really max per 24 hours, and max per week is really max per any given 168 hours regardless of the calendar.
    * Google Map integration - show a google map of images in a gallery that have latitude and longitude specified. To turn on a map, add this to your  shortcode: gmap='map_id'.  The 'map_id' will be the ID of the DIV that you want to contain your map.  It will also become the Map's Javascript ID, so you can do other things in Javascript with the ID, basically anything allowed by the Google Maps API V3.  This uses the Google Maps API V3, so you don't need an API key.  If you are using another plugin that already loads that API, you can tell PhotoSmash to skip loading it by adding to the shortcode:  gmap_skip_api=true.  You can manually place the DIV to hold the map (be sure to give it the same id as specified in your shortcode).  You can place the div in your page or post with this shortcode (remove the space after the "["):  [ photosmash_gmap id='map_id'].  I'll have to do a tutorial on map integration since there are more features than can be explained here.  But this is enough to get you started.  Note: the most basic way to get a map going is to just say: [ photosmash gmap='true']  This will automatically add the map after your gallery.  Also, note that in PhotoSmash Settings, a new tab (Maps) has been added that will let you set a layout for the marker popup infowindows in your map, as well as set the size of your maps.
    * Fixed a Multi-Site problem for tag galleries - in bwbps-layout.php, the table 'wp_term_taxonomy' had been hardcoded into the SQL statement.  This is now dynamic, and will handle MU sites properly.
    * Added the ability to change the wording in the Gallery viewer page - add before_gallery='My Gallery Viewer Wording' to your shortcode.

= 0.8.04 - 9/25/2010 - well that was fast ;-) =

    * Made PhotoSmash compatible with latest version of jquery.form.js -- and by corrollary, the latest version of Contact Form 7 -- well, the latest version of Contact Form 7 introduced a new version of jquery.form.js -- this is not the version that is currently distributed with WP.  The new version of that file requires you to wrap your JSON in textarea tags if you're uploading files.  Since CF 7 is so popular, it behooves me to make PhotoSmash compatible...and now it is. Hopefully! ;-)
    * Removed JSON.php class - this file was for PHP4 compatibility.  WordPress actually includes this file itself as of 2.8 or 2.9, so this is no longer necessary.

= 0.8.03 - 9/24/2010 =

    * Added '[delete_button]' to custom layouts - this gives you the ability to present your users with a delete button on the images they uploaded.  You'll need to add this to the custom layouts (there's a tutorial on custom layouts if interested).
    * Fixed importing image - was using 2nd image multiple times when multiple images were imported.  Was working earlier, but something along the way broke this.
    * Fixed custom layouts use of the # of images per row for tables - this was broken during the last big overhaul which removed certain code to make it faster.
    * Added image_id to [ photosmash] shortcode - while this has long been possible using the [ psmash] shortcode.  Using it in the photosmash shortcode allows you to display a single image with all the benefits of a full gallery, including star ratings and favorites.
    * Added Post-Author Uploads as a Gallery type - this gallery type allows you to set up galleries that only allow the Author of the Page/Post on which it is displayed to upload images to it.  This is probably most useful when allowing users to create new posts on uploads with PhotoSmash Extend, but can be used without Extend.  The only requirement is that the user you want to upload to the gallery has to be the Author of the WP page/post.

= 0.8.02 - 8/18/2010 =

    * Added post_cats for shortcodes [PhotoSmash Extend] - use post_cats='1,2,3' (where 1,2,3 are the id's of Categories) inside your shortcode you want to set for newly created Posts. Note, this only works with PhotoSmash Extend. An example shortcode is: [ photosmash create_post='New_post_uploald' id=1 post_tags=true post_cats='1,2,3'] (remove the space from the shortcode)

= 0.8.01 - 7/22/2010 =

    * Fixed Sorting and Pagination - the changes in the last release caused sort order to behave incorrectly and caused the gallery to prefer the Images/Page set in Gallery Settings over the # of Images set in shortcodes when limiting the number of images returned in the query.

= 0.8.00 - 7/15/2010 =

    * Note: this is a big release with a number of significant changes to the core code. It is impossible to test all use cases (as we've see from the new releases of WordPress itself, though the WP 3.0 release seems to have been very clean), so if you have specialized use cases, you should test them in a development system before moving this to your production system.
    * Added search post name field in Image Importer (admin) - allows you to limit the images fetched by searching on post name (partial words are ok).  This is in the Import Photos page.
    * Text Inserts for PhotoSmash Extend now Add to the number of images being displayed on a page - previous behavior was to only show the number of images selected in the images per page setting in Gallery Settings.  But due to the use of LIMIT in the images retrieval query, it is impossible to know what the limits should be since the inserts are not known in the Layout loop.  You should Adjust your Gallery Settings to account for text inserts that you have in your galleries, if you have a strong requirement for a specific number of images on the page.  So, if you need 25 images and you have 3 inserts set, you should set the images per page in Gallery Settings to 22 (since 3 additional spots will be added by the Text Inserts module).
    * Fixed an issue with inserting a rating - the 'comment' field is NOT NULL in the database, and it wasn't being inserted, so some MySQL setups were failing to insert. The NOT NULL has been removed, so it should update to allow nulls.
    * OPTIMIZATION - Implemented get_children() to cache Attachments when using get_attachment_link() - when linking to the Attachment pages, the get_attachment_link() WP function was executing 2 SQL queries on the database for each image just to calculate the permalink. So, if you were displaying 50 images, you would have 100 additional queries. Using get_children() can incur 1-2 more queries that cache all of the attachment pages (only the ones in the page's result set...see the paging change below for more info), but it's not 100
    * OPTIMIZATION - Various query reduction measures - now caching the Custom Field information in get_option(), several other query optimizations.
    * OPTIMIZATION - Switched Paging to return only rows for current page - we began noticing that sites with lots of images in a single gallery were using a lot of memory.  Turns out it was because of lazy paging...i.e. the entire result set was being returned (thousands of images in some cases), when only 20 or so were needed.  The downside is that an extra query has been added to count the total result set for paging purposes.  My reading on MySql was that running a straight select (on a single column in this case, so memory doesn't take too big of a hit) was faster than doing a Count( DISTINCT() ).  If someone can help optimize this, I'm all ears.
    * Added sort_field, sort_order to shortcode - you can now specify a sort_field ( sequence, user, user_name, user_login, rank, favorites ) in the shortcode by adding:  sort_field='rank'.  You can also specify the order by adding: sort_order='asc' or 'desc'.  These are also available in the $_REQUEST variable (for you techies), but you'll need to add the gallery ID to your $_GET or $_POST (because you can have multiple galleries on a page): sort_field22=rank&sort_order22=desc .  The shortcode portion should work fine with paging, but the $_REQUEST method may have weird results on paging...I haven't tried it yet.
    * Added ability to display galleries as standard WordPress Galleries - as long as you've got both options under PhotoSmash Settings / Uploading (tab) / "Use WordPress Upload process" turned on, you have always been able to display your PhotoSmash images using the standard WordPress [ gallery]  (sans the space) shortcode.  There are some other galleries that might co-opt this shortcode and cause this to fail if you're running them on the same blog (I think NextGEN uses that same shortcode, so it might not play nice).  Now you can have PhotoSmash display galleries using the WP gallery out of the box.  Here's more info: http://smashly.net/blog/wordpress-attachment-pages-and-photosmash/
    * OPTIMIZATION - Separated Gallery Functions to new Class - this was a big change, so please keep your eyes pealed for gotchas. The code is now more efficient and more compact.
    * Added 'name' attribute to Shortcode - this allows you to call a Gallery by name instead of by ID. It will create a new Gallery with the name if it doesn't exist. So you can now easily create multiple galleries in a Post/Page by adding shortcodes like: [ photosmash name='fun pics'] and [ photosmash name='funny pics']
    * Added sort by file_name  (shortcode sorting only) - by request!
    * Added sort by Caption  (shortcode sorting only) - probably won't get much use, but good for testing!
    * Added sort by Favorites Count (shortcode or dropdown in Settings) - makes the Most Favorited gallery work much better
    * Added various filters and actions - making it easier for other plugins to interact. Filters: bwbps_add_photo_link (passes the 'Add Photo' html for filtering); bwbps_empty_gallery (passes the empty gallery html); bwbps_image (passes the image html block after it is processed). Actions: bwbps_save_new_image (passes the image object immediately after image is added to database)
    * OPTIMIZATION - Moved Admin Javascript to bwbps-admin.js - reduced the size of the javascript file that gets loaded to your visitors on the front end by 20KB. The Admin JS is now only being loaded on the Admin pages.

= 0.7.03 - 5/5/2010 =

    * Fixed Encoding in Photo Manager - for caption and custom fields.  Was using htmlentities(). Switched to esc_attr().  Fixed problem with non-English alphabets
    * Fixed Gallery DropDowns in Photo Manager - was not showing galleries with NULL gallery types
    * Changed Sort Order alogrithms for Ratings - for Vote up/Vote down, changed to average. For Vote up, changed to straight number of votes.  Stars still use Bayesian ordering

= 0.7.02 - 4/27/2010 =

    * Fixed Resizing message when no resize needed - when resizing an image, if there were no new image sizes to create, it was causing an error by trying to update the database. On the front-end, you just saw the Saving message...forever.
    * Coming soon!: Pixoox Photo Sharing - Press-to-Press Photo sharing. Just think about it ;-)
    * Fixed Gallery Viewer Database Error - when there were no galleries with a cover image set, it was giving a Database Error.
    * Fixed Pagination - pagination on sites that had pretty permalinks turned off wasn't working properly.
    * Fixed Admin Image Uploads - I left some test code in there that prevented the uploads. Ugggg...
    * Added Color Border and Gallery ID to Photo Importer - when attachments were fetched that are in a gallery, they are outlined in red.

Visit the [Changelog on Smashly.net](http://smashly.net/photosmash-galleries/ "Changelog on Smashly.net") to see what is currently in development.

= 0.7.01 - 4/19/2010 =

    * Added Update Tag Counts - to Plugin Info page, there is now a button that will update your Photo Tag counts if you start seeing tags that don't have proper counts (they're too big or too small in the tag cloud)
    * Added Gallery Viewer - in PhotoSmash Settings, you set which page you'd like the Gallery Viewer to show on (first setting under the Defaults tab). You can tweak the look and feel of the Gallery Viewer by adjusting Custom Layout "gallery_viewer" or by copying the code from that layout and creating your own, in which case you'll need to specify your layout by using this shortcode in the Page you want your viewer to appear in: [ photosmash gallery_viewer=true layout='my_custom_layout' image_layout='image_view_layout'] The image_layout there is used for displaying images when they are called by the Photosmash image permalinks. Gallery Viewer doubles as index for your galleries...it only shows galleries that contain images, and it doesn't show virtual galleries. It randomly selects an image from the gallery as the Cover image. You can change this in Gallery Settings. When you click on a gallery from the Gallery Viewer, it will display that gallery on the same page. You can control which layout is used to show the galleries on that page by using the shortcode and adding this attribute: gallery_view_layout='my_custom_layout' When that attribute is present, all galleries displayed on the gallery viewer page will use the layout you specify.
    * Added Exif Support - pulls in the Exif data from the WordPress attachment record if you're allowing PhotoSmash to add your images to the media library. You can show Exif data in your custom layouts by using tags: [image_meta field='aperture'] The field can be any valid Exif field available in the WP attachment. You can also show a table of Exif data in your custom layouts with the tag: [exif_table] If you want to include blank fields, do: [exif_table show_blank=true] To control what shows on exif_table if no exif data exists, add the attribute: no_exif_msg='No EXIF data available' Or whatever you want the message to say. There is a button beside the Exif field in Photo Manager that will fetch and save the related attachment's Exif.

= 0.7.00 - 4/4/2010 =

    * Added Drop Down of Galleries to Media Uploader – when you’re adding an image to a Post or in the Add New in the Media Library, you get a drop down list of PhotoSmash Galleries that you can automatically add the images to.
    * Added Uploading Photos from Photo Manager – uses the built-in WordPress Media Uploader.  So, it only took me until 32,000 downloads and version 0.7, but there you go!  Nicely integrated with WordPress Media Library.  Extends Media Library in ways I haven’t even told you about :P !
    * Fixed Custom Photo Tag Slug – was defaulting back to the default slug when PhotoSmash was deactivated and reactivated (witnessed in upgrading!)
    * Fixed the post_date_gmt - when creating new posts through Extend, you can publish them through Photo Manager. There is a bug in wp_publish_post() that does not update the post_date_gmt field, which is used in Feeds.  Added code to update this field in ajax.php when publish posts through Photo Manager.


= 0.6.00 - 3/28/2010 =

    * Added sort options for User Name and User Login - in PhotoSmash Settings and Gallery Settings.
    * Added allowable attributes to HTML filtering - in the HTML filtering in Custom Fields Editor, "Allow formatting & links & lists" now permits the following attributes in a href links: id, class, style, and target.
    * Fixed closing > on option tag in bwbps-layout.php
    * Major renovation of Photo Manager - new look, with options for hiding fields more effectively. Clicking images now opens in Thickbox and you can scroll through the images. More Meta fields being show.
    * Save Buttons updated in Photo Manager - Save buttons now save both Standard and Custom Fields.
    * Deleting images behavior changed - deleting an image will only remove the files if no other image record references any of the files. So, if you copy an image from one gallery to another, and then delete the image from one of the galleries, the files will not be deleted until the other image record is deleted.
    * Changed Gallery Drop Downs to only show Appropriate galleries - virtual galleries are not shown in the drop downs for copy/move or importing or Photo Manager. They shouldn't have images saved to them, so they shouldn't be used in those cases.
    * Changed the look of Image Size fields - in PhotoSmash Settings and Gallery Settings, the image size fields are now more like the Media image sizes in WordPress Settings / Media.
    * Added a video for Sizing and Resizing images - links to the video are included in appropriate places in PhotoSmash admin pages. 
    * Renovated "Import Photos" page - changed the look. Added image paging.

= 0.5.08 - 3/17/2010 =

    * Added "Favorites" for Images - you can now turn on ability for your users to Favorite Images. There are 2 new Gallery Types: one for displaying the logged in user's favorites to them, and the other for displaying the most favorited images (need to set sorting to Descending). There is also a template tag for displaying a link to the page you set for displaying Favorites to users. That page must include a gallery with a shortcode something like: [ photosmash gallery_type='favorites'] (without the space). You can use all of the features of a normal gallery. It will not show an upload form or link. The template tag for displaying the link to your Favorites page is: photosmash_favlink($link_text, $before, $after); You have to set the Page that it will link to in PhotoSmash Settings, near the bottom of the first tab...choose the page from the Drop Down. $link_text, $before, $after are all optional arguments. The $link_text defaults to Favorite Images. Before and After default to empty, but they are useful if you want to enclose in an 'li' tag for placing it in your navigation.
    * Added ability to customize the Page Slug and Page Title for the photo tag custom taxonomy - these can be set in PhotoSmash Settings on the first tab at bottom.  Settings are: "Photo tags Page Title" and "Photo tags URL Slug".  The url slug should be something useable in a URL.  The default is 'photo-tag'.
    * Fixed Image Duplication in Lightbox/Shadowbox for "No Caption" caption styles - the Caption Style "No Caption" was missing the close '/a' (anchor tag) in its link.
    * Fixed 'more' attribute in [caption] tag - this wasn't exactly broken...the attribute is actually 'more_text', but I added 'more'. 'more_link' actually creates a link to the post
    * Added 'length' attribute to [contributor], [user_name], etc for Custom Layouts
    * Fixed widget layout selection - selecting the Standard Layout in a widget was going back to < Default > instead of Standard Layout
    * Fixed the link in PhotoSmash Extend version incompatibility warning - the version check between Extend and PhotoSmash had a bad link to the PhotoSmash Extend download page.
    * Added 'action' attribute to Extended Navigation (PhotoSmash Extend) - this allows you to specify a Page or Post ID to be used as the Form action in the Extended Navigation form submission (searching images, filtering on tags, etc)
    * Fixed 'View' post permalink in Photo Manager - was not displaying properly for blogs that aren't in root directory

= 0.5.07 - 3/9/2010 =

    * Photo Manager enhancement - Resize Image - added ability to resize individual images or multiple images by selecting, creating new size files based on Gallery settings.
    * Photo Manager enhancement - made toggling Custom Field data sticky.
    * Added tag [preview_post] to Custom Form - for PhotoSmash Extend use to show a link to preview newly created post.
    * Changed bwbps-layout.php so that Caption Styles that link thumbs to Posts works - these particular settings are not ideal unless you use a different layout for posts, which can be accomplished by specifying a separate single_layout in the shortcode: so something like [ photosmash layout='main_pagelayout' single_layout='post_pagelayout' ] (without the space before photosmash.
    * Added Maximum Image Size - in PhotoSmash Settings, go to Images tab. You can set the maximum number of bytes an uploaded image is allowed. This will let you gracefully reject images that are so large that they would cause the Out of Memory error associated with resizing images that are too big. Example: Enter 500000 for a max of 500kB image upload size.
    * Added New Image Size...mini - now, in addition to thumb, medium, and image, you have a 4th image size called mini. Use this in your custom layouts with tags: [mini] for an full blown image tag or [mini_url] for just the URL so you can built your own links and image tags.
    * Added Search field to Extended Navigation for PhotoSmash Extend users - this allows you to add a search field that searches the Image Tags, Image Caption, Image URL, Image Contributor, and Image Attribution.
    * Fixed bug in Image Importing - when importing Images from the Media Library that had thumbnail and medium sized images that were the dimensions set for the photosmash gallery to which they were being imported, it was using the large image URL for all fields. Now it will use the appropriate file urls based on size.
    * Added Ability to Attach/Display Other File Types - this ability allows you to Select Attachments from the WP Media Gallery and have a Thumbnail in a gallery link to those attachments (or you can paste in any URL manually). Go to Photo Manager, toggle "Toggle Video/File URL" to show the File URL field, either paste in the URL of the file you wish to link to or click the browse icon beside the field, use the pop-up to search for attachments in your WP Media Library (you can add them through the Media/Add New interface independently of posts), click the name of the file you wish to use, the save the image record. At this point, your thumbnail will link to the file based on the rules you have set in Caption Settings in your Gallery Settings.
    * Fixed image_id tag in Layouts - you can use the Image ID as a tag in your Layouts (especially useful when creating new posts through PhotoSmash Extend - it allows you to use the psmash id=## shortcode to show individual images or fields attached to an image record

= 0.5.05 - 2/23/2010 =

    * Enabled Extended Navigation for PhotoSmash Extend - ExtNav gives ability to create various navigations using drop down lists with Tags.  Use multiple drop down lists to limit displayed images to specific tags.
    * Optimized gallery display code for Custom Layouts - in bwbps-layout.php, added code so that on the first image for each layout and each alternate html, it will remember which fields are being used and only do the find and replace on subsequent images.  There are now over 35 standard fields available to custom layouts, so in a gallery of 30 images, this becomes a lot of search and replace, particularly when only 4 - 7 fields are typically being used.
    * Added Copy / Move multiple images from one gallery to another gallery - in Photo Manager, look for Copy/Move Images link.  This will display the Copy/Move menu box.  Select which gallery you want to copy/move images to (there is a dropdown for this in the Copy/Move menu box).  Click on images to select for copy/move (the background will turn green when selected).  Then click either the Copy or Move link.  NOTE: copying an image does not copy the image files.  It merely adds a new reference to the image files to another gallery.  You can change the tags and custom fields for the new record without affecting the old image.  VERY IMPORTANT, or you might delete images you don't meant to...If you "DELETE" an image that is in 2 separate galleries, it will orphan the image in the gallery that you did not delete.  To remove an image from one gallery and keep the files and also keep the image record in any other galleries, you should use "REMOVE" instead of "DELETE".
    * Photo Manager enhancements - Tweaked layout of the menu structure in Photo Manager.  Added ability to toggle on/off Image Data
    * Added field tag [tag_links] to Custom Layouts - this will display an images tags, linked to the tag gallery.
    * Bug fixed - there was a problem with the bwbps.js javascript file where if there were multiple forms on a page, it was not looking at the right set of radio buttons during the upload file validation and was giving a validation error without uploading.
    * Added esc_sql() to image query - with the addition of multiple tag viewing support, this was needed to prevent SQL injection attacks.
    * Bug fixed - post_cat_exclude attribute of the photosmash shortcode was not working.  This will now allow you to exclude categories when using the category dropdown via the shortcode.
    * Added - ability to restrict Highest Ranked galleries to display images from a single gallery.  Include the 'where_gallery=id' attribute in the shortcode.
    * Added [author] tag to Custom Layouts - tag will display the image contributor's login or nice name.  Allows you to build your own links to an author page or other uses.  Use [author_link] to get an automatic link to the author page.


= 0.5.04 - 2/3/2010 =

    * Added manual sort option - set the sequence numbers in the Photo Manager and then set the Sort Field to Manual sort in Gallery Settings.
    * Added options for linking to WP Attachment Pages - this lets you link your thumbnails to standard WordPress Attachment Pages just like the standard WordPress galleries do.  Either choose the link option from Caption Styles (near very bottom) in Gallery Settings (also set defaults in PhotoSmash Settings), or use:  href='[wp_attachment_link]'  in you link tags in a custom layout.
    * Added the height and width settings for the Thickbox forms - set this in Uploading in PhotoSmash Settings


= 0.5.03 – 1/27/2010 =

    * The SVN archive missed a file during the creation of the Stable Tag folder

= 0.5.02 – 1/26/2010 =

    * NOTE: Photosmash Extend users - Please contact Byron for a new release of PhotoSmash Extend.  This version of Photosmash may not work with older versions of PS-Extend
    * Added 'Delete Layout' button to Custom Layouts admin page...be careful.  Once deleted, they're gone forever! I know the hard way.
    * Added 'images' attribute to shortcode - it's the number of images you want displayed. Use images=X to specify how many images you'd like the MySQL query to return.  Do not use for paging, as it will never retrieve all the images and generate the page links.  Use for special cases when you want to show a single image or a set number of images. Note that Random and the other Widget gallery types already had this option.
    * Added PicLens support. Use [piclens] in your Custom Layouts to get the PicLens link, or use attribute piclens=true in the photosmash shortcode to get the link without tweaking a custom layout.  If you're going the shortcode route, you can also use the attribute piclens_link='Start Slideshow' for the text of the piclens link...use whatever text you're wanting in place of Start Slideshow.  It will default to 'Start Slideshow' with a little piclens icon.
    * Added new widget gallery options: Random Tag and Tag (you specify one or more tags).  Note that the Random Tag widget will automatically create a title: "Images tagged 'your tag'.  For the Tag gallery, you'll need to  specify your own title for the widget.
    * Re-added image counts to Photo Manager.
    * Security enhancement - Added HTML filtering to title attributes on URL links.
    * Added if_field attribute to Custom Layout fields - this will let you put a field into a layout like this:  [first_name if_field='last_name' if_before=', ']  This will put a comma before the first name if it exists...and only displays first_name if last_name exists.

= 0.5.00 – 12/27/2009 =

    * Added 2 standard fields: Image Attribution and Image License. These must be turned on in PhotoSmash Settings to get them to display on the standard form. Also, the field values for uploaded images are only available on Custom Layouts at this time. Use the [img_attribution] and [img_license] tags to display the values on a custom layout.
    * Fixed Photo Tagging feature so that when you delete an image that has tags, the tags are also deleted…that wasn’t happening before.
    * Added ability to Add/Edit/Remove image tags in the Photo Manager admin page! Woohoo!
    * Tweaked PhotoSmash so that PhotoSmash Extend can now add Post Thumbnails available in WP 2.9. This might make PhotoSmash Extend a 2.9+ plugin, though PhotoSmash is still compatible with 2.8+.
    * Fixed the Permalink for the title of the tag gallery on the Photo Tags page.
    * Added ‘tab_index’ attribute to Custom Fields and Standard fields (except file upload and buttons) in custom forms.  Use like: …photosmash id=5 tab_index=4]
    * Added attributes to the Posts Category dropdown [post_cat] custom form option: show_option_none=’– none –’ will show an option in the category dropdown called — none — (replace with whatever text you like). id=’my_id’ was added so you can now have multiple category dropdowns (or multi-select listboxes) with different ID’s so you can do javascript manipulations in the form…the id gets appended to: bwbps-post-cats like ‘bwbps-post-cats-my-id’. Now you can do something like onclick=’jQuery(“#bwbps-post-cats-my-id�?).val(“-1″); return false;’ to se that particular category dropdown value to none (if you’ve got none turned on).
    * Added attributes to Custom Layout fields:
          o if_before – allows you to specify html to place before the field if the field has a value
          o if_after – allows you to specify html to place after the field if the field has a value
          o if_blank – allows you to specify default html for when the field has no value
          o yep…it’s getting pretty cool!!! 
    * Added ability to display a gallery containing all images that have the same tags as the Post. in the photosmash shortcode, you can show a tag gallery by adding the attribute tag=’put tags here, separate with commas’.  If you want a gallery that has all images that are tagged with any of the tags of the post your on, simply make the tag attribute:  tag=’post_tags’ .  Love this feature too!

= 0.4.04 – 12/01/2009 =

	* Added ability to change the text of Approve and Reject messages on the fly.  Beneath the gallery selector at top of Photo Manager, click Edit/Display Moderation Msgs link to show the messages...use the checkbox to turn on/off of sending messages on Review, Approve, Delete.  Use the following variable tags: [author_link] - displays a link to the image contributor's Author page on your blog; [post_link] - displays a link to the post related to the image; [user_name] - displays the user's login name; [blogname] - displays the name of your blog.
    * Added Editing of Custom Field data in Photo Manager – click the toggle link beneath the gallery selection at top to display the custom field data forms for all displayed images.
    * Added ‘Publish’ button in Photo Manager to allow for publishing unpublished Posts – this will be useful to persons using the PhotoSmash Extend add-on plugin which allows for creating new posts on image uploads
    * Added tagging of Photos – uses WordPress Custom Taxonomy call ‘photosmash’
    * Added tag cloud for Photo tags…clicking Photo Tags will display galleries of all images with a tag
    * Added shortcode parameter for displaying tagged image galleries.  Add this type of parameter to your shortcode:  tags=’my tag, my other tag, tag1, etc’
    * To add tag field to your Upload form, add these parameters to your shortcode:  post_tags=true post_tags_label=’Add tags: ‘
    * Added Sorting by Rank (uses Bayesian ranking to weight rankings)
    * Added Sorting by User ID in galleries
    * Added simple paging in Photo Manager…tell it how many to images to show (defaults to 50) and what image # to start with (not zero based like MySQL…it adjusts for that.
    * Changed the sort order in Photo Manager to Descending
    * Fixed Photo Manager bug – wasn’t showing up new images for moderation until after the email alert had been sent…now shows up immediately.  If admin views it in moderation area, email will not be sent notifying admin of need for moderation.
    * Fixed – Recent, Random, and now…Highest Rated widget displays to allow for showing ratings.  You’ll need to find the appropriate gallery in Gallery Settings and set it to show either the 5 Star or the Vote up rating types…choose whether to display beneath or as an overlay…if displaying as an overlay, make sure that in the Custom Layout that you use that you have ‘position: relevant’ set for the CSS for the element that wraps the image and the rating…oh and you need to have [ps_rating] in the custom layout too.  Something like:    <div style=’position: relative;  float:left;’>[thumbnail] [ps_rating]</div>  should probably work


= 0.4.03 - 11/7/2009 =

    * Fixed gallery setting "maintain ratio" so that it does not force thumbnail sizes when gallery is displayed
    * Several code tweaks to enable PhotoSmash Extend functionality - particularly around creating new posts on upload
    * Now only supporting WordPress 2.8 and higher.  Does not specifically break compatibility with versions later than 2.6, but going forward, only 2.8+ will be supported.  There is a big security hole in WordPress versions prior to 2.8, so this is my part in encouraging users folks to upgrade.  It's for your own good...I know from experience ;-)
    
= 0.4.02 - 09/26/2009 =

    * Added THE Widget!! Display Random, Recent, or normal galleries.  ONLY WORKS WITH WP 2.8+. PhotoSmash will continue to work with versions back to 2.6, but this widget uses the new Widget API available in WP 2.8.
    * Added a new default Custom Layout for the Widget.  You can use any layout you want, but I built one that should (HOPEFULLY!!!) work well.  It can be used in regular galleries too.  It's called Std_Widget
    * Added Recent images gallery type.  Use shortcode:  [photosmash gallery_type=recent images=10 where_gallery=185 ] , use any # of images you want, defaults to 8 if left blank.  "where_gallery" lets you specify a particular gallery to pull images from...optional.
    * Added Random images gallery type. Use shortcode:  [photosmash gallery_type=random images=10 ] The images and where_gallery attributes above can be used here too.
    * Added option for setting CSS Class on the 'a href' for images...facilitates using Thickbox to display images instead of Lightbox or Shadowbox.  Make sure you change the Rel if you have both Thickbox and Lightbox/Shadowbox activated at the same time, otherwise Shadowbox overlays the thickbox...not pretty ;-)
    * Fixed paging for text/ad inserts using the unreleased PhotoSmash Extend product.
    * Fixed paging for Contributor (author) galleries.
    * Added ability to send emails upon Approve/Reject of images in moderation.


= 0.4.01 ñ 09/04/2009 =

    * Added ability to Import images to PhotoSmash galleries from the WordPress Media Library.  This lets you use the WP Media uploader (multiple simultaneous uploads) in Admin, then import them into galleries.
    * Changed the default delete from deleting the Media Library images to be on-demand in Photo Manager.  Deleting a gallery does not delete Media Library images now.  Too much risk.
    * Fixed a javascript bug - when uploading images with the new (0.4.00) WP upload functionality, the link to the image was broken until you reloaded the page.

= 0.4.00 ñ 09/01/2009 =

    * This gets a version bump! Added option [is default for new installs] to use WordPress upload functionality. Can optionally add uploaded images to the WP Media Library. Set these options in PhotoSmash SettingsÖtop of the Uploading tab. This is in preparation for the upcoming new WordPress 2.9 media features. By adding these images to the Media Library, you should be able to utilize new features that WordPress builds in. The new WP 2.9 feature set hasnít been officially announced yet, but stay tuned!!! This should also solve upload issues where people have trouble with folder permissions. I could be wrong, but I think this is pretty big :P
    * Fixed a couple of annoying ThickBox images that werenít loading. You have to set the variables in the page footerÖFYI.


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

Using PhotoSmash can be as simple as just adding this shortcode to a Post: [photosmash]

Here's how to get up and running:

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