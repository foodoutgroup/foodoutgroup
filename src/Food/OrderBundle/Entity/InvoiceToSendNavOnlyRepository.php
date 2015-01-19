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
                    ->setParameters(['status' => 'unsent'])
                    ->getQuery()
                    ->getResult();
    }
}
