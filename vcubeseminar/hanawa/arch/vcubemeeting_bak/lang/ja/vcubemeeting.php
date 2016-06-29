<?php

/**
 * Japanese strings for vcubemeeting
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage vcubemeeting
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] ='V-CUBE Meeting';
$string['modulename_help'] ='V-CUBE Meetingシステムへの会議予約、開催、記録の閲覧を行うことができます。';
$string['modulenameplural'] ='V-CUBE Meeting';
$string['vcubemeeting'] ='V-CUBE Meeting';
$string['pluginadministration'] ='V-CUBE Meeting管理画面';
$string['pluginname'] ='V-CUBE Meeting';

$string['domain'] ='APIドメイン';
$string['domaindesc'] ='V-CUBE MeetingのAPIドメインです。';
$string['account'] ='V-CUBE Meetingアカウント';
$string['accountdesc'] ='V-CUBE Meetingアカウント名です。';
$string['password'] ='パスワード';
$string['passworddesc'] ='V-CUBE Meetingアカウントのパスワードです。';
$string['adminpassword'] ='Adminパスワード';
$string['adminpassworddesc'] ='V-CUBE Meeting管理者パスワードです。';

$string['confname'] ='会議名';
$string['start_datetime'] ='会議開始日時';
$string['end_datetime'] ='会議終了日時';
$string['room'] ='会議室';
$string['timezone'] ='タイムゾーン';
$string['maxseat']='最大{$a}名';
$string['desktopshare0']='';
$string['desktopshare1']='PC画面共有';
$string['require_message'] ='会議室を選択してください。';
$string['room_error'] ='利用できる会議室がありません。管理者にお問い合わせください';
$string['noseeting'] ='設定しない';
$string['materialenter'] ='会議記録と入室';
$string['material'] ='会議記録';
$string['passeffective'] ='パスワード設定';
$string['err_meetingpassword'] ='「会議資料と入室」または「会議資料」を選んだ場合は必須です';
$string['err_datetime'] ='予約開始日時と予約終了日時が不正です。';
$string['err_confdate'] ='会議日程が重複しています';
$string['err_open_meeting'] ='開催中または開催後のため編集できません。';
$string['err_password'] ='パスワードが不正です（半角英数6文字以上16文字以下）';
$string['err_name'] ='会議名を50文字以下で入力してください';
$string['err_no_name'] ='会議名を入力してください';


$string['pre_open_meeting'] ='この会議は現在開催待ちです。';
$string['open_meeting'] ='この会議は現在開催中です。';

$string['minute'] ='議事録{$a}';
$string['video'] ='録画{$a}';

$string['event_entering_room'] ='入室しました。';
$string['event_entering_room_msg'] ='ユーザid{$a}が入室しました.';
$string['recording_reference'] ='録画資料参照';
$string['recording_reference_msg'] ='録画資料{$a}を参照しました';
$string['minute_reference'] ='議事録参照';
$string['minute_reference_msg'] ='議事録{$a}を参照しました';

$string['js_error_password'] ='半角英数6文字以上16文字以下で入力してください';
$string['js_stop_confernce'] ='この会議を中止してよろしいですか？';

$string['stop_confirence'] ='会議中止';
$string['participants'] ='参加者';
$string['number_of_participants'] ='{$a}名';
$string['entering'] ='入室';
$string['minute_log'] ='会議記録';
$string['set_password'] ='パスワード設定';
$string['delete_password'] ='パスワード解除';
$string['entry_history'] ='入室履歴';
$string['name'] ='名前';
$string['entry_datetime'] ='入室日時';

$string['course_fullname'] ='コース名';
$string['room_name'] ='会議名';
$string['maccount'] ='Moodleのアカウント名';
$string['entering_datetime'] ='入室日時';
$string['teacher_flag'] ='教師権限の有無';
$string['tflag'] ='有';
$string['no_enteringlog'] ='会議記録はありません。';
$string['include_teacher'] ='教師を含む';

$string['resetbtn'] = '会議記録の再取得';

$string['isenabledownload'] = 'ファイルのダウンロードを可能にする';
$string['fileupload'] = 'ファイルアップロード';
$string['uploadfiles'] = '会議資料';

$string['error'] = '変換エラー';
$string['uploading'] = '変換待ち';
$string['pending'] = '変換中';
$string['done'] = '変換完了';
$string['delete'] = '削除済み';

$string['status'] = '変換状態';
$string['filename'] = 'ファイル名';
$string['err_filesize'] = 'アップロード可能なサイズは、画像は最大5MB、それ以外は20MBとなります。';