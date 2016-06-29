<?php


/**
 * Define all the backup steps that will be used by the backup_vcubemeeting_activity_task
 *
 * @package    mod
 * @subpackage vcubemeeting
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete vcubemeeting structure for backup, with file and id annotations
 */
class backup_vcubemeeting_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
    	global $DB;

    	$ver = $DB->get_field('config', 'value', array('name'=>'version'));
        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        //バックアップするテーブルの定義

        //メインテーブル
        $vcubemeeting = new backup_nested_element('vcubemeeting', array('id'), array('course',
        'name', 'intro', 'introformat', 'showdescription','roomid', 'meetingid',
        'reservationid', 'pass_flag', 'password', 'inviteurl', 'timecreated', 'timemodified'));

        //urlテーブル
        $vcubemeetingurls = new backup_nested_element('vcubemeetingurls');

        $vcubemeetingurl = new backup_nested_element('vcubemeetingurl', array('id'), array('instanceid',
        'meetingsequencekey', 'minutesflag', 'videoflag', 'timecreated', 'timemodified' ));

        //filesテーブル
        $vcubemeetingfiles = new backup_nested_element('vcubemeeting_files', array('id'), array('instanceid',
        		'fileid', 'documentid', 'download', 'timecreated', 'timemodified' ));


        //ステータステーブル
        $vcubemeetingstatus = new backup_nested_element('vcubemeeting_status', array('id'), array('instanceid',
        		'status', 'value', 'timecreated', 'timemodified'));




        // Build the tree
		$vcubemeeting->add_child($vcubemeetingurls);
		$vcubemeetingurls->add_child($vcubemeetingurl);
		$vcubemeeting->add_child($vcubemeetingfiles);
		$vcubemeeting->add_child($vcubemeetingstatus);

		if($ver < 2014051200){//moodle2.6まで
			//ログ
			$log = new backup_nested_element('log', array('id'), array('time',
					'userid', 'ip', 'course', 'module','cmid','action','url','info'));
		}else{//moodle2.7
			//ログ
			$log = new backup_nested_element('logstore_standard_log', array('id'), array('eventname',
					'component', 'action', 'target', 'objecttable','objectid','crud','edulevel','contextid','contextlevel','contextinstanceid','userid','courseid',
			'relateduserid','anonymous','other','timecreated','origin','ip','realuserid'));
		}
		$vcubemeeting->add_child($log);

        // Define sources
        //実際のバックアップデータ取得
        $vcubemeeting->set_source_table('vcubemeeting', array('id' => backup::VAR_ACTIVITYID));
        $sql = <<< SQL
SELECT * FROM {vcubemeetingurl}
WHERE instanceid = ?
SQL;
        $vcubemeetingurl->set_source_sql($sql, array(backup::VAR_PARENTID));

        $sql = <<< SQL
SELECT * FROM {vcubemeeting_files}
WHERE instanceid = ?
SQL;
        $vcubemeetingfiles->set_source_sql($sql, array(backup::VAR_PARENTID));

        $sql = <<< SQL
SELECT * FROM {vcubemeeting_status}
WHERE instanceid = ?
SQL;
        $vcubemeetingstatus->set_source_sql($sql, array(backup::VAR_PARENTID));

        if($userinfo){

        	if($ver < 2014051200){//moodle2.6まで
        	//log取得
	        $sql = <<< SQL
SELECT * FROM {log}
WHERE action = 'entering' AND cmid=?
SQL;
	        $log->set_source_sql($sql, array(backup::VAR_MODID));
        	}else{//moodle2.7から
        		$sql = <<< SQL
SELECT * FROM {logstore_standard_log}
WHERE component = 'mod_vcubemeeting' AND action = 'entering' AND contextid=?
SQL;
        		$log->set_source_sql($sql, array(backup::VAR_CONTEXTID));
        	}
        }


        // Define id annotations
        $log->annotate_ids('user', 'userid');

        //ファイルのバックアップ
        // Define file annotations
//         global $CFG;
         $vcubemeeting->annotate_files('mod_vcubemeeting', 'intro', null);
         $vcubemeeting->annotate_files('mod_vcubemeeting', 'content', null);

        // Return the root element (vcubemeeting), wrapped into standard activity structure
        return $this->prepare_activity_structure($vcubemeeting);
    }

}
