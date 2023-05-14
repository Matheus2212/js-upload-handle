/**
 * JS upload script made by Matheus https://github.com/Matheus2212
 *
 * This script was supposed to upload very small and very large files. Feel Free to improve/modify as necessary
 */

var Upload = {
  defaultMessage: {
    upload: "upload",
    uploading: "uploading",
    uploaded: "uploaded",
  },
  defaultURL: null,
  defaultSliceSize: 10 * 1024 * 256, // 2.5mb
  validationCallback: null,
  onReadCallback: null,
  newID: function () {
    var result = [];
    var length = 10;
    var characters =
      "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    var charactersLength = characters.length;
    for (var i = 0; i < length; i++) {
      result.push(
        characters.charAt(Math.floor(Math.random() * charactersLength))
      );
    }
    return result.join("");
  },
  build: function (inputs) {
    if (inputs.length == 0) {
      console.log("Input tags not found!");
      return false;
    }
    for (var i = 0; i < inputs.length; i++) {
      var newID = this.newID();
      var input = inputs[i];
      var keys = Object.keys(this.defaultMessage);
      var label = document.createElement("label");
      label.classList.add("js-upload-button");
      label.setAttribute("for", newID);
      input.setAttribute("id", newID);
      for (var iterate = 0; iterate < keys.length; iterate++) {
        var span = document.createElement("span");
        span.innerText = this.defaultMessage[keys[iterate]];
        label.appendChild(span);
      }
      var a = document.createElement("a");
      a.setAttribute("href", "javascript:void(0)");
      a.setAttribute("rel", "nofollow noindex noreferrer");
      var div = document.createElement("div");
      div.classList.add("js-upload-wrapper");
      div.appendChild(label);
      input.parentNode.appendChild(div);
      label.appendChild(a);
      label.appendChild(input);
      this.setCancelable(a, input);
      this.setUploadStatus(label, "upload");
    }
  },
  setCancelable: function (a, input) {
    a.addEventListener("click", function (event) {
      event.preventDefault();
      input.cancelUpload = true;
    });
  },
  setUploadStatus: function (target, status, info) {
    var spans = target.getElementsByTagName("span");
    status =
      status == "upload"
        ? 0
        : status == "uploading"
          ? 1
          : status == "uploaded"
            ? 2
            : null;
    for (var i = 0; i < spans.length; i++) {
      spans[i].classList.remove("active");
      spans[i].removeAttribute("style");
      if (status == 0 && i == 0) {
        target.classList.remove("js-uploading");
      }
      if (status == 1 && i == 1) {
        spans[i].style.width = (info.current * 100) / info.total + "%";
      }
      i == status ? spans[i].classList.add("active") : "";
    }
  },
  mount: function (input, config) {
    var keys = Object.keys(config);
    for (var i = 0; i < keys.length; i++) {
      if (keys[i] == "vars") {
        input.vars = config.vars;
      }
      if (keys[i] == "types") {
        var f = new Array();
        for (var it = 0; it < config.types.length; it++) {
          f.push("." + config.types[it]);
        }
        input.setAttribute("accept", f.join(","));
      }
      if (keys[i] == "total") {
        if (config.total > 1) {
          input.setAttribute("multiple", true);
        }
      }
    }
    input.integrity = config.integrity;
  },
  middleware: function (input, config) {
    var UPLOAD = this;
    if (typeof config.url == "undefined") {
      config.url = this.defaultURL;
    }
    config.slice = this.defaultSliceSize;
    input.addEventListener("change", function (event) {
      var count = 0;
      for (var i = 0; i < this.files.length; i++) {
        count++;
        if (count <= config.total) {
          var valid = UPLOAD.validation(this.files[i], config);
          if (valid.valid) {
            this.parentNode.classList.add("js-uploading");
            console.log(valid.message);
            UPLOAD.send(UPLOAD, input, this.files[i], config);
          } else {
            console.log(valid.message);
            if (
              UPLOAD.validationCallback !== null &&
              typeof UPLOAD.validationCallback == "function"
            ) {
              UPLOAD.validationCallback(valid.message);
            }
            event.preventDefault();
            delete this.files;
          }
        }
      }
    });
  },
  send: function (UPLOAD, input, file, config, result) {
    if (typeof result == "undefined") {
      var uploading = {
        config: config,
        totalRequests:
          config.slice > file.size ? 1 : Math.ceil(file.size / config.slice),
        currentRequest: 0,
        fileName: file.name,
      };
    } else {
      var uploading = result;
      delete uploading.data;
    }
    UPLOAD.setUploadStatus(input.parentNode, "uploading", {
      current: uploading.currentRequest,
      total: uploading.totalRequests,
    });
    var xhr = new XMLHttpRequest();
    xhr.open("POST", config.url, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.addEventListener("readystatechange", function () {
      if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
        var response = JSON.parse(this.response);
        if (
          response.status &&
          (typeof input.cancelUpload == "undefined" ||
            input.cancelUpload == false)
        ) {
          ++uploading.currentRequest;
          if (response.fileNameSet) {
            uploading.fileNameSet = true;
            uploading.fileName = response.fileName;
          }
          if (uploading.currentRequest == uploading.totalRequests) {
            console.log(
              "File uploaded. " +
              uploading.currentRequest +
              "/" +
              uploading.totalRequests
            );
            input.parentNode.classList.remove("js-uploading");
            UPLOAD.setUploadStatus(input.parentNode, "uploaded");
            if (uploading.totalRequests !== 1) {
              delete uploading.data;
            }
            UPLOAD.afterUpload(input, uploading);
            setTimeout(function () {
              UPLOAD.setUploadStatus(input.parentNode, "upload");
            }, 3000);
            return uploading;
          } else {
            delete uploading.data;
            console.log(
              "File still uploading... " +
              uploading.currentRequest +
              "/" +
              uploading.totalRequests
            );
            return UPLOAD.send(UPLOAD, input, file, config, uploading);
          }
        } else {
          if (
            typeof input.cancelUpload !== "undefined" &&
            input.cancelUpload === true
          ) {
            delete uploading.data;
            if (response.fileNameSet) {
              uploading.fileNameSet = true;
              uploading.fileName = response.fileName;
            }
            var xhr = new XMLHttpRequest();
            xhr.open("POST", config.url, true);
            xhr.setRequestHeader(
              "Content-Type",
              "application/x-www-form-urlencoded"
            );
            xhr.send("cancel=" + JSON.stringify(uploading));
            delete input.cancelUpload;
          }
          console.log("File weren't uploaded.");
          UPLOAD.setUploadStatus(input.parentNode, "upload");
          return false;
        }
      } else if (
        this.readyState === XMLHttpRequest.DONE &&
        this.status === 404
      ) {
        console.log("An error has ocurred. Please, try again later.");
        setTimeout(function () {
          UPLOAD.setUploadStatus(input.parentNode, "upload");
        }, 1000);
        return false;
      }
    });
    var reader = new FileReader();
    reader.onload = function () {
      uploading.data = this.result;
      if (
        UPLOAD.onReadCallback !== null &&
        typeof UPLOAD.onReadCallback == "function"
      ) {
        UPLOAD.onReadCallback(uploading.data);
      }
      if (typeof config.url !== "undefined" && config.url != null) {
        xhr.send("upload=" + JSON.stringify(uploading));
      } else {
        console.log(
          "It seems this is a LOCAL UPLOAD. File not sent to server."
        );
        UPLOAD.setUploadStatus(input.parentNode, "uploaded");
        setTimeout(function () {
          UPLOAD.setUploadStatus(input.parentNode, "upload");
        }, 5000);
      }
      //delete uploading.data;
    };
    var size = config.slice * uploading.currentRequest;
    reader.readAsDataURL(
      config.slice > file.size ? file : file.slice(size, size + config.slice)
    );
  },
  afterUpload: function (input, config) {
    var wrapper = input.parentNode.parentNode;
    var newInput = document.createElement("input");
    var type = config.fileName.split(".");
    type = type[type.length - 1];
    newInput.setAttribute("type", "hidden");
    newInput.value = config.fileName;
    newInput.setAttribute("name", "js-upload[" + this.newID() + "]");
    var check = wrapper.getElementsByClassName("js-upload-line");
    if (check.length == 0) {
      var check = document.createElement("div");
      check.classList.add("js-upload-line");
      wrapper.appendChild(check);
    } else {
      check = check[0];
    }
    var icon = document.createElement("span");
    icon.classList.add("js-upload-icon");
    if (typeof config.data !== "undefined") {
      icon.style.backgroundColor = "#FFFFFF";
      icon.style.backgroundImage = "url('" + config.data + "')";
      icon.style.backgroundSize = "cover";
      icon.style.backgroundPosition = "center";
    } else {
      icon.setAttribute("data-content", type);
    }
    icon.innerHTML = "<a></a>";
    icon.appendChild(newInput);
    check.appendChild(icon);
    icon.getElementsByTagName("a")[0].addEventListener("click", function (evt) {
      evt.preventDefault();
      evt.stopPropagation();
      var xhr = new XMLHttpRequest();
      xhr.open("POST", config.config.url, true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.addEventListener("readystatechange", function () { });
      xhr.send("delete=" + JSON.stringify(config));
      if (this.parentNode.parentNode.getElementsByTagName("a").length == 1) {
        this.parentNode.parentNode.remove();
      } else {
        this.parentNode.remove();
      }
    });
  },
  validation: function (file, config) {
    var returns = {
      valid: true,
      message: "",
    };
    if (file.size > config.size) {
      returns.valid = false;
      returns.message = "File size is bigger than allowed";
    }
    if (config.types.length === 1 && config.types[0] === "*") {
      return returns;
    }
    var regex = new RegExp(config.types.join("|").toLowerCase());
    if (!regex.test(file.type)) {
      returns.valid = false;
      returns.message = "File type is not valid";
    }
    return returns;
  },
  setValidationCallback: function (callback) {
    if (typeof callback == "function") {
      this.validationCallback = callback;
    }
  },
  setOnReadCallback: function (callback) {
    if (typeof callback == "function") {
      this.onReadCallback = callback;
    }
  },
  bind: function (inputs, profile) {
    /*{
      "{{md5-ciphered-upload-profile}}": {
          "config": {
              "url": "{{URL fetched from backend}}",
              "types": [
                  {{string of file type fetched from backend}}
              ],
              "size": "{{max file size fetched from backend}}",
              "total": "{{total number of uploads in same input tag fetched from backend}}",
              "vars": [], //this can be added as 'extra' from backend
              "integrity": "{{this is very important - this variable is the whole CIPHERED profile, which will be used to validated the data being sent to backend}}"
          }
      }
  }*/
    config = profile.config;
    if (!config.hasOwnProperty("total")) {
      config.total = 1;
    }
    for (var i = 0; i < inputs.length; i++) {
      this.mount(inputs[i], config);
      this.middleware(inputs[i], config);
    }
  },
  newUpload: function (inputName, uploadProfile) {
    var inputs = document.getElementsByName(inputName);
    this.build(inputs);
    if (typeof uploadProfiles == "undefined") {
      console.log("Upload profiles are not set. Buttons won't work...");
      return false;
    }
    var profile = uploadProfiles[uploadProfile];
    this.bind(inputs, profile);
  },
};

if (typeof module !== "undefined") {
  module.exports = Upload;
}
