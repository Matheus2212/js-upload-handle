<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include("upload.class.php");

$profile = array(
        "types" => array("jpeg", "jpg", "png", "mkv", "matroska","mp3"),
        "folder" => "./uploads/",
        "size" => 25000000000,
        "total" => 5,
        "vars" => array(), // send additional data to backend
);

Upload::addProfile('imagem', $profile);

Upload::set('imagem', 'imagem');

?>

<!DOCTYPE html>
<html lang="en">

<head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Upload</title>
        <script type="text/javascript" src='upload.functions.js'></script>
        <link rel="stylesheet" href="upload.style.css" />
</head>

<body>
        <input type="file" name="imagem" />
        <?php Upload::init() ?>
</body>

</html>