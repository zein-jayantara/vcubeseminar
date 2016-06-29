<?php

/**
 *
 * @package    mod
 * @subpackage vcubeseminar
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $PAGE->requires->jquery();
    $PAGE->requires->js('/mod/vcubeseminar/vcubeseminar_setting.js');
    require_once($CFG->dirroot.'/mod/vcubeseminar/lib.php');

    //--- general settings -----------------------------------------------------------------------------------
//    $settings->add(new admin_setting_configtext('vcseminar_domain',
//        get_string('domain', 'vcubeseminar'), get_string('domaindesc', 'vcubeseminar'), '', PARAM_TEXT));
//    $settings->add(new admin_setting_configtext('vcseminar_id',
//    	get_string('account', 'vcubeseminar'), get_string('accountdesc', 'vcubeseminar'), '', PARAM_TEXT));
//    $settings->add(new admin_setting_configpasswordunmask('vcseminar_password',
//    	get_string('password', 'vcubeseminar'), get_string('passworddesc', 'vcubeseminar'), '', PARAM_TEXT));

    //Update and get available domains
    $domains = vcubeseminar_get_domains();

    $str = '<table class="admintable serminardomains generaltable"><thead>';
    $str .= '<tr>';
    $str .= '<th style="width:20%;">' . get_string('alias', 'vcubeseminar') . '</th>';
    $str .= '<th style="width:20%;">' . get_string('domain', 'vcubeseminar') . '</th>';
    $str .= '<th style="width:20%;">' . get_string('account', 'vcubeseminar') . '</th>';
    $str .= '<th style="width:20%;">' . get_string('password', 'vcubeseminar') . '</th>';
    $str .= '<th style="width:10%;"></th>';
    $str .= '<th></th>';
    $str .= '</tr>';
    $str .= '</thead><tbody>';
    foreach($domains as $domain) {
        $str .= '<tr class="domainrow' . $domain['id'] . '">';
        $str .= '<td><input type="hidden" name="id[' . $domain['id'] . ']" value="' . $domain['id'] . '" />';
        $str .= '<input type="text" name="alias[' . $domain['id'] . ']" value="' . $domain['alias'] . '" />';
        $str .= '</td><td>';
        $str .= '<input type="text" name="vcseminar_domain[' . $domain['id'] . ']" value="' . $domain['domain'] . '" />';
        $str .= '</td><td>';
        $str .= '<input type="text" name="vcseminar_id[' . $domain['id'] . ']" value="' . $domain['account'] . '" />';
        $str .= '</td><td>';
        $str .= '<input type="password" name="vcseminar_password[' . $domain['id'] . ']" value="' . $domain['password'] . '" />';
        $str .= '</td><td>';
        $str .= '<input type="submit" class="form-submit" title="' . get_string('domainlist_update', 'vcubeseminar') . '" id="vcubeseminarsubmitdomain' . $domain['id'] . '" value="' . get_string('domainlist_update', 'vcubeseminar') . '" /></span>';
        $str .= '</td><td>';
        $str .= '<span class="vcseminar_domain_list_view"><input type="button" class="form-delete" title="' . get_string('domainlist_delete', 'vcubeseminar') . '" id="vcubeseminardeletedomain' . $domain['id'] . '" value="' . get_string('domainlist_delete', 'vcubeseminar') . '" /></span>';
        $str .= '<span class="vcseminar_domain_list_edit" style="display:none"><input type="reset" class="form-reset" title="' . get_string('domainlist_cancel', 'vcubeseminar') . '" id="vcubeseminarcanceldomain' . $domain['id'] . '" value="' . get_string('domainlist_cancel', 'vcubeseminar') . '"></span>';
        $str .= '</td></tr>';
    }

    $str .= '</tbody><tfoot>';
    $str .= '<tr class="lastrow domainlist_add_row"><form action="/mod/vcubeseminar/domain.php?mode=send&sesskey=' . sesskey() . '" method="post">';
    $str .= '<td><input type="hidden" name="id[0]" value="0" /><span class="vcseminar_domain_list_regist">';
    $str .= '<input type="text" name="alias[0]" value="" />';
    $str .= '</span></td><td><span class="vcseminar_domain_list_regist">';
    $str .= '<input type="text" name="vcseminar_domain[0]" value="" />';
    $str .= '</span></td><td><span class="vcseminar_domain_list_regist">';
    $str .= '<input type="text" name="vcseminar_id[0]" value="" />';
    $str .= '</span></td><td><span class="vcseminar_domain_list_regist">';
    $str .= '<input type="password" name="vcseminar_password[]" value="" />';
    $str .= '</span></td><td><span class="vcseminar_domain_list_regist">';
    $str .= '<input type="submit" class="form-submit" title="' . get_string('domainlist_add', 'vcubeseminar') . '" value="' . get_string('domainlist_add', 'vcubeseminar') . '" />';
    $str .= '</span></td><td><span class="vcseminar_domain_list_regist">';
    $str .= '<input type="reset" class="form-clear" title="' . get_string('clear') . '" value="' . get_string('clear') . '" style="display:none;" />';
    $str .= '</span></td>';
    $str .= '</form></tr>';

    $str .= '</tfoot></table>';
    $str .= '<input type="hidden" name="domainlistdeleteconfirm" value="' . get_string('domainlist_delete_confirm', 'vcubeseminar') . '" />';
    
    //$str = htmlspecialchars($str);
//    $str .= '<span id="delete_icon_original" style="display:none;"><img src="' . $OUTPUT->pix_url('t/delete') . '" /></span>';

    $settings->add(new admin_setting_heading('vcubeseminar_domains_header', get_string('domainlist', 'vcubeseminar'), $str));
}
