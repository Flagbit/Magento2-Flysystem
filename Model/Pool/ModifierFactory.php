<?php
namespace Flagbit\Flysystem\Model\Pool;

use \Magento\Framework\ObjectManagerInterface;

/**
 * Class ModifierFactory
 * @package Flagbit\Flysystem\Model\Pool
 */
class ModifierFactory
{
    /**
     * Object Manager
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Construct
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create model
     *
     * @param string $className
     * @param array $data
     * @return ModifierInterface
     * @throws \InvalidArgumentException
     */
    public function create($className, array $data = [])
    {
        $model = $this->objectManager->create($className, $data);

        if (!$model instanceof ModifierInterface) {
            throw new \InvalidArgumentException(
                'Type "' . $className . '" is not instance on ' . ModifierInterface::class
            );
        }

        return $model;
    }
}
