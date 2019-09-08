<?php
/**
 * Return the previous item by metadata.
 *
 * @package Bibnum
 */
class Helpers_View_Helper_GetPreviousItem extends Zend_View_Helper_Abstract
{
    /**
     * Get the previous item by metadata.
     *
     * This manages multiple items with same element texts (by id in that case).
     *
     * @todo Find good last item in the previous collection by one query.
     *
     * @see link_to_previous_item_show()
     *
     * @param Item $item
     * @param string $elementSetName If empty, order will be the default one.
     * If Item order plugin is enabled, it will be used and order will be by collection.
     * @param string $elementName
     * @param bool $orderByCollection
     * @return Item|null
     */
    public function getPreviousItem($item = null, $elementSetName = '', $elementName = '', $orderByCollection = true)
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
                $previous = $this->_getPreviousItemViaItemOrder($item);
                if ($previous !== false) {
                    return $previous;
                }
            }
            $elementSetName = get_option('helpers_order_element_set_name');
            $elementName = get_option('helpers_order_element_name');
            $byCollection = get_option('helpers_order_by_collection');
        }

        // Else default order.
        $db = get_db();
        $element = $db->getTable('Element')->findByElementSetNameAndElementName($elementSetName, $elementName);
        // In case of error, the default item is returned.
        if (empty($element)) {
            $previous = $item->previous();
        } else {
            $elementText = metadata($item, array($elementSetName, $elementName));

            // TODO Use filters for user or use regular methods (get_record_by_id() checks it).
            if (!(is_admin_theme() || current_user())) {
                $sqlWhereIsPublic = 'AND items.public = 1';
            } else {
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
                    IF (items.collection_id = ?, 1, 0) DESC,
                ";
            } else {
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
                    AND (element_texts.text < ?
                        OR (element_texts.text = ? AND element_texts.record_id < ?))
                GROUP BY items.id
                ORDER BY
                    $sqlOrderByCollection
                    IF (ISNULL(element_texts.text), 1, 0) DESC,
                    element_texts.text DESC,
                    items.added DESC,
                    items.id DESC
                LIMIT 1
            ";
            $result = $db->fetchAll($sql, $bind);

            // If no result or result in a different collection, search the last in the
            // previous collection.
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
                    WHERE items.collection_id < ?
                        $sqlWhereIsPublic
                    GROUP BY items.id
                    ORDER BY
                        items.collection_id DESC,
                        IF (ISNULL(element_texts.text), 1, 0) DESC,
                        element_texts.text DESC,
                        items.added DESC,
                        items.id DESC
                    LIMIT 1
                ";
                $result = $db->fetchAll($sql, $bind);
            }

            $previous = $result
                ? get_record_by_id('Item', $result[0]['id'])
                : null;
        }

        return $previous;
    }

    /**
     * Return previous item via Item Order plugin.
     *
     * @return Item|string|false
     * Empty string means it's the first item, so there is no previous.
     * False means the previous is not determinable (Item Order is not used for
     * this collection).
     */
    protected function _getPreviousItemViaItemOrder($item)
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

            if ($key != 0) {
                return get_record_by_id('Item', $itemsArray[$key - 1]['id']);
            }

            // Get the last of the previous collection.
            $collection_id = $this->_getPreviousCollectionId($item->collection_id);
            if ($collection_id) {
                return get_view()->getItemInCollection($collection_id, 'last');
            }

            // Else this is the first collection, so return empty result.
            return '';
        }

        return false;
    }

    /**
     * Return id of previous collection.
     */
    protected function _getPreviousCollectionId($collection_id, $withItems = true)
    {
        $db = get_db();

        // TODO Use filters for user or use regular methods (get_record_by_id() checks it).
        if (!(is_admin_theme() || current_user())) {
            $sqlWhereIsPublic = 'AND collections.public = 1';
        } else {
            $sqlWhereIsPublic = '';
        }

        if ($withItems) {
            // LEFT JOIN is enough to get collections with items.
            $sqlFromWithItems = "LEFT JOIN {$db->Item} items ON collections.id = items.collection_id";
        } else {
            $sqlFromWithItems = '';
        }

        $bind = array(
            $collection_id,
        );
        $sql = "
            SELECT collections.id
            FROM {$db->Collection} collections
                $sqlFromWithItems
            WHERE collections.id < ?
                $sqlWhereIsPublic
            ORDER BY
                collections.id DESC
            LIMIT 1
        ";
        $result = $db->fetchOne($sql, $bind);
        return $result;
    }
}
