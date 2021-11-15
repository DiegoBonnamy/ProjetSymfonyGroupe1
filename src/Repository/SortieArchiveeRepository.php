<?php

namespace App\Repository;

use App\Entity\SortieArchivee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SortieArchivee|null find($id, $lockMode = null, $lockVersion = null)
 * @method SortieArchivee|null findOneBy(array $criteria, array $orderBy = null)
 * @method SortieArchivee[]    findAll()
 * @method SortieArchivee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieArchiveeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SortieArchivee::class);
    }

    // /**
    //  * @return SortieArchivee[] Returns an array of SortieArchivee objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SortieArchivee
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
