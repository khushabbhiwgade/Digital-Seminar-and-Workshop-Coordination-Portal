<?php
// scratch/install_tcpdf.php
// ------------------------------------------------------------
// Self-contained TCPDF Installer Script
// downloads TCPDF and extracts it to includes/tcpdf/
// ------------------------------------------------------------

define('ROOT_PATH', dirname(__DIR__) . '/');

echo "=== TCPDF Automatic Installer ===\n";

$zip_url = "https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.6.2.zip";
$temp_zip = ROOT_PATH . 'scratch/tcpdf.zip';
$temp_extract = ROOT_PATH . 'scratch/tcpdf_temp/';
$target_dir = ROOT_PATH . 'includes/tcpdf/';

if (is_dir($target_dir) && file_exists($target_dir . 'tcpdf.php')) {
    echo "TCPDF is already installed in includes/tcpdf/\n";
    exit(0);
}

// 1. Download ZIP file
echo "Downloading TCPDF from: $zip_url ...\n";
$ctx = stream_context_create([
    'http' => [
        'timeout' => 60,
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
    ]
]);
$zip_data = @file_get_contents($zip_url, false, $ctx);

if ($zip_data === false) {
    echo "ERROR: Failed to download TCPDF. Check your internet connection.\n";
    exit(1);
}

if (!is_dir(dirname($temp_zip))) {
    mkdir(dirname($temp_zip), 0777, true);
}

file_put_contents($temp_zip, $zip_data);
echo "Download completed successfully. Saved to scratch/tcpdf.zip\n";

// 2. Extract ZIP file
echo "Extracting zip archive...\n";
$zip = new ZipArchive();
if ($zip->open($temp_zip) === true) {
    if (!is_dir($temp_extract)) {
        mkdir($temp_extract, 0777, true);
    }
    $zip->extractTo($temp_extract);
    $zip->close();
    echo "Extraction complete.\n";
} else {
    echo "ERROR: Failed to open zip file. The download might be corrupted.\n";
    @unlink($temp_zip);
    exit(1);
}

// 3. Move files to target directory
echo "Moving TCPDF files to includes/tcpdf/ ...\n";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$source_dir = $temp_extract . 'TCPDF-6.6.2/';

if (!is_dir($source_dir)) {
    echo "ERROR: Extracted directory structure is not as expected ($source_dir not found).\n";
    exit(1);
}

// Recursive function to copy directory contents
function copy_recursive($src, $dst) {
    $dir = opendir($src);
    if (!is_dir($dst)) {
        mkdir($dst, 0777, true);
    }
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            if (is_dir($src . '/' . $file)) {
                copy_recursive($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

copy_recursive($source_dir, $target_dir);
echo "Files moved successfully.\n";

// 4. Cleanup
echo "Cleaning up temporary files...\n";
@unlink($temp_zip);

function delete_recursive($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delete_recursive("$dir/$file") : @unlink("$dir/$file");
    }
    return @rmdir($dir);
}

delete_recursive($temp_extract);
echo "Cleanup completed successfully!\n";

echo "=== TCPDF INSTALLED SUCCESSFULLY IN includes/tcpdf/ ===\n";
?>
