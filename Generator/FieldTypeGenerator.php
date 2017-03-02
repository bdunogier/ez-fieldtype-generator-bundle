<?php
namespace BD\EzFieldTypeGeneratorBundle\Generator;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\Filesystem\Filesystem;
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

        $bundle = $this->kernel->getBundle($targetBundle);
        $parameters = [
            'namespace' => $bundle->getNamespace(),
            'target_bundle' => $targetBundle,
            'target_bundle_dir' => $targetBundleDir,
            'fieldtype_name' => $fieldTypeName,
            'fieldtype_identifier' => $fieldTypeIdentifier,
            'fieldtype_type_class' => sprintf('%s\eZ\FieldType\%s\Type', $bundle->getNamespace(), $fieldTypeName),
            'fieldtype_converter_class' => sprintf('%s\eZ\FieldType\%s\LegacyConverter', $bundle->getNamespace(), $fieldTypeName),
            'public_bundle_dir' => 'bundles/' . strtolower(str_replace('Bundle', '', $targetBundle)),
        ];

        $this->renderFile(
            'Type.php.twig',
            sprintf('%s/eZ/FieldType/%s/Type.php', $targetBundleDir, $fieldTypeName),
            $parameters
        );
        $this->renderFile(
            'Value.php.twig',
            sprintf('%s/eZ/FieldType/%s/Value.php', $targetBundleDir, $fieldTypeName),
            $parameters
        );
        $this->renderFile(
            'LegacyConverter.php.twig',
            sprintf('%s/eZ/FieldType/%s/LegacyConverter.php', $targetBundleDir, $fieldTypeName),
            $parameters
        );

        // service definition
        $this->renderFile(
            'services.yml.twig',
            sprintf('%s/Resources/config/services.yml', $targetBundleDir),
            $parameters
        );

        // fieldtypes templates configuration
        $this->renderFile(
            'fieldtypes_templates.yml.twig',
            sprintf('%s/Resources/config/fieldtypes_templates.yml', $targetBundleDir),
            $parameters
        );
        // fieldtypes templates
        $this->renderFile(
            'fieldtypes_templates.html.twig',
            sprintf('%s/Resources/views/fieldtypes_templates.html.twig', $targetBundleDir),
            $parameters
        );
        // DI Extension that prepends the fieldtypes_templates configuration
        $this->renderFile(
            'Extension.php.twig',
            sprintf(
                '%s/DependencyInjection/%s.php',
                $targetBundleDir,
                str_replace('Bundle', 'Extension', $targetBundle)
            ),
            $parameters + [
                'class_name' => str_replace('Bundle', 'Extension', $targetBundle),
            ]
        );

        // yui items
        $this->renderFile(
            'fieldedit.hbt.twig',
            sprintf(
                '%s/Resources/public/templates/fields/edit/%s.hbt',
                $targetBundleDir,
                $fieldTypeIdentifier
            ),
            $parameters
        );
        $this->renderFile(
            'fieldeditview.js.twig',
            sprintf(
                '%s/Resources/public/js/views/fields/ez-%s-editview.js',
                $targetBundleDir,
                $fieldTypeIdentifier
            ),
            $parameters
        );
        $this->renderFile(
            'fieldview.hbt.twig',
            sprintf(
                '%s/Resources/public/templates/fields/view/%s.hbt',
                $targetBundleDir,
                $fieldTypeIdentifier
            ),
            $parameters
        );
        $this->renderFile(
            'fieldview.js.twig',
            sprintf(
                '%s/Resources/public/js/views/fields/ez-%s-view.js',
                $targetBundleDir,
                $fieldTypeIdentifier
            ),
            $parameters
        );
        $this->renderFile(
            'yui.yml.twig',
            sprintf('%s/Resources/config/yui.yml', $targetBundleDir),
            $parameters
        );
    }
}
