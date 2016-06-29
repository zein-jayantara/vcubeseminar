<?php
define('VCUBESEMINAR_FORMAT','Y-m-d H:i-');//2014-10-30 18:00-
//ドキュメントの最大サイズ
define('VCUBESEMINAR_DOCUMENT_SIZE_LIMIT',20*1024*1024);//20MB

//画像の最大サイズ
define('VCUBESEMINAR_PIC_SIZE_LIMIT',5*1024*1024);//5MB

//バナーの最大サイズ
define('VCUBESEMINAR_BANNER_SIZE_LIMIT',1024*1024);//1MB

//ドキュメントとして扱うファイルの拡張子
global $VCUBESEMINAR_DOCUMENT;
$VCUBESEMINAR_DOCUMENT=array('.doc','.docx','.xls','.xlsx','.ppt','.pptx','.pdf');

//画像として扱うファイルの拡張子
global $VCUBESEMINAR_PIC;
$VCUBESEMINAR_PIC=array('.bmp','.gif','.jpg','.png');