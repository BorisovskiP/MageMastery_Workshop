<?php

namespace MagicToolbox\MagicSlideshow\Controller\Adminhtml\Settings;

use MagicToolbox\MagicSlideshow\Controller\Adminhtml\Settings;

class Index extends \MagicToolbox\MagicSlideshow\Controller\Adminhtml\Settings
{
    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('magicslideshow/*/edit', ['active_tab' => $activeTab]);
        return $resultRedirect;
    }
}
