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
     * @return bool|SmsTemplate
     */
    public function findOneByOrder(Order $order)
    {
        $params = [
            'order_status' => $order->getOrderStatus(),
            'preorder' => (bool)$order->getPreorder(),
            'source' => $order->getSource(),
            'active' => true,
            'type' => 'deliver'
        ];

        if($order->getOrderPicked()) {
            $params['type'] = 'pickup';
        }

        $qb = $this->createQueryBuilder('st')
            ->where('st.order_status = :order_statis')
            ->andWhere('st.preorder = :preorder')
            ->andWhere('st.source = :source')
            ->andWhere('st.active = 1')
            ->andWhere('st.type = :type')
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
