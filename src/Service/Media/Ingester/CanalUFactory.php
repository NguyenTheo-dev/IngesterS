<?php
namespace IngesterS\Service\Media\Ingester;

use IngesterS\Media\Ingester\CanalU;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class CanalUFactory implements FactoryInterface
{

    /**
     * Create the CanalU ingester service.
     *
     * @return CanalU
     */

    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {

        return new CanalU(
            $services->get('Omeka\File\Downloader')
        );
    }
}