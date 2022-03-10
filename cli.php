<?php 
include "UploadException.php";
include "GoogleDrive.php";

try {
    if(!isset($argv[1])) throw new Exception("File path is missing. First argument is required. \n", 400);

    if(!isset($argv[2])) throw new Exception("Target directory is missing. Second argument is required. \n", 400);

    $drive = new GoogleDrive();
    $filePath = $argv[1];
    $folderId = $argv[2];

    $drive->targetDirectory = [$folderId];
    if (file_exists($filePath)) 
    {
        $response = $drive->writeFile(file_get_contents($filePath), $filePath);
        if(isset($response["id"])) 
            throw new Exception("File uploaded successfully: {$response["id"]}\n", 200);
        else
            throw new Exception("Unable to upload file. Try again later.", 403);
    }
    else
    {
        throw new Exception("Source file path not found: $filePath\n", 404);
    }
} catch (\Throwable $th) {
    error_log($th->getMessage(), $th->getCode());
}