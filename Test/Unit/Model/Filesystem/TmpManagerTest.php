<?php
namespace Flagbit\Flysystem\Test\Unit\Model\Filesystem;

use \Flagbit\Flysystem\Adapter\FilesystemAdapter;
use \Flagbit\Flysystem\Adapter\FilesystemAdapterFactory;
use \Flagbit\Flysystem\Adapter\FilesystemManager;
use \Flagbit\Flysystem\Helper\Config;
use \Flagbit\Flysystem\Helper\Filesystem;
use \Flagbit\Flysystem\Model\Filesystem\TmpManager;
use \League\Flysystem\Adapter\Local;
use \Magento\Backend\Model\Auth\Session;
use \Magento\Catalog\Controller\Adminhtml\Category\Image\Upload;
use \Magento\Catalog\Model\Product\Media\Config as ProductMediaConfig;
use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Framework\App\ObjectManager;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\Filesystem as MagentoFilesystem;
use \Magento\Framework\Logger\Monolog;
use \Magento\MediaStorage\Helper\File\Storage\Database;
use \Magento\MediaStorage\Model\File\Uploader;
use \Magento\Store\Model\Store;
use \Magento\Store\Model\StoreManager;
use \Magento\User\Model\User;
use \PHPUnit\Framework\MockObject\MockObject;
use \PHPUnit\Framework\TestCase;

class TmpManagerTest extends TestCase
{
    /**
     * @var FilesystemManager|MockObject
     */
    protected $_flysystemManagerMock;

    /**
     * @var FilesystemAdapterFactory|MockObject
     */
    protected $_flysystemFactoryMock;

    /**
     * @var Config|MockObject
     */
    protected $_flysystemConfigMock;

    /**
     * @var Filesystem|MockObject
     */
    protected $_flysystemHelperMock;

    /**
     * @var MagentoFilesystem|MockObject
     */
    protected $_filesystemMock;

    /**
     * @var Monolog|MockObject
     */
    protected $_loggerMock;

    /**
     * @var Session|MockObject
     */
    protected $_adminSessionMock;

    /**
     * @var ProductMediaConfig|MockObject
     */
    protected $_productMediaConfigMock;

    /**
     * @var ObjectManager|MockObject
     */
    protected $_objectManagerMock;

    /**
     * @var Database|MockObject
     */
    protected $_coreFileStorageDatabaseMock;

    /**
     * @var StoreManager|MockObject
     */
    protected $_storeManagerMock;

    /**
     * @var DirectoryList|MockObject
     */
    protected $_directoryListMock;

    /**
     * @var Uploader|MockObject
     */
    protected $_uploaderMock;

    /**
     * @var Upload|MockObject
     */
    protected $_categoryUploaderMock;

    /**
     * @var FilesystemAdapter|MockObject
     */
    protected $_flysystemAdapterMock;

    /**
     * @var Local|MockObject
     */
    protected $_localAdapterMock;

    /**
     * @var Store|MockObject
     */
    protected $_storeMock;

    /**
     * @var User|MockObject
     */
    protected $_userMock;

    /**
     * @var TmpManager
     */
    protected $_object;


