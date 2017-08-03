=== Wicked Folders ===
Contributors: wickedplugins
Tags: folders, administration, tree view, content management, page organization, custom post type organization, media library folders, media library categories, media library organization
Requires at least: 4.6
Tested up to: 4.8
Stable tag: 2.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Organize pages and custom post types into folders.

== Description ==

Wicked Folders is the ultimate tool for managing large numbers of pages and custom post types.  The plugin simplifies content management by allowing you to organize your content into folders like you would on a computer.  Wicked Folders is an administration plugin that does not alter your content’s permalinks or hierarchy giving you the complete freedom to organize your pages and/or custom post types any way you want independently of your site’s structure.

= Features =
* Organize pages and custom post types into folders
* Control what post types can be organized using folders
* Create an unlimited number of folders and nest them any way you like
* Tree view of folders
* A ‘Folders’ page in the admin for each post type that you’ve enabled folders for that allows you to view your content by navigating a folder tree
* Drag and drop folders to easily reorganize them
* Drag and drop items to quickly move them into folders
* Bulk move items to folders
* Assign items to multiple folders
* Dynamic folders (read more below)

= Dynamic Folders =
Dynamic folders let you to filter pages and other content by things like date or author.  Dynamic folders are generated on the fly meaning you don’t have to do anything; simply install the plugin and you can automatically filter pages by date or author out-of-the-box.  See the screenshots section for an example.

= How the Plugin Works =
Wicked Folders works by leveraging WordPress’s built-in taxonomy API.  When you enable folders for pages or a custom post type, the plugin creates a new taxonomy for that post type called ‘Folders’.  Folders are essentially another type of category and work like blog post categories; the difference is that Wicked Folders allows you to easily browse your content by folder.

This plugin does not alter your page or custom post types’ permalinks, hierarchy, sort order, or anything else; it simply allows you to organize your pages and custom post types into virtual folders so that you can find them more easily.

