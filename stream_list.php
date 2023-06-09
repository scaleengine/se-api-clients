<?php
/******************************************************************************
*******************************************************************************
**                           stream_list.php                                 **
** Example of how to get info for a stream using the ScaleEngine API.        **
** Other objects can be listed using the same method, by calling the         **
** appropriate endpoint.                                                     **
** Please consult the documentation at https://cp.scaleengine.net/api_docs   **
** Written by Andrew Fengler                                                 **
*******************************************************************************
** Copyright (c) 2023, Andrew Fengler <andrew.fengler@scaleengine.com>       **
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
** V. 1.0.0: Initial release                                        20230609 **
******************************************************************************/

// Change these variables to use your credentials

// ScaleEngine CDN ID
$cdn_id = "#CDN-ID#";
// API Private Key
$api_private = "#API-PRIVATE-KEY#";
// Replace "username" with account username to make up the sestore url
$sestore_url = "username-sestore.secdn.net";

// Variables specific to this call, change these as needed
$stream_name = 'test_stream';
$app_name = 'username-origin';

// Internal variables, nothing should need to be changed here:

$api_url = 'https://api.scaleengine.net';
$timeout = 180;
$endpoint = 'streams';
$object = $stream_name; // use stream_name as object to get
$method = 'GET'; // Use get method to list


// Perform the call
$ch = curl_init();
$request = "${api_url}/v2/${endpoint}/${object}";
curl_setopt($ch, CURLOPT_URL, $request);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_USERPWD, "{$cdn_id}:{$api_private}");
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
	die('Got error from ScaleEngine API: '.$arr_info['http_code']);
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
