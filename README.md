# js-php-upload-class (work-in-progress)

Upload class for huge files. It was tested with a 35gb file.

---

## How to use

- Add profile: 
  Set the Upload 'profile', for validation during upload proccess:

```php
include('class/path/file.php');

$profile = array(
        "types" => array("jpeg", "jpg", "png"), // which filetypes are accepted
        "folder" => "./uploads/", // which folder your files go, for this specific profile, on your local server
        "size" => 266000, // max size of file upload
        "total" => 10, // total files that can be upload on this input
        "vars" => array(), // additional vars that will be passed to frontend and backend
);

Upload::addProfile('image', $profile);
```

After that, bind the input name to the profile:

```php
Upload::set('image', 'image');
```

---

- Prepare your page: 
  Include the functions script and stylesheet to your page (of course, be sure that the PHP methods are being called within the file included on the page):

```html
<script type="text/javascript" src="upload.functions.js"></script>
<link rel="stylesheet" href="upload.style.css" />
```

And add the input tag on you page:

```html
<input type="file" name="imagem" />
```

---

- Init: 
  After preparation, on your page's footer, call:

```php
Upload::init() ;
```

---

The class will call the script functions and prepare the input and upload functions to you!

Enjoy!