<?php
/**
 * Payments Module
 *
 * @package modules
 * @subpackage payments
 * @category Third Party Xaraya Module
 * @version 1.0.0
 * @copyright (C) 2016 Luetolf-Carroll GmbH
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <marc@luetolf-carroll.com>
 */
/**
 * Begin by choosing amount and currency
 *
 */
    function payments_user_amount()
    {
        if (!xarSecurityCheck('SubmitPayments')) return;

        //Psspl:Implemented the code for return url.
        //if(!xarVarFetch('return_url', 'array', $data['return_url'],  NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('allowEdit_Payment', 'int', $data['allowEdit_Payment'],   0,    XARVAR_DONT_SET)) {return;}
        
        //Psspl:Implemented the code for return url.
        $return_url_property = DataPropertyMaster::getProperty(array('name' => 'array'));       
        $return_url_property->initialization_associative_array = 1;         
        $return_url_property->checkInput('return_url');
        $data['return_url'] = $return_url_property->value;
        
        // Check for gateway
        $module_id = xarSession::getVar('clientmodule');
        $gateway = xarModVars::get('payments', 'gateway',$module_id);
        if (empty($gateway)) {
            return xarTplModule('payments','user','errors',array('layout' => 'no_gateway'));
        }
        // Check for the anonymous user
        $allowanonpay = xarModVars::get('payments', 'allowanonpay',$module_id);
        if (!xarUserIsLoggedIn() && !$allowanonpay) {
            xarController::redirect(xarModURL('roles','user','showloginform'));
            return true;
        }

        //Psspl:Added the code for saferpay gateway support.
        if (!xarVarFetch('errorFlag',      'int:0:', $errorFlag,   null,    XARVAR_DONT_SET)) {return;}
        if(!$errorFlag){
            xarSession::setVar('error_message' , "");
        }
        if (!xarVarFetch('DATA',      'str:', $saferpaydata,   null,    XARVAR_DONT_SET)) {return;}
        if (!xarVarFetch('b',         'str:', $gestpaydata,    null,    XARVAR_DONT_SET)) {return;}
        $data['authid'] = xarSecGenAuthKey();
        //Psspl:check if user selects 'MakeChanges'.
        $data['MakeChanges'] = null;
        if (!xarVarFetch('MakeChanges',      'int:0:', $MakeChanges,   null,    XARVAR_DONT_SET)) {return;}
        $data['MakeChanges'] = $MakeChanges;
        
        // Get the order object 
        $object = DataObjectMaster::getObject(array('name' => xarModVars::get('payments', 'orderobject')));
        
        //Psspl: modified the code for restoring order object values.
        $fields = unserialize(xarSession::GetVar('orderfields'));
        //Psspl:set the field values previously selected.
        if(($fields != null || $fields != '') && $MakeChanges) {
            //Psspl: modified the code for return url.
            $data['return_url'] = unserialize(xarSession::getVar('return_url'));
            xarSession::delVar('return_url');
                        
            $object->setFieldValues($fields);
            //Psspl : modified the code for resolving the issue of storing changed order fields values.
            $data['MakeChanges'] = null;
            
            if(isset($gestpaydata)){
                sys::import('modules.payments.class.' . 'gestpay');        
                $objgateway = new gestpay();
                $status = $objgateway->displayfailurestatus();                
            }
        }
        $fields = unserialize(xarSession::GetVar('paymentfields'));
        $data['object'] = $object;
        $data['properties'] = $object->getProperties();
        xarSession::SetVar('paymentfields',serialize($fields));
        return $data;
    }
?>