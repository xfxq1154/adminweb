<?php
/**
 * 邮件发送工具
 *
 * @author ellis
 */
class Mail {
    /**
     * 发送邮件方法
     * TODO待优化
     * @param type $to
     * @param type $subject
     * @param type $body
     * @param type $from
     */
    public static function quickSend($to, $subject, $body, $from = null, $fromName = '') {


        if (empty($from)) {

            if (strpos($to, '@in1001.com') > 0) {
                $fromMail = 'luaxcn@gmail.com';
                $fromName = '千夜旅游网';
            } else {
                $fromMail = 'welcome@in1001.com';
                $fromName = '千夜旅游网';
            }
        }

        $headers = "";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        $headers .= "From: {$fromName} <{$fromMail}>\r\n";
        $result = mail($to, $subject, $body, $headers);
        return $result;
    }

}
