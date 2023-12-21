<?php
/**
 * Workflow Module Tracker for Symfony Workflow events
 *
 * This keeps track of the latest marking for each workflow after transition for all object items and users
 * It is deleted once the workflow is finished for that user and object item
 *
 * Note: each user can only handle 1 instance of a workflow for each object item (e.g. cd_loans for CD 123)
 * @checkme this also means that you cannot assign a workflow instance to another user even if they need to
 * handle the next step e.g. to review or approve it
 * @checkme extend this to be able to handle any kind of subject like module + itemtype + itemid too?
 *
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Workflow Module
 * @link http://xaraya.com/index.php/release/188.html
 * @author Workflow Module Development Team
 */

sys::import('modules.dynamicdata.class.objects.factory');
sys::import('modules.dynamicdata.class.objects.loader');

class xarWorkflowTracker extends xarObject
{
    protected static $objectName = 'workflow_tracker';
    protected static $fieldList = ['workflow', 'user', 'object', 'item', 'marking', 'updated'];
    protected static $paging = [];
    protected static $count = false;

    public static function init(array $args = []) {}

    // @checkme we want to be able to get items for a list of itemIds of a particular objectName here (= dataobjectlist)
    public static function getItems(string $workflowName = '', string $objectName = '', int|array $itemId = 0, string|array $marking = '', int|array $userId = 0, array $trackerIds = [], array $paging = [])
    {
        // we have a list of internal trackerIds for the items that we want to get/update/delete for some reason
        if (!empty($trackerIds)) {
            return static::getTrackerItems($trackerIds);
        }
        // we want to filter on any combination of elements to get tracker items here
        if (empty($userId)) {
            // we want to get tracker items for current user here
            $userId = xarSession::getVar('role_id') ?? 0;
        } elseif ($userId < 0) {
            // @checkme we want to get tracker items for all users here
            $userId = null;
        }
        //$objectList = DataObjectFactory::getObjectList(['name' => static::$objectName]);
        //$items = $objectList->getItems(['where' => "user eq $userId"]);
        $filter = [];
        if (!empty($workflowName)) {
            $filter[] = implode(",", ["workflow", "eq", $workflowName]);
        }
        if (!empty($objectName)) {
            $filter[] = implode(",", ["object", "eq", $objectName]);
        }
        if (!empty($itemId)) {
            if (is_array($itemId)) {
                $filter[] = implode(",", ["item", "in", implode(",", $itemId)]);
            } else {
                $filter[] = implode(",", ["item", "eq", (string) $itemId]);
            }
        }
        if (!empty($userId)) {
            if (is_array($userId)) {
                $filter[] = implode(",", ["user", "in", implode(",", $userId)]);
            } else {
                $filter[] = implode(",", ["user", "eq", (string) $userId]);
            }
        }
        if (!empty($marking)) {
            if (is_array($marking)) {
                $filter[] = implode(",", ["marking", "in", implode(",", $marking)]);
            } else {
                $filter[] = implode(",", ["marking", "eq", $marking]);
            }
        }
        // for paging params see DataObjectLoader = aligned with API params, not DD list params
        $params = ['filter' => $filter];
        if (!empty($paging)) {
            static::setPaging($paging);
        }
        if (!empty(static::$paging)) {
            $params += static::$paging;
        }
        $loader = new DataObjectLoader(static::$objectName, static::$fieldList);
        $items = $loader->query($params);
        // @checkme if we didn't ask for a count in paging, this will return false
        static::$count = $loader->count;
        return array_values($items);
    }

    public static function setPaging(array $paging = [])
    {
        $allowed = array_flip(['order', 'offset', 'limit', 'count']);
        //$allowed = array_flip(['order', 'offset', 'limit', 'filter', 'count', 'access']);
        static::$paging = array_intersect_key($paging, $allowed);
    }

    public static function getCount()
    {
        return static::$count;
    }

    // this method is overridden in xarWorkflowHistory to get the history for trackerId(s)
    public static function getTrackerItems(int|array $trackerIds = [], array $paging = [])
    {
        // we have a list of internal trackerIds for the items that we want to get/update/delete for some reason
        if (is_array($trackerIds)) {
            $loader = new DataObjectLoader(static::$objectName, static::$fieldList);
            // @todo add paging - not really used here, but in history
            return array_values($loader->getValues($trackerIds));
        }
        return [ static::getTrackerItem($trackerIds) ];
    }

