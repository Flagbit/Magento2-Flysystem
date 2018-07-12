<?php
namespace Flagbit\Flysystem\Block\Adminhtml\Filesystem\Content;

use \Flagbit\Flysystem\Helper\Config;
use \Flagbit\Flysystem\Helper\Errors;
use \Flagbit\Flysystem\Helper\Filesystem;
use \Flagbit\Flysystem\Model\Filesystem\Manager;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Backend\Block\Template;
use \Magento\Backend\Block\Template\Context;
use \Magento\Framework\View\Element\Messages;

/**
 * Class Files
 * @package Flagbit\Flysystem\Block\Adminhtml\Filesystem\Content
 */
class Files extends Template
{
    /**
     * Files collection object
     *
     * @var array
     */
    protected $_filesCollection = [];

    /**
     * @var Manager
     */
    protected $_flysystemManager;

    /**
     * @var Filesystem
     */
    protected $_flysystemHelper;

    /**
     * @var Config
     */
    protected $_flysystemConfig;

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var Messages
     */
    protected $_messages;

    /**
     * Files constructor.
     * @param Context $context
     * @param Manager $flysystemManager
     * @param Filesystem $flysystemHelper
     * @param Config $flysystemConfig
     * @param ManagerInterface $messageManager
     * @param Messages $messages
     * @param array $data
     */
    public function __construct(
        Context $context,
        Manager $flysystemManager,
        Filesystem $flysystemHelper,
        Config $flysystemConfig,
        ManagerInterface $messageManager,
        Messages $messages,
        array $data = []
    ) {
        $this->_flysystemManager = $flysystemManager;
        $this->_flysystemHelper = $flysystemHelper;
        $this->_flysystemConfig = $flysystemConfig;
        $this->_messageManager = $messageManager;
        $this->_messages = $messages;
        parent::__construct($context, $data);
    }

    /**
     * Prepared Files collection for current directory
     *
     * @return array
     */
    public function getFiles()
    {
        try {
            if (count($this->_filesCollection) === 0) {
                $path = $this->_flysystemHelper->getCurrentPath();

                $contents = $this->_flysystemManager->getAdapter()->listContents($path);
                foreach ($contents as $file) {
                    if ($this->validateFile($file)) {
                        $this->_filesCollection[] = $file;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_messageManager->addErrorMessage($e->getMessage());
            return [];
        }

        return $this->_filesCollection;
    }

    public function validateFile($file)
    {
        $requiredValues = ['type' => null, 'basename' => null, 'path' => null];
        if(!is_array($file) || count(array_diff_key($requiredValues, $file)) > 0) {
            throw new \Exception(Errors::getErrorMessage(201));
        }

        if($file['type'] === 'file' && $file['basename'][0] !== '.') {
            $supportedFileTypes = $this->_flysystemConfig->getSupportedFileTypes();
            if(in_array($this->getFileEnding($file), $supportedFileTypes)) {
                return true;
            }
        }

        return false;
    }

    public function getMessages()
    {
        $this->_messages->setMessages($this->_messageManager->getMessages());
        return $this->_messages->getGroupedHtml();
    }

    /**
     * Files collection count getter
     *
     * @return int
     */
    public function getFilesCount()
    {
        return count($this->getFiles());
    }

    /**
     * @param $file
     * @return null|string
     */
    public function getFileId($file)
    {
        if(!isset($file['path'])) {
            return null;
        }
        return $this->_flysystemHelper->idEncode($file['path']);
    }

    /**
     * @param $file
     * @return null|string
     */
    public function getFileShortName($file) {
        if(!isset($file['path'])) {
            return null;
        }
        return $this->_flysystemHelper->getShortFilename($file['path']);
    }

    /**
     * @param $file
     * @return string
     */
    public function getFileEnding($file) {
        if(!isset($file['extension']) || empty($file['extension'])) {
            return 'unknown';
        }

        return $file['extension'];
    }

    /**
     * @param $file
     * @return string
     */
    public function getFileSize($file) {
        if(!isset($file['size'])) {
            return '';
        }

        $size = $file['size'] / 1024 / 1024;
        if($size >= 1) {
            return round($size, 2) . ' MB';
        }

        $size = $file['size'] / 1024;
        if($size >= 1) {
            return round($size, 2) . ' KB';
        }

        return $file['size'] . ' Byte';
    }

    /**
     * @param $file
     * @return false|string
     */
    public function getLastModified($file) {
        if(!isset($file['timestamp'])) {
            return '';
        }

        return date('d-m-Y H:i', $file['timestamp']);
    }

    /**
     * @param $files
     */
    public function setFilesCollection($files)
    {
        $this->_filesCollection = $files;
    }
}
