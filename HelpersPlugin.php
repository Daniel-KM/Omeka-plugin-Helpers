 <?php
/**
 * Various tools to help builders of plugins and themes.
 *
 * @copyright Daniel Berthereau 2012-2017
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */

/**
 * The Helpers plugin.
 *
 * @package Omeka\Plugins\Helpers
 */
class HelpersPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array(
        'initialize',
        'install',
        'uninstall',
        'config_form',
        'config',
    );

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array(
        'item_previous',
        'item_next',
    );

    /**
     * @var array This plugin's options.
     */
    protected $_options = array(
        'helpers_order_element_set_name' => 'Dublin Core',
        'helpers_order_element_name' => 'Title',
        'helpers_order_by_collection' => true,
    );

    public function hookInitialize()
    {
        require_once dirname(__FILE__)
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'Helpers'
            . DIRECTORY_SEPARATOR . 'helpers.php';
    }

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_installOptions();
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Shows plugin configuration page.
     */
    public function hookConfigForm($args)
    {
        $view = get_view();

        $elementSetName = get_option('helpers_order_element_set_name');
        $elementName = get_option('helpers_order_element_name');
        if ($elementSetName && $elementName) {
            $orderElement = get_db()->getTable('Element')
                ->findByElementSetNameAndElementName($elementSetName, $elementName);
            if ($orderElement) {
                $orderElement = $orderElement->id;
            }
        } else {
            $orderElement = '';
        }

        echo $view->partial('plugins/helpers-config-form.php', array(
            'orderElement' => $orderElement,
        ));
    }

    /**
     * Saves plugin configuration page.
     *
     * @param array Options set in the config form.
     */
    public function hookConfig($args)
    {
        $post = $args['post'];

        if (!empty($post['helpers_order_element'])) {
            $orderElement = get_db()->getTable('Element')
                ->find((int) $args['post']['helpers_order_element']);
            if ($orderElement) {
                $post['helpers_order_element_set_name'] = $orderElement
                    ->getElementSet()->name;
                $post['helpers_order_element_name'] = $orderElement
                    ->name;
            }
        }

        foreach ($this->_options as $optionKey => $optionValue) {
            if (isset($post[$optionKey])) {
                set_option($optionKey, $post[$optionKey]);
            }
        }
    }

    public function filterItemPrevious($item, $args = array())
    {
        static $items = array();

        if (is_admin_theme()) {
            return;
        }

        if (!empty($item)) {
            return $item;
        }

        $item = $args['item'];

        // Avoid an infinite loop in some cases.
        if (isset($items[$item->id])) {
            return $items[$item->id];
        }

        if (plugin_is_active('ItemOrder')) {
            $previousItem = get_view()->getPreviousItem($item);
        } else {
            $elementSetName = get_option('helpers_order_element_set_name');
            $elementName = get_option('helpers_order_element_name');
            $byCollection = get_option('helpers_order_by_collection');
            $previousItem = get_view()->getPreviousItem($item, $elementSetName, $elementName, $byCollection);
        }

        $items[$item->id] = $previousItem;
        return $previousItem;
    }

    public function filterItemNext($item, $args = array())
    {
        static $items = array();

        if (is_admin_theme()) {
            return;
        }

        if (!empty($item)) {
            return $item;
        }

        $item = $args['item'];

        // Avoid an infinite loop in some cases.
        if (isset($items[$item->id])) {
            return $items[$item->id];
        }

        if (plugin_is_active('ItemOrder')) {
            $nextItem = get_view()->getNextItem($item);
        } else {
            $elementSetName = get_option('helpers_order_element_set_name');
            $elementName = get_option('helpers_order_element_name');
            $byCollection = get_option('helpers_order_by_collection');
            $nextItem = get_view()->getNextItem($item, $elementSetName, $elementName, $byCollection);
        }

        $items[$item->id] = $nextItem;
        return $nextItem;
    }
}
