<?php

class Upload {

    private static $timeout = 1; // tempo em dias que um arquivo enviado e/ou abortado permanecerá no sistema
    private static $modo = 1; // 1 -> copy, 2 -> AJAX (direto), 3 -> AJAX (quebrado)
    private static $arquivo = null;
    private static $arquivo_new = null;
    private static $arquivo_informacoes = array();
    private static $arquivo_tmp = null;
    private static $pasta = "./";
    private static $perfil = array();
    private static $binded_fields = array();
    private static $stream = false;

    public static function setModo($modo)
    {
        $modos = array(
            1, // move uploaded files
            2, // fopen 
            3 // ajax
        );
        if (in_array($modo, $modos)) {
            self::$modo = $modo;
        }
    }

    public static function addPerfil($nome, $perfil)
    {
        /* $template = array(
          "tipos" => array("jpeg", "jpg", "png"),
          "modo" => 1,2,3;
          "pasta" => "./uploads/",
          "tamanhoMaximo" => 260000,
          "quantidadeMaxima" => 10,
          "variaveis" => array(), // passa variaveis adicionais
          ); */
        $chave = md5($nome);
        $perfil['nome'] = $nome;
        self::$perfil[$chave] = array("nome" => $nome, "perfil" => $perfil);
        if (isset($perfil['modo'])) {
            self::setModo($perfil['modo']);
        }
    }

    private static function __uploadEncode($data)
    {
        if (is_array($data)) {
            $data = array_map("json_encode", $data);
        }
        $chave_criptografia = self::__uploadGetKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $criptografado = openssl_encrypt($data, 'aes-256-cbc', $chave_criptografia, 0, $iv);
        return base64_encode($criptografado . '::' . $iv);
    }

    private static function __uploadDecode($data)
    {
        $chave_criptografia = self::__uploadGetKey();
        list($encrypted_data, $iv) = array_pad(explode('::', base64_decode($data), 2), 2, null);
        $data = openssl_decrypt($encrypted_data, 'aes-256-cbc', $chave_criptografia, 0, $iv);
        $check = @json_decode($data, true);
        if ($check) {
            $data = array_map("utf8_decode", $check);
        }
        return $data;
    }

    private static function __uploadGetKey()
    {
        return sha1(md5("?:?#?!@%:::!@_-_-=+" . $_SERVER['HTTP_USER_AGENT']));
    }

    private static function getPerfil($arquivo)
    {
        $perfil = null;
        $tranca = false;
        if (!empty(self::$perfil)) {
            if (array_key_exists($arquivo, self::$perfil)) {
                $perfil = self::$perfil[$arquivo];
                $tranca = true;
            }
        }
        if (!empty(self::$perfil) && !empty(self::$binded_fields) && !$tranca) {
            foreach (self::$binded_fields as $chave => $binded) {
                if (in_array($arquivo, $binded)) {
                    $perfil = self::$perfil[$chave];
                    break;
                }
            }
        }

        return $perfil;
    }

    public static function bindPerfil($campo, $perfil)
    {
        if (!empty(self::$perfil)) {
            $chave = md5($perfil);
            $perfil = self::getPerfil($chave);
            if (!in_array($campo, self::$binded_fields)) {
                self::$binded_fields[$chave][] = $campo;
            }
        }
    }

    public static function setJS($campo, $perfil, $tags = true)
    {
        $JS = array();

        self::bindPerfil($campo, $perfil);
        $chave = md5($perfil);
        $perfil = self::getPerfil($chave);
        if ($perfil !== null) {
            $JS['nome'] = $perfil['nome'];
            $perfil = $perfil['perfil'];
            if (isset($perfil['tipos'])) {
                $JS['tipos'] = $perfil['tipos'];
            }
            if (isset($perfil['tamanhoMaximo'])) {
                $JS['tamanhoMaximo'] = $perfil['tamanhoMaximo'];
            }
            if (isset($perfil['quantidadeMaxima'])) {
                $JS['quantidadeMaxima'] = $perfil['quantidadeMaxima'];
            }
            if (isset($perfil['variaveis'])) {
                $JS['variaveis'] = $perfil['variaveis'];
            }
            if (isset($perfil['pasta'])) {
                $JS['pasta'] = self::__uploadEncode($perfil['pasta']);
            }
            if (!isset($perfil['modo'])) {
                $JS['modo'] = self::$modo;
            } else {
                $JS['modo'] = $perfil['modo'];
            }
            $utf8 = function (&$JS, &$utf8) {
                foreach ($JS as $chave => $valor) {
                    if (is_array($valor)) {
                        $utf8($valor, $utf8);
                    } else {
                        $JS[$chave] = utf8_encode($valor);
                    }
                }
            };
            $utf8($JS, $utf8);

            if ($tags) {
                echo "<script>";
            }

            echo "Upload.addPerfil(" . json_encode($JS) . ");";

            echo "Upload.setJS('" . $campo . "','" . $JS['nome'] . "');";

            if ($tags) {
                echo "</script>";
            }
        }
    }

