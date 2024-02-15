<?php 
include_once __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Http\MediaFileUpload;

class GoogleDrive {
	
	private $client = false;
	private $service = false;
	private $file = false;
	public $targetDirectory = [
		'1j-TDTsiJQHSU3QBO1YWW-8hC67rlAGp9'
	];
	
	public function __construct() {
		$this->client = new Client();
		$this->service = new Drive($this->client);
		$this->file = new DriveFile();
	
		$this->setConfig();
	}

	public function getAbout()
	{
		try {
			return $this->service->about->get(['fields' => '*']);
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}
	
	private function setConfig() {
		try {
			$this->client->setAuthConfig(__DIR__ . '/credentials.json');
			$this->client->setApplicationName("astram-backup");
			$this->client->setScopes(Drive::DRIVE);
		} catch (\Throwable $th) {
			throw $th;
		}		
	}

	public function listDrives(Array $query = [])
	{
		try {
			$drives = $this->service->drives->listDrives($query);

			return $drives;
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}

	public function listFiles(Array $query)
	{
		try {
			$query['fields'] = '*';
			$files = $this->service->files->listFiles($query);

			return $files;
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}

	public function getItem(String $itemId)
	{
		return $this->service->files->get($itemId, [
			'fields' => '*'
		]);
	}

	public function listParents(String $itemId, Array $parents = [])
	{
		$folder = $this->getItem($itemId);
		if(is_array($folder->getParents()) && count($folder->getParents()) > 0)
		{
			$pId = $folder->getParents()[0];
			array_push($parents, [
				"id" => $pId,
				"name" => $this->getItem($pId)->getName()
			]);

			$parents = $this->listParents($pId, $parents);
		}
		
		return $parents;
	}
	
	public function uploadFile(string $fileData, string $fileName)
	{
		try {
			
			$fileDetails = $this->CreateFolderTrail($fileName, $this->targetDirectory);
			$fileDetails['mime'] = $this->getExtensionMimeType($fileName);
			$fileDetails['fileData'] = $fileData;
			
			if($this->isLargeFile($fileData))
				$this->UploadLargeFile($fileDetails);
			else 
				$this->UploadSimpleFile($fileDetails);
						
		} catch (\Throwable $th) {
			throw $th;
		}
	}
	
	public function writeFile(string $fileData, string $fileName)
	{
		try {
			
			$fileDetails = $this->CreateFolderTrail($fileName, $this->targetDirectory);
			$fileDetails['mime'] = $this->getExtensionMimeType($fileName);
			$fileDetails['fileData'] = $fileData;
			
			$this->file->setParents($fileDetails['targetDirectory']);
			$this->file->setName($fileDetails['fileName']);
			$this->file->setMimeType($fileDetails['mime']);
			$result = $this->service->files->create($this->file, [
				'data' => $fileDetails['fileData'],
				'mimeType' => $fileDetails['mime'],
				'uploadType' => 'multipart'
			]);
			
			return $result;
						
		} catch (\Throwable $th) {
			throw $th;
		}
	}
	
	private function CreateFolderTrail(String $fileName, Array $targetDirectory)
	{
		$folderTrail = explode('/', $fileName);
		for ($i=0; $i < count($folderTrail) - 1; $i++) {
			$folderName = $folderTrail[$i];
			// Create Sub Folder
			$folder = $this->CreateFolder($folderName, $targetDirectory);
			// Add the sub folder to target directory trail
			$targetDirectory = [$folder->getId()];
		}
		
		return ['targetDirectory' => $targetDirectory, 'fileName' => end($folderTrail)];
	}
	
	public function CreateFolder(String $folderName, Array $targetDirectory)
	{
		$this->file->setParents($targetDirectory);
		$listFolder = $this->GetFolder($folderName, $targetDirectory[0]);
		if(count($listFolder) == 0)
		{
			$this->file->setName($folderName);
			$this->file->setMimeType('application/vnd.google-apps.folder');
			$folder = $this->service->files->create($this->file, ['mimeType' => 'application/vnd.google-apps.folder']);
		}
		else 
		{
			$folder = $listFolder[0];
		}
		
		return $folder;
	}
	
	private function GetFolder(String $folderName, String $parentFolderId)
	{
		$file = $this->service->files->listFiles([
			"q" => "'{$parentFolderId}' in parents and name = '$folderName' and mimeType = 'application/vnd.google-apps.folder'"
		]);
		
		return $file->getFiles();
	}
	
	private function UploadSimpleFile(Array $fileDetails)
	{
		try {
			$this->file->setParents($fileDetails['targetDirectory']);
			$this->file->setName($fileDetails['fileName']);
			$this->file->setMimeType($fileDetails['mime']);
			$result = $this->service->files->create($this->file, [
				'data' => file_get_contents($fileDetails['fileData']),
				'mimeType' => $fileDetails['mime'],
				'uploadType' => 'multipart'
			]);
			
			return $result;
		} catch (\Throwable $th) {
			throw $th;
		}		
	}
	
	private function UploadLargeFile(Array $fileDetails)
	{
		$status = false;
		$response = "";
		$request = NULL;
		$chunkSizeBytes = 1 * 1024 * 1024;

		try {
			
			// Call the API with the media upload, defer so it doesn't immediately return.
			if($this->client){
				$this->client->setDefer(TRUE);
			}
			
			$this->file->setParents($fileDetails['targetDirectory']);
			$this->file->setName($fileDetails['fileName']);
			$this->file->setMimeType($fileDetails['mime']);
			
			$request = $this->service->files->create($this->file);

			if($request) {
				// Create a media file upload to represent our upload process.
				$media = new MediaFileUpload(
					$this->client,
					$request,
					$fileDetails['mime'],
					null,
					true,
					$chunkSizeBytes
				);
				$media->setFileSize(filesize($fileDetails['fileData']));
				
				// Upload the various chunks. $status will be false until the process is complete.
				$handle = fopen($fileDetails['fileData'], "rb");
				while (!$status && !feof($handle)) {
					$chunk = fread($handle, $chunkSizeBytes);
					$status = $media->nextChunk($chunk);
				}
				
				// The final value of $status will be the data from the API for the object that has been uploaded.
				$result = false;
				if($status != false) {
					$result = $status;
				}
				
				fclose($handle);

			} else {
				throw new Exception("ERROR: NO REQUEST, ABORTING.", 403);
			}
		} catch(\Google_Service_Exception $e) {
			$response = $e->getMessage();
			throw new Exception("SERVICE EXCEPTION: $response", $e->getCode());
		} catch(\Google_Exception $e) {
			$response = $e->getMessage();
			throw new Exception("GOOGLE EXCEPTION: $response", $e->getCode());
		} finally {
			$response = "RELEASED CLIENT";
			// Reset to the client to execute requests immediately in the future.
			if($this->client){
				$this->client->setDefer(FALSE);
			}
			throw new Exception("SUCCESS: $response", 200);
		}

		return([$status,$response,$result]);
	}
	
	private function isLargeFile(String $fileData)
	{
		try {
			if(number_format(filesize($fileData) / 1048576, 2) > 20) return true;
			
			return false;
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function deleteItem(String $id)
	{
		try {
			$this->service->files->delete($id);
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	function getExtensionMimeType($file, $search = null)
	{

		$mimeTypes = $this->getMime();
		if($search !== null)
		{
			return array_search($file, $mimeTypes);
		}
		else
		{
			$extension = $this->getFileExtension($file);
			return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : NULL;
		}
	}

	function getMime()
	{
		return array_reverse([
			"323"       => "text/h323",
			"acx"       => "application/internet-property-stream",
			"ai"        => "application/postscript",
			"aif"       => "audio/x-aiff",
			"aifc"      => "audio/x-aiff",
			"aiff"      => "audio/x-aiff",
			"asf"       => "video/x-ms-asf",
			"asr"       => "video/x-ms-asf",
			"asx"       => "video/x-ms-asf",
			"au"        => "audio/basic",
			"avi"       => "video/x-msvideo",
			"axs"       => "application/olescript",
			"bas"       => "text/plain",
			"bcpio"     => "application/x-bcpio",
			"bin"       => "application/octet-stream",
			"bmp"       => "image/bmp",
			"c"         => "text/plain",
			"cat"       => "application/vnd.ms-pkiseccat",
			"cdf"       => "application/x-cdf",
			"cer"       => "application/x-x509-ca-cert",
			"class"     => "application/octet-stream",
			"clp"       => "application/x-msclip",
			"cmx"       => "image/x-cmx",
			"cod"       => "image/cis-cod",
			"cpio"      => "application/x-cpio",
			"crd"       => "application/x-mscardfile",
			"crl"       => "application/pkix-crl",
			"crt"       => "application/x-x509-ca-cert",
			"csh"       => "application/x-csh",
			"css"       => "text/css",
			"dcr"       => "application/x-director",
			"der"       => "application/x-x509-ca-cert",
			"dir"       => "application/x-director",
			"dll"       => "application/x-msdownload",
			"dms"       => "application/octet-stream",
			"doc"       => "application/msword",
			"dot"       => "application/msword",
			"docx"      => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
			"dvi"       => "application/x-dvi",
			"dxr"       => "application/x-director",
			"eps"       => "application/postscript",
			"etx"       => "text/x-setext",
			"evy"       => "application/envoy",
			"exe"       => "application/octet-stream",
			"fif"       => "application/fractals",
			"flr"       => "x-world/x-vrml",
			"gif"       => "image/gif",
			"gtar"      => "application/x-gtar",
			"gz"        => "application/x-gzip",
			"h"         => "text/plain",
			"hdf"       => "application/x-hdf",
			"hlp"       => "application/winhlp",
			"hqx"       => "application/mac-binhex40",
			"hta"       => "application/hta",
			"htc"       => "text/x-component",
			"htm"       => "text/html",
			"html"      => "text/html",
			"htt"       => "text/webviewhtml",
			"ico"       => "image/x-icon",
			"ief"       => "image/ief",
			"iii"       => "application/x-iphone",
			"ins"       => "application/x-internet-signup",
			"isp"       => "application/x-internet-signup",
			"jfif"      => "image/pipeg",
			"jpe"       => "image/jpeg",
			"jpg"       => "image/jpeg",
			"jpeg"      => "image/jpeg",
			"js"        => "application/x-javascript",
			"latex"     => "application/x-latex",
			"lha"       => "application/octet-stream",
			"lsf"       => "video/x-la-asf",
			"lsx"       => "video/x-la-asf",
			"lzh"       => "application/octet-stream",
			"m13"       => "application/x-msmediaview",
			"m14"       => "application/x-msmediaview",
			"m3u"       => "audio/x-mpegurl",
			"man"       => "application/x-troff-man",
			"mdb"       => "application/x-msaccess",
			"me"        => "application/x-troff-me",
			"mht"       => "message/rfc822",
			"mhtml"     => "message/rfc822",
			"mid"       => "audio/mid",
			"mny"       => "application/x-msmoney",
			"mov"       => "video/quicktime",
			"movie"     => "video/x-sgi-movie",
			"mp2"       => "video/mpeg",
			"mp3"       => "audio/mpeg",
			"mpa"       => "video/mpeg",
			"mpe"       => "video/mpeg",
			"mpeg"      => "video/mpeg",
			"mpg"       => "video/mpeg",
			"mpp"       => "application/vnd.ms-project",
			"mpv2"      => "video/mpeg",
			"ms"        => "application/x-troff-ms",
			"mvb"       => "application/x-msmediaview",
			"nws"       => "message/rfc822",
			"oda"       => "application/oda",
			"p10"       => "application/pkcs10",
			"p12"       => "application/x-pkcs12",
			"p7b"       => "application/x-pkcs7-certificates",
			"p7c"       => "application/x-pkcs7-mime",
			"p7m"       => "application/x-pkcs7-mime",
			"p7r"       => "application/x-pkcs7-certreqresp",
			"p7s"       => "application/x-pkcs7-signature",
			"pbm"       => "image/x-portable-bitmap",
			"pdf"       => "application/pdf",
			"pfx"       => "application/x-pkcs12",
			"pgm"       => "image/x-portable-graymap",
			"pko"       => "application/ynd.ms-pkipko",
			"pma"       => "application/x-perfmon",
			"pmc"       => "application/x-perfmon",
			"pml"       => "application/x-perfmon",
			"pmr"       => "application/x-perfmon",
			"pmw"       => "application/x-perfmon",
			"pnm"       => "image/x-portable-anymap",
			"png"       => "image/png",
			"pot"       => "application/vnd.ms-powerpoint",
			"ppm"       => "image/x-portable-pixmap",
			"pps"       => "application/vnd.ms-powerpoint",
			"ppt"       => "application/vnd.ms-powerpoint",
			"prf"       => "application/pics-rules",
			"ps"        => "application/postscript",
			"pub"       => "application/x-mspublisher",
			"qt"        => "video/quicktime",
			"ra"        => "audio/x-pn-realaudio",
			"ram"       => "audio/x-pn-realaudio",
			"ras"       => "image/x-cmu-raster",
			"rgb"       => "image/x-rgb",
			"rmi"       => "audio/mid",
			"roff"      => "application/x-troff",
			"rtf"       => "application/rtf",
			"rtx"       => "text/richtext",
			"scd"       => "application/x-msschedule",
			"sct"       => "text/scriptlet",
			"setpay"    => "application/set-payment-initiation",
			"setreg"    => "application/set-registration-initiation",
			"sh"        => "application/x-sh",
			"shar"      => "application/x-shar",
			"sit"       => "application/x-stuffit",
			"snd"       => "audio/basic",
			"spc"       => "application/x-pkcs7-certificates",
			"spl"       => "application/futuresplash",
			"src"       => "application/x-wais-source",
			"sst"       => "application/vnd.ms-pkicertstore",
			"stl"       => "application/vnd.ms-pkistl",
			"stm"       => "text/html",
			"svg"       => "image/svg+xml",
			"sv4cpio"   => "application/x-sv4cpio",
			"sv4crc"    => "application/x-sv4crc",
			"t"         => "application/x-troff",
			"tar"       => "application/x-tar",
			"tcl"       => "application/x-tcl",
			"tex"       => "application/x-tex",
			"texi"      => "application/x-texinfo",
			"texinfo"   => "application/x-texinfo",
			"tgz"       => "application/x-compressed",
			"tif"       => "image/tiff",
			"tiff"      => "image/tiff",
			"tr"        => "application/x-troff",
			"trm"       => "application/x-msterminal",
			"tsv"       => "text/tab-separated-values",
			"txt"       => "text/plain",
			"uls"       => "text/iuls",
			"ustar"     => "application/x-ustar",
			"vcf"       => "text/x-vcard",
			"vrml"      => "x-world/x-vrml",
			"wav"       => "audio/x-wav",
			"wcm"       => "application/vnd.ms-works",
			"wdb"       => "application/vnd.ms-works",
			"wks"       => "application/vnd.ms-works",
			"wmf"       => "application/x-msmetafile",
			"wps"       => "application/vnd.ms-works",
			"wri"       => "application/x-mswrite",
			"wrl"       => "x-world/x-vrml",
			"wrz"       => "x-world/x-vrml",
			"xaf"       => "x-world/x-vrml",
			"xbm"       => "image/x-xbitmap",
			"xla"       => "application/vnd.ms-excel",
			"xlc"       => "application/vnd.ms-excel",
			"xlm"       => "application/vnd.ms-excel",
			"xls"       => "application/vnd.ms-excel",
			"xlsx"      => "vnd.ms-excel",
			"xlsm"      => "application/vnd.ms-excel.sheet.macroEnabled.12",
			"xlt"       => "application/vnd.ms-excel",
			"xlw"       => "application/vnd.ms-excel",
			"xof"       => "x-world/x-vrml",
			"xpm"       => "image/x-xpixmap",
			"xwd"       => "image/x-xwindowdump",
			"z"         => "application/x-compress",
			"zip"       => "application/zip"
		]);
	}

	function getFileExtension( $filename ) {
		if(strpos($filename, "?") != false) $filename = substr($filename, 0, strpos($filename, "?"));
		$file_arr = explode('/', $filename);
		$file_rtv = end($file_arr);
		$file_spt = explode('.', $file_rtv);
		$file_ext = end($file_spt);
		$file_ext = strtolower($file_ext);

		return $file_ext;
	}

	function formatBytes($bytes, $precision = 2) { 
		$units = array('B', 'KB', 'MB', 'GB', 'TB'); 
	
		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1); 
	
		// Uncomment one of the following alternatives
		// $bytes /= pow(1024, $pow);
		$bytes /= (1 << (10 * $pow));
	
		return round($bytes, $precision) . ' ' . $units[$pow]; 
	} 
}