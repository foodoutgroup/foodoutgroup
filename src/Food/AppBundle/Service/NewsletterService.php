<?php
namespace Food\AppBundle\Service;

use Food\AppBundle\Entity\Subscribers;
use Food\AppBundle\Traits;

class NewsletterService {
    use Traits\Service;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @param \Food\AppBundle\Service\Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return \Food\AppBundle\Service\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Subscribe to newsletter
     *
     * @param string $email
     * @param string $locale
     * @throws \InvalidArgumentException
     */
    public function subscribe($email, $locale)
    {
        if (empty($email)) {
            throw new \InvalidArgumentException('Sorry, cant subscribe without email!');
        }

        $em = $this->em();

        $subscriber = new Subscribers();
        $subscriber->setDateAdded(new \DateTime("now"));
        $subscriber->setLocale($locale);
        $subscriber->setEmail($email);

        $em->persist($subscriber);
        $em->flush();

    }
}