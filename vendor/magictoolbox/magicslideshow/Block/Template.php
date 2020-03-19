<?php

namespace MagicToolbox\MagicSlideshow\Block;

/**
 * Magic Slideshow view template block
 *
 */
class Template extends \Magento\Framework\View\Element\Template
{
    /**
     * Default template
     *
     * @var string
     */
    protected $defaultTemplate = 'MagicToolbox_MagicSlideshow::product/view/layouts/bottom.phtml';

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        if (isset($data['layout'])) {
            $this->setTemplate('MagicToolbox_MagicSlideshow::product/view/layouts/' . $data['layout'] . '.phtml');
        } else {
            $this->setTemplate($this->defaultTemplate);
        }

        parent::__construct($context, $data);
    }
}
