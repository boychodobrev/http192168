<?php

if(!empty($_GET['file'])){
    // Define file name and path
    $fileName = basename($_GET['file']);
    $filePath = $_GET['file'];

    if(!empty($fileName) && file_exists($filePath)){
        // Define headers

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$fileName.'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));



	@ob_end_flush();
    flush();

$fileDescriptor = fopen($filePath, 'rb');

while ($chunk = fread($fileDescriptor, 8192)) {
    echo $chunk;
    @ob_end_flush();
    flush();
}

fclose($fileDescriptor);

    exit;
}
    else {
        echo 'The file does not exist.';
    }
}

?>
