<?php
namespace Flagbit\Flysystem\Controller\Adminhtml\Filesystem;

use \Flagbit\Flysystem\Helper\Filesystem;
use \Flagbit\Flysystem\Model\Filesystem\Manager;
use \Flagbit\Flysystem\Model\Filesystem\TmpManager;
use \Magento\Backend\App\Action\Context;
use \Magento\Backend\Model\Session;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Magento\Framework\UrlInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Psr\Log\LoggerInterface;

class Preview extends AbstractController
{
    /**
     * @var Filesystem
     */
    protected $_flysystemHelper;

    /**
     * @var TmpManager
     */
    protected $_tmpManager;

    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        Context $context,
        Manager $flysystemManager,
        Session $session,
        Filesystem $flysystemHelper,
        TmpManager $tmpManager,
        JsonFactory $resultJsonFactory,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->_flysystemHelper = $flysystemHelper;
        $this->_tmpManager = $tmpManager;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_logger = $logger;
        $this->_storeManager = $storeManager;
        parent::__construct($context, $flysystemManager, $session);
    }

    public function execute()
    {
        try {
            $manager = $this->getStorage();

            $filename = $this->getRequest()->getParam('filename');
            $filename = $this->_flysystemHelper->idDecode($filename);

            $contents = $manager->getAdapter()->read($filename);

            $this->_tmpManager->writePreview($filename, $contents);

            $resultFile = $this->_tmpManager->getUserPreviewDir().'/'.basename($filename);
            $url = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA).'/'.$resultFile;

            $result = ['error' => false, 'url' => $url];
        } catch (\Exception $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
            $this->_logger->critical($e);
        }

        $resultJson = $this->_resultJsonFactory->create();
        return $resultJson->setData($result);
    }
}