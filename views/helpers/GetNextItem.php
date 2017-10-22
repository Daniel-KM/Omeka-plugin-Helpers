<?php
/**
 * Return the next item by metadata.
 *
 * @package Helpers
 */
class Helpers_View_Helper_GetNextItem extends Zend_View_Helper_Abstract
{
    /**
     * Get the next item by metadata.
     *
     * This manages multiple items with same element texts (by id in that case).
     *
     * @todo Find good first item in the next collection by one query.
     *
     * @see link_to_next_item_show()
     *
     * @param Item $item
     * @param string $elementSetName If empty, order will be the default one.
     * If Item order plugin is enabled, it will be used and order will be by collection.
     * @param string $elementName
     * @param boolean $orderByCollection
     * @return Item|null
     */
    public function getNextItem($item = null, $elementSetName = '', $elementName = '', $orderByCollection = true)
    {
        if (empty($item)) {
            $item = get_current_record('item', false);
            if (empty($item)) {
                return;
            }
        }

        // Order via item order plugin.
        if (empty($elementSetName)) {
            if ($item->collection_id && plugin_is_active('ItemOrder')) {
                $next = $this-> _getNextItemViaItemOrder($item);
                if ($next !== false) {
                    return $next;
                }
            }
            $elementSetName = get_option('helpers_order_element_set_name');
            $elementName = get_option('helpers_order_element_name');
            $byCollection = get_option('helpers_order_by_collection');
        }

        // Else default order.
        $db = get_db();
        $element = $db->getTable('Element')->findByElementSetNameAndElementName($elementSetName, $elementName);
        if (empty($element)) {
            $next = $item->next();
        }
        else {
            $elementText = metadata($item, array($elementSetName, $elementName));

            // TODO Use filters for user or use regular methods (get_record_by_id() checks it).
            if (!(is_admin_theme() || current_user())) {
                $sqlWhereIsPublic = 'AND items.public = 1';
            }
            else {
                $sqlWhereIsPublic = '';
            }

            if ($orderByCollection) {
                $bind = array(
                    $element->id,
                    $item->id,
                    $item->collection_id,
                    $elementText,
                    $elementText,
                    $item->id,
                    $item->collection_id,
                );

                $sqlByCollection = "AND items.collection_id = ?";
                $sqlOrderByCollection = "
                    IF (items.collection_id = ?, 1, 0) ASC,
                ";
            }
            else {
                $bind = array(
                    $element->id,
                    $item->id,
                    $elementText,
                    $elementText,
                    $item->id,
                );

                $sqlByCollection = '';
                $sqlOrderByCollection = '';
            }

            $sql = "
                SELECT items.id, items.collection_id
                FROM {$db->Item} items
                    LEFT JOIN {$db->ElementText} element_texts
                        ON element_texts.record_id = items.id
                            AND element_texts.record_type = 'Item'
                            AND element_texts.element_id = ?
                WHERE items.id != ?
                    $sqlWhereIsPublic
                    $sqlByCollection
                    AND (element_texts.text > ?
                        OR (element_texts.text = ? AND element_texts.record_id > ?))
                GROUP BY items.id
                ORDER BY
                    $sqlOrderByCollection
                    IF (ISNULL(element_texts.text), 1, 0) ASC,
                    element_texts.text ASC,
                    items.added ASC,
                    items.id ASC
                LIMIT 1
            ";
            $result = $db->fetchAll($sql, $bind);

            // If no result or result in a different collection, search the first in the
            // next collection.
            if ($orderByCollection && (!$result || $result[0]['collection_id'] != $item->collection_id)) {
                $bind = array(
                    $element->id,
                    $item->collection_id,
                );
                $sql = "
                    SELECT items.id
                    FROM {$db->Item} items
                        LEFT JOIN {$db->ElementText} element_texts
                            ON element_texts.record_id = items.id
                                AND element_texts.record_type = 'Item'
                                AND element_texts.element_id = ?
                    WHERE items.collection_id > ?
                        $sqlWhereIsPublic
                    GROUP BY items.id
                    ORDER BY
                        items.collection_id ASC,
                        IF (ISNULL(element_texts.text), 1, 0) ASC,
                        element_texts.text ASC,
                        items.added ASC,
                        items.id ASC
                    LIMIT 1
                ";
                $result = $db->fetchAll($sql, $bind);
            }

            $next = $result
                ? get_record_by_id('Item', $result[0]['id'])
                : null;
        }

        return $next;
    }

    /**
     * Return next item via Item Order plugin.
     *
     * @return Item|string|false
     * Empty string means it's the last item, so there is no next.
     * False means the bext is not determinable (Item Order is not used for
     * this collection).
     */
    protected function _getNextItemViaItemOrder($item)
    {
        if (!$item->collection_id) {
            return false;
        }

        // Check if there is an order by collection, because the function
        // returns the reverse order else.
        $db = get_db();
        $itemOrderTable = $db->getTable('ItemOrder_ItemOrder');
        $alias = $itemOrderTable->getTableAlias();
        $select = $itemOrderTable->getSelectForCount(array('collection_id', $item->collection_id));
        $result = $itemOrderTable->fetchOne($select);
        if (empty($result)) {
            return false;
        }

        $itemsArray = $itemOrderTable->fetchOrderedItems($item->collection_id);
        if (empty($itemsArray)) {
            return false;
        }

        foreach ($itemsArray as $key => $orderedItem) {
            if ($orderedItem['id'] != $item->id) {
                continue;
            }

            end($itemsArray);
            $last = key($itemsArray);
            if ($key != $last) {
                return get_record_by_id('Item', $itemsArray[$key + 1]['id']);
            }

            // Get the first of the next collection.
            $collection_id = $this->_getNextCollectionId($item->collection_id);
            if ($collection_id) {
                return get_view()->getItemInCollection($collection_id, 'first');
            }

            // Else this is the last collection, so return empty result.
            return '';
        }

        return false;
    }

    /**
     * Return id of next collection.
     */
    protected function _getNextCollectionId($collection_id, $withItems = true)
    {
        $db = get_db();

        // TODO Use filters for user or use regular methods (get_record_by_id() checks it).
        if (!(is_admin_theme() || current_user())) {
            $sqlWhereIsPublic = 'AND collections.public = 1';
        }
        else {
            $sqlWhereIsPublic = '';
        }

        if ($withItems) {
            // LEFT JOIN is enough to get collections with items.
            $sqlFromWithItems = "LEFT JOIN {$db->Item} items ON collections.id = items.collection_id";
        }
        else {
            $sqlFromWithItems = '';
        }

        $bind = array(
            $collection_id,
        );
        $sql = "
            SELECT collections.id
            FROM {$db->Collection} collections
                $sqlFromWithItems
            WHERE collections.id > ?
                $sqlWhereIsPublic
            ORDER BY
                collections.id ASC
            LIMIT 1
        ";
        $result = $db->fetchOne($sql, $bind);
        return $result;
    }
}
