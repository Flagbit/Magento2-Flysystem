<?php
namespace Flagbit\Flysystem\Controller\Adminhtml\Filesystem;

use \Flagbit\Flysystem\Helper\Filesystem;
use \Flagbit\Flysystem\Model\Filesystem\Manager;
use \Magento\Backend\App\Action\Context;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Magento\Backend\Model\Session;

/**
 * Class DeleteFiles
 * @package Flagbit\Flysystem\Controller\Adminhtml\Filesystem
 */
class DeleteFiles extends AbstractController
{
    /**
     * @var JsonFactory
     */
    protected $_resultJson;

    /**
     * @var Filesystem
     */
    protected $_flysystemHelper;

    /**
     * DeleteFiles constructor.
     * @param Context $context
     * @param Manager $flysystemManager
     * @param Session $session
     * @param JsonFactory $resultJsonFactory
     * @param Filesystem $flysystemHelper
     */
    public function __construct(
        Context $context,
        Manager $flysystemManager,
        Session $session,
        JsonFactory $resultJsonFactory,
        Filesystem $flysystemHelper
    ) {
        $this->_resultJson = $resultJsonFactory;
        $this->_flysystemHelper = $flysystemHelper;
        parent::__construct($context, $flysystemManager, $session);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            if (!$this->getRequest()->isPost()) {
                throw new \Exception('Wrong request.');
            }
            $files = $this->getRequest()->getParam('files');
            $manager = $this->getStorage();

            foreach($files as $file) {
                $file = $this->_flysystemHelper->idDecode($file);
                $manager->getAdapter()->delete($file);
            }
            $result = ['error' => false];
        } catch(\Exception $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->_resultJson->create();
        return $resultJson->setData($result);
    }
}