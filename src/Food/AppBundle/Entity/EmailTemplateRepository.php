<?php

namespace Food\AppBundle\Entity;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Food\OrderBundle\Entity\Order;
use Gedmo\Translatable\TranslatableListener;

/**
 * EmailTemplateRepository
 */

class EmailTemplateRepository extends EntityRepository
{
    /**
     * @param Order $order
     * @return bool|EmailTemplate[]
     */
    public function findByOrder(Order $order)
    {
        $params = [
            'order_status' => $order->getOrderStatus(),
            'preorder' => (bool)$order->getPreorder(),
            'source' => $order->getSource(),
            'defaultSource' => 'All',
            'type' => 'deliver',
            'self_delivery' => $order->getPlace()->getSelfDelivery()
        ];

        if ('pickup' == $order->getDeliveryType()) {
            $params['type'] = 'pickup';
        }

        $qb = $this->createQueryBuilder('et');
        $qb->where('et.status = :order_status')
            ->andWhere('et.preorder = :preorder')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('et.source', ':source'),
                $qb->expr()->eq('et.source', ':defaultSource'),
                $qb->expr()->eq('et.selfDelivery', ':self_delivery')
            ))
            ->andWhere('et.active = 1')
            ->andWhere('et.type = :type');


        $result = $qb->getQuery()->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker')
            ->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $order->getLocale())
            ->execute($params);

        return $result;
    }
}
