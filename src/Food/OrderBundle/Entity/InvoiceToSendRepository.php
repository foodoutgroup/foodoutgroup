<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\EntityRepository;

class InvoiceToSendRepository extends EntityRepository
{
    /**
     * @return InvoiceToSend[]
     */
    public function getInvoiceToSend()
    {
        return $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->andWhere('i.dateAdded >= :date')
            ->setParameters(
                array(
                    'status' => 'unsent',
                    'date' => new \DateTime('- 1 day')
                )
            )
            ->getQuery()
            ->getResult();
    }
}
