<?php
/**
 * AddressBook user getAddressList
 *
 * @package modules
 * @copyright (C) 2002-2007 Chad Kraeft
 * @license GPL {http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage AddressBook Module
 * @author Chad Kraeft <cdavidkraeft@miragelab.com>
 * Based on pnAddressBook by Thomas Smiatek <thomas@smiatek.com>
 */
include_once('modules/addressbook/xarglobal.php');
/**
 * Retrieves the list of address
 *
 * @return mixed
 */
function dossier_userapi_getall_addressbook($args)
{
    extract($args);

    // Get the menu values
    $menuValues = xarModAPIFunc('addressbook','user','getmenuvalues');
    foreach ($menuValues as $key=>$value) {
        $output[$key] = $value;
    }

    $addresslist = array();

    // SQL Query
    $dbconn =& xarDBGetConn();
    $xarTables =& xarDBGetTables();
    $address_table = $xarTables['addressbook_address'];

    // Note Searchorder
    $output['sql'] = "SELECT * FROM $address_table";
//    if ($output['sortview'] != 1) {
//        $sort_1 = xarModGetVar('addressbook', 'sortorder_1');
//        $output['sql'] = "SELECT *, CONCAT($sort_1) AS listname FROM $address_table";
//    }
//    else {
//        $sort_2 = xarModGetVar('addressbook', 'sortorder_2');
//        $output['sql'] = "SELECT *, CONCAT($sort_2) AS listname FROM $address_table";
//    }

    // Get user id
    if (xarUserIsLoggedIn()) { $user_id = xarUserGetVar('uid');}
    else {$user_id = 0;}
    $output['user_id'] = $user_id;

    // Filter Companies
    if (!isset($company)) {
        $output['company'] = "";
    } else {
        $output['company'] = $company;
        $output['sql'] .= " AND (company = \"".$output['company']."\")";
    }

    // Retrieve all the custom fields, we use this throughout.
    $custFields = array(); // xarModAPIFunc('addressbook','user','getcustfieldinfo',array('flag'=>1));

    // Search
    if ($output['formSearch']) {
        $output['sql'] .= " AND (lname LIKE '%".$output['formSearch']."%'
                  OR fname LIKE '%".$output['formSearch']."%'
                  OR company LIKE '%".$output['formSearch']."%'
                  OR title LIKE '%".$output['formSearch']."%'
                  OR city LIKE '%".$output['formSearch']."%'
                  OR address_1 LIKE '%".$output['formSearch']."%'
                  OR address_2 LIKE '%".$output['formSearch']."%'
                  OR zip LIKE '%".$output['formSearch']."%'
                  OR country LIKE '%".$output['formSearch']."%'
                  OR state LIKE '%".$output['formSearch']."%'
                  OR note LIKE '%".$output['formSearch']."%'
                  OR contact_1 LIKE '%".$output['formSearch']."%'
                  OR contact_2 LIKE '%".$output['formSearch']."%'
                  OR contact_3 LIKE '%".$output['formSearch']."%'
                  OR contact_4 LIKE '%".$output['formSearch']."%'
                  OR contact_5 LIKE '%".$output['formSearch']."%')";

        foreach($custFields as $custField) {
            if ((!strstr($custField['custType'],_AB_CUSTOM_BLANKLINE)) && (!strstr($custField['custType'],_AB_CUSTOM_HORIZ_RULE))) {
                if (strstr($custField['custType'],_AB_CUST_TEST_STRING)) {
                    $output['sql'] .= " OR ".$custField['colName']." LIKE '%".$output['formSearch']."%'";
                }
            }
        }
    }

    // Sort
    $sortCols = array("sortname");

