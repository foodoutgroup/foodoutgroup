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
     * @return bool|EmailTemplate
     */
    public function findOneByOrder(Order $order)
    {
        $params = [
            'order_status' => $order->getOrderStatus(),
            'preorder' => (bool)$order->getPreorder(),
            'source' => $order->getSource(),
            'type' => 'deliver'
        ];

        if($order->getOrderPicked()) {
            $params['type'] = 'pickup';
        }

        $qb = $this->createQueryBuilder('et')
            ->where('et.status = :order_status')
            ->andWhere('et.preorder = :preorder')
            ->andWhere('et.source = :source')
            ->andWhere('et.active = 1')
            ->andWhere('et.type = :type')
            ->setMaxResults(1);

        $result = $qb->getQuery()->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker')
            ->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $order->getLocale())
            ->execute($params);

        if(count($result)) {
            return $result[0];
        }

        return false;
    }
}
