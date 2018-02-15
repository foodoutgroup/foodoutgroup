<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Food\OrderBundle\Entity\Order;
use Gedmo\Translatable\TranslatableListener;

/**
 * SmsTemplateRepository
 */
class SmsTemplateRepository extends EntityRepository
{
    /**
     * @param Order $order
     * @return bool|SmsTemplate[]
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

        $qb = $this->createQueryBuilder('st');

        $qb->where('st.status = :order_status')
            ->andWhere('st.preorder = :preorder')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('st.source', ':source'),
                $qb->expr()->eq('st.source', ':defaultSource'),
                $qb->expr()->isNull('st.source'),
                $qb->expr()->eq('st.selfDelivery', ':self_delivery')
            ))
            ->andWhere('st.type = :type')
            ->andWhere('st.active = 1');

        $result = $qb->getQuery()->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker')
            ->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $order->getLocale())
            ->execute($params);

        return $result;
    }
}
