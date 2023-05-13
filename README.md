# js-php-upload-class (work-in-progress)

Upload class for huge files. It was tested with a 35gb file.

NOTE: This class and README are going being redone. Please, wait for more detailed information.

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

- For FrontEnd Only:
  Maybe you just want to work on frontend or use indexedDB in your application, and you need to handle uploads. Well, you can do it with the following (assuming you already have any profile of any sort): 
  ```javascript
  const build = () => {
    setTimeout(() => {
        Upload.setOnReadCallback((result) => {
          let contents = atob(result.split(",")[1]);
          //Do your stuff
        });
        let input = document.querySelector("[name=s_a]");
        Upload.build([input]);
        Upload.mount(input, config);
        Upload.middleware(input, config);
    }, 1000);
  };
  ```

The script will do whatever you tell it to while READING the file. This method is still to be improved.


The class will call the script functions and prepare the input and upload functions to you!

Enjoy!