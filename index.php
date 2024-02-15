<?php 
include "UploadException.php";
include "GoogleDrive.php";
$drive = new GoogleDrive();

// About Service Account User
$about = $drive->getAbout();
// Get Folder ID
$folderId = (isset($_GET['folders']) && $_GET['folders'] != '') ? $_GET['folders'] : $drive->targetDirectory[0];
$folderInfo = $drive->getItem($folderId);
// Upload File
if(isset($_FILES['file_upload'])) 
{
    $folderName = $folderInfo->getName();
    if ($_FILES['file_upload']['error'] === 0) {
        $drive->uploadFile($_FILES['file_upload']['tmp_name'], "{$folderName}/{$_FILES['file_upload']['name']}");
    } else {
        die(new UploadException($_FILES['file_upload']['error']));
    }
}
// Add new folder
if(isset($_POST['new_folder_name']) && $_POST['new_folder_name'] != '')
{
    try {
        $drive->CreateFolder($_POST['new_folder_name'], [$folderId]);
    } catch (\Exception $e) {
        die (new RuntimeException($e->getMessage()));
    }
}
// Delete folder
if(isset($_GET['deleteFolderId']) && $_GET['deleteFolderId'] != '')
{
    try {
        $drive->deleteItem($_GET['deleteFolderId']);
        header("Location: index.php?folders={$_GET['folders']}");
    } catch (\Exception $e) {
        die (new RuntimeException($e->getMessage()));
    }
}
// Delete multiple folders// Get the JSON string from the request body
if(isset($_POST['folderIds']) && $_POST['folderIds'] != '')
{
    try {
        $folderIds = $_POST['folderIds'];
        foreach ($folderIds as $key => $folderId) {
            $drive->deleteItem($folderId);
        }
    echo json_encode([
        "flag" => "success",
        "msg" => "Deleted items successfully"
    ]);
    exit;
    } catch (\Exception $e) {
        die (new RuntimeException($e->getMessage()));
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/icons/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/icons/ionicons.min.css" />
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/jquery.slim.min.js"></script>
    <script src="assets/popper.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function action(folderId, TYPE) {
            switch (TYPE) {
                case 'application/vnd.google-apps.folder':
                    window.location.href = `index.php?folders=${folderId}`;
                    break;
            
                default:
                    break;
            }
        }
        function deleteFolder(ID, folderId) {
            if(confirm("Are you sure?"))
            {
                window.location.href = `index.php?folders=${folderId}&deleteFolderId=${ID}`;                
            }
        }

        // Function to serialize the array into a query string
        function serializeArray(arr) {
            return arr.map(function(id) {
                return 'folderIds[]=' + encodeURIComponent(id);
            }).join('&');
        }

        function removeFolders() {
            if(confirm("Are you sure?")) {
                const selections = document.querySelectorAll('[name="selection[]"]:checked');
                const folderIds = [];
                for (const item of selections) {
                    folderIds.push(item.value);
                }
    
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '/google-drive-file-manager/index.php', true);
    
                // Send the proper header information along with the request
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    
                xhr.onreadystatechange = () => {
                    // Call a function when the state changes.
                    if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                        // Request finished. Do processing here.
                        console.log(xhr.responseText)
                        window.location.reload()
                    } else {
                        console.error('Request failed with status:', xhr.status);
                    }
                };
    
                // Send the request with the data
                xhr.send(serializeArray(folderIds));
    
            }
        }
    </script>
    <title>File Manager</title>
