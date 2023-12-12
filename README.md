# js-upload-class

Frontend handle using info pulled from backend or frontend.

---

## How to use

- Gets upload profile from backend: 
  Set the Upload 'profile', for validation during upload proccess. Please, read backend upload class for more details: [https://github.com/Matheus2212/php-upload-class] or [https://github.com/Matheus2212/django-upload-app]

- Prepare your page: 
  Include the functions script and stylesheet to your page (of course, be sure to fetch backend's upload settings and profiles):

```html
<script type="text/javascript" src="functions.js"></script>
<link rel="stylesheet" href="style.css" />
```

And add the input tag on you page:

```html
<input type="file" name="image" />
```

---


- For FrontEnd Only:
  Maybe you just want to work on frontend or use indexedDB in your application, and you need to handle uploads. Well, you can do it with the following (assuming you already have any profile of any sort): 
  ```javascript
  const build = () => {

    var x = {
      config: {
        types: ["*"],
        total: 1,
        integrity: "none",
        size: 10000000,
      }
    };

    setTimeout(() => {
        Upload.setOnReadCallback((result) => {
          let contents = atob(result.split(",")[1]);
          //Do your stuff
        });
        Upload.setOnUploadCallback((result,inputEl) => {
          //Do your stuff
        });
        let input = document.querySelector("[name=image]");
        Upload.build([input]);
        Upload.mount(input, x);
        Upload.middleware(input, x);
    }, 1000);
  };
  ```

The script will do whatever you tell it to while READING the file. This method is still to be improved.


The class will call the script functions and prepare the input and upload functions to you!

Enjoy!