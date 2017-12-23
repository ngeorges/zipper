<?php
/**
 * Extract .zip, .rar and .gz archives or compress files on web servers.
 *
 * @author  Ner Karso
 * @license MIT
 * @version 0.1.0
 */
define('VERSION', '0.1.0');

$timestart = microtime(true);
$GLOBALS['status'] = array();
$GLOBALS['icon'] = array();

$unzipper = new Unzipper;
if (isset($_POST['dounzip'])) {
    //check if an archive was selected for unzipping
    $archive = isset($_POST['zipfile']) ? strip_tags($_POST['zipfile']) : '';
    $destination = isset($_POST['extpath']) ? strip_tags($_POST['extpath']) : '';
    $unzipper->prepareExtraction($archive, $destination);
}

if (isset($_POST['dozip'])) {
    $zippath = !empty($_POST['zippath']) ? strip_tags($_POST['zippath']) : '.';
    // Resulting zipfile e.g. zipper--2016-07-23--11-55.zip
    // $zipfile = 'zipper-' . date("Y-m-d--H-i") . '.zip';
    $zipfile = $zippath . '.zip';
    Zipper::zipDir($zippath, $zipfile);
}

$timeend = microtime(true);
$time = $timeend - $timestart;

/**
 * Class Unzipper
 */
class Unzipper
{
    public $localdir = '.';
    public $zipfiles = array();

    public function __construct()
    {

    //read directory and pick .zip and .gz files
        if ($dh = opendir($this->localdir)) {
            while (($file = readdir($dh)) !== false) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'zip'
          || pathinfo($file, PATHINFO_EXTENSION) === 'gz'
          || pathinfo($file, PATHINFO_EXTENSION) === 'rar'
        ) {
                    $this->zipfiles[] = $file;
                }
            }
            closedir($dh);

