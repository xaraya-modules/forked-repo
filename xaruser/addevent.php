<?php
/**
 * Let user add an event
 *
 * @package modules
 * @copyright (C) 2005-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Julian Module
 * @link http://xaraya.com/index.php/release/319.html
 * @author Julian Module Development Team
 */
/**
 * Generates a form for adding an event.
 *
 * @copyright (C) 2004 by Metrostat Technologies, Inc.
 * @link http://www.metrostat.net
 *
 * initial template: Roger Raymond
 * @author Jodie Razdrh/John Kevlin/David St.Clair
 * @param int $cal_date The date set to add an event to
 * @return array $data for template
 */
function julian_user_addevent($args)
{
    extract ($args);

    // This prevents users from viewing something they are not suppose to.
    if (!xarSecurityCheck('AddJulian')) return;

    // See if we get passed a date, otherwise add the date of today
    // TODO: that's a big range! What does it mean? Is that YYYYMMDD, 1800 to 2050?
    if (!xarVarFetch('cal_date', 'int:18000000:20500000', $cal_date, xarLocaleFormatDate('%Y%m%d'))) return;

    // Build description for the item we want the hooks (i.e. category) for.
    $item = array();
    $item['module'] = 'julian';
    $item['multiple'] = false;

    // Get the hooks for this item.
    $hooks = xarModCallHooks('item', 'new', '', $item);

    // Deal with no-hook scenario (the template then must get an empty hook-array)
     if (empty($hooks)) {
        $data['hookoutput'] = array();
    } else {
        $data['hookoutput'] = $hooks;
    }

    // Add bullet for header
    $data['Bullet'] = '&'.xarModGetVar('julian', 'BulletForm').';';

    $data['todays_month'] = date("n", strtotime($cal_date));
    $data['todays_year'] = date("Y", strtotime($cal_date));
    $data['todays_day'] = date("d", strtotime($cal_date));

    //building share options
    $share_group = xarModGetVar('julian','share_group');
    // Following is nasty thing
    // Get the group with the id of share group
    // Work around because roles_get is giving errors
    $groups = xarModAPIFunc('roles','user','getallgroups');
    foreach ($groups as $group) {
        if ($share_group == $group['uid']) {
            $julgroup = $group;
        }
    }
    $data['group_validation'] = 'group:' . $julgroup['name'];
    //$data['share_options'] = xarModAPIFunc('julian','user','getuseroptions',array('uids'=>''));
    $data['cal_date'] = $cal_date;

    // TODO Turn these into API functions.
    // Building duration minute options
    // Get the interval
    $StartMinInterval = xarModGetVar('julian', 'StartMinInterval');
    if ($StartMinInterval == 1) {
        $sminend = 60;
    } elseif ($StartMinInterval == 5) {
        $sminend = 56;
    } elseif ($StartMinInterval == 10) {
        $sminend = 51;
    } elseif ($StartMinInterval == 15) {
        $sminend = 46;
    }

    // TODO: move this to the template.
    $start_minute_options = '';
    for($i = 0; $i < $sminend; $i = $i + $StartMinInterval) {
        $j = str_pad($i, 2, '0', STR_PAD_LEFT);
        $start_minute_options .= '<option value="' . $j . '"';
        $start_minute_options .= '>' . $j . '</option>';
    }
    $data['start_minute_options'] = $start_minute_options;

    // Building duration minute options
    // Get the interval
    $DurMinInterval = xarModGetVar('julian', 'DurMinInterval');
    if ($DurMinInterval == 1) {
        $minend = 60;
    } elseif ($DurMinInterval == 5) {
        $minend = 56;
    } elseif ($DurMinInterval == 10) {
        $minend = 51;
    } elseif ($DurMinInterval == 15) {
        $minend = 46;
    }

    // TODO: move this to the template.
    $dur_minute_options = '';
    for($i = 0; $i < $minend; $i = $i + $DurMinInterval) {
        $j = str_pad($i, 2, '0', STR_PAD_LEFT);
        $dur_minute_options .= '<option value="' . $j . '"';
        $dur_minute_options .= '>' . $j . '</option>';
    }

    $data['dur_minute_options'] = $dur_minute_options;

    // Add authentication
    $data['authid']=xarSecGenAuthKey();

    return $data;
}
?>