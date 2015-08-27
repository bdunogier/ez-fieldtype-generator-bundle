<?php
namespace BD\EzFieldTypeGeneratorBundle\Command;

use BD\EzFieldTypeGeneratorBundle\Generator\FieldTypeGenerator;
use Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Generates bundles.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class GenerateFieldTypeCommand extends GeneratorCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption('target-bundle', '', InputOption::VALUE_REQUIRED, 'The name of the existing bundle the FieldType must be generated into. Ex: AcmeTestBundle'),
                new InputOption('fieldtype-name', '', InputOption::VALUE_REQUIRED, 'The name of the FieldTpe to generate. Ex: ezstring'),
            ))
            ->setDescription('Generates an eZ FieldType')
            ->setHelp(<<<EOT
Writeme
EOT
            )
            ->setName('generate:ez:fieldtype')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When namespace doesn't end with Bundle
     * @throws \RuntimeException         When bundle can't be executed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        if ($input->isInteractive()) {
            if (!$questionHelper->ask($input, $output, new ConfirmationQuestion($questionHelper->getQuestion('Do you confirm generation', 'yes', '?'), true))) {
                $output->writeln('<error>Command aborted</error>');

                return 1;
            }
        }

        foreach (array('namespace', 'dir') as $option) {
            if (null === $input->getOption($option)) {
                throw new \RuntimeException(sprintf('The "%s" option must be provided.', $option));
            }
        }

        // validate the namespace, but don't require a vendor namespace
        $namespace = Validators::validateBundleNamespace($input->getOption('namespace'), false);
        if (!$bundle = $input->getOption('bundle-name')) {
            $bundle = strtr($namespace, array('\\' => ''));
        }
        $bundle = Validators::validateBundleName($bundle);
        $dir = Validators::validateTargetDir($input->getOption('dir'), $bundle, $namespace);
        if (null === $input->getOption('format')) {
            $input->setOption('format', 'annotation');
        }
        $format = Validators::validateFormat($input->getOption('format'));
        $structure = $input->getOption('structure');

        $questionHelper->writeSection($output, 'Bundle generation');

        if (!$this->getContainer()->get('filesystem')->isAbsolutePath($dir)) {
            $dir = getcwd().'/'.$dir;
        }

        $generator = $this->getGenerator();
        $generator->generate($namespace, $bundle, $dir, $format, $structure);

        $output->writeln('Generating the bundle code: <info>OK</info>');

        $errors = array();
        $runner = $questionHelper->getRunner($output, $errors);

        // check that the namespace is already autoloaded
        $runner($this->checkAutoloader($output, $namespace, $bundle, $dir));

        // register the bundle in the Kernel class
        $runner($this->updateKernel($questionHelper, $input, $output, $this->getContainer()->get('kernel'), $namespace, $bundle));

        // routing
        $runner($this->updateRouting($questionHelper, $input, $output, $bundle, $format));

        $questionHelper->writeGeneratorSummary($output, $errors);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the eZ FieldType generator');

        // target bundle
        $targetBundle = null;
        try {
            // validate the namespace option (if any) but don't require the vendor namespace
            $targetBundle = $input->getOption('target-bundle') ? Validators::validateBundleName($input->getOption('target-bundle'), false) : null;
        } catch (\Exception $error) {
            $output->writeln($questionHelper->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
        }

        if ($targetBundle === null ) {
            $output->writeln(array(
                '',
                'Your FieldType must be created in a <comment>bundle</comment>. ',
                '',
                'If you don\'t have a bundle for it, please create one using <comment>generate:bundle</comment> first',
            ));

            $acceptedTargetBundle = false;
            while (!$acceptedTargetBundle) {
                $question = new Question($questionHelper->getQuestion('Target bundle name', $input->getOption('target-bundle')), $input->getOption('target-bundle'));
                $question->setValidator(function ($answer) {
                    return Validators::validateBundleName($answer);

                });
                $targetBundle = $questionHelper->ask($input, $output, $question);

                try {
                    $dir = $this->getContainer()->get('kernel')->locateResource("@$targetBundle");
                } catch (\InvalidArgumentException $e) {
                    $output->writeln($questionHelper->getHelperSet()->get('formatter')->formatBlock($e->getMessage(), 'error'));
                    continue;
                }
                $acceptedTargetBundle = $questionHelper->ask($input, $output, new ConfirmationQuestion("Generating to $targetBundle ($dir). Do you confirm ?"));

                // mark as accepted, unless they want to try again below
                $acceptedTargetBundle = true;
            }
            $input->setOption('target-bundle', $targetBundle);
        }

        // FieldType name
        $fieldTypeName = null;
        try {
            // @todo validate me
            $fieldTypeName = $input->getOption('fieldtype-name');
        } catch (\Exception $error) {

        }

        if (null === $fieldTypeName) {
            $output->writeln(array(
                '',
                'A FieldType requires a unique identifier.',
                'Identifiers can contain alphanumeric characters as well as underscores',
                '',
            ));
            $question = new Question($questionHelper->getQuestion('FieldType name', $fieldTypeName), $fieldTypeName);
            // @todo validate me // $question->setValidator();
            $fieldTypeName = $questionHelper->ask($input, $output, $question);
            $input->setOption('fieldtype-name', $fieldTypeName);
        }

        // summary
        $output->writeln(array(
            '',
            $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg=white', true),
            '',
            sprintf("You are going to generate a \"<info>%s</info>\" FieldType in the \"<info>%s</info>\" bundle.", $fieldTypeName, $targetBundle),
            '',
        ));
    }

    protected function createGenerator()
    {
        return new FieldTypeGenerator($this->getContainer()->get('filesystem'), $this->getContainer()->get('kernel'));
    }
}
