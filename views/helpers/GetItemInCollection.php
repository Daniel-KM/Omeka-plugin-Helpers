<?php
/**
 * Return an item in a collection.
 *
 * @todo Move to ItemOrder.
 *
 * @package Helpers
 */
class Helpers_View_Helper_GetItemInCollection extends Zend_View_Helper_Abstract
{
    /**
     * Return an item in a collection.
     *
     * @todo Manage public / non public with Item Order?
     *
     * @param Collection|integer $collection
     * @param integer|string $position Position is an integer or a string
     * "first", "last", "previous", "next". Previous and next need an item.
     * When integer, the first item is 1.
     * @param Item|integer $item
     * @return Item|string|false
     * Empty string means it's the first item, so there is no previous.
     * False means the previous is not determinable (Item Order is not used for
     * this collection).
     */
    public function getItemInCollection($collection = null, $position = 1, $item = null)
    {
        if (!plugin_is_active('ItemOrder')) {
            return false;
        }

        if (empty($collection)) {
            $collection = get_current_record('collection', false);
            if (empty($collection)) {
                return false;
            }
        }

        $collection_id = is_object($collection)
            ? $collection->id
            : $collection;

        $db = get_db();

        $itemOrderTable = $db->getTable('ItemOrder_ItemOrder');
        $itemsArray = $itemOrderTable->fetchOrderedItems($collection_id);
        if (empty($itemsArray)) {
            return false;
        }

        switch ($position) {
            case 'first':
            case 0:
            case '0':
                $itemsArrayId = array_shift($itemsArray);
                return get_record_by_id('Item', $itemsArrayId['id']);
            case 'last':
                $itemsArrayId = array_pop($itemsArray);
                return get_record_by_id('Item', $itemsArrayId['id']);
            case 'previous':
                // TODO To be optimized.
                foreach ($itemsArray as $key => $orderedItem) {
                    if ($orderedItem['id'] == $item->id) {
                        return $key == 0 ? '' : get_record_by_id('Item', $itemsArray[$key - 1]['id']);
                    }
                }
                return '';
            case 'next':
                // TODO To be optimized.
                foreach ($itemsArray as $key => $orderedItem) {
                    if ($orderedItem['id'] == $item->id) {
                        end($itemsArray);
                        $last = key($itemsArray);
                        return $key == $last ? '' : get_record_by_id('Item', $itemsArray[$key + 1]['id']);
                    }
                }
                return '';
            // Integer.
            default:
                --$position;
                return isset($itemsArray[$position])
                    ? get_record_by_id('Item', $itemsArray[$position]['id'])
                    : '';
        }
    }
}