            if (!empty($this->zipfiles)) {
                if (count($this) > 1) {
                    $GLOBALS['status'] = array('primary' => count($this). ' archived files found, ready for extraction');
                } else {
                    $GLOBALS['status'] = array('primary' => count($this). ' archived file found, ready for extraction');
                }
                $GLOBALS['icon'] = array('name' => 'checkmark');
            } else {
                $GLOBALS['status'] = array('secondary' => 'No archived files found, only compression available');
                $GLOBALS['icon'] = array('name' => 'close');
            }
        }
    }

    /**
     * Prepare and check zipfile for extraction.
     *
     * @param $archive
     * @param $destination
     */
    public function prepareExtraction($archive, $destination)
    {
        // Determine paths.
        if (empty($destination)) {
            $extpath = $this->localdir;
        } else {
            $extpath = $this->localdir . '/' . $destination;
            // todo move this to extraction function
            if (!is_dir($extpath)) {
                mkdir($extpath);
            }
        }
        //allow only local existing archives to extract
        if (in_array($archive, $this->zipfiles)) {
            self::extract($archive, $extpath);
        }
    }

    /**
     * Checks file extension and calls suitable extractor functions.
     *
     * @param $archive
     * @param $destination
     */
    public static function extract($archive, $destination)
    {
        $ext = pathinfo($archive, PATHINFO_EXTENSION);
        switch ($ext) {
      case 'zip':
        self::extractZipArchive($archive, $destination);
        break;
      case 'gz':
        self::extractGzipFile($archive, $destination);
        break;
      case 'rar':
        self::extractRarArchive($archive, $destination);
        break;
    }
    }

    /**
     * Decompress/extract a zip archive using ZipArchive.
     *
     * @param $archive
     * @param $destination
     */
    public static function extractZipArchive($archive, $destination)
    {
        // Check if webserver supports unzipping.
        if (!class_exists('ZipArchive')) {
						$GLOBALS['status'] = array('danger' => 'Error: Your PHP version does not support unzip functionality');
						$GLOBALS['icon'] = array('name' => 'close');
            return;
        }

        $zip = new ZipArchive;

        // Check if archive is readable.
        if ($zip->open($archive) === true) {
            // Check if destination is writable
            if (is_writeable($destination . '/')) {
                $zip->extractTo($destination);
                $zip->close();
								$GLOBALS['status'] = array('success' => 'Files extraction successful');
								$GLOBALS['icon'] = array('name' => 'opened_folder');
            } else {
								$GLOBALS['status'] = array('danger' => 'Error: Directory not writeable by webserver');
								$GLOBALS['icon'] = array('name' => 'close');
            }
        } else {
						$GLOBALS['status'] = array('danger' => 'Error: Cannot read .zip archive');
						$GLOBALS['icon'] = array('name' => 'close');
        }
    }

    /**
     * Decompress a .gz File.
     *
     * @param $archive
     * @param $destination
     */
    public static function extractGzipFile($archive, $destination)
    {
        // Check if zlib is enabled
        if (!function_exists('gzopen')) {
						$GLOBALS['status'] = array('danger' => 'Error: Your PHP has no zlib support enabled');
						$GLOBALS['icon'] = array('name' => 'close');
            return;
        }

        $filename = pathinfo($archive, PATHINFO_FILENAME);
        $gzipped = gzopen($archive, "rb");
        $file = fopen($filename, "w");

        while ($string = gzread($gzipped, 4096)) {
            fwrite($file, $string, strlen($string));
        }
        gzclose($gzipped);
        fclose($file);

        // Check if file was extracted.
        if (file_exists($destination . '/' . $filename)) {
						$GLOBALS['status'] = array('success' => 'File extraction successful');
						$GLOBALS['icon'] = array('name' => 'opened_folder');
        } else {
						$GLOBALS['status'] = array('danger' => 'Error extracting file');
						$GLOBALS['icon'] = array('name' => 'close');
        }
    }

    /**
     * Decompress/extract a Rar archive using RarArchive.
     *
     * @param $archive
     * @param $destination
     */
    public static function extractRarArchive($archive, $destination)
    {
        // Check if webserver supports unzipping.
        if (!class_exists('RarArchive')) {
						$GLOBALS['status'] = array('danger' => 'Error: Your PHP version does not support .rar archive functionality. <a class="info" href="http://php.net/manual/en/rar.installation.php" target="_blank">How to install RarArchive</a>');
						$GLOBALS['icon'] = array('name' => 'close');
            return;
        }
        // Check if archive is readable.
        if ($rar = RarArchive::open($archive)) {
            // Check if destination is writable
            if (is_writeable($destination . '/')) {
                $entries = $rar->getEntries();
                foreach ($entries as $entry) {
                    $entry->extract($destination);
                }
                $rar->close();
								$GLOBALS['status'] = array('success' => 'Files extraction successful');
								$GLOBALS['icon'] = array('name' => 'opened_folder');
            } else {
								$GLOBALS['status'] = array('danger' => 'Error: Directory not writeable by webserver');
								$GLOBALS['icon'] = array('name' => 'close');
            }
        } else {
						$GLOBALS['status'] = array('danger' => 'Error: Cannot read .rar archive');
						$GLOBALS['icon'] = array('name' => 'close');
        }
    }
}

/**
 * Class Zipper
 *
 * Copied and slightly modified from http://at2.php.net/manual/en/class.ziparchive.php#110719
 * @author umbalaconmeogia
 */
