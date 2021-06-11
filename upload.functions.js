var Upload = {
        defaultMessage: 'Enviar Arquivos',
        newID: function () {
                var result = [];
                var length = 17;
                var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                var charactersLength = characters.length;
                for (var i = 0; i < length; i++) {
                        result.push(characters.charAt(Math.floor(Math.random() *
                                charactersLength)));
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
                        if (keys[i] == 'formats') {
                                var f = new Array();
                                for (var it = 0; it < config.formats.length; it++) {
                                        f.push('.' + config.formats[it]);
                                }
                                input.setAttribute('accept', f.join(','));
                        }
                        if (keys[i] == 'total') {
                                if (config.total > 1) {
                                        input.setAttribute('multiple', true);
                                }
                                input.setAttribute('data-total', config.total);
                        }
                }
                input.integrity = config.integrity;
        },
        getFilesInfo: function (files) {
                console.log(files)
        },
        validation: function (input, config) {
                var getFilesInfo = this.getFilesInfo;
                input.addEventListener('change', function (event) {
                        var fileInfo = getFilesInfo(this.files);
                });
        },
        bind: function (inputs, profile) {
                /**
                 * $profile = array(
                        "formats" => array("jpeg", "jpg", "png"),
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
                        this.validation(inputs[i], config);
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