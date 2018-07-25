<?php
namespace Flagbit\Flysystem\Helper;

use \Magento\Cms\Helper\Wysiwyg\Images;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\UrlInterface;
use \Magento\Store\Model\StoreManagerInterface;

/**
 * Class Config
 * @package Flagbit\Flysystem\Helper
 */
class Filesystem extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Images
     */
    protected $_imageHelper;

    /**
     * @var string
     */
    protected $_currentPath;


    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Images $imageHelper
    ) {
        $this->_storeManager = $storeManager;
        $this->_imageHelper = $imageHelper;
        parent::__construct($context);
    }

    /**
     * Ext Tree node key name
     *
     * @return string
     */
    public function getTreeNodeName()
    {
        return 'node';
    }

    /**
     * Encode string to valid HTML id element, based on base64 encoding
     *
     * @param string $string
     * @return string
     */
    public function idEncode($string)
    {
        return strtr(base64_encode($string), '+/=', ':_-');
    }


    /**
     * Revert opration to idEncode
     *
     * @param string $string
     * @return string
     */
    public function idDecode($string)
    {
        $string = strtr($string, ':_-', '+/=');
        return base64_decode($string);
    }

    /**
     * Return path of the current selected directory or root directory for startup
     * Try to create target directory if it doesn't exist
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentPath()
    {
        if (!$this->_currentPath) {
            $currentPath = '/';
            $path = $this->_getRequest()->getParam($this->getTreeNodeName());
            if ($path && $path !== 'root') {
                $currentPath = $this->idDecode($path);
            }
            $this->_currentPath = $currentPath;
        }
        return $this->_currentPath;
    }

    /**
     * Reduce filename by replacing some characters with dots
     *
     * @param string $filename
     * @param int $maxLength Maximum filename
     * @return string Truncated filename
     */
    public function getShortFilename($filename, $maxLength = 20)
    {
        $path = explode('/', $filename);
        $filename = $path[(count($path)-1)];

        if (strlen($filename) <= $maxLength) {
            return $filename;
        }
        return substr($filename, 0, $maxLength) . '...';
    }

    /**
     * @param string $filename
     * @param bool $renderAsTag
     * @return string
     */
    public function getImageHtmlDeclaration($filename, $renderAsTag = false)
    {
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $mediaPath = $mediaUrl.$filename;
        $directive = sprintf('{{media url="%s"}}', $mediaPath);
        if ($renderAsTag) {
            $html = sprintf('<img src="%s" alt="" />', $this->_imageHelper->isUsingStaticUrlsAllowed() ? $mediaPath : $directive);
        } else {
            if ($this->_imageHelper->isUsingStaticUrlsAllowed()) {
                $html = $mediaPath; // $mediaPath;
            } else {
                $directive = $this->urlEncoder->encode($directive);

                $html = $this->_storeManager->getStore()->getUrl(
                    'cms/wysiwyg/directive',
                    [
                        '___directive' => $directive,
                        '_escape_params' => false,
                    ]
                );
            }
        }
        return $html;
    }
}