<?php

namespace Food\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Writer\TranslationWriter;

class TranslationConverterCommand extends ContainerAwareCommand
{
    protected $finder;
    protected $filesystem;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('food:translation:convert')
            ->setDescription('Convert translations from one format to another')
            ->setHelp('You must specify a path using the --path option. Available formats: php|xliff|po|mo|yml|qt|csv|ini|json|res')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Specify a path of files')
            ->addOption('input', null, InputOption::VALUE_REQUIRED, 'Specifiy a input translation format')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Specifiy an output translation format')
            ->addOption('locale', null, InputOption::VALUE_OPTIONAL, 'Specify locale, for example: en')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getOption('path');
        $inputFormat = $input->getOption('input');
        $outputFormat = $input->getOption('output') ?: 'xliff';
        $locale = $input->getOption('locale');

        if (!$inputFormat) {
            throw new \InvalidArgumentException('You must specify a --input format option.');
        }

        if (!$outputFormat) {
            throw new \InvalidArgumentException('You must specify a --output format option.');
        }

        // if (!$path || !$this->getFilesystem()->exists($path)) {
        //     throw new \InvalidArgumentException('You must specify a valid --path option.');
        // }

        $dumper = $this->getDumper($outputFormat);
        $this->getTranslationWriter()->addDumper($outputFormat, $dumper);

        if ($locale) {
            $pattern = sprintf('/^messages\.%s\.[a-z]+/', $locale);
        } else {
            $pattern = '/^messages\.[a-z]{2}\.[a-z]+/';
        }

        $files = $this->getFinder()->files()->name($pattern)->in($path);

        foreach ($files as $file) {
            $pathName = $file->getPathName();
            $pathDir = dirname($pathName);

            list($domain, $language) = explode('.', $file->getFilename());

            $output->writeln(sprintf('Taking file %s', $file->getRealPath()));

            $file = $this->getLoader($inputFormat)->load($file->getRealPath(), $language);
            $messages = $file->all($domain);

            if (!$messages) {
                $output->writeln('No translations found in this file.');

                continue;
            }

            try {
                $this->getTranslationWriter()->writeTranslations($file, $outputFormat, [
                    'path' => $pathDir
                ]);

                $output->writeln('<fg=green>New translation files saved in the same path.</fg=green>');
            } catch (\Exception $e) {
                $output->writeln(sprintf('<fg=red>An error has occured while trying to write translations: %s</fg=red>', $e->getMessage()));
            }
        }
    }

    protected function getFilesystem()
    {
        if (null === $this->filesystem) {
            $this->filesystem = new Filesystem();
        }

        return $this->filesystem;
    }

    protected function getFinder()
    {
        if (null === $this->finder) {
            $this->finder = new Finder();
        }

        return $this->finder;
    }

    protected function getTranslationWriter()
    {
        return $this->getContainer()->get('translation.writer');
    }

    protected function getLoader($format)
    {
        $service = sprintf('translation.loader.%s', $format);

        if (!$this->getContainer()->has($service)) {
            throw new \InvalidArgumentException(sprintf('<fg=red>Unable to find Symfony Translation loader for format "%s"</fg=red>', $format));
        }

        return $this->getContainer()->get($service);
    }

    protected function getDumper($format)
    {
        $service = sprintf('translation.dumper.%s', $format);

        if (!$this->getContainer()->has($service)) {
            throw new \InvalidArgumentException(sprintf('<fg=red>Unable to find Symfony Translation dumper for format "%s"</fg=red>', $format));
        }

        return $this->getContainer()->get($service);
    }

    protected function getTranslationPath()
    {
        return $this->getContainer()->get('kernel')->getRootDir() . '/Resources/translations';
    }
}
