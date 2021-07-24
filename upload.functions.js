var Upload = {
        defaultMessage: 'Enviar Arquivos',
        defaultURL: 'upload.middleware.php',
        //defaultSliceSize: (10 * 1024 * 1024), // 10mb
        defaultSliceSize: (10 * 10 * 1024),
        newID: function () {
                var result = [];
                var length = 17;
                var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                var charactersLength = characters.length;
                for (var i = 0; i < length; i++) {
                        result.push(characters.charAt(Math.floor(Math.random() * charactersLength)));
                }
                return result.join('');
        },
        build: function (inputs) {
                if (inputs.length == 0) {
                        console.log('Inputs not found!');
                        return false;
                }
                for (var i = 0; i < inputs.length; i++) {
                        var newID = this.newID();
                        var input = inputs[i];
                        input.setAttribute('id', newID);
                        var text = input.getAttribute('data-message');
                        if (text == null || text == undefined) {
                                text = this.defaultMessage;
                        }
                        var label = document.createElement('label');
                        var span = document.createElement('span');
                        span.innerText = text;
                        label.appendChild(span);
                        label.classList.add('js-upload-button');
                        label.setAttribute('for', newID);
                        input.parentNode.appendChild(label);
                        label.appendChild(input);
                }
        },
        mount: function (input, config) {
                var keys = Object.keys(config);
                for (var i = 0; i < keys.length; i++) {
                        if (keys[i] == 'vars') {
                                input.vars = config.vars;
                        }
                        if (keys[i] == 'types') {
                                var f = new Array();
                                for (var it = 0; it < config.types.length; it++) {
                                        f.push('.' + config.types[it]);
                                }
                                input.setAttribute('accept', f.join(','));
                        }
                        if (keys[i] == 'total') {
                                if (config.total > 1) {
                                        input.setAttribute('multiple', true);
                                }
                                //input.setAttribute('data-total', config.total);
                        }
                }
                input.integrity = config.integrity;
        },
        middleware: function (input, config) {
                var validation = this.validation;
                var SEND = this.send;
                config.url = this.defaultURL;
                config.slice = this.defaultSliceSize;
                input.addEventListener('change', function (event) {
                        var count = 0;
                        for (var i = 0; i < this.files.length; i++) {
                                count++;
                                if (count <= config.total) {
                                        var valid = validation(this.files[i], config);
                                        if (valid.valid) {
                                                console.log(valid.message);
                                                SEND(SEND, this.files[i], config);
                                        } else {
                                                console.log(valid.message);
                                                //BoxMessage(valid.message); // needs validation script
                                                event.preventDefault();
                                                delete this.files;
                                                return false;
                                        }
                                } else {
                                        return false;
                                }
                        }
                });
        },
        send: function (SEND, file, config, result) {
                if (typeof result == "undefined") {
                        var uploading = {
                                config: config,
                                totalRequests: (config.slice > file.size ? 1 : Math.ceil(file.size / config.slice)),
                                currentRequest: 0,
                                fileName: file.name
                        };
                } else {
                        var uploading = result;
                        delete uploading.data;
                }
                var xhr = new XMLHttpRequest();
                xhr.open("POST", config.url, true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function (event) { // Chama a função quando o estado mudar.
                        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                                var response = JSON.parse(this.response);
                                if (response.status) {
                                        ++uploading.currentRequest;
                                        if (response.fileNameSet) {
                                                uploading.fileNameSet = true;
                                                uploading.fileName = response.fileName;
                                        }
                                        if (uploading.currentRequest == uploading.totalRequests) {
                                                console.log("File completely uploaded");
                                                return true;
                                        } else {
                                                delete uploading.data
                                                console.log("File still uploading... " + uploading.currentRequest + "/" + uploading.totalRequests);
                                                SEND(SEND, file, config, uploading);
                                        }
                                } else {
                                        console.log("File weren't uploaded");
                                }
                        }
                }
                var reader = new FileReader();
                reader.onload = function () {
                        uploading.data = this.result;
                        xhr.send("upload=" + JSON.stringify(uploading));
                        delete uploading.data;
                }
                var size = (config.slice * uploading.currentRequest);
                reader.readAsDataURL((config.slice > file.size ? file : file.slice(size, size + config.slice)));
        },
        validation: function (file, config) {
                var returns = {
                        valid: true,
                        message: '',
                }
                if (file.size > config.size) {
                        returns.valid = false;
                        returns.message = 'O arquivo é maior que o tamanho limite.';
                }
                var regex = new RegExp(config.types.join('|').toLowerCase());
                if (!regex.test(file.type)) {
                        returns.valid = false;
                        returns.message = 'O arquivo não é de um tipo válido.';
                }
                return returns;
        },
        bind: function (inputs, profile) {
                /**
                 * $profile = array(
                        "types" => array("jpeg", "jpg", "png"),
                        "size" => 266000,
                        "total" => 10,
                        "vars" => array(), // passa variaveis adicionais
                        "integrity" => crypted
                );
                 */
                config = profile.config;
                if (!config.hasOwnProperty('total')) {
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
                        console.log("Upload profiles are not set. Buttons won't work");
                        return false;
                }
                var profile = uploadProfiles[uploadProfile];
                this.bind(inputs, profile);
        }
}