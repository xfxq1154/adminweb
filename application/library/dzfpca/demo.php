<?php

header("content-type:text/html; charset=utf-8");

// Dir
$here = realpath(dirname($_SERVER["SCRIPT_FILENAME"]));
$javaDir = $here.DIRECTORY_SEPARATOR."java";
$libDir = $here.DIRECTORY_SEPARATOR."lib";
$configDir = $here.DIRECTORY_SEPARATOR."config";
echo $here."<br>";

// Java.inc
require_once($javaDir.DIRECTORY_SEPARATOR."Java.inc");

// Library path
java_set_library_path(".".PATH_SEPARATOR.$libDir);

// Cert
$cerPath = $configDir.DIRECTORY_SEPARATOR."test.cer";
$pfxPath = $configDir.DIRECTORY_SEPARATOR."test.pfx";
$pfxPwd  = "1234";

// Test5 instance
$client  = new Java("Test5");

// Invoke methods
$src = "1234567890";
$sign = $client->signature($src, $pfxPath, $pfxPwd);
$verifySign = $client->verifySign($src, $sign, $cerPath);
$encrypt = $client->encryCfca($src, $cerPath);
$decrypt = $client->deEncryCfca($encrypt, $pfxPath, $pfxPwd);

// Print
echo "原文: ".$src."<br>";
echo "签名: ".$sign."<br>";
echo "验签: ".$verifySign."<br>";
echo "加密: ".$encrypt."<br>";
echo "解密: ".$decrypt."<br>";

?>