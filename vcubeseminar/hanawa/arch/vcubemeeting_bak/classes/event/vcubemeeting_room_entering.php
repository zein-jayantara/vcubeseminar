<?php
/**
 * @package    mod
 * @subpackage vcubemeeting
 * @copyright  V-Cube Inc.
 *
 */
namespace mod_vcubemeeting\event;
defined('MOODLE_INTERNAL') || die();

class vcubemeeting_room_entering extends \core\event\base {
	protected function init() {
		$this->data['crud'] = 'c'; // c(reate), r(ead), u(pdate), d(elete)
		$this->data['edulevel'] = self::LEVEL_OTHER;
		$this->data['objecttable'] = 'vcubemeeting';
		$this->data['level'] = self::LEVEL_OTHER;
	}

	public static function get_name() {
		return get_string('event_entering_room', 'vcubemeeting');
	}

	public function get_description() {
		return get_string('event_entering_room_msg', 'vcubemeeting', $this->userid);
	}

	public function get_url() {
		return new \moodle_url('mod/vcubemeeting/view.php', array('id' => $this->objectid));
	}

	public function get_legacy_logdata() {
	// Override if you are migrating an add_to_log() call.
		return array($this->courseid, 'vcubemeeting', 'entering',
				'view.php?id='.$this->objectid,
				get_string('event_entering_room_msg', 'vcubemeeting', $this->userid), $this->contextinstanceid);
	}
}
