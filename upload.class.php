<?php
class Upload
{
    protected static $JSMode = 1; // 1 -> old ; 2 -> modern

    private static $JSObject = "Upload";
    private static $JSCall = "newUpload";
    protected static $key = "$2y$10\$krizQb2DpgDm40Fy1VLxiODhWqAiZxJ4HGOCmBVxNjIHvHN\/jLIYG";
    protected static $rootDir = null;
    protected static $fields = array();
    private static $profiles = array();

    public static function addProfile($name, $config)
    {
        /* $template = array(
          "url" => "where the request will be sent",
          "types" => array("jpeg", "jpg", "png"),
          "folder" => "./uploads/",
          "maxSize" => 260000,
          "maxFiles" => 10,
          "vars" => array(), // passa variaveis adicionais
          ); */
        if (isset($config['folder'])) {
            $last = substr($config['folder'], -1);
            if ($last == '/' || $last == "\\") {
                $config['folder'] = substr($config['folder'], 0, -1);
            }
        }
        $config['integrity'] = self::AESencrypt($config, self::getKey());
        unset($config['folder']);
        self::$profiles[md5($name)] = array(
            "config" => $config
        );
    }

    public static function getProfile($ciphered)
    {
        return self::AESdecrypt($ciphered, self::getKey());
    }

    public static function set($input, $profile)
    {
        self::$fields[] = array('input' => $input, 'profile' => $profile);
    }

    public static function setRootDir($dir)
    {
        self::$rootDir = $dir;
    }

    public static function getKey()
    {
        return self::$key;
    }

    public static function recursive_utf8_encode($array)
    {
        if (is_array($array)) {
            return array_map('self::recursive_utf8_encode', $array);
        } else {
            return utf8_encode($array);
        }
    }

    public static function recursive_utf8_decode($array)
    {
        if (is_array($array)) {
            return array_map('self::recursive_utf8_decode', $array);
        } else {
            return utf8_decode($array);
        }
    }

    public static function AESencrypt($data, $key)
    {
        if (is_array($data)) {
            $data = json_encode(self::recursive_utf8_encode($data));
        }
        $keyEncrypt = base64_decode($key);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-128-CBC'));
        $encrypted = openssl_encrypt($data, 'AES-128-CBC', $keyEncrypt, 0, $iv);
        return base64_encode($encrypted . '::' . base64_encode($iv));
    }

    public static function AESdecrypt($data, $key)
    {
        $keyDecrypt = base64_decode($key);
        list($encryptedData, $iv) = array_pad(explode('::', base64_decode($data), 2), 2, null);
        $data = openssl_decrypt($encryptedData, 'AES-128-CBC', $keyDecrypt, 0, base64_decode($iv));
        $check = @json_decode($data, true);
        if ($check) {
            $data = self::recursive_utf8_decode($check);
        }
        return $data;
    }

    public static function setProfiles($tags = true, $profile = "all")
    {
        if ($tags) {
            echo "<script type='text/javascript'>";
            echo (self::$JSMode == 1 ? "var" : "const") . " uploadProfiles = " . json_encode($profile == 'all' ? self::recursive_utf8_encode(self::$profiles) : self::recursive_utf8_encode(self::$profiles[md5($profile)])) . ";";
            echo "</script>";
        } else {
            return json_encode($profile == "all" ? self::recursive_utf8_encode(self::$profiles) : self::recursive_utf8_encode(self::$profiles[md5($profile)]));
        }
    }

    public static function setTo($input, $profile, $tags = true)
    {
        if ($tags) {
            echo "<script type='text/javascript'>";
        }
        echo self::$JSObject . "." . self::$JSCall . "('" . $input . "','" . md5($profile) . "');";
        if ($tags) {
            echo "</script>";
        }
    }

    public static function saveFile($fileName, $data, $folder)
    {
        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
            chmod($folder, 0775);
        }
        $id = md5($fileName);
        $jsonData = array(
            "name" => $fileName,
            "date" => date("Y-m-d"),
            "status" => "uploading"
        );
        if (file_exists($folder . DIRECTORY_SEPARATOR . "upload.log.json")) {
            $log = self::recursive_utf8_decode(json_decode(file_get_contents($folder . DIRECTORY_SEPARATOR . "upload.log.json"), true));
            $log[$id] = $jsonData;
        } else {
            $log = array();
            $log[$id] = $jsonData;
        }
        file_put_contents($folder . DIRECTORY_SEPARATOR . "upload.log.json", json_encode(self::recursive_utf8_encode($log)));
        self::removeTrash($folder);
        $file = @fopen($folder . DIRECTORY_SEPARATOR . $fileName, 'a');
        if ($file) {
            $data = explode(',', $data);
            fwrite($file, base64_decode(str_replace(" ", "+", $data[1])));
            fclose($file);
            return true;
        } else {
            // script doesn't have permission to create folders or files.
            return false;
        }
    }

    private static function removeTrash($folder)
    {
        if (file_exists($folder . DIRECTORY_SEPARATOR . "upload.log.json")) {
            $json = self::recursive_utf8_decode(json_decode(file_get_contents($folder . DIRECTORY_SEPARATOR . "upload.log.json"), true));
            $date = date("Y-m-d");
            foreach ($json as $id => $data) {
                if ($data["date"] !== $date) {
                    @unlink($folder . DIRECTORY_SEPARATOR . $data["name"]);
                    unset($json[$id]);
                }
            }
            file_put_contents($folder . DIRECTORY_SEPARATOR . "upload.log.json", json_encode(self::recursive_utf8_encode($json)));
        }
        return true;
    }

    public static function unsetLog($fileName, $folder)
    {
        if (file_exists($folder . DIRECTORY_SEPARATOR . "upload.log.json")) {
            $json = self::recursive_utf8_decode(json_decode(file_get_contents($folder . DIRECTORY_SEPARATOR .  "upload.log.json"), true));
            unset($json[md5($fileName)]);
            if (empty($json)) {
                @unlink($folder . DIRECTORY_SEPARATOR . "upload.log.json");
                return true;
            } else {
                file_put_contents($folder . DIRECTORY_SEPARATOR . "upload.log.json", json_encode(self::recursive_utf8_encode($json)));
                return true;
            }
        }
    }

    public static function delete($path)
    {
        if (file_exists($path) && !preg_match("/(\.)(php|js|css|html)/", $path)) {
            if (unlink($path)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function setNewName($source)
    {
        $aux = explode(".", $source);
        $extension = $aux[count($aux) - 1];
        unset($aux[count($aux) - 1]);
        $aux = implode('.', $aux);
        return preg_replace("/[^a-zA-Z0-9\.]/", "-", $aux) . md5($source) . md5(time()) . '.' . $extension;
    }

    public static function init($tags = true)
    {
        self::setProfiles($tags);
        foreach (self::$fields as $key => $field) {
            self::setTo($field['input'], $field['profile']);
        }
    }
}
