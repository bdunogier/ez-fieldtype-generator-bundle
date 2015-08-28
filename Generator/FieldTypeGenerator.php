<?php
namespace BD\EzFieldTypeGeneratorBundle\Generator;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Generates an eZ FieldType
 */
class FieldTypeGenerator extends Generator
{
    private $filesystem;

    /** @var \Symfony\Component\HttpKernel\KernelInterface */
    private $kernel;

    public function __construct(Filesystem $filesystem, KernelInterface $kernel)
    {
        $this->filesystem = $filesystem;
        $this->kernel = $kernel;
    }

    public function generate($targetBundle, $targetBundleDir, $fieldTypeName, $fieldTypeIdentifier)
    {
        $this->setSkeletonDirs(realpath(__DIR__.'/../Resources/skeleton'));
        if (file_exists($targetBundleDir)) {
            if (!is_dir($targetBundleDir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.', realpath($targetBundleDir)));
            }
            if (!is_writable($targetBundleDir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not writable.', realpath($targetBundleDir)));
            }
        }

        $parameters = array(
            'namespace' => 'BD\Test',
            'target_bundle' => $targetBundle,
            'target_bundle_dir' => $targetBundleDir,
            'fieldtype_name' => $fieldTypeName,
            'fieldtype_identifier' => $fieldTypeIdentifier,
        );

        $this->renderFile('Type.php.twig', $targetBundleDir.'/eZ/FieldType/FieldTypeName/Type.php', $parameters);
        $this->renderFile('Value.php.twig', $targetBundleDir.'/eZ/FieldType/FieldTypeName/Value.php', $parameters);

        // service definition
        $this->renderFile('fieldtypes.yml.twig', $targetBundleDir.'/Resources/config/fieldtypes.yml', $parameters);

        // external storage if any
        // form mapper
        // form template
    }
}