class Zipper
{
    /**
     * Add files and sub-directories in a folder to zip file.
     *
     * @param string     $folder
     *   Path to folder that should be zipped.
     *
     * @param ZipArchive $zipFile
     *   Zipfile where files end up.
     *
     * @param int        $exclusiveLength
     *   Number of text to be exclusived from the file path.
     */
    private static function folderToZip($folder, &$zipFile, $exclusiveLength)
    {
        $handle = opendir($folder);

        while (false !== $f = readdir($handle)) {
            // Check for local/parent path or zipping file itself and skip.
            if ($f != '.' && $f != '..' && $f != basename(__FILE__)) {
                $filePath = "$folder/$f";
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusiveLength);

                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    /**
     * Zip a folder (including itself).
     * Usage:
     *   Zipper::zipDir('path/to/sourceDir', 'path/to/out.zip');
     *
     * @param string $sourcePath
     *   Relative path of directory to be zipped.
     *
     * @param string $outZipPath
     *   Relative path of the resulting output zip file.
     */
    public static function zipDir($sourcePath, $outZipPath)
    {
        $pathInfo = pathinfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        $dirName = $pathInfo['basename'];

        $z = new ZipArchive();
        $z->open($outZipPath, ZipArchive::CREATE);
        $z->addEmptyDir($dirName);
        if ($sourcePath == $dirName) {
            self::folderToZip($sourcePath, $z, 0);
        } else {
            self::folderToZip($sourcePath, $z, strlen("$parentPath/"));
        }
        $z->close();

				$GLOBALS['status'] = array('success' => 'Successfully created archive <a href="'.$outZipPath.'" class="text-primary">' . $outZipPath . '</a>');
				$GLOBALS['icon'] = array('name' => 'archive');
    }
}
?>

	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="description" content="The independent single file archives extractor and files compressor for web servers.">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
		<link rel="shortcut icon" href="https://raw.githubusercontent.com/nerkarso/zipper/master/screenshots/icon.png?=0.1.0" type="image/png">
		<title>Zipper</title>
		<!-- CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css">
		<style>
			a,
			button {
				cursor: pointer;
			}

			.jumbotron {
				border-radius: 0;
				margin-bottom: 0;
				padding-top: 2rem;
				padding-bottom: 4rem;
			}

			#tabs {
				margin-top: -2.65rem;
				margin-bottom: 1.5rem;
			}

			.nav-link {
				padding: .5rem 2rem;
			}

			.status .icon {
				border-radius: 50%;
				margin: 0 auto 1rem;
				padding-top: 20px;
				height: 120px;
				width: 120px;
			}

			.status-primary .icon {
				background: rgba(0, 123, 255, 0.3);
			}

			.status-secondary .icon {
				background: rgba(134, 142, 150, 0.3);
			}

			.status-success .icon {
				background: rgba(40, 167, 69, 0.3);
			}

			.status-danger .icon {
				background: rgba(220, 53, 69, 0.3);
			}

			.status .icon svg {
				max-height: 80px;
				width: 80px;
			}

			.status-primary .icon svg {
				fill: var(--primary);
			}

			.status-secondary .icon svg {
				fill: var(--secondary);
			}

			.status-success .icon svg {
				fill: var(--success);
			}

			.status-danger .icon svg {
				fill: var(--danger);
			}
		</style>
		<!-- /CSS -->
	</head>

	<body>
		<!-- Main -->
		<main role="main">
			<div class="jumbotron">
				<div class="container">
					<h1 class="display-4 mb-3">Zipper</h1>
					<p class="lead">
						An independent single file archives extractor and files compressor for web servers.
						<a href="https://github.com/nerkarso/zipper" target="_blank" rel="nofollow">View on GitHub</a>
					</p>
				</div>
			</div>

