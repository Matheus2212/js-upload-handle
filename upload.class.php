<?php
class Upload
{
    protected static $JSMode = 1; // 1 -> old ; 2 -> modern

    private static $JSObject = "Upload";
    private static $JSCall = "newUpload";
    protected static $key = "00cb9d1409ea4bbe52d83b3adbb25452622d5b8f";
    protected static $rootDir = null;
    protected static $fields = array();
    private static $profiles = array();

    public static function addProfile($name, $config)
    {
        /* $template = array(
          "formats" => array("jpeg", "jpg", "png"),
          "folder" => "./uploads/",
          "maxSize" => 260000,
          "maxFiles" => 10,
          "vars" => array(), // passa variaveis adicionais
          ); */
        $config['integrity'] = self::AESencrypt($config, self::$key);
        unset($config['folder']);
        self::$profiles[md5($name)] = array(
            //"name" => md5($name),
            "config" => $config
            //$config
        );
    }

    public static function set($input, $profile)
    {
        self::$fields[] = array('input' => $input, 'profile' => $profile);
    }

    public static function setRootDir($dir)
    {
        self::$rootDir = $dir;
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
            array_map('self::recursive_utf8_decode', $array);
        } else {
            return utf8_decode($array);
        }
    }

    public static function AESencrypt($data, $key)
    {
        if (is_array($data)) {
            $data = json_encode(self::recursive_utf8_encode($data));
        }
        $key = base64_decode($key);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $data = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($data . '::' . $iv);
    }

    function AESdecrypt($data, $key)
    {
        $key = base64_decode($key);
        list($data, $iv) = array_pad(explode('::', base64_decode($data), 2), 2, null);
        $data = openssl_decrypt($data, 'aes-256-cbc', $key, 0, $iv);
        $check = @json_decode($data, true);
        if ($check) {
            $data = self::recursive_utf8_decode(($check));
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

    public static function init($tags = true)
    {
        self::setProfiles($tags);
        foreach (self::$fields as $key => $field) {
            self::setTo($field['input'], $field['profile']);
        }
    }
}
