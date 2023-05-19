<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include("UploadClass.php");

$profile = array(
        "url" => "{{UPLOAD_URL}}",
        "types" => array("jpeg", "jpg", "png", "bmp", "webp"),
        "folder" => "./storage/path",
        "size" => 5000000000,
        "total" => 5,
        "vars" => array(), // send additional data to frontend
);

Upload::addProfile('imagem', $profile);

Upload::set('imagem', 'imagem');
Upload::set('imagem2', 'imagem');
Upload::set('imagem3', 'imagem');
Upload::set('imagem4', 'imagem');

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
        <script>
                var config = {
                        types: ["*"],
                        total: 1,
                        integrity: "none",
                        size: 10000000,
                };

                var build = () => {
                        setTimeout(() => {
                                window['Upload'].setOnReadCallback((result) => {
                                        let data = atob(result.split(",")[1]);
                                        console.log(data);
                                });
                                let input = document.querySelector("[name=file]");
                                window['Upload'].build([input]);
                                window['Upload'].mount(input, config);
                                window['Upload'].middleware(input, config);
                        }, 500);
                };
                build();
        </script>
</head>

<body>
        <input type="file" name="file" />
</body>

</html>