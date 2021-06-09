<?php
/**
 * simple script to check http code of an url
 */


function fetch(string $url) : array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch,  CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [
        'body' => $response,
        'httpCode' => $httpCode
    ];
}

function parsePlaylist(string $m3u8) : array
{
    // process the string
    $pieces = explode("\n", $m3u8); // make an array out of curl return value
    $pieces = array_map('trim', $pieces); // remove unnecessary space
    $chunklists = array_filter($pieces, function (string $piece) { // pluck out m3u8 urls
       return strtolower(substr($piece, -5)) === '.m3u8';
    });
    return $chunklists;
}

$url = urldecode(filter_input(INPUT_GET, 'url'));
$base_url = substr($url, 0, strrpos($url, '/') + 1);
$response = fetch($url);
// check playlist
if (($response['httpCode'] !== 200) || (strpos($response['body'], 'EXT-X-STREAM-INF') === false)) {
    echo 'false';
    exit;
}

// check chunklists in m3u8 playlist (only one has to be accessible)
foreach (parsePlaylist($response['body']) as $chunklist_url) {
    $url = (strpos($chunklist_url, 'http') === 0) ? $chunklist_url : ($base_url . $chunklist_url);
    $response = fetch($base_url . $chunklist_url);
    if ($response['httpCode'] === 200) {
        echo 'true';
        exit;
    }
}

echo 'false';
exit;
