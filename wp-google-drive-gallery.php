<?php
/*
Plugin Name: WP Google Drive Gallery
Plugin URI: https://github.com/alexsawallich/wp-google-drive-gallery
Description: Plugin for Wordpress to embed Google Drive Folders with images as gallery in a post or page.
Author: Alex Sawallich
Version: 0.1
Author URI: https://alex.tools
*/

// require Googles PHP Client
require_once __DIR__ . '/google-api-php-client-2.2.2/vendor/autoload.php';

// register shortcode with wordpress
add_shortcode('wp_google_drive_gallery', 'wpGoogleDriveGallery');

// define shortcode callback
function wpGoogleDriveGallery($attributes, $content = '', $shortcode = '')
{
    // check if source folder is set
    if (false === isset($attributes['folder'])) {
        return '';
    }

    // get client
    $client = getClient();

    $service = new Google_Service_Drive($client);

    // check if folder exists
    try {
        $folder = $service->files->get($attributes['folder']);
        /* @var $folder Google_Service_Drive_DriveFile */
    } catch (Google_Service_Exception $e) {
        return '';
    }

    // get all files within folder
    $filters = '\'' . $attributes['folder'] . '\' in parents';
    $filters .= ' and (mimeType = \'image/jpeg\' or mimeType = \'image/png\' or mimeType = \'image/gif\')';
    $fields = 'files(id,name,webViewLink,thumbnailLink)';
    $files = $service->files->listFiles(['q' => $filters, 'pageSize' => 1000, 'fields' => $fields]);
    /* @var $files Google_Service_Drive_FileList */

    // are there any files?
    if (0 >= count($files)) {
        return '';
    }

    $markup = '<table>';
    foreach ($files->getFiles() as $file) { /* @var $file Google_Service_Drive_DriveFile */
        $markup .= '<tr>
            <td><a href="https://drive.google.com/uc?export=view&id=' . $file->getId() . '"><img src="' . $file->getThumbnailLink() . '" alt=""></a></td>
            <td>' . $file->getName() . '</td>
        </tr>';
    }

    return $markup . '</table>';
}

function getClient()
{
    $client = new Google_Client();
    $client->setAccessType('offline');
    $client->setApprovalPrompt('force');
    $client->setScopes(Google_Service_Drive::DRIVE);
    $client->setAuthConfig(__DIR__ . '/data/credentials.json');
    $client->setRedirectUri('http://blog.alexsawallich.de/abenteuer-schweden-2018/');

    // Load previously authorized credentials from a file.
    $credentialsPath = __DIR__ . '/data/token.json';
    if (file_exists($credentialsPath)) {
        $accessToken = /*json_decode(*/file_get_contents($credentialsPath);//, true);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        header('Location: ' . $authUrl);
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    #echo '<pre>';print_r($accessToken);echo '</pre>';

    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    return $client;
}
