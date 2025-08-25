# Neat Media Tags

A lightweight and simple WordPress plugin to manage tags for media files.

## Description

Media Tags is a lightweight WordPress plugin that enhances media management by allowing you to add tags to media files. Organize your media library efficiently with custom tags, filter media by tags in the admin interface, and showcase tagged media in front-end galleries using a powerful shortcode. Perfect for photographers, bloggers, and site administrators who need better control over their media assets.

**Key Features:**
- Add and manage tags for media files in the WordPress media library.
- Filter media by tags in the admin media library with clickable tag links.
- Bulk edit tags for multiple media files at once.
- Autocomplete tag suggestions for easy tag management.
- Display tagged media in galleries using the `[media_tag_gallery]` shortcode with customizable options:
  - Filter by single or multiple tags with OR/AND logic.
  - Sort media by date, title, or random order.
  - Control image size, number of columns, and link behavior.
- Seamless integration with WordPress core media functionality.

Whether you're organizing a large media library or creating dynamic galleries, Media Tags makes it simple and efficient.

## Installation

1. Upload the `neat-media-tags` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the Media Library to start adding tags to your media files.
4. Use the `[media_tag_gallery]` shortcode in your posts or pages to display tagged media galleries.

Alternatively:

1. Clone this repository into your `/wp-content/plugins/neat-media-tags/` directory.
2. Activate the "Neat Media Tags" plugin from the WordPress plugins menu.

## Usage

1. **Tagging Media**:
   - In the Media Library or when editing a media file, use the "Media Tags" field to add tags (comma-separated).
   - Use bulk edit in the Media Library to tag multiple files at once.

2. **Filtering in Media Library**:
   - In the Media Library, click on a tag in the "Media Tags" column to filter media by that tag.

3. **Displaying Galleries**:
   - Use the `[media_tag_gallery]` shortcode with the following attributes:
     - `tag`: Comma-separated list of tag slugs (e.g., `tag="landscape,portrait"`).
     - `logic`: Filter logic, either `OR` or `AND` (default: `OR`).
     - `size`: Image size (e.g., `thumbnail`, `medium`, `large`, `full`; default: `medium`).
     - `columns`: Number of columns in the gallery (default: `3`).
     - `link`: Link type (`file` for direct image, `attachment` for attachment page, or empty for no link; default: `file`).
     - `orderby`: Sort order (`date`, `title`, `RAND` for random; default: `date`).
     - `order`: Sort direction (`ASC` or `DESC`; default: `DESC`; ignored if `orderby="RAND"`).
     - `limit`: Maximum number of images to display (default: `0` for all).

   **Example Shortcodes**:

[media_tag_gallery tag="landscape" size="medium" columns="3" link="file" orderby="date" order="DESC" limit="10"]

Displays up to 10 media items tagged "landscape", sorted by date (descending).

[media_tag_gallery tag="landscape,portrait" logic="AND" size="large" columns="4" orderby="RAND" limit="8"]

Displays up to 8 media items tagged with both "landscape" and "portrait", in random order.

## Frequently Asked Questions

1. How do I add tags to media files?
In the Media Library, edit a media file. Enter tags in the "Media Tags" field, separated by commas. Tags will autocomplete as you type.
2. How can I filter media by tags in the Media Library?
In the Media Library list view, click on a tag name in the "Media Tags" column to filter media by that specific tag.
3. What does the `logic` attribute do in the shortcode?
The `logic` attribute (`OR` or `AND`) determines how multiple tags are handled:
- `OR`: Displays media with any of the specified tags.
- `AND`: Displays media that have all specified tags.
4. Can I display media in random order?
Yes, use `orderby="RAND"` in the shortcode to display media in a random order. For example: `[media_tag_gallery tag="landscape" orderby="RAND"]`.
5. Why don't I see my tags in the gallery?
Ensure the tag slugs are correct and that media files are tagged with them. Also, check if the `logic` attribute is set appropriately (e.g., `AND` requires media to have all tags).

## Changelog

= 1.2 =
* Added support for random ordering in the `[media_tag_gallery]` shortcode using `orderby="RAND"`.
* Release Date: August 7, 2025

= 1.1 =
* Fixed media library tag filtering to correctly display media when clicking tag links.
* Added OR/AND logic to the `[media_tag_gallery]` shortcode with the `logic` attribute.

= 1.0 =
* Initial release with media tagging, bulk editing, autocomplete, and gallery shortcode.