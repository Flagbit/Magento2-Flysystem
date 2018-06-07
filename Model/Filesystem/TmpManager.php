<?php
namespace Flagbit\Flysystem\Model\Filesystem;

use \Flagbit\Flysystem\Adapter\FilesystemAdapter;
use \Flagbit\Flysystem\Adapter\FilesystemAdapterFactory;
use \Flagbit\Flysystem\Adapter\FilesystemManager;
use \Flagbit\Flysystem\Helper\Config;
use \Flagbit\Flysystem\Helper\Filesystem;
use \Magento\Framework\UrlInterface;
use \Magento\MediaStorage\Helper\File\Storage\Database;
use \Magento\MediaStorage\Model\File\Uploader;
use \Magento\Catalog\Model\Product\Media\Config as ProductMediaConfig;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\ObjectManagerInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Psr\Log\LoggerInterface;
use \Magento\Framework\Filesystem as MagentoFilesystem;
use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Backend\Model\Auth\Session;

/**
 * Class TmpManager
 * @package Flagbit\Flysystem\Model\Filesystem
 */
class TmpManager
{
    /**
     * @var FilesystemManager
     */
    protected $_flysystemManager;

    /**
     * @var FilesystemAdapterFactory
     */
    protected $_flysystemFactory;

    /**
     * @var Config
     */
    protected $_flysystemConfig;

    /**
     * @var Filesystem
     */
    protected $_flysystemHelper;

    /**
     * @var MagentoFilesystem
     */
    protected $_filesystem;

    /**
     * @var MagentoFilesystem\Directory\WriteInterface
     */
    protected $_directoryList;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var Session
     */
    protected $_adminSession;

    /**
     * @var ProductMediaConfig
     */
    protected $_productMediaConfig;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Database
     */
    protected $_coreFileStorageDatabase;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var null|FilesystemAdapter
     */
    protected $_adapter;

    /**
     * TmpManager constructor.
     * @param FilesystemManager $flysystemManager
     * @param FilesystemAdapterFactory $flysystemFactory
     * @param Config $flysystemConfig
     * @param Filesystem $flysystemHelper
     * @param MagentoFilesystem $filesystem
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param Session $adminSession
     * @param ProductMediaConfig $productMediaconfig
     * @param ObjectManagerInterface $objectManager
     * @param Database $coreFileStorageDatabase
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        FilesystemManager $flysystemManager,
        FilesystemAdapterFactory $flysystemFactory,
        Config $flysystemConfig,
        Filesystem $flysystemHelper,
        MagentoFilesystem $filesystem,
        DirectoryList $directoryList,
        LoggerInterface $logger,
        Session $adminSession,
        ProductMediaConfig $productMediaconfig,
        ObjectManagerInterface $objectManager,
        Database $coreFileStorageDatabase,
        StoreManagerInterface $storeManager
    ) {
        $this->_flysystemManager = $flysystemManager;
        $this->_flysystemFactory = $flysystemFactory;
        $this->_flysystemConfig = $flysystemConfig;
        $this->_flysystemHelper = $flysystemHelper;
        $this->_filesystem = $filesystem;
        $this->_directoryList = $this->_filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_logger = $logger;
        $this->_adminSession = $adminSession;
        $this->_productMediaConfig = $productMediaconfig;
        $this->_objectManager = $objectManager;
        $this->_coreFileStorageDatabase = $coreFileStorageDatabase;
        $this->_storeManager = $storeManager;

        $this->create();
    }

    /**
     * @return FilesystemAdapter|mixed|null
     */
    public function create()
    {
        if(!$this->_adapter) {
            $path = $this->_directoryList->getAbsolutePath();
            $this->_adapter = $this->_flysystemFactory->create($this->_flysystemManager->createLocalDriver($path));
        }
        return $this->_adapter;
    }

    /**
     * @param $file
     * @param null $content
     * @return bool
     */
    public function writeTmp($file, $content = null)
    {
        $this->clearTmp();
        return $this->getAdapter()->write($this->getTmpPath($file), $content);
    }

    /**
     * @param $file
     * @return bool|false|string
     * @throws LocalizedException
     */
    public function getTmp($file)
    {
        if($this->getAdapter()->has($this->getTmpPath($file))){
            return $this->getAdapter()->read($this->getTmpPath($file));
        }

        throw new LocalizedException(__('Could not find '.$file.' in Tmp Path'));
    }

    /**
     * @param $file
     * @return string
     */
    public function getAbsoluteTmpPath($file)
    {
        $encodedFile = $this->_flysystemHelper->idEncode($file);
        return $this->_directoryList->getAbsolutePath().'/'.$this->getUserTmpDir().'/'.$encodedFile;
    }

    /**
     * @return string
     */
    protected function getUserTmpDir()
    {
        $userDir = $this->_flysystemHelper->idEncode($this->_adminSession->getUser()->getUserName());
        return Config::FLYSYSTEM_DIRECTORY.'/'.Config::FLYSYSTEM_DIRECTORY_TMP.'/'.$userDir;
    }

    /**
     * @param $file
     * @return string
     */
    protected function getTmpPath($file)
    {
        $file = $this->_flysystemHelper->idEncode($file);

        return $this->getUserTmpDir().'/'.$file;
    }

    /**
     * @return bool
     */
    public function clearTmp()
    {
        return $this->getAdapter()->deleteDir($this->getUserTmpDir());
    }

    /**
     * @return FilesystemAdapter|null
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * @param $adapter
     */
    public function setAdapter($adapter)
    {
        $this->_adapter = $adapter;
    }

    /**
     * @param $file
     * @return mixed
     */
    public function createProductTmp($file)
    {
        $tmpRoot = $this->_productMediaConfig->getBaseTmpMediaPath();

        $uploader = $this->_objectManager->create(Uploader::class, ['fileId' => $file, 'isFlysystem' => true]);
        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(true);
        $result = $uploader->save($this->_directoryList->getAbsolutePath($tmpRoot));

        unset($result['tmp_name']);
        unset($result['path']);

        $result['url'] = $this->_productMediaConfig->getTmpMediaUrl($result['file']);
        $result['file'] = $result['file'] . '.tmp';

        return $result;
    }

    public function createCategoryTmp($file)
    {
        /** @var \Magento\Catalog\Model\ImageUploader $imageUploader*/
        $imageUploader = $this->_objectManager->get(\Magento\Catalog\CategoryImageUpload::class);
        $baseTmpPath = $imageUploader->getBaseTmpPath();

        $uploader = $this->_objectManager->create(Uploader::class, ['fileId' => $file, 'isFlysystem' => true]);
        $uploader->setAllowedExtensions($imageUploader->getAllowedExtensions());
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(true);
        $result = $uploader->save($this->_directoryList->getAbsolutePath($baseTmpPath));

        unset($result['path']);

        if (!$result) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('File can not be saved to the destination folder.')
            );
        }

        $result['tmp_name'] = str_replace('\\', '/', $result['tmp_name']);
        $result['url'] = $this->_storeManager
                ->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $imageUploader->getFilePath($baseTmpPath, $result['file']);
        $result['name'] = $result['file'];

        if (isset($result['file'])) {
            try {
                $relativePath = rtrim($baseTmpPath, '/') . '/' . ltrim($result['file'], '/');
                $this->_coreFileStorageDatabase->saveFile($relativePath);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Something went wrong while saving the file(s).')
                );
            }
        }
        return $result;
    }
}