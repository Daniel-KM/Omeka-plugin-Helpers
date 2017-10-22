<fieldset id="fieldset-record-order"><legend><?php echo __('Record order'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('helpers_order_element', __('Metadata to use to order records')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php
                $elements = get_table_options('Element', null, array(
                    'record_types' => array('Item', 'All'),
                    'sort' => 'alphaBySet',
                ));
                echo $this->formSelect('helpers_order_element',
                    $orderElement,
                    array(),
                    $elements);
            ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('helpers_order_by_collection', __('Order by collection')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formCheckbox('helpers_order_by_collection', true, array('checked' => (boolean) get_option('helpers_order_by_collection'))); ?>
        </div>
    </div>
</fieldset>
