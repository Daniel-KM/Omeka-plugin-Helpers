<?php
/**
 * Return a link to the record immediately before the current one.
 *
 * The previous item is defined by the plugin ItemOrder if enabled, the config
 * of the plugin, or a specific order.
 * The previous item is the previous in the collection, or the last in the
 * previous collection.
 *
 * @package Omeka\Function\View\Navigation
 * @uses link_to()
 * @param Omeka_Record_AbstractRecord|string $record
 * @param array $order If empty, uses the order of the plugin ItemOrder if
 * enabled, or the default order set in the config of the plugin. Else, it
 * should be an array with an element set name and an element name, with an
 * optional argument to bypass the collections.
 * Example with collection check: array('Dublin Core', 'Title')
 * Example without collection check: array('Dublin Core', 'Date', false)
 * @param string $text
 * @param array $props
 * @return string
 */
function link_to_previous($record = null, $order = array(), $text = null, $props = array())
{
    if (empty($record)) {
        $record = get_current_record('item', false);
        if (empty($record)) {
            return '';
        }
    } elseif (is_string($record)) {
        $record = get_current_record($record, false);
        if (empty($record)) {
            return '';
        }
    }

    $props['rel'] = 'prev';

    switch (get_class($record)) {
        case 'Item':
            if (!$text) {
                $text = __('&larr; Previous item');
            }
            if ($order && count($order) >= 2) {
                list($elementSetName, $elementName, $useCollection) = $order;
                $previousItem = get_view()->getPreviousItem($record, $elementSetName, $elementName, $useCollection);
            } else {
                $previousItem = plugin_is_active('ItemOrder')
                    ? get_view()->getPreviousItem($record)
                    : $record->previous();
            }

            if ($previousItem) {
                return link_to($previousItem, 'show', $text, $props);
            }
            break;

        case 'File':
            if (!$text) {
                $text = __('&larr; Previous file');
            }
            $item = $record->getItem();
            $files = $item->getFiles();
            foreach ($files as $key => $file) {
                if ($file->id == $record->id) {
                    return ($key > 0)
                        ? link_to($files[$key - 1], 'show', $text, $props)
                        : '';
                }
            }
            break;
    }
    return '';
}

/**
 * Return a link to the record immediately following the current one.
 *
 * The next item is defined by the plugin ItemOrder if enabled, the config
 * of the plugin, or a specific order.
 * The next item is the next in the collection, or the first  in the
 * next collection.
 *
 * @package Omeka\Function\View\Navigation
 * @uses link_to()
 * @param Omeka_Record_AbstractRecord|string $record
 * @param array $order If empty, uses the order of the plugin ItemOrder if
 * enabled, or the default order set in the config of the plugin. Else, it
 * should be an array with an element set name and an element name, with an
 * optional argument to bypass the collections.
 * Example with collection check: array('Dublin Core', 'Title')
 * Example without collection check: array('Dublin Core', 'Date', false)
 * @param string $text
 * @param array $props
 * @return string
 */
function link_to_next($record = null, $order = array(), $text = null, $props = array())
{
    if (empty($record)) {
        $record = get_current_record('item', false);
        if (empty($record)) {
            return '';
        }
    } elseif (is_string($record)) {
        $record = get_current_record($record, false);
        if (empty($record)) {
            return '';
        }
    }

    $props['rel'] = 'next';

    switch (get_class($record)) {
        case 'Item':
            if (!$text) {
                $text = __('Next item &rarr;');
            }
            if ($order && count($order) >= 2) {
                list($elementSetName, $elementName, $useCollection) = $order;
                $nextItem = get_view()->getNextItem($record, $elementSetName, $elementName, $useCollection);
            } else {
                $nextItem = plugin_is_active('ItemOrder')
                    ? get_view()->getNextItem($record)
                    : $record->next();
            }

            if ($nextItem) {
                return link_to($nextItem, 'show', $text, $props);
            }
            break;

        case 'File':
            if (!$text) {
                $text = __('Next file &rarr;');
            }
            $item = $record->getItem();
            $files = $item->getFiles();
            foreach ($files as $key => $file) {
                if ($file->id == $record->id) {
                    if ($key < count($files) - 1) {
                        return link_to($files[$key + 1], 'show', $text, $props);
                    }
                    return '';
                }
            }
            break;
    }
    return '';
}
