<?php 
include "UploadException.php";
include "GoogleDrive.php";

try {
    echo "Welcome to Google Drive Manager via CLI\n";
    echo "=======================================\n\n";

    if(!isset($argv[1])) throw new Exception("ERROR: File path is missing. First argument is required. \n", 400);

    if(!isset($argv[2])) throw new Exception("ERROR: Target directory is missing. Second argument is required. \n", 400);

    $drive = new GoogleDrive();
    $filePath = $argv[1];
    $folderId = $argv[2];

    $drive->targetDirectory = [$folderId];
    if (file_exists($filePath)) 
    {
        $fileSize = number_format(filesize($filePath) / 1048576, 2);

        echo "File found: $filePath($fileSize)MB\n";

        $response = $drive->writeFile(file_get_contents($filePath), $filePath);
        if(isset($response["id"])) 
            throw new Exception("SUCCESS: File uploaded successfully: {$response["id"]}\n", 200);
        else
            throw new Exception("ERROR: Unable to upload file. Try again later.", 403);
    }
    else
    {
        throw new Exception("ERROR: Source file path not found: $filePath\n", 404);
    }
} catch (\Throwable $th) {
    error_log($th->getMessage(), $th->getCode());
}