//die("sortCols: ".serialize($sortCols));
    $output['sql'] .= " ORDER BY ";
    foreach ($sortCols as $sortCol) {
        $output['sql'] .= $sortCol.",";
    }
    $output['sql'] = rtrim($output['sql'],",");
    $output['sql'] .= " ASC";

    if (!$output['total']) {
        $numRec =& $dbconn->Execute($output['sql']);
        $output['total'] = $numRec->RecordCount();
        $output['page'] = 1;
        $numRec->Close();
    }
    
    if (!is_int($output['total'])) {
        xarErrorSet(XAR_USER_EXCEPTION, _AB_ERR_INFO, new abUserException(xarML('There are no records to show in this view'))); //gehDEBUG
    }
    
    $items = -1; //xarModGetVar('addressbook', 'itemsperpage');
    $result =& $dbconn->Execute($output['sql']);

    if ($dbconn->ErrorNo() != 0) {
        xarErrorSet(XAR_USER_EXCEPTION, _AB_ERR_ERROR, new abUserException("sql = ".$output['sql']));
    }
    
    //Show Result

    // No Records found!
    if ($output['total'] < 1) {
        return $addresslist;
    }

    /**
     * Get the title of each column to be displayed.
     */
    if ($output['sortview'] != 1) {
        $output['headers'] = xarModAPIFunc('addressbook','user','getlistheader',array('sort'=>1));
    }
    else {
        $output['headers'] = xarModAPIFunc('addressbook','user','getlistheader',array('sort'=>2));
    }

    /**
     * Get the prefix decodes if we are to display them
     */
    $prefixes = array();
    if (xarModGetVar('addressbook', 'display_prefix')) {
        $prefixes = xarModAPIFunc('addressbook','util','getitems',array('tablename'=>'prefixes'));
    }

    $abData = array('id'            => ''
                   ,'cat_id'        => ''
                   ,'prefix'        => ''
                   ,'lname'         => ''
                   ,'fname'         => ''
                   ,'sortname'      => ''
                   ,'title'         => ''
                   ,'company'       => ''
                   ,'sortcompany'   => ''
                   ,'img'           => ''
                   ,'zip'           => ''
                   ,'city'          => ''
                   ,'address_1'     => ''
                   ,'address_2'     => ''
                   ,'state'         => ''
                   ,'country'       => ''
                   ,'contact_1'     => ''
                   ,'contact_2'     => ''
                   ,'contact_3'     => ''
                   ,'contact_4'     => ''
                   ,'contact_5'     => ''
                   ,'c_label_1'     => ''
                   ,'c_label_2'     => ''
                   ,'c_label_3'     => ''
                   ,'c_label_4'     => ''
                   ,'c_label_5'     => ''
                   ,'c_main'        => ''
                   ,'custom_1'      => ''
                   ,'custom_2'      => ''
                   ,'custom_3'      => ''
                   ,'custom_4'      => ''
                   ,'note'          => ''
                   ,'user'          => ''
                   ,'private'       => ''
                   ,'last_updt'     => '');


    foreach($custFields as $custField) {
        $abData[$custField['colName']] = '';
    }


    // Retrieve all records and format as needed for display. The 'searchResults' var is temp
    // only and 'displayRows' is used be the template to display the data
    for (; !$result->EOF; $result->MoveNext()) {
    
        $index = 0;
        foreach ($abData as $key=>$value) {
            $abData[$key] = $result->fields[$index++];
        }
        // Only doing this step because I'm too lazy to change all the variable refs to the $abData. I use it as a temp holding place to build in all the custom
        // fields
        extract ($abData);

        $displayRow = array();

        /*
         * Step 1
         */
        if ($output['sortview'] != 1) {
            $sortCols = explode(',',xarModGetVar('addressbook', 'sortorder_1'));
        }
        else {
            $sortCols = explode(',',xarModGetVar('addressbook', 'sortorder_2'));
        }

        /*
         * Step 2
         */

        $displayName = '';

        if ((!empty($fname) && !empty($lname)) ||
            (!empty($fname) || !empty($lname))) {
            if (xarModGetVar('addressbook', 'name_order')==_AB_NO_FIRST_LAST) {
                if (!empty($prefixes) && $prefix > 0) {
                    $displayName .= $prefixes[$prefix-1]['name'].' ';
                }
                $displayName .= xarVarPrepHTMLDisplay($fname).' '.xarVarPrepHTMLDisplay($lname);
            } else {
                if (!empty($lname)) {
                    $displayName .= xarVarPrepHTMLDisplay($lname).', ';
                }
                if (!empty($prefixes) && $prefix > 0) {
                    $displayName .= $prefixes[$prefix-1]['name'].' ';
                }
                $displayName .= xarVarPrepHTMLDisplay($fname);
            }
        }
        else {
            if (!empty($company)) {
                $displayName .= xarVarPrepHTMLDisplay($company);
            }
        }

        $displayRow[] = trim($displayName,",");

        /*
         * Step 3
         */
        if ($sortCols[1] == 'sortname') {
            if (xarModGetVar('addressbook', 'name_order')==1) {
                if ((!empty($fname)) && (!empty($lname))) {
                    $displayRow[] = xarVarPrepHTMLDisplay($fname).' '.xarVarPrepHTMLDisplay($lname);
                } else {
                    $displayRow[] = xarVarPrepHTMLDisplay($fname).xarVarPrepHTMLDisplay($lname);
                }
            }
            else {
                if ((!empty($lname)) && (!empty($fname))) {
                    $displayRow[] = xarVarPrepHTMLDisplay($lname).', '.xarVarPrepHTMLDisplay($fname);
                } else {
                    $displayRow[] = xarVarPrepHTMLDisplay($lname).xarVarPrepHTMLDisplay($fname);
                }
            }
        }
        else {
            if ($sortCols[1] == 'sortcompany') {
                if (!empty($company)) {
                    $displayRow[] = xarVarPrepHTMLDisplay($company);
                } else {
                    $displayRow[] = '&nbsp;';
                }
            } else {
                $displayRow[] = xarVarPrepHTMLDisplay($$sortCols[1]);
            }
        }

        /*
         * Step 4 - Check for any custom fields to display
         */
        foreach($custFields as $custField) {
            if ($custField['custDisplay']) {
                switch ($custField['custType']) {
                    case 'int(1) default NULL':
                        if ($$custField['colName']) {
                            $displayRow[] = '<acronym title="'.$custField['custLabel'].'">'.$custField['custShortLabel'].'</acronym>';
                        } else {
                            $displayRow[] = "&nbsp;";
                        }
                        break;
                    default:
                        $displayRow[] = $$custField['colName'];
                        break;
                }
            }
        }

        /*
         * Step 5 - Format Contact information
         */
        switch($c_main) {
            case 0:
                if(!xarModAPIFunc('addressbook','util','is_email',array('email'=>$contact_1))) {
                    if(!xarModAPIFunc('addressbook','util','is_url',array('url'=>$contact_1))) {
                        if (!empty($contact_1)) {
                            $displayRow[] = xarVarPrepHTMLDisplay($contact_1);
                        } else {
                            $displayRow[] = '&nbsp;';
                        }
                    }
                    else {
                        $displayRow[] = '<a href="'.xarVarPrepHTMLDisplay($contact_1).'" target="_blank">'.xarVarPrepHTMLDisplay($contact_1).'</a>';
                    }
                }
                else {
                    $displayRow[] = '<a href="mailto:'.xarVarPrepHTMLDisplay($contact_1).'">'.xarVarPrepHTMLDisplay($contact_1).'</a>';
                }
                break;
            case 1:
                if(!xarModAPIFunc('addressbook','util','is_email',array('email'=>$contact_2))) {
                    if(!xarModAPIFunc('addressbook','util','is_url',array('url'=>$contact_2))) {
                        if (!empty($contact_2)) {
                            $displayRow[] = xarVarPrepHTMLDisplay($contact_2);
                        } else {
                            $displayRow[] = '&nbsp;';
                        }
                    }
                    else {
                        $displayRow[] = '<a href="'.xarVarPrepHTMLDisplay($contact_2).'">'.xarVarPrepHTMLDisplay($contact_2).'</a>';
                    }
                }
                else {
                    $displayRow[] = '<a href="mailto:'.xarVarPrepHTMLDisplay($contact_2).'">'.xarVarPrepHTMLDisplay($contact_2).'</a>';
                }
                break;
            case 2:
                if(!xarModAPIFunc('addressbook','util','is_email',array('email'=>$contact_3))) {
                    if(!xarModAPIFunc('addressbook','util','is_url',array('url'=>$contact_3))) {
                        if (!empty($contact_3)) {
                            $displayRow[] = xarVarPrepHTMLDisplay($contact_3);
                        } else {
                            $displayRow[] = '&nbsp;';
                        }
                    }
                    else {
                        $displayRow[] = '<a href="'.xarVarPrepHTMLDisplay($contact_3).'">'.xarVarPrepHTMLDisplay($contact_3).'</a>';
                    }
                }
                else {
                    $displayRow[] = '<a href="mailto:'.xarVarPrepHTMLDisplay($contact_3).'">'.xarVarPrepHTMLDisplay($contact_3).'</a>';
                }
                break;
            case 3:
                if(!xarModAPIFunc('addressbook','util','is_email',array('email'=>$contact_4))) {
                    if(!xarModAPIFunc('addressbook','util','is_url',array('url'=>$contact_4))) {
                        if (!empty($contact_4)) {
                            $displayRow[] = xarVarPrepHTMLDisplay($contact_4);
                        } else {
                            $displayRow[] = '&nbsp;';
                        }
                    }
                    else {
                        $displayRow[] = '<a href="'.xarVarPrepHTMLDisplay($contact_4).'">'.xarVarPrepHTMLDisplay($contact_4).'</a>';
                    }
                }
                else {
                    $displayRow[] = '<a href="mailto:'.xarVarPrepHTMLDisplay($contact_4).'">'.xarVarPrepHTMLDisplay($contact_4).'</a>';
                }
                break;
            case 4:
                if(!xarModAPIFunc('addressbook','util','is_email',array('email'=>$contact_5))) {
                    if(!xarModAPIFunc('addressbook','util','is_url',array('url'=>$contact_5))) {
                        if (!empty($contact_5)) {
                            $displayRow[] = xarVarPrepHTMLDisplay($contact_5);
                        } else {
                            $displayRow[] = '&nbsp;';
                        }
                    }
                    else {
                        $displayRow[] = '<a href="'.xarVarPrepHTMLDisplay($contact_5).'">'.xarVarPrepHTMLDisplay($contact_5).'</a>';
                    }
                }
                else {
                    $displayRow[] = '<a href="mailto:'.xarVarPrepHTMLDisplay($contact_5).'">'.xarVarPrepHTMLDisplay($contact_5).'</a>';
                }
                break;
            default:
                if(!xarModAPIFunc('addressbook','util','is_email',array('email'=>$contact_1))) {
                    if(!xarModAPIFunc('addressbook','util','is_url',array('url'=>$contact_1))) {
                        if (!empty($contact_1)) {
                            $displayRow[] = xarVarPrepHTMLDisplay($contact_1);
                        } else {
                            $displayRow[] = '&nbsp;';
                        }
                    }
                    else {
                        $displayRow[] = '<a href="'.xarVarPrepHTMLDisplay($contact_1).'">'.xarVarPrepHTMLDisplay($contact_1).'</a>';
                    }
                }
                else {
                    $displayRow[] = '<a href="mailto:'.xarVarPrepHTMLDisplay($contact_1).'">'.xarVarPrepHTMLDisplay($contact_1).'</a>';
                }
                break;

        } // END switch

        $iteminfo = $abData;
        $iteminfo['id'] = $abData['id'];
        $iteminfo['displayName'] = $displayName;
        $iteminfo['displaydetails'] = $displayRow;
        $addresslist[$iteminfo['id']] = $iteminfo;
    } // END for $results
    
    return $addresslist;

} // END getAddressList

?>