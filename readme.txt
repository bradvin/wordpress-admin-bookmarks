=== Admin Bookmarks ===
Contributors: bradvin
Tags: admin, bookmark, favorites
Requires at least: 3.8
Tested up to: 6.8
Stable tag: 2.0.0

Bookmark your favorite posts, pages or custom post types within the WordPress admin

== Description ==

This plugin allows you to easily bookmark/favorite posts, pages and custom post types. When you 'star' a post a shortcut link will appear in the admin menu.
Use this plugin if you constantly find yourself editing the same posts or pages, and are sick of showing the list of posts first, then finding the post, then clicking edit.

*Steps to use*

1. Go to Pages
2. Click the Star icon on a page.
3. Your page is now bookmarked!
4. Repeat for other pages you always edit.
5. Save time!

*Plugin Features*

* Intuitive interface to bookmark posts using a star icon.
* Zero settings! Activate and enjoy.
* Works with posts, pages and any custom post types.
* Dashboard widget listing all bookmarks.
* Setting a bookmark is done in realtime with no page reload.
* Bookmarks are added to the admin menu for quick access under a "Bookmarks" menu item.
* Adds a Bookmarks view/filter to the listing pages.
* Bookmark shortcuts are also grouped by post type within the WordPress admin bar.
* Optional "Bookmark Title" quick edit field to customize menu labels

*Filters*

* `admin_bookmarks_post_types` - Filter to scope bookmarks to specific post types
* `admin_bookmarks_untitled_label` - Filter to customize the untitled label
* `admin_bookmark_feature-dashboard_widget` - Filter to disable the dashboard widget
* `admin_bookmark_feature-quick-edit` - Filter to disable the quick edit feature
* `admin_bookmark_feature-view` - Filter to disable the view feature
* `admin_bookmark_feature-admin-bar` - Filter to disable the admin bar feature

To disable a feature, add a small bit of custom code to your theme's `functions.php` file:

`add_filter( 'admin_bookmark_feature-dashboard_widget', '__return_false' );`

== Installation ==

1. Upload the plugin folder 'admin-bookmarks' to your `/wp-content/plugins/` folder
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Click on the 'Pages' menu item to view the list of pages and start bookmarking!

== Screenshots ==

1. New bookmark column
2. Bookmarks added to admin menu
3. Bookmarks in Admin Bar
4. Bookmarks View
5. Dashboard bookmarks widget
6. Override title used for bookmark

== Changelog ==

= 2.0.0 =
* Updated to work with WordPress 6.0
* Removed dependency on jQuery
* Code cleanup for modern WordPress plugin development
* Added `admin_bookmarks_post_types` filter to scope bookmarks to specific post types
* Added optional Quick Edit "Bookmark Title" field (stored as `_bookmark_title`)
* Added Bookmarks view to the listing pages.
* Added WordPress admin bar integration, grouped by post type with direct edit links

= 1.0.0 =
* Initial Release. First version. Written from the ground up. Based on my outdated "Post Admin Shortcuts" plugin.

== Frequently Asked Questions ==

= How do I use this plugin? =
View all your pages or posts and click on the star icon to bookmark one of them.

= What is a bookmark? =
A bookmark is a shortcut to a post, page or custom post type. It allows you to quickly access the post, page or custom post type in the admin menu.
Think of it like a favorite link to a post, page or custom post type.

= Can I bookmark custom post types? =
Yes! The plugin works with any custom post type you have registered.

= Can I bookmark custom taxonomies? =
No. The plugin only works with posts, pages and custom post types.

= How do I remove a bookmark? =
Click on the star icon next to the post, page or custom post type you want to remove.

= Can I control which post types expose bookmarks? =
Yes! Use the `admin_bookmarks_post_types` filter to scope bookmarks to specific post types.