			<div class="container">
				<nav class="nav nav-tabs" id="tabs" role="tablist">
					<a class="nav-item nav-link active" data-toggle="tab" href="#tab-extract" role="tab">Extract</a>
					<a class="nav-item nav-link" data-toggle="tab" href="#tab-compress" role="tab">Compress</a>
				</nav>
				<div class="row justify-content-center h-100">
					<div class="col-sm-6">
						<div class="tab-content">
							<div class="tab-pane fade show active" id="tab-extract" role="tabpanel">
								<form action="" method="POST">
									<div class="form-group">
										<label for="zipfile">File
											<span class="text-primary">*</span>
										</label>
										<select class="form-control" name="zipfile" required>
											<?php 
													if (count($unzipper->zipfiles) > 0) {
															foreach ($unzipper->zipfiles as $zip) {
																	echo '<option value="'.$zip.'">'.$zip.'</option>';
															}
													} else {
															echo '<option value="">No archived files found</option>';
													}
											?>
										</select>
										<small class="form-text text-muted">Select
											<code>.zip</code> or
											<code>.rar</code> or
											<code>.gz</code> file to extract.</small>
									</div>
									<div class="form-group">
										<label for="extpath">Extraction Path
											<span class="text-muted">(optional)</span>
										</label>
										<input type="text" class="form-control" name="extpath" placeholder="e.g. yourpath">
										<small class="form-text text-muted">Leave empty for current directory or add a path without leading or trailing slashes.</small>
									</div>
									<div class="form-group mt-4">
										<button type="submit" name="dounzip" class="btn btn-primary">Extract</button>
									</div>
								</form>
							</div>
							<div class="tab-pane fade" id="tab-compress" role="tabpanel">
								<form action="" method="POST">
									<div class="form-group">
										<label for="zippath">Compression Path
											<span class="text-muted">(optional)</span>
										</label>
										<input type="text" class="form-control" name="zippath" placeholder="e.g. yourpath">
										<small class="form-text text-muted">Leave empty to compress current directory or add a path without leading or trailing slashes.</small>
									</div>
									<div class="form-group mt-4">
										<button type="submit" name="dozip" class="btn btn-primary">Compress</button>
									</div>
								</form>
							</div>
						</div>
					</div>
					<div class="col-sm-6">
						<div class="row">
							<div class="status status-<?php echo key($GLOBALS['status']); ?> text-center m-auto">
								<div class="icon">
									<?php
											if (reset($GLOBALS['icon']) == 'archive') {
													echo '<svg xmlns="http://www.w3.org/2000/svg" id="archive" viewBox="0 0 32 32" width="128" height="128"><path d="M6 3v26h20V3H6zm2 2h7v1h2V5h7v22H8V5zm7 2v2h2V7h-2zm0 3v2h2v-2h-2zm0 3v2.188c-1.156.418-2 1.52-2 2.812 0 1.645 1.355 3 3 3s3-1.355 3-3c0-1.292-.844-2.394-2-2.813V13h-2zm1 4c.564 0 1 .436 1 1 0 .564-.436 1-1 1-.564 0-1-.436-1-1 0-.564.436-1 1-1z"/></svg>';
											} elseif (reset($GLOBALS['icon']) == 'close') {
													echo '<svg xmlns="http://www.w3.org/2000/svg" id="close" viewBox="0 0 512 512" width="128" height="128"><path d="M405 137L286 256l119 119-30 30-119-119-119 119-30-30 119-119-119-119 30-30 119 119 119-119 30 30z"/></svg>';
											} elseif (reset($GLOBALS['icon']) == 'checkmark') {
													echo '<svg xmlns="http://www.w3.org/2000/svg" id="checkmark" viewBox="0 0 32 32" width="128" height="128"><path d="M28.28 6.28L11 23.563l-7.28-7.28-1.44 1.437 8 8 .72.686.72-.687 18-18-1.44-1.44z"/></svg>';
											} elseif (reset($GLOBALS['icon']) == 'opened_folder') {
													echo '<svg xmlns="http://www.w3.org/2000/svg" id="opened_folder" viewBox="0 0 32 32" width="96" height="96" style="fill: rgb(40, 167, 69);"><path d="M5 3v24.813l.78.156 12 2.5 1.22.25V28h6V15.437l1.72-1.718.28-.314V3H5zm9.125 2H25v7.563l-1.72 1.718-.28.314V26h-4v-8.906l-.28-.313L17 15.063V5.72l-.75-.19L14.125 5zM7 5.28l8 2V15.907l.28.313L17 17.937V28.28L7 26.188V5.282z"/></svg>';
											} else {
													echo '<svg xmlns="http://www.w3.org/2000/svg" id="file" viewBox="0 0 32 32" width="128" height="128"><path d="M6 3v26h20V9.594l-.28-.313-6-6-.314-.28H6zm2 2h10v6h6v16H8V5zm12 1.438L22.563 9H20V6.437z"/></svg>';
											}
									?>
								</div>
								<p class="mb-2">
									<?php echo reset($GLOBALS['status']); ?>
								</p>
								<p class="small">Processing Time:
									<?php echo $time; ?> seconds</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</main>
		<!-- /Main -->

		<!-- JS -->
		<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.bundle.min.js"></script>
		<!-- /JS -->
	</body>

	</html>