= Wicked Folders Pro =
Want to organize your media library using folders?  Our WordPress media library plugin, [Wicked Folders Pro](https://wickedplugins.com/plugins/wicked-folders/), allows you to do just that!  Wicked Folders Pro extends the same great folder functionality to the media library so that you can easily organize and browse items by folder.  [Learn more about Wicked Folders Pro](https://wickedplugins.com/plugins/wicked-folders/).

= Support =
Please see the [FAQ section]( https://wordpress.org/plugins/wicked-folders/#faq) for common questions, [check out the documentation](https://wickedplugins.com/support/wicked-folders/) or, [visit the support forum]( https://wordpress.org/support/plugin/wicked-folders) if you have a question or need help.

= About Wicked Plugins =
Wicked Plugins specializes in crafting high-quality, reliable plugins that extend WordPress in powerful ways while being simple and intuitive to use.  We’re full-time developers who know WordPress inside and out and our customer happiness engineers offer friendly support for all our products. [Visit our website](https://wickedplugins.com) to learn more about us.

== Installation ==

1. Upload 'wicked-folders' to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen by searching for 'Wicked Folders'.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Visit to Settings > Wicked Folders and enable folders for the desired post types (folder management for pages is enabled by default)

To start organizing your content into folders, go to Pages > Folders in your WordPress admin.  From there you can add new folders, drag and drop to re-arrange them, and drag and drop pages to move them into folders.

If you've enabled folders for custom post types, you should see a 'Folders' link in the submenu for the post type.

== Frequently Asked Questions ==

= I installed the plugin, now what? =
The plugin will automatically enable folder management for pages upon activation.  To start organizing your pages into folders, go to Pages > Folders in your WordPress admin.  From there, click the ‘Add New Folder’ link to add your first folder.  Visit the plugin’s settings page at Settings > Wicked Folders to enable folders for custom post types.

= Does this plugin change my page’s or custom post types’ permalinks? =
No, the plugin doesn’t modify pages or custom post types beyond controlling what folders they belong to.

= Can I drag and drop to reorder pages? =
No.  Folders are completely independent of your site’s page hierarchy so it is not possible to re-order pages using this plugin.

= What happens when I delete a folder? =
Folders work like categories.  When you delete a folder, any pages that were in the folder are simply unassigned from that folder.  The pages are not deleted or modified in any way.

= If I delete a folder will the pages in the folder be deleted? =
No, only the folder is deleted.

= How do I put a page in a folder? =
There are two ways.  The first is to navigate to Pages > Folders, move your mouse over the page icon in the pages list and drag and drop the page to the desired folder.  Alternatively, you can edit the page and assign folders in the ‘Folders’ meta box in the right sidebar.

= How do I put a page in multiple folders? =
Edit the page and select the desired folders in the ‘Folders’ meta box in the right sidebar.

= How do I remove a page from a folder? =
Edit the page and uncheck the box next to the folder you want to remove it from in the ‘Folders’ meta box in the right sidebar.

= Can I organize my media library using folders? =
Media library folders is a premium feature available in Wicked Folders Pro.  [Learn more](https://wickedplugins.com/plugins/wicked-folders).

== Screenshots ==

1. Page folders
2. Easily drag and drop folders to rearrange
3. Drag and drop pages to quickly move pages into folders
4. Bulk move pages to folders
5. Dynamic folders let you quickly filter content by properties like date or author
6. Pro feature: media library folders

== Changelog ==

= 2.4.1 =
* Add callouts for pro version to settings page
* Minor CSS change for pro version

= 2.4.0 =
* Changes to core plugin code to support new features in pro version

= 2.3.6 =
* Load core app Javascript when wp_enqueue_media is called to prevent errors in pro version with front-end editors
* Bug fix for utility function that checks if tax query is an array before manipulating

= 2.3.5 =
* Fix bug regarding folder browser not working for posts

= 2.3.4 =
* Prevent folder pane from being wider than folder browser
* Modify tree view UI to support checkboxes
* Minor bug fixes

= 2.3.3 =
* Minor bug fixes and changes for pro version

= 2.3.2 =
* Fix issue with version numbers

= 2.3.1 =
* Fix indentation level of top-level folders in new folder popup

= 2.3.0 =
* Add dynamic folders feature
* Add settings link to plugin links

= 2.2.2 =
* Update 'tested up to' tag for WordPress 4.8

= 2.2.1 =
* Hide folder tree in media modal when clicking edit link from Advanced Custom Fields image field

= 2.2.0 =
* Add support for posts
* Fix bug regarding folder screen state being overwritten by other folder pages
* Fix minor bug caused by checking for post type in request when saving settings

= 2.1.1 =
* All checked items are now moved when dragging a checked item
* Add "Folders" menu to admin toolbar so that folder actions such as add, edit, etc. can be accessed without having to scroll back up to top of screen

= 2.1.0 =
* Add feature allowing items that have been assigned to a folder to be hidden when viewing the root folder
* Add ability to search items on folder pages

= 2.0.7 =
* Fix version number on WordPress.org

= 2.0.6 =
* Prevent default action when closing folder dialog
* Fix get_terms call for WordPress 4.5 and earlier

= 2.0.5 =
* Change root folder to not be movable
* Replace pseudo element folder icons with span to fix bug regarding move cursor not displaying in IE

= 2.0.4 =
* Various bug fixes

= 2.0.3 =
* Fix display issues in Internet Explorer
* Fix FolderBrowser property not defined as function bug

= 2.0.2 =
* Various bug fixes

= 2.0.1 =
* Enable Backbone emulate HTTP option to support older servers

= 2.0.0 =
* Rebuild folders page as Backbone application
* Various bug fixes

= 1.1.0 =
* Add folder tree navigation to media modal (Wicked Folders Pro)

= 1.0.1 =
* Minor bug fixes

= 1.0.0 =
* Initial release