</head>
<body>
    <div class="container flex-grow-1 light-style container-p-y">
        <div class="container-m-nx container-m-ny bg-lightest mb-3">
            <ol class="breadcrumb text-big container-p-x py-3 m-0">
                <li class="breadcrumb-item">
                    <a href="javascript:void(0)">My Drive</a>
                </li>
                <?php 
                if(count($drive->listParents($folderId)) > 0) {
                    foreach (array_reverse($drive->listParents($folderId)) as $parent) { 
                ?><li class="breadcrumb-item">
                    <a href="index.php?folders=<?= $parent['id'] ?>"><?= $parent['name'] ?></a>
                </li><?php 
                    } 
                } 
                ?><li class="breadcrumb-item active"><?= $folderInfo->getName(); ?></li>
            </ol>
            
            <hr class="m-0" />
            <div class="file-manager-actions container-p-x py-2">
                <div>
                    <form action="" method="post" enctype="multipart/form-data">
                        <label class="btn btn-primary mr-2 mb-0">
                            <input type="file" name="file_upload" class="d-none" onchange="this.form.submit()">
                            <i class="ion ion-md-cloud-upload"></i>&nbsp; Upload
                        </label>
                    </form>
                    
                    <div class="mr-2">
                        <button type="button" class="btn btn-secondary icon-btn" data-toggle="dropdown"><i class="ion ion-md-folder"></i></button>
                        <div class="dropdown-menu px-2">
                            <form action="" method="post">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Name</span>
                                    </div>
                                    <input type="text" class="form-control" name="new_folder_name">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-success icon-btn" >OK</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="btn-group mr-2">
                        <button type="button" class="btn btn-default md-btn-flat dropdown-toggle px-2" data-toggle="dropdown"><i class="ion ion-ios-settings"></i></button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0)">Move</a>
                            <a class="dropdown-item" href="javascript:void(0)">Copy</a>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="removeFolders()">Remove</a>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-default icon-btn md-btn-flat active"> <input type="radio" name="file-manager-view" value="file-manager-col-view" checked="" /> <span class="ion ion-md-apps"></span> </label>
                        <label class="btn btn-default icon-btn md-btn-flat"> <input type="radio" name="file-manager-view" value="file-manager-row-view" /> <span class="ion ion-md-menu"></span> </label>
                        
                        <div class="btn-group mr-2">
                            <button type="button" class="btn btn-default btn-sm rounded-pill icon-btn borderless md-btn-flat hide-arrow dropdown-toggle" data-toggle="dropdown"><i class="ion ion-md-help"></i></button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li class="dropdown-item">Current User: <?= $about["user"]->displayName ?></li>
                                <li class="dropdown-item">Max Upload Size: <?= $drive->formatBytes($about["maxUploadSize"]) ?></li>
                                <li class="dropdown-item">Total quota : <?= $drive->formatBytes($about["storageQuota"]->limit) ?></li>
                                <li class="dropdown-item">Used quota : <?= $drive->formatBytes($about["storageQuota"]->usageInDrive) ?></li>
                                <li class="dropdown-item">Usage In Drive Trash : <?= $drive->formatBytes($about["storageQuota"]->usageInDrive) ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr class="m-0" />
        </div>
        
        <div class="file-manager-container file-manager-col-view">
            <div class="file-manager-row-header">
                <div class="file-item-name pb-2">Filename</div>
                <div class="file-item-changed pb-2">Changed</div>
            </div>
            <?php if(is_array($folderInfo->getParents()) && count($folderInfo->getParents()) > 0) { ?>
            <div class="file-item" ondblclick="action('<?= $folderInfo->getParents()[0] ?>', 'application/vnd.google-apps.folder');">
                <div class="file-item-icon file-item-level-up fas fa-level-up-alt text-secondary"></div>
                <a href="javascript:void(0)" class="file-item-name"> .. </a>
            </div>
            <?php } ?>
            <?php 
            $results = $drive->listFiles([
                "q" => "'{$folderId}' in parents"
            ]);
            if (count($results->getFiles()) == 0) {
                print '<img src="assets/empty-folder-icon.png" style="width: 500px; position: absolute; left: 30%;" />';
            } else {
                foreach ($results->getFiles() as $k => $file) {
            ?>
            <div class="file-item" ondblclick="action('<?= $file->getId() ?>', '<?= $file->getMimeType() ?>');">
                <div class="file-item-select-bg bg-primary"></div>
                <label class="file-item-checkbox custom-control custom-checkbox">
                    <input type="checkbox" name="selection[]" class="custom-control-input" value="<?= $file->getId() ?>" />
                    <span class="custom-control-label"></span>
                </label>
                <?php 
                if($file->getIconLink() != '')
                {
                    echo '<img src="'.str_replace('16', '64', $file->getIconLink()).'" />';
                }
                else
                {
                    switch ($file->getMimeType()) {
                        case 'application/vnd.google-apps.folder':
                            echo '<div class="file-item-icon far fa-folder text-secondary"></div>';
                            break;
                        
                        default:
                            echo '<div class="file-item-icon far fa-file-alt text-secondary"></div>';
                            break;
                    }
                }
                ?>
                <a href="javascript:void(0)" class="file-item-name">
                    <?= $file->getName() ?>
                </a>
                <div class="file-item-changed">02/13/2018</div>
                <div class="file-item-actions btn-group">
                    <button type="button" class="btn btn-default btn-sm rounded-pill icon-btn borderless md-btn-flat hide-arrow dropdown-toggle" data-toggle="dropdown"><i class="ion ion-ios-more"></i></button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="javascript:void(0)">Rename</a>
                        <a class="dropdown-item" href="javascript:void(0)">Move</a>
                        <a class="dropdown-item" href="javascript:void(0)">Copy</a>
                        <a class="dropdown-item" href="javascript:void(0)" onclick="deleteFolder('<?= $file->getId() ?>', '<?= $folderId ?>')">Delete</a>
                    </div>
                </div>
            </div>
            <?php 
                }
            }
            ?>
        </div>
    </div>
</body>
</html>
