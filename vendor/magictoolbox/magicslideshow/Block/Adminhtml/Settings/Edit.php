<?php

namespace MagicToolbox\MagicSlideshow\Block\Adminhtml\Settings;

/**
 * Backend form container block
 *
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'object_id';
        $this->_controller = 'adminhtml_settings';
        $this->_blockGroup = 'MagicToolbox_MagicSlideshow';
        $this->_headerText = 'Magic Slideshow Config';

        parent::_construct();

        $this->_formScripts[] = '
            var mtErrMessage = \'Error: It seems that your Static Files Cache is outdated. Please, update it so that module\\\'s scripts can be loaded.\';
            var mtRequireConfigMap = null;
            try {
                mtRequireConfigMap = requirejs.s.contexts._.config.map[\'*\'];
            } catch (e) {
                mtRequireConfigMap = null;
            }
            if (mtRequireConfigMap && typeof(mtRequireConfigMap[\'magicslideshow\']) == \'undefined\') {
                throw mtErrMessage;
            }
            require([\'magicslideshow\'], function(magicslideshow){
                if (typeof(magicslideshow) == "undefined") {
                    throw mtErrMessage;
                }
                magicslideshow.initSettings();
            });
        ';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->updateButton('save', 'label', __('Save Settings'));
    }
}