    private function validarTipos($perfil)
    {
        if (isset($perfil["perfil"]["tipos"])) {
            $tipos = array_map(function ($tipo) {
                $tipo = strtolower($tipo);
                return $tipo;
            }, $perfil["perfil"]["tipos"]);
            $mimeType = self::getFileMimeType();
            $extensionArray = array_reverse(explode("/", $mimeType));
            $extension = preg_replace("/(\+|\=|\|\-|\*|\.)([a-zA_Z0-9]+)/", "", strtolower($extensionArray[0]));
            if (in_array($extension, $tipos)) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    private function validarTamanho($perfil)
    {
        if (isset($perfil["perfil"]["tamanhoMaximo"])) {
            $tamanhoMaximo = $perfil["perfil"]["tamanhoMaximo"];
            $tamanhoArquivo = self::$arquivo_informacoes['size'];
            if ($tamanhoArquivo <= $tamanhoMaximo) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    private function validarEnvio($perfil)
    {
        if ($perfil === null) {
            return true;
        }
        $tipo_permitido = self::validarTipos($perfil);
        $tamanho_permitido = self::validarTamanho($perfil);
        if ($tipo_permitido && $tamanho_permitido) {
            return true;
        } else {
            return false;
        }
    }

    private static function getFileMimeType()
    {
        if (!empty(self::$arquivo_informacoes)) {
            return self::$arquivo_informacoes['type'];
        }
        $mimetype = @mime_content_type(self::$arquivo_tmp);
        if ($mimetype) {
            return $mimetype;
        } else {
            return false;
        }
    }

    private static function setPasta($perfil)
    {
        if ($perfil == null) {
            return false;
        } else {
            if (isset($perfil['perfil']['pasta'])) {
                $pasta = $perfil['perfil']['pasta'];
                $last_character = substr($pasta, -1);
                self::$pasta = $pasta . ($last_character == "/" ? "" : "/");
            } else {
                return false;
            }
        }
    }

    private static function setFileNewName()
    {
        if (self::$arquivo !== null) {
            if (preg_match("/\./", self::$arquivo)) {
                $helper = array_reverse(explode(".", self::$arquivo));
                if (count($helper) > 1) {
                    $helper[1] = $helper[1] . "_" . uniqid();
                } else {
                    $helper[0] = $helper[0] . "_" . uniqid();
                }
                self::$arquivo_new = self::tratarString(implode(".", array_reverse($helper)));
            } else {
                self::$arquivo_new = self::tratarString(self::$arquivo . "_" . uniqid());
            }
        }
    }

    public static function tratarString($string)
    {
        $string = utf8_encode($string);
        return preg_replace(array("/\ /i", "/\ /i", "/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $string);
    }

    public static function enviar($configs = array())
    {
        self::limpar();
        if (!empty($configs)) {
            foreach ($configs as $config => $value) {
                if ($config == "pasta") {
                    self::setPasta($value);
                }
                if ($config == "modo") {
                    self::setModo($value);
                }
            }
        }
        if (self::$modo == 1) {
            if (isset($_FILES)) {
                $quantidade = 0;
                foreach ($_FILES as $arquivo => $informacoes) {
                    $perfil = self::getPerfil($arquivo);
                    self::setPasta($perfil);

                    if (!is_array($informacoes['name'])) {
                        self::$arquivo = $informacoes['name'];
                        self::$arquivo_tmp = $informacoes['tmp_name'];
                        self::$arquivo_informacoes = array(
                            "type" => $informacoes['type'],
                            "size" => $informacoes['size'],
                            "error" => $informacoes['error'],
                        );
                        self::setFileNewName();
                        if (self::validarEnvio($perfil)) {
                            if (@copy(self::$arquivo_tmp, self::$pasta . self::$arquivo_new)) {
                                $quantidade++;
                            }
                        }
                    } else {
                        $quantidade = 0;
                        $quantidadeLimite = ($perfil !== null ? (isset($perfil['quantidadeMaxima']) ? $perfil['quantidadeMaxima'] : 10) : 10);
                        foreach ($informacoes['name'] as $chave => $valor) {
                            if ($quantidade < $quantidadeLimite) {
                                self::$arquivo = $informacoes['name'][$chave];
                                self::$arquivo_tmp = $informacoes['tmp_name'][$chave];
                                self::$arquivo_informacoes = array(
                                    "type" => $informacoes['type'][$chave],
                                    "size" => $informacoes['size'][$chave],
                                    "error" => $informacoes['error'][$chave],
                                );
                                self::setFileNewName();
                                if (self::validarEnvio($perfil)) {
                                    if (@copy(self::$arquivo_tmp, self::$pasta . self::$arquivo_new)) {
                                        self::registrar();
                                        $quantidade++;
                                    }
                                }
                            }
                        }
                    }
                }
                return ($quantidade > 0);
            }
        }
        if (self::$modo == 2) {
        }
    }

    private static function registrar()
    {
        /*if (class_exists("db")) {
            $check = db::simples("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%uploads_ajax'");
            if (is_array($check) && !empty($check)) {
                db::insere(array("arquivo" => self::$arquivo, "caminho" => realpath(self::$pasta), "data" => db::data()), $check['TABLE_NAME']);
            } else {
                return false;
            }
        }*/
    }

    private static function limpar()
    {
        /*if (class_exists("db")) {
            $check = db::simples("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%uploads_ajax'");
            if (is_array($check) && !empty($check)) {
                $busca = db::busca("SELECT * FROM $check[TABLE_NAME] WHERE data<=DATE_SUB(CURDATE(), INTERVAL " . self::$timeout . " DAY)");
                if (!db::vazio()) {
                    while ($uploads = db::correr($busca)) {
                        @unlink($uploads['caminho'] . "/" . $uploads['arquivo']);
                    }
                    db::executa("DELETE FROM $check[TABLE_NAME] WHERE data<=DATE_SUB(CURDATE(), INTERVAL " . self::$timeout . " DAY)");
                }
            } else {
                return false;
            }
        }*/
    }
}
