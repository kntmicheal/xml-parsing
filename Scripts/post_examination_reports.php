<?php

set_time_limit(0); // run without timeout limit

/*
|--------------------------------------------------------------------------
| Set up file structure stuff properly
|--------------------------------------------------------------------------
|
*/


// Local Rsync'd Location
$files = glob('/DSD/Reports/Examinations/*.{xml, XML}', GLOB_BRACE);

/*
 * What we are doing here is copying the files from the location after Rsyncing.
 * Since PHP is being called directly after the Rsync, there may be issues with
 * the PHP excecutable running over itself, causing double entries.
 * This will copy all of the files that Rsync copied over, and place them into a
 * temporary directory. We iterate over them in the temporary directory to keep them
 * separate from any new entries that may be created while this process in ongoing.
 *
 */

// Set up the temp directory
$tempDirName = uniqid('temp-');
mkdir('/DSD/Archive/' . $tempDirName);
$tempPath = '/DSD/Archive/' . $tempDirName;


foreach($files as $file) {
  $filename = explode('/', $file);
  $filename = end($filename);
  rename($file, $tempPath . "/" . $filename);
}

// Point the files variable to the temp directory instead of the base local.
$files = glob($tempPath . '/*.{xml, XML}', GLOB_BRACE);

foreach($files as $file) {
  echo "File: " . $file . "\n";
  $filename = explode('/', $file);
  $filename = end($filename);
  echo "Filename:"  . $filename . "\n";

  /*
  |--------------------------------------------------------------------------
  | Load XML file and set the attributes that need to be sent
  |--------------------------------------------------------------------------
  |
  */

  // XML file to be read
  $xml_file = simplexml_load_file($file);

  // Make a basic class to store the information in a tidy manner
  $drive = new stdClass();

  // Set the attributes
  $drive->mount             = explode(' ', $xml_file->result->disk->bay)[0];
  $drive->bay               = explode(' ', $xml_file->result->disk->bay)[1];
  $drive->model             = (string)$xml_file->result->disk->device->attributes()->product;
  $drive->revision          = (string)$xml_file->result->disk->device->attributes()->revision;
  $drive->serial            = (string)$xml_file->result->disk->device->attributes()->serial;
  $drive->type              = (string)$xml_file->result->disk->device->attributes()->type;
  $drive->size              = (string)$xml_file->result->disk->device->attributes()->size;
  $drive->percent_examined  = (string)$xml_file->attributes->method->attributes()->read_percent;
  $drive->sectors_checked   = (string)$xml_file->result->disk->checked;
  $drive->errors            = (string)$xml_file->result->disk->errors;
  $drive->status            = (string)$xml_file->result->disk->status;
  $drive->graded            = ((string)$xml_file->attributes->grades->attributes()->assign == "Yes") ? 1 : 0;
  $drive->grade             = (string)$xml_file->result->disk->grade;
  $drive->started           = (string)$xml_file->result->disk->started;
  $drive->elapsed           = (string)$xml_file->result->disk->elapsed;


  /*
  |--------------------------------------------------------------------------
  | cURL set up and configuration
  |--------------------------------------------------------------------------
  |
  */

  // Set the URL and intialize cURL
  $service_url = 'http://10.5.45.180/dev/dsd/api/examinations';
  $curl = curl_init($service_url);

  // Cast the Drive object to an array before being sent to the server
  $curl_post_data = (array)$drive;

  // Set cURL options
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);

  // Send the cURL request and assign the response to a variable. Also grab the HTTP code returned
  $curl_response = curl_exec($curl);
  $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

  // Finally, close the cURL request
  curl_close($curl);

  /*
  |--------------------------------------------------------------------------
  | Error handling
  |--------------------------------------------------------------------------
  |
  */


  if($httpcode != 200) {
    echo "Oops, something went wrong! \n";
    echo "Error Code: " . $httpcode . "\n";
    $moved = rename($file, '/DSD/Reports/Examinations/'. $filename);
    if($moved) {
      echo "Moved the file back to the original location! \n";
    }
  } else {
    echo "Success! HTTP response code is:  " . $httpcode . "\n";
    $moved = rename($file, '/DSD/Archive/Reports/Examinations/' . $filename);
    if($moved) {
      echo "Moved the file to the archive! \n";
    }
  }
}

@unlink($tempPath . "/" . ".DS_Store");
rmdir('/DSD/Archive/' . $tempDirName);

?>
