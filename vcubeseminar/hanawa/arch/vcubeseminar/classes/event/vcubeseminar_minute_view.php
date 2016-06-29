<?php
/**
 * @package    mod
 * @subpackage vcubeseminar
 * @copyright  V-Cube Inc.
 *
 */
namespace mod_vcubeseminar\event;
defined('MOODLE_INTERNAL') || die();

class vcubeseminar_minute_view extends \core\event\base {
	protected function init() {
		$this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
		$this->data['edulevel'] = self::LEVEL_OTHER;
		$this->data['objecttable'] = 'vcubeseminar';
		$this->data['level'] = self::LEVEL_OTHER;
	}

	public static function get_name() {
		return get_string('minute_reference', 'vcubeseminar');
	}

	public function get_description() {
		return get_string('minute_reference_msg', 'vcubeseminar');
	}

	public function get_url() {
		return new \moodle_url('mod/vcubeseminar/view.php', array('id' => $this->objectid));
	}

	public function get_legacy_logdata() {
		// Override if you are migrating an add_to_log() call.
		return array($this->courseid, 'vcubeseminar', 'minute view',
				'view.php?id='.$this->objectid,
				get_string('minute_reference_msg', 'vcubeseminar'), $this->contextinstanceid);
	}
}
