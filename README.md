# js-upload-class (work-in-progress)

Frontend handle for upload classes (PHP).

NOTE: This class and README are being redone. Please, wait for more detailed information.

---

## How to use

- Gets upload profile from backend: 
  Set the Upload 'profile', for validation during upload proccess. Please, read backend upload class for more details: [https://github.com/Matheus2212/php-upload-class]

- Prepare your page: 
  Include the functions script and stylesheet to your page (of course, be sure to fetch backend's upload settings and profiles):

```html
<script type="text/javascript" src="upload.functions.js"></script>
<link rel="stylesheet" href="upload.style.css" />
```

And add the input tag on you page:

```html
<input type="file" name="imagem" />
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