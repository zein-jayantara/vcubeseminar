<?php
define('COOLTIME',60);//議事録取得のクールタイム　会議終了後からこの時間が経過するまで議事録は取得しない
define('FORMAT','Y-m-d H:i-');//2014-10-30 18:00-
//ドキュメントの最大サイズ
define('DOCUMENT_SIZE_LIMIT',20*1024*1024);//20MB
//画像の最大サイズ
define('PIC_SIZE_LIMIT',5*1024*1024);//5MB
//ドキュメントをアップロードするときの待ち時間
define('DOCUMENT_SLEEP_TIME',10);// add_documentのクールタイム
//画像をアップロードするときの待ち時間
define('PIC_SLEEP_TIME',1);// add_documentのクールタイム

//ドキュメントとして扱うファイルの拡張子
global $DOCUMENT;
$DOCUMENT=array('.doc','.docx','.xls','.xlsx','.ppt','.pptx','.vsd','.pdf');

//画像として扱うファイルの拡張子
global $PIC;
$PIC=array('.bmp','.gif','.jpg','.png','.tif','.eps','.psd');
