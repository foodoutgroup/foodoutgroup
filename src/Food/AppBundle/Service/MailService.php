<?php
namespace Food\AppBundle\Service;

use Food\AppBundle\Entity\EmailToSend;
use Food\OrderBundle\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerAware;

class MailService extends ContainerAware
{
    public static $typeCompleted = "order_completed";
    public static $typePartialyCompleted = "order_partialy_completed";

    /**
     * @param $order
     * @param $type
     * @param \DateTime|null $sendOnDate
     *
     * @throws \InvalidArgumentException
     */
    public function addEmailForSend($order, $type, $sendOnDate = null)
    {
        if (empty($order) || !$order instanceof Order) {
            throw new \InvalidArgumentException('Can not schedule email sending - non order given');
        }

        if (empty($type)) {
            throw new \InvalidArgumentException('Can not schedule email sending - unknown type of email: "'.$type.'"');
        }

        $doctrine = $this->container->get('doctrine');
        $em = $doctrine->getManager();

        if (empty($sendOnDate)) {
            $sendOnDate = new \DateTime('now');
        }

        $emailToSend = new EmailToSend();
        $emailToSend->setOrder($order)
            ->setType($type)
            ->setCreatedAt(new \DateTime('now'))
            ->setSendOnDate($sendOnDate)
            ->setSent(false);

        $em->persist($emailToSend);
        $em->flush();
    }

    /**
     * @return array|EmailToSend[]
     */
    public function getEmailsToSend()
    {
        $repo = $this->container->get('doctrine')->getRepository('FoodAppBundle:EmailToSend');

        $emails = $repo->createQueryBuilder('m')
            ->where('m.sent = 0')
            ->andWhere('m.error is NULL')
            ->andWhere('m.sendOnDate <= :thisIsTheEnd')
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults(40)
            ->setParameter('thisIsTheEnd', new \DateTime('-1 minute'))
            ->getQuery()
            ->getResult();

        if (!$emails) {
            return array();
        }

        return $emails;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array|EmailToSend[]
     *
     * @throws \InvalidArgumentException
     */
    public function getUnsentEmailsForRange($from, $to)
    {
        if (!$from instanceof \DateTime) {
            throw new \InvalidArgumentException('No beginning of the range has been given in email selec');
        }
        if (!$to instanceof \DateTime) {
            throw new \InvalidArgumentException('No end of the range has been given in email selec');
        }

        $repo = $this->container->get('doctrine')->getRepository('FoodAppBundle:EmailToSend');

        $emails = $repo->createQueryBuilder('m')
            ->where('m.sent = 0')
            ->andWhere('m.sendOnDate BETWEEN :thisIsTheStart AND :thisIsTheEnd')
            ->setParameter('thisIsTheStart', $from)
            ->setParameter('thisIsTheEnd', $to)
            ->getQuery()
            ->getResult();

        if (!$emails) {
            return array();
        }

        return $emails;
    }

    /**
     * @param EmailToSend $emailToSend
     *
     * @throws \InvalidArgumentException
     */
    public function markEmailSent($emailToSend)
    {
        if (!$emailToSend instanceof EmailToSend) {
            throw new \InvalidArgumentException('Can not mark email sent - no email object given');
        }

        $em = $this->container->get('doctrine')->getManager();

        $emailToSend->setSent(true)
            ->setSentAt(new \DateTime('now'));

        $em->persist($emailToSend);
        $em->flush();
    }

    /**
     * @param EmailToSend $emailToSend
     * @param string $error
     *
     * @throws \InvalidArgumentException
     */
    public function markAsError($emailToSend, $error)
    {
        if (!$emailToSend instanceof EmailToSend) {
            throw new \InvalidArgumentException('Can not mark email sent - no email object given');
        }

        $em = $this->container->get('doctrine')->getManager();

        $emailToSend->setSent(false)
            ->setError($error);

        $em->persist($emailToSend);
        $em->flush();
    }
}