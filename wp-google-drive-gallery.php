<?php
/*
Plugin Name: WP Google Drive Gallery
Plugin URI: https://github.com/alexsawallich/wp-google-drive-gallery
Description: Plugin for Wordpress to embed Google Drive Folders with images as gallery in a post or page.
Author: Alex Sawallich
Version: 0.1
Author URI: https://alex.tools
*/

// register shortcode with wordpress
add_shortcode('wp_google_drive_gallery', 'wpGoogleDriveGallery');

// define shortcode callback
function wpGoogleDriveGallery($attributes, $content = '', $shortcode = '')
{
    // check if source uri is set
    if (false === isset($attributes['src'])) {
        return '';
    }

    // check if source uri is in correct format
    $src = $attributes['src'];
    if (false === filter_var($src, FILTER_VALIDATE_URL)) {
        return '';
    }

    // check if source uri is from google drive
    $srcParts = parse_url($src);
    if (
        $srcParts['scheme'] != 'https' ||
        $srcParts['host'] != 'drive.google.com' ||
        !preg_match('#^/drive/folders#', $srcParts['path'])
    ) {
        return '';
    }

    // get folder contents


    echo '<pre>';print_r($attributes);echo'</pre>';exit;

}