    public function setUp()
    {
        $this->_flysystemManagerMock = $this->getMockBuilder(FilesystemManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['createLocalDriver'])
            ->getMock();

        $this->_flysystemFactoryMock = $this->getMockBuilder(FilesystemAdapterFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->_flysystemConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_flysystemHelperMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_filesystemMock = $this->getMockBuilder(MagentoFilesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDirectoryWrite'])
            ->getMock();

        $this->_loggerMock = $this->getMockBuilder(Monolog::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical'])
            ->getMock();

        $this->_adminSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUser'])
            ->getMock();

        $this->_productMediaConfigMock = $this->getMockBuilder(ProductMediaConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseTmpMediaPath', 'getTmpMediaUrl'])
            ->getMock();

        $this->_objectManagerMock = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'get'])
            ->getMock();

        $this->_coreFileStorageDatabaseMock = $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_storeManagerMock = $this->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMock();

        $this->_directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAbsolutePath'])
            ->getMock();

        $this->_uploaderMock = $this->getMockBuilder(Uploader::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();

        $this->_categoryUploaderMock = $this->getMockBuilder(Upload::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseTmpPath', 'getAllowedExtensions', 'getFilePath'])
            ->getMock();

        $this->_flysystemAdapterMock = $this->getMockBuilder(FilesystemAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['has', 'read', 'write', 'deleteDir'])
            ->getMock();

        $this->_localAdapterMock = $this->getMockBuilder(Local::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAbsolutePath'])
            ->getMock();

        $this->_storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseUrl'])
            ->getMock();

        $this->_userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUserName'])
            ->getMock();

        $this->_filesystemMock->expects($this->once())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::MEDIA)
            ->willReturn($this->_directoryListMock);

        $this->_directoryListMock->expects($this->at(0))
            ->method('getAbsolutePath')
            ->willReturn('/var/www/html/pub/media');

        $this->_flysystemManagerMock->expects($this->once())
            ->method('createLocalDriver')
            ->with('/var/www/html/pub/media')
            ->willReturn($this->_localAdapterMock);

        $this->_flysystemFactoryMock->expects($this->once())
            ->method('create')
            ->with($this->_localAdapterMock)
            ->willReturn($this->_flysystemAdapterMock);

        $this->_object = new TmpManager(
            $this->_flysystemManagerMock,
            $this->_flysystemFactoryMock,
            $this->_flysystemConfigMock,
            $this->_flysystemHelperMock,
            $this->_filesystemMock,
            $this->_loggerMock,
            $this->_adminSessionMock,
            $this->_productMediaConfigMock,
            $this->_objectManagerMock,
            $this->_coreFileStorageDatabaseMock,
            $this->_storeManagerMock
        );
    }


    public function testCreateProductTmp()
    {
        $file = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/var/www/html/pub/media/flysystem/.tmp/test.jpg',
            'error' => 0,
            'size' => 100
        ];

        $returnFile = [
            'file' => 'test.jpg',
            'tmp_name' => 'TEST',
            'path' => '/var/www/html/pub/product/test.jpg'
        ];

        $expectedReturn = [
            'file' => 'test.jpg.tmp',
            'url' => 'test.test/product/test.jpg'
        ];

        $baseTmpPath = '/product/tmp';
        $absolutePath = '/var/www/html/pub/product/tmp';

        $this->_productMediaConfigMock->expects($this->once())
            ->method('getBaseTmpMediaPath')
            ->willReturn($baseTmpPath);

        $this->_objectManagerMock->expects($this->once())
            ->method('create')
            ->withAnyParameters()
            ->willReturn($this->_uploaderMock);

        $this->_directoryListMock->expects($this->at(0))
            ->method('getAbsolutePath')
            ->with($baseTmpPath)
            ->willReturn($absolutePath);

        $this->_uploaderMock->expects($this->once())
            ->method('save')
            ->with($absolutePath)
            ->willReturn($returnFile);

        $this->_productMediaConfigMock->expects($this->once())
            ->method('getTmpMediaUrl')
            ->with($returnFile['file'])
            ->willReturn($expectedReturn['url']);

        $this->assertEquals($expectedReturn, $this->_object->createProductTmp($file));
    }

    public function testCreateProductTmpException()
    {
        $file = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/var/www/html/pub/media/flysystem/.tmp/test.jpg',
            'error' => 0,
            'size' => 100
        ];

        $baseTmpPath = '/product/tmp';

        $exception = new \Exception();

        $this->_productMediaConfigMock->expects($this->once())
            ->method('getBaseTmpMediaPath')
            ->willReturn($baseTmpPath);

        $this->_objectManagerMock->expects($this->once())
            ->method('create')
            ->withAnyParameters()
            ->willThrowException($exception);

        $this->expectException(\Exception::class);

        $this->_object->createProductTmp($file);
    }

    public function testCreateCategoryTmp()
    {
        $baseTmpPath = '/category/tmp';
        $absolutePath = '/var/www/html/pub/category/tmp';

        $file = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/var/www/html/pub/media/flysystem/.tmp/test.jpg',
            'error' => 0,
            'size' => 100
        ];

        $returnFile = [
            'file' => 'test.jpg',
            'tmp_name' => 'TEST',
            'path' => '/var/www/html/pub/category/test.jpg'
        ];

        $expectedReturn = [
            'file' => 'test.jpg',
            'name' => 'test.jpg',
            'tmp_name' => 'TEST',
            'url' => 'test.test/category/test.jpg'
        ];

        $this->_objectManagerMock->expects($this->once())
            ->method('get')
            ->withAnyParameters()
            ->willReturn($this->_categoryUploaderMock);

        $this->_categoryUploaderMock->expects($this->once())
            ->method('getBaseTmpPath')
            ->willReturn($baseTmpPath);

        $this->_objectManagerMock->expects($this->once())
            ->method('create')
            ->withAnyParameters()
            ->willReturn($this->_uploaderMock);

        $this->_categoryUploaderMock->expects($this->once())
            ->method('getAllowedExtensions')
            ->willReturn(['jpg']);

        $this->_directoryListMock->expects($this->at(0))
            ->method('getAbsolutePath')
            ->with($baseTmpPath)
            ->willReturn($absolutePath);

        $this->_uploaderMock->expects($this->once())
            ->method('save')
            ->with($absolutePath)
            ->willReturn($returnFile);

        $this->_storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->_storeMock);

        $this->_storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('test.test');

        $this->_categoryUploaderMock->expects($this->once())
            ->method('getFilePath')
            ->with($baseTmpPath, $returnFile['file'])
            ->willReturn('/category/test.jpg');

        $this->_coreFileStorageDatabaseMock->expects($this->once())
            ->method('saveFile')
            ->with($baseTmpPath.'/'.$expectedReturn['file']);

        $this->assertEquals($expectedReturn, $this->_object->createCategoryTmp($file));
    }

