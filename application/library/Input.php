<?php
/**
 * 输入工具
 *
 * @author dawei
 */
trait Input {

    public function input_getpost_param($name, $default_value = null) {
        $data = array_merge($_POST, $_GET);
        
        if (isset($data["$name"])) {
            return trim($data["$name"]);
        } else {
            return $default_value;
        }

    }

    public function input_post_param($name, $default_value = null) {

        if (isset($_POST["$name"])) {
            return trim($_POST["$name"]);
        } else {
            return $default_value;
        }

    }

    public function input_get_param($name, $default_value = null) {

        if (isset($_GET["$name"])) {
            return trim($_GET["$name"]);
        } else {
            return $default_value;
        }

    }

    public function input_getpost() {
        $data = array_merge($_POST, $_GET);

        return $data;

    }

    public function input_post() {
        $data = $_POST;

        return $data;

    }

    public function input_get() {
        $data = $_GET;

        return $data;

    }

}
