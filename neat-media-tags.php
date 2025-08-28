<?php
/*
Plugin Name:       Neat Media Tags
Plugin URI:        https://neatwp.com/c/neat-releases/neat-media-tags/
Description:       A neat plugin to add tagging functionality for media files in WordPress
Version:           1.2.0
Requires at least: 5.2
Requires PHP:      7.2
Author:            Neat WP
Author URI:        https://neatwp.com/
License:           GPL v2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       neat-media-tags
Domain Path:       /languages
GitHub Plugin URI: https://github.com/neatwp/neat-media-tags
Update URI:        https://github.com/neatwp/neat-media-tags
*/

// Prevent direct access
defined('ABSPATH') || exit;

// Include the main plugin class
require_once __DIR__ . '/class-neat-media-tags.php';

// Initialize the plugin
new NeatWP\MediaTags\NeatMediaTagsPlugin();