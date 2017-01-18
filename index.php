<?php
/**
 * MidyCombo v1.0 (http://www.midy.me)
 * (C)2016-2099 MIDY ,Ltd..
 * Author: ZhiGuo Wang <977612005@qq.com>
 * Date: 2017/1/19
 * Time: 2:41
 */

/**
 * 是否开启压缩
 * */
$MINIFY = true;

/**
 * 本地文件目录
 * */
$YOUR_ROOT = '';

/**
 * CDN地址
 * */
$YOUR_CDN = 'http://cdn.bootcss.com/';

/**
 * 接收请求键名
 * */
$var = 'f';

require_once 'combo/CssMin.php';
require_once 'combo/JsMin.php';
/**
 * set e-tag cache
 */
function cache($etag){
    $etag = $etag; //标记字符串，可以任意修改
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag){
        header('Etag:'.$etag,true,304);
        exit;
    }
    else header('Etag:'.$etag);
}

//得到扩展名
function get_extend($file_name) {
    $extend =explode("." , $file_name);
    $va=count($extend)-1;
    return $extend[$va];
}

/**
 * logic begin
 */
$files = [];
$success = [];
$error = [];
//cdn上存在的各种可能的文件类型
$header = [
    'js' => 'Content-Type: application/x-javascript',
    'css' => 'Content-Type: text/css',
    'jpg' => 'Content-Type: image/jpg',
    'gif' => 'Content-Type: image/gif',
    'png' => 'Content-Type: image/png',
    'jpeg' => 'Content-Type: image/jpeg',
    'swf' => 'Content-Type: application/x-shockwave-flash'
];
$type = '';
foreach (explode(',',$_GET[$var]) as $key => $value) {
    if(empty($type)) {
        $type = get_extend($value);
    }
    //文件存在
    if(file_exists($YOUR_ROOT.$value)) {
        $in_str = file_get_contents($YOUR_ROOT.$value);
        $success[] = $value;
        //$files[] = "/* $value */\r\n";
        //处理文本
        if(preg_match('/js|css/',$type)){
            if($MINIFY == true && $type == 'js'){
                $files[] = JsMin::minify($in_str);
            }else if($MINIFY == true && $type == 'css'){
                $files[] = CssMin::minify($in_str);
            }else{
                $files[] = $in_str;
            }
        }else{
            //处理非文本
            $files[] = array($in_str);
        }
    }else{
        //文件不存在
        $in_sta = file($YOUR_CDN.$value);
        //文本的处理
        if(is_array($in_sta)){
            if(preg_match('/js|css/',$type)){
                $success[] = $value;
                //$files[] = "/* $YOUR_CDN$value */\r\n";
                $inner_str = join('',$in_sta);
                if($MINIFY == true && $type == 'js'){
                    $files[] = JSMin::minify($inner_str);
                }else if($MINIFY == true && $type == 'css'){
                    $files[] = cssmin::minify($inner_str);
                }else{
                    $files[] = $inner_str;
                }
            }else{
                //非文本的处理
                $files[] = $in_sta;
            }
        }else{
            $error[] = $value;
        }
    }
}
if(preg_match('/js|css/',$type)){
    $files = array_merge(
        ["/**********"],
        ["\r\n * MidyCombo v1.0 (https://github.com/lovemidiuser/MidyCombo)"],
        ["\r\n * Copyright 2017".(date('Y')>2017?date('-Y'):'')." MIDY,Ltd."],
        ["\r\n **********"],
        ["\r\n * Time:".date('Y-m-d H:i:s')],
        ["\r\n * Success:"],[implode(' ,',$success)],
        ["\r\n * Failed:"],[implode(' ,',$error)],
        ["\r\n **********/"],
        ["\r\n"],
        $files
    );
}
header("Access-Control-Allow-Origin:'http://www.ykyjgy.com'");
header("Expires: " . date("D, j M Y H:i:s", strtotime("now + 10 years")) ." GMT");
header($header[$type]);//文件类型
if(preg_match('/js|css/',$type)){
    $result = join("",$files);
}else{
    //非文本的处理
    $result = join("",$files[0]);
}
cache(md5($result));//etag,处理Etag是否多余?
echo $result;