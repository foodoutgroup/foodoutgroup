<?php

namespace Food\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Curl;

class DelfiBannerDownloadCommand extends ContainerAwareCommand
{
    /**
     * @var Curl
     */
    private $_cli;

    /**
     * @param \Curl $cli
     */
    public function setCli($cli)
    {
        $this->_cli = $cli;
    }

    /**
     * @return \Curl
     */
    public function getCli()
    {
        if (empty($this->_cli)) {
            $this->_cli = new Curl;
            $this->_cli->options['CURLOPT_SSL_VERIFYPEER'] = false;
            $this->_cli->options['CURLOPT_SSL_VERIFYHOST'] = false;
        }
        return $this->_cli;
    }

    protected function configure()
    {
        $this
            ->setName('delfi:banner:download')
            ->setDescription('Downloads Delfi 1000receptu banners and stores them for show')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Url 1. JS - http://www.1000receptu.lt/misc/export/header_v2.php?e=meta&p=foodout.1000receptu.lt
        // Url 2. Banner - http://www.1000receptu.lt/misc/export/header_v2.php?e=header&p=foodout.1000receptu.lt&hide-ads=1

        $miscService = $this->getContainer()->get('food.app.utils.misc');

        // TODO - configurable maby?
        $jsCode = $this->getCli()->get(
            'http://www.1000receptu.lt/misc/export/header_v2.php?e=meta&p=foodout.1000receptu.lt'
        );

        $bannerCode = $this->getCli()->get(
            'http://www.1000receptu.lt/misc/export/header_v2.php?e=header&p=foodout.1000receptu.lt&hide-ads=1'
        );

        try {
            if (!empty($jsCode->body)) {
                $miscService->setParam(
                    'delfiJs',
                    $jsCode->body
                );
            }

            if (!empty($bannerCode->body)) {
                $miscService->setParam(
                    'delfiBanner',
                    $bannerCode->body
                );
            }
        } catch (\Exception $e) {
            $this->getContainer()->get('logger')->error('Omg, Delfi banner save failed, what will we do??? Error: '.$e->getMessage());

            throw $e;
        }
    }
}
