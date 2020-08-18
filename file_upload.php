<?php
/******************************************************************************
*******************************************************************************
**                           file_upload.php                                 **
** Sample file upload script in php. Uploads a file from disk to users       **
** designated sestore. The destination file path and name is set using the   **
** dest_file_name.                                                           **
** Please consult the documentation at https://cp.scaleengine.net/docs       **
** Written by Lloyd Waddell                                                  **
*******************************************************************************
** Copyright (c) 2016, Lloyd Waddell <lloyd.waddell@scaleengine.com>         **
**                                                                           **
** Permission to use, copy, modify, and/or distribute this software for any  **
** purpose with or without fee is hereby granted, provided that the above    **
** copyright notice and this permission notice appear in all copies.         **
**                                                                           **
** THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES  **
** WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF          **
** MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR   **
** ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES    **
** WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN     **
** ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF   **
** OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE             **
*******************************************************************************
** V. 1.0.0: Initial release                                        20200818 **
******************************************************************************/

// ScaleEngine CDN ID
$cdn_id = "#CDN-ID#";
// API Private Key
$api_private = "#API-PRIVATE-KEY#";
// Replace "username" with account username to make up the sestore url
$sestore_url = "username-sestore.secdn.net";
// Curl timeout
$timeout = 180;

// File to upload
$file = 'my-file.txt';
// Destination filepath and name
$dest_file_name = 'my-file.txt';

$curl_file = curl_file_create($file, mime_content_type($file), basename($file));
$data = array(
	'filename[]' => $curl_file,
);
$ch = curl_init();

$request = "{$sestore_url}/v1/upload/{$dest_file_name}";
curl_setopt($ch, CURLOPT_URL, $request);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));

curl_setopt($ch, CURLOPT_USERPWD, "{$cdn_id}:{$api_private}");
curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Real-IP: {$_SERVER['REMOTE_ADDR']}"));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

$response = curl_exec($ch); //Execute the API Call

// API Call failed
if (!$response)
{
	die('Failed to connect to ScaleEngine API: '.curl_error($ch));
}
$arr_response = json_decode($response, true);

// HTTP codes over 299 are errors
$arr_info = curl_getinfo($ch);
if ($arr_info['http_code'] > 299)
{
	print_r($arr_response);
	exit();
}

// TODO Handle API Response
if ($arr_response)
{
	print_r($arr_response);
}
else
{
	die("Unknown API Error: {$response}");
}