    public static function getSubjectItems(string $subjectId, string $workflowName = '')
    {
        // get items for a particular subjectId = objectName.itemId or objectName for all DD object items
        [$objectName, $itemId] = explode('.', $subjectId . '.0');
        return static::getItems($workflowName, $objectName, (int) $itemId);
    }

    public static function getWorkflowItems(string $workflowName)
    {
        // get items for a particular workflow
        return static::getItems($workflowName);
    }

    public static function getObjectValues(string $objectName, array $itemIds, array $fieldList = [])
    {
        // with an empty fieldlist, let DataObjectMaster setup the fieldlist
        $loader = new DataObjectLoader($objectName, $fieldList);
        return $loader->getValues($itemIds);
    }

    public static function getTrackerItem(int $trackerId)
    {
        $objectRef = DataObjectFactory::getObject(['name' => static::$objectName, 'itemid' => $trackerId]);
        $trackerId = $objectRef->getItem();
        // @checkme bypass getValue() for properties here
        $item = $objectRef->getFieldValues([], 1);
        if (empty($item['workflow'])) {
            return;
        }
        return $item;
    }

    public static function getItem(string $workflowName, string $objectName, int $itemId, string $marking = '', int $userId = 0, int $trackerId = 0)
    {
        // we have the internal trackerId of the item that we want to get/update/delete for some reason
        if (!empty($trackerId)) {
            return static::getTrackerItem($trackerId);
        }
        // we want to get the tracker for a particular workflow, object item and user here, regardless of the marking
        if (empty($userId)) {
            $userId = xarSession::getVar('role_id') ?? 0;
        }
        $oldItems = static::getItems($workflowName, $objectName, $itemId, '', $userId);
        if (empty($oldItems)) {
            // nothing to do here
            $oldItem = null;
        } elseif (count($oldItems) < 2) {
            $oldItem = $oldItems[0];
        } else {
            throw new Exception("More than 1 item matches the selection criteria:\n" . var_export($oldItems, true));
        }
        return $oldItem;
    }

    public static function setItem(string $workflowName, string $objectName, int $itemId, string $marking, int $userId = 0, int $trackerId = 0)
    {
        if (empty($userId)) {
            $userId = xarSession::getVar('role_id') ?? 0;
        }
        $newItem = [
            'workflow' => $workflowName,
            'object' => $objectName,
            'item' => $itemId,
            'user' => $userId,
            'marking' => $marking,
            'updated' => time(),
        ];
        $oldItem = static::getItem($workflowName, $objectName, $itemId, '', $userId, $trackerId);
        if (empty($oldItem)) {
            $objectRef = DataObjectFactory::getObject(['name' => static::$objectName]);
            $trackerId = $objectRef->createItem($newItem);
            xarLog::message("New tracker item $trackerId created");
        } elseif ($newItem['marking'] != $oldItem['marking']) {
            $objectRef = DataObjectFactory::getObject(['name' => static::$objectName, 'itemid' => $oldItem['id']]);
            $trackerId = $objectRef->updateItem($newItem);
            xarLog::message("Old tracker item $trackerId updated");
        } else {
            // nothing to do here
            $trackerId = $oldItem['id'];
            xarLog::message("Old tracker item $trackerId unchanged");
        }
        return $trackerId;
    }

    public static function deleteItem(string $workflowName, string $objectName, int $itemId, string $marking = '', int $userId = 0, int $trackerId = 0)
    {
        if (empty($userId)) {
            $userId = xarSession::getVar('role_id') ?? 0;
        }
        $oldItem = static::getItem($workflowName, $objectName, $itemId, '', $userId, $trackerId);
        if (empty($oldItem)) {
            // nothing to do here
            $trackerId = 0;
        } else {
            $objectRef = DataObjectFactory::getObject(['name' => static::$objectName, 'itemid' => $oldItem['id']]);
            $trackerId = $objectRef->deleteItem();
            xarLog::message("Old tracker item $trackerId deleted");
        }
        return $trackerId;
    }
}