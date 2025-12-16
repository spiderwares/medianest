=== Medianest ===
Contributors: cosmicinfosoftware
Requires at least: 5.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
Version: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: media library, media folders, file manager, organize media, image gallery

Organize your WordPress media library with unlimited folders, drag & drop interface, and advanced file management features.

== Description ==

**Medianest** transforms your WordPress Media Library into a powerful, organized file manager. Just like on your computer, you can create folders and subfolders to organize your images, videos, and documents. Use the intuitive drag-and-drop interface to move files, arrange folders, and keep your media library clutter-free.

== External Services ==

This plugin includes the following open-source libraries and third-party integrations:

1. **[PhotoSwipe](https://photoswipe.com/)**  
   JavaScript image gallery for mobile and desktop, used for the lightbox functionality in galleries.  
   **License:** MIT

== Key Features ==

⭐ Drag & Drop Interface: Easily move files into folders and reorder folders with drag and drop.
⭐ Unlimited Folders: Create as many folders and subfolders as you need to organize your content.
⭐ Context Menu: Right-click on folders to quickly Create, Rename, Delete, change color or see details.
⭐ Upload to Folder: Select a specific folder from the dropdown before uploading new files.
⭐ Folder Filtering: Quickly filter your media library to view files in a specific folder.
⭐ Dynamic "Uncategorized" Folder: Automatically find files that haven't been assigned to a folder.
⭐ Breadcrumb Navigation: Navigate through your folder hierarchy easily.
⭐ Resizable Sidebar: Adjust the width of the folder tree sidebar to fit your screen.
⭐ Import/Export: Export your folder structure to CSV and import it to another site.
⭐ SVG Support: Securely upload SVG files with built-in sanitization.
⭐ Gallery Block: Native Gutenberg block to create beautiful galleries from your folders with ease.
⭐ File Size Sorting: Sort your media files by file size to find large items.
⭐ REST API Support: Full REST API endpoints for developers to manage folders and attachments programmatically.

== Pro Features ==

⭐ User Folders: Restrict folder management so users can only see and manage their own folders.
⭐ Subfolder Counting: Option to count files in the current folder plus all its subfolders.
⭐ Advanced Permissions: Fine-grained control over who can create, edit, or delete folders.

== Installation ==

1. Download the plugin ZIP file.
2. Log in to your WordPress dashboard and navigate to Plugins > Add New.
3. Click Upload Plugin, select the ZIP file, and click Install Now.
4. Activate the plugin.
5. Go to Settings > Medianest to configure your preferences, or straight to the Media library to start organizing!

== Usage ==

*  Create Folder: Click the "Add Folder" button or right-click in the sidebar to create a new folder.
*  Move Files: Drag and drop images from the main grid into a folder in the sidebar.
*  Upload: Choose a target folder from the dropdown menu in the "Add New" media screen.
*  Gallery: In the Block Editor, search for "Medianest Gallery" to add a gallery from your folders.

== Frequently Asked Questions ==

= Does this change the file URL? =
No. Medianest uses a virtual folder system (custom taxonomy). Your file URLs (e.g., `.../wp-content/uploads/2023/01/image.jpg`) remain exactly the same. It is safe to install and uninstall without breaking links.

= Can I import my existing folder structure? =
Yes, Medianest allows you to import folder structures via CSV.

= Does it work with page builders? =
Medianest works with the native WordPress Media Library, so it is compatible with most page builders that use the standard media modal (Elementor, Divi, Beaver Builder, etc.).

== Screenshots ==

1. Media Library View - Organized folder tree view in the media library.
2. Context Menu - Right-click options for managing folders.
3. Settings Panel - Configuration options for the plugin.
4. Gallery Block - Using the Medianest Gallery block in Gutenberg.

== Changelog ==

= 1.0.0 =
*   Initial release.
*   Drag and drop folder management.
*   Virtual folder structure using custom taxonomy.
*   Import/Export functionality.
*   Gutenberg Gallery block.

== Upgrade Notice ==

= 1.0.0 =
Initial release of Medianest.