    public function testCreateCategoryTmpException()
    {
        $baseTmpPath = '/category/tmp';
        $absolutePath = '/var/www/html/pub/category/tmp';

        $file = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/var/www/html/pub/media/flysystem/.tmp/test.jpg',
            'error' => 0,
            'size' => 100
        ];

        $returnFile = [
            'file' => 'test.jpg',
            'tmp_name' => 'TEST',
            'path' => '/var/www/html/pub/category/test.jpg'
        ];

        $expectedReturn = [
            'file' => 'test.jpg',
            'name' => 'test.jpg',
            'tmp_name' => 'TEST',
            'url' => 'test.test/category/test.jpg'
        ];

        $exception = new \Exception();

        $this->_objectManagerMock->expects($this->once())
            ->method('get')
            ->withAnyParameters()
            ->willReturn($this->_categoryUploaderMock);

        $this->_categoryUploaderMock->expects($this->once())
            ->method('getBaseTmpPath')
            ->willReturn($baseTmpPath);

        $this->_objectManagerMock->expects($this->once())
            ->method('create')
            ->withAnyParameters()
            ->willReturn($this->_uploaderMock);

        $this->_categoryUploaderMock->expects($this->once())
            ->method('getAllowedExtensions')
            ->willReturn(['jpg']);

        $this->_directoryListMock->expects($this->at(0))
            ->method('getAbsolutePath')
            ->with($baseTmpPath)
            ->willReturn($absolutePath);

        $this->_uploaderMock->expects($this->once())
            ->method('save')
            ->with($absolutePath)
            ->willReturn($returnFile);

        $this->_storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->_storeMock);

        $this->_storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('test.test');

        $this->_categoryUploaderMock->expects($this->once())
            ->method('getFilePath')
            ->with($baseTmpPath, $returnFile['file'])
            ->willReturn('/category/test.jpg');

        $this->_coreFileStorageDatabaseMock->expects($this->once())
            ->method('saveFile')
            ->with($baseTmpPath.'/'.$expectedReturn['file'])
            ->willThrowException($exception);

        $this->_loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->expectException(LocalizedException::class);

        $this->_object->createCategoryTmp($file);
    }

    public function testGetTmp()
    {
        $file = 'test.jpg';
        $encodedFile = 'encodedFile';
        $username = 'testuser';
        $encodedUsername = 'encodeduser';

        $tmpPath = Config::FLYSYSTEM_DIRECTORY.'/'.Config::FLYSYSTEM_DIRECTORY_TMP.'/'.$encodedUsername.'/'.$encodedFile;

        $fileContent = 'testcontent';

        $this->_flysystemHelperMock->expects($this->at(0))
            ->method('idEncode')
            ->with($file)
            ->willReturn($encodedFile);

        $this->_adminSessionMock->expects($this->once())
            ->method('getUser')
            ->willReturn($this->_userMock);

        $this->_userMock->expects($this->once())
            ->method('getUserName')
            ->willReturn($username);

        $this->_flysystemHelperMock->expects($this->at(1))
            ->method('idEncode')
            ->with($username)
            ->willReturn($encodedUsername);

        $this->_flysystemAdapterMock->expects($this->once())
            ->method('has')
            ->with($tmpPath)
            ->willReturn(true);

        $this->_flysystemAdapterMock->expects($this->once())
            ->method('read')
            ->with($tmpPath)
            ->willReturn($fileContent);

        $this->assertEquals($fileContent, $this->_object->getTmp($file));
    }

    public function testWritePreview()
    {
        $username = 'test';
        $userDir = 'userhash';
        $file = 'path/test.png';
        $previewDir = Config::FLYSYSTEM_DIRECTORY.'/'.Config::FLYSYSTEM_DIRECTORY_PREVIEW.'/'.$userDir;
        $fullPath = $previewDir.'/'.'test.png';
        $content = 'test';

        $this->_adminSessionMock->expects($this->once())
            ->method('getUser')
            ->willReturn($this->_userMock);

        $this->_userMock->expects($this->once())
            ->method('getUserName')
            ->willReturn($username);

        $this->_flysystemHelperMock->expects($this->once())
            ->method('idEncode')
            ->with($username)
            ->willReturn($userDir);

        $this->_flysystemAdapterMock->expects($this->once())
            ->method('deleteDir')
            ->with($previewDir)
            ->willReturn(true);

        $this->_flysystemAdapterMock->expects($this->once())
            ->method('write')
            ->with($fullPath, $content)
            ->willReturn(true);

        $this->assertEquals(true, $this->_object->writePreview($file, $content));
    }

    public function testGetUserPreviewDirException()
    {
        $this->_adminSessionMock->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->expectException(LocalizedException::class);

        $this->_object->getUserPreviewDir();
    }
}