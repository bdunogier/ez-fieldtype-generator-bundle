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

    public function generate($targetBundle, $fieldTypeName, $fieldTypeIdentifier)
    {
        $this->setSkeletonDirs(realpath(__DIR__.'/../Resources/skeleton'));

        $bundle = $this->kernel->getBundle($targetBundle);

        $parameters = [
            'namespace' => $bundle->getNamespace(),
            'target_bundle' => $targetBundle,
            'target_bundle_dir' => $bundle->getPath(),
            'fieldtype_name' => $fieldTypeName,
            'fieldtype_identifier' => $fieldTypeIdentifier,
            'fieldtype_type_class' => sprintf('%s\eZ\FieldType\%s\Type', $bundle->getNamespace(), $fieldTypeName),
            'fieldtype_converter_class' => sprintf('%s\eZ\FieldType\%s\LegacyConverter', $bundle->getNamespace(), $fieldTypeName),
            'public_bundle_dir' => 'bundles/' . strtolower(str_replace('Bundle', '', $targetBundle)),
        ];

        $this->renderFile(
            'eZ/FieldType/Name/Type.php.twig',
            sprintf('%s/eZ/FieldType/%s/Type.php', $parameters['target_bundle_dir'], $fieldTypeName),
            $parameters
        );
        $this->renderFile(
            'eZ/FieldType/Name/Value.php.twig',
            sprintf('%s/eZ/FieldType/%s/Value.php', $parameters['target_bundle_dir'], $fieldTypeName),
            $parameters
        );
        $this->renderFile(
            'eZ/FieldType/Name/LegacyConverter.php.twig',
            sprintf('%s/eZ/FieldType/%s/LegacyConverter.php', $parameters['target_bundle_dir'], $fieldTypeName),
            $parameters
        );

        // service definition
        $this->renderFile(
            'Resources/config/services.yml.twig',
            sprintf('%s/Resources/config/services.yml', $parameters['target_bundle_dir']),
            $parameters
        );

        // fieldtypes templates configuration
        $this->renderFile(
            'Resources/config/fieldtypes_templates.yml.twig',
            sprintf('%s/Resources/config/fieldtypes_templates.yml', $parameters['target_bundle_dir']),
            $parameters
        );
        // fieldtypes templates
        $this->renderFile(
            'Resources/views/fieldtypes_templates.html.twig',
            sprintf('%s/Resources/views/fieldtypes_templates.html.twig', $parameters['target_bundle_dir']),
            $parameters
        );
        // DI Extension that prepends the fieldtypes_templates configuration
        $this->renderFile(
            'DependencyInjection/Extension.php.twig',
            sprintf(
                '%s/DependencyInjection/%s.php',
                $parameters['target_bundle_dir'],
                str_replace('Bundle', 'Extension', $targetBundle)
            ),
            $parameters + [
                'class_name' => str_replace('Bundle', 'Extension', $targetBundle),
            ]
        );

        // yui items
        $this->renderFile(
            'Resources/public/templates/fields/edit/fieldedit.hbt.twig',
            sprintf(
                '%s/Resources/public/templates/fields/edit/%s.hbt',
                $parameters['target_bundle_dir'],
                $fieldTypeIdentifier
            ),
            $parameters
        );
        $this->renderFile(
            'Resources/public/js/views/fields/fieldeditview.js.twig',
            sprintf(
                '%s/Resources/public/js/views/fields/ez-%s-editview.js',
                $parameters['target_bundle_dir'],
                $fieldTypeIdentifier
            ),
            $parameters
        );
        $this->renderFile(
            'Resources/public/templates/fields/view/fieldview.hbt.twig',
            sprintf(
                '%s/Resources/public/templates/fields/view/%s.hbt',
                $parameters['target_bundle_dir'],
                $fieldTypeIdentifier
            ),
            $parameters
        );
        $this->renderFile(
            'Resources/public/js/views/fields/fieldview.js.twig',
            sprintf(
                '%s/Resources/public/js/views/fields/ez-%s-view.js',
                $parameters['target_bundle_dir'],
                $fieldTypeIdentifier
            ),
            $parameters
        );
        $this->renderFile(
            'Resources/config/yui.yml.twig',
            sprintf('%s/Resources/config/yui.yml', $parameters['target_bundle_dir']),
            $parameters
        );

        // README
        $this->renderFile(
            'README.md.twig',
            sprintf('%s/README.md', $parameters['target_bundle_dir']),
            $parameters
        );
    }
}
