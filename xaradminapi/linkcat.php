<?php

/**
 * link items to categories
 * @param $args['cids'] Array of IDs of the category
 * @param $args['iids'] Array of IDs of the items
 * @param $args['basecids'] Array of IDs of the base category
 * @param $args['modid'] ID of the module
 * @param $args['itemtype'] item type

 * Links each cid in cids to each iid in iids

 * @param $args['clean_first'] If is set to true then any link of the item IDs
 *                             at iids will be removed before inserting the
 *                             new ones
 */
function categories_adminapi_linkcat($args)
{
    // Argument check
    if (isset($args['clean_first']) && $args['clean_first'] == true)
    {
        $clean_first = true;
    } else {
        $clean_first = false;
    }

    if (
        (!isset($args['cids'])) ||
        (!isset($args['iids'])) ||
        (!isset($args['modid']))
       )
    {
        $msg = xarML('Invalid Parameter Count');
        throw new BadParameterException(null,$msg);
    }
    $basecids = isset($args['basecids']) ? $args['basecids'] : array();
    if (isset($args['itemtype']) && is_numeric($args['itemtype'])) {
        $itemtype = $args['itemtype'];
    } else {
        $itemtype = 0;
    }
    if (!empty($itemtype)) {
        $modtype = $itemtype;
    } else {
        $modtype = 'All';
    }

    foreach ($args['cids'] as $cid) {
        $cat = xarModAPIFunc('categories',
                             'user',
                             'getcatinfo',
                             Array
                             (
                              'cid' => $cid
                             )
                            );
         if ($cat == false) {
            $msg = xarML('Unknown Category');
            throw new BadParameterException(null, $msg);
         }
    }

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $categorieslinkagetable = $xartable['categories_linkage'];

    if ($clean_first)
    {
        // Get current links
        $childiids = xarModAPIFunc('categories',
                                   'user',
                                   'getlinks',
                                   array('iids' => $args['iids'],
                                         'itemtype' => $itemtype,
                                         'modid' => $args['modid'],
                                         'reverse' => 0));
        if (count($childiids) > 0) {
            // Security check
            foreach ($args['iids'] as $iid)
            {
                foreach (array_keys($childiids) as $cid)
                {
                    if(!xarSecurityCheck('EditCategoryLink',1,'Link',"$args[modid]:$modtype:$iid:$cid")) return;
                }
            }
            // Delete old links
            $bindmarkers = '?' . str_repeat(',?',count($args['iids'])-1);
            $sql = "DELETE FROM $categorieslinkagetable
                    WHERE module_id = $args[modid] AND
                          itemtype = $itemtype AND
                          item_id IN ($bindmarkers)";
            $result = $dbconn->Execute($sql,$args['iids']);
            if (!$result) return;
        } else {
            // Security check
            foreach ($args['iids'] as $iid)
            {
                if(!xarSecurityCheck('SubmitCategoryLink',1,'Link',"$args[modid]:$modtype:$iid:All")) return;
            }
        }
    }

    foreach ($args['iids'] as $iid)
    {
       $i=0;
       foreach ($args['cids'] as $cid)
       {
          // Security check
          if(!xarSecurityCheck('SubmitCategoryLink',1,'Link',"$args[modid]:$modtype:$iid:$cid")) return;

          // Insert the link
          $sql = "INSERT INTO $categorieslinkagetable (
                    category_id,
                    item_id,
                    itemtype,
                    module_id,
                    basecategory)
                  VALUES(?,?,?,?,?)";
          $basecid = isset($basecids[$i]) ? $basecids[$i] : 0;
          $bindvars = array($cid, $iid, $itemtype, $args['modid'], $basecid);
          $result =& $dbconn->Execute($sql,$bindvars);
          if (!$result) return;
          $i++;
       }
    }

    /* Don't implement for now
    // Remove the entries of these categories from the summary table
    $categorieslinkagesummarytable = $xartable['categories_linkage_summary'];
    $bindmarkers = '?' . str_repeat(',?',count($args['cids'])-1);
    $sql = "DELETE FROM $categorieslinkagesummarytable
            WHERE module_id = $args[modid] AND
                  itemtype = $itemtype AND
                  category_id IN ($bindmarkers)";
    $result = $dbconn->Execute($sql,$args['cids']);

    // Insert the entries of these categories from the summary table
    foreach ($args['cids'] as $cid)
    {
      $sql = "INSERT INTO $categorieslinkagesummarytable (
                category_id,
                item_id,
                itemtype,
                module_id,
                links)
              VALUES(?,?,?,?,?)";
      $bindvars = array($cid, $iid, $itemtype, $args['modid'], 0);
      $result =& $dbconn->Execute($sql,$bindvars);
      if (!$result) return;
    }
    */

    return true;
}

?>