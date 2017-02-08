<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\EntityRepository;

class InvoiceToSendNavOnlyRepository extends EntityRepository
{
    /**
     * @return InvoiceToSendNavOnly[]
     */
    public function getInvoiceToSendNavOnly()
    {
        return $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->andWhere('i.dateAdded >= :date')
            ->setParameters([
                'status' => 'unsent',
                'date' => new \DateTime('-7 day')
            ])
            ->getQuery()
            ->getResult();
    }
}
