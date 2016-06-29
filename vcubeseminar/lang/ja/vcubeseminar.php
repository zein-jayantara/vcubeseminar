<?php

/**
 * Japanese strings for vcubeseminar
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage vcubeseminar
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] ='V-CUBE Seminar';
$string['modulename_help'] ='V-CUBE Seminarシステムへのセミナー予約、開催、記録の閲覧を行うことができます。';
$string['modulenameplural'] ='V-CUBE Seminar';
$string['vcubeseminar'] ='V-CUBE Seminar';
$string['pluginadministration'] ='V-CUBE Seminar管理画面';
$string['pluginname'] ='V-CUBE Seminar';

$string['domain'] ='APIドメイン';
$string['domaindesc'] ='V-CUBE SeminarのAPIドメインです。';
$string['account'] ='V-CUBE Seminarアカウント';
$string['accountdesc'] ='V-CUBE Seminarのアカウント名です。';
$string['password'] ='パスワード';
$string['passworddesc'] ='V-CUBE Seminarアカウントのパスワードです。';
$string['adminpassword'] ='Adminパスワード';
$string['adminpassworddesc'] ='V-Seminarの管理者パスワードです。';

$string['confname'] ='セミナー名';
$string['start_datetime'] ='セミナー開場日時';
$string['curtaintime'] ='セミナー開演日時';
$string['end_datetime'] ='セミナー終了日時';
$string['room'] ='セミナールーム';
$string['seminar_type'] ='セミナー種別';
$string['ondemand_seminar'] ='オンデマンドセミナー';
$string['live_seminar'] = 'ライブセミナー';
$string['ondemand'] = 'オンデマンド';
$string['timezone'] ='タイムゾーン';
$string['max_user'] ='参加者数';
$string['err_datetime'] ='セミナー開場、開演、終了日時が不正です。';
$string['require_message'] ='セミナールームを選択してください。';
$string['require_numeric'] ='半角数字で入力してください';
$string['room_error'] ='利用できるセミナールームがありません。管理者にお問い合わせください';
$string['err_open_meeting'] ='開催中または開催後のため編集できません。';
$string['err_name'] ='セミナー名を50文字以下で入力してください';
$string['err_confdate'] ='セミナー日程が重複しています';
$string['err_max'] ='参加者数を入力してください';
$string['err_no_name'] ='セミナー名を入力してください';

$string['pre_open_meeting'] ='このセミナーは現在開催待ちです。';
$string['open_meeting'] ='このセミナーは現在開催中です。';

$string['recording_reference'] ='映像';
$string['recording_reference_msg'] ='録画を参照しました';
$string['minute_reference'] ='議事録';
$string['minute_reference_msg'] ='議事録を参照しました';
$string['mobile_reference'] ='映像';
$string['mobile_reference_msg'] ='モバイルから参照しました';

$string['js_error_password'] ='半角英数1文字以上50文字以下で入力してください';
$string['entering'] ='入室';

$string['minute_log'] ='コンテンツ';
$string['set_password'] ='パスワード設定';
$string['delete_password'] ='パスワード削除';
$string['entry_history'] ='入退室履歴';
$string['viewing_history'] ='視聴履歴';
$string['viewing_starttime'] = '視聴開始';
$string['name'] ='名前';
$string['entry_datetime'] ='入室日時';
$string['leave_datetime'] ='退室日時';
$string['teacher'] ='教師';

$string['course_fullname'] ='コース名';
$string['room_name'] ='セミナー名';
$string['maccount'] ='Moodleのアカウント名';
$string['entering_datetime'] ='入室日時';
$string['teacher_flag'] ='教師権限の有無';
$string['tflag'] ='有';
$string['no_enteringlog'] ='入退室ログはありません。';
$string['no_viewinglog'] ='視聴ログはありません。';
$string['include_teacher'] ='教師を含む';

$string['resetbtn'] = '会議記録の再取得';

$string['whiteboard'] ='ホワイトボード';
$string['filecabinet'] ='ファイルキャビネット';
$string['banner'] ='バナー';
$string['is_animation'] ='アニメーション資料';
$string['isenabledownload'] = 'ファイルのダウンロードを可能にする';
$string['link_url'] = 'リンクURL';
$string['fileupload'] = 'ファイルアップロード';
$string['uploadfiles'] = '会議資料';
$string['err_filesize'] = 'アップロード可能なサイズは、画像は最大5MB、それ以外は20MBとなります。';
$string['err_bannersize'] = 'バナーに設定可能な画像サイズは最大1MBとなります。';
$string['err_noturl'] = 'URLを入力してください。';
$string['err_banner_noimage'] = 'リンクURLを有効にする場合は、バナー画像を設定してください。';