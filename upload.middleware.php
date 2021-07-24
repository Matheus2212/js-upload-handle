<?php

if ($_POST) {
        header("Content-type:application/json");
        include('upload.class.php');
        $return = array(
                'status' => false,
                'message' => '',
        );
        $data = @json_decode($_POST['upload'], true);
        if ($data) {
                if (!isset($data['config']['integrity'])) {
                        $return['status'] = false;
                        $return['message'] = "Warning! Upload integrity has been compromised. Upload failed";
                } else {
                        $profile = Upload::getProfile($data['config']['integrity']);
                        if (!isset($data['fileNameSet']) || !$data['fileNameSet']) {
                                $data['fileName'] = Upload::setNewName($data['fileName']);
                        }
                        if (Upload::saveFile($data['fileName'], $data['data'], $profile['folder'])) {
                                $return['status'] = true;
                                $return['fileName'] = $data['fileName'];
                                $return['fileNameSet'] = true;
                                $return['message'] = "File Uploaded";
                        } else {
                                $return['message'] = "File weren't Uploaded";
                        }
                }
        }
        echo json_encode(Upload::recursive_utf8_encode($return));
}
