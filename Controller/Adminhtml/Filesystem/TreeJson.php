<?php
namespace Flagbit\Flysystem\Controller\Adminhtml\Filesystem;

use \Flagbit\Flysystem\Block\Adminhtml\Filesystem\Tree;
use \Magento\Backend\App\Action\Context;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Magento\Framework\Registry;
use \Magento\Framework\View\LayoutFactory;
use \Magento\Backend\Model\Session;

class TreeJson extends AbstractController
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * TreeJson constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Session $session
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Session $session,
        JsonFactory $resultJsonFactory,
        LayoutFactory $layoutFactory
    ) {
        $this->layoutFactory = $layoutFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context, $coreRegistry, $session);
    }

    /**
     * Tree json action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        try {
            $this->_initAction();
            /** @var \Magento\Framework\View\Layout $layout */
            $layout = $this->layoutFactory->create();
            $resultJson->setJsonData(
                $layout->createBlock(Tree::class)->getTreeJson()
            );
        } catch (\Exception $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
            $resultJson->setData($result);
        }
        return $resultJson;
    }
}
