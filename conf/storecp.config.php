<?php
define('ENVIRONMENT','develop');

//7牛
define('SAFE_QINIU_ACCESSKEY','h8zgvg0hJzAqWJBFyqaMf7GUs8VX5kic8_tktJNg');
define('SAFE_QINIU_SECRETKEY','ZB5jyg1WMLTuaSakM0Bco65z-P1ZP1otudQVFY4K');
define('API_QINIU_DOMAIN', 'http://7xjcwz.com1.z0.glb.clouddn.com');

define('API_SMS_HOST','http://sms.api.ywl.internal.com/message/sms');

define('API_PAY_URL','http://pay.dev.didatrip.com/');
define('API_PAY_SECRET', 'a051fe1ec88311e4ae396c4008bff0be');

//asset
define('ASSET_URL', 'http://storecp.lxf.dev.com/');
define('ASSET_IMG_URL', ASSET_URL . '/images');
define('ASSET_IMG_PATH','/data/home/lxf/storecp/static/images');
define('ASSET_JS_URL', ASSET_URL . '/js');
define('ASSET_JS_PATH','/data/home/lxf/storecp/static/js');
define('ASSET_CSS_URL', ASSET_URL . '/css');
define('ASSET_CSS_PATH','/data/home/lxf/storecp/static/css');

// img server
define('API_SERVER_IMGURL','http://img.internal.com');

define('SAPI_HOST','http://sapi.lxf.dev.com/');
define('SAPI_SOURCE_ID','WEB0001');

define('SDATA_HOST','http://sdata.lxf.dev.com/');
define('SDATA_SOURCE_ID','WEB0001');

//passport
define('PASSPORT_HOST', 'http://passport.dev.didatrip.com/v2/');
define('PASSPORT_SYSCODE', '3');

define('PAYMENT_HOST','http://pay2.dev.didatrip.com/');

//短信接口
define('NOTIFICATION_API','http://notification.dev.didatrip.com');

//图片
define('IMG_SOURCE_ID','ADMIN0001');
define('IMG_API_HOST','http://img.lxf.dev.com/');

//电子发票
define('DZFP_HOST', 'http://111.202.226.70:8002/zxkp_base64/services/WKWebService?wsdl');
define('TAXPAYER_NUMBER', '110104201110174');

//有赞
define('KDT_APP_ID', 'f222469b08dcf5b3d7');
define('KDT_APP_SECERT', '9ed217880ab4b394243cef42ec9b7c92');

//商品兑换码
define('CDKEY_HOST','http://cdkey.lxf.dev.com/');
define('CDKEY_SOURCE_ID','IGET0001');

//客服host
define('KFAPI_HOST','http://kfapi.dev.didatrip.com/');