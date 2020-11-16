<?php
/**
 * Pubsub Module
 *
 * @package modules
 * @subpackage pubsub module
 * @category Third Party Xaraya Module
 * @version 2.0.0
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.com/index.php/release/181.html
 * @author Pubsub Module Development Team
 * @author Chris Dudley <miko@xaraya.com>
 * @author Garrett Hunter <garrett@blacktower.com>
 * @author Marc Lutolf <mfl@netspan.ch>
 */
/**
 * Create a new item of the pubsub_event object
 *
 */
    sys::import('modules.dynamicdata.class.objects.master');
    
    function pubsub_admin_new_event()
    {
        if (!xarSecurityCheck('AddPubSub')) {
            return;
        }

        if (!xarVarFetch('name', 'str', $name, 'pubsub_events', XARVAR_NOT_REQUIRED)) {
            return;
        }
        if (!xarVarFetch('confirm', 'bool', $data['confirm'], false, XARVAR_NOT_REQUIRED)) {
            return;
        }

        $data['object'] = DataObjectMaster::getObject(array('name' => $name));
        $data['tplmodule'] = 'pubsub';
        
        if ($data['confirm']) {
        
            // Check for a valid confirmation key
            if (!xarSecConfirmAuthKey()) {
                return;
            }
            
            // Get the data from the form
            $isvalid = $data['object']->checkInput();
            
            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                return xarTplModule('pubsub', 'admin', 'new_event', $data);
            } else {
                // Good data: create the item
                $item = $data['object']->createItem();
                
                // Jump to the next page
                xarController::redirect(xarModURL('pubsub', 'admin', 'view_events'));
                return true;
            }
        }
        return $data;
    }