 <?php
/**
 * Various tools to help builders of plugins and themes.
 *
 * @copyright Daniel Berthereau 2012-2018
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

        if (empty($post['helpers_order_element'])) {
            $post['helpers_order_element_set_name'] = '';
            $post['helpers_order_element_name'] = '';
        } else {
            $orderElement = get_db()->getTable('Element')
                ->find((int) $args['post']['helpers_order_element']);
            if ($orderElement) {
                $post['helpers_order_element_set_name'] = $orderElement->getElementSet()->name;
                $post['helpers_order_element_name'] = $orderElement->name;
            }
        }

        foreach ($this->_options as $optionKey => $optionValue) {
            if (isset($post[$optionKey])) {
                set_option($optionKey, $post[$optionKey]);
            }
        }
    }

    public function filterItemPrevious($value, array $args)
    {
        static $items = array();

        if (is_admin_theme()) {
            return;
        }

        $item = $args['item'];

        // Avoid an infinite loop in some cases.
        if (isset($items[$item->id])) {
            return $items[$item->id];
        }

        $previousItem = get_view()->getPreviousItem($item);
        $items[$item->id] = $previousItem;
        return $previousItem;
    }

    public function filterItemNext($value, $args = array())
    {
        static $items = array();

        if (is_admin_theme()) {
            return;
        }

        $item = $args['item'];

        // Avoid an infinite loop in some cases.
        if (isset($items[$item->id])) {
            return $items[$item->id];
        }

        $nextItem = get_view()->getNextItem($item);
        $items[$item->id] = $nextItem;
        return $nextItem;
    }
}
