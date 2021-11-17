<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Sortie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sortie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sortie[]    findAll()
 * @method Sortie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    /**
    * @return Sortie[] Returns an array of Sortie objects
    */
    public function findByFilters($site, $search, $dateDebut, $dateFin)
    {
        $search = "%".$search."%";
        return $this->createQueryBuilder('s')
            ->andWhere('s.site = :site')
            ->andWhere('s.nom LIKE :search')
            ->andWhere('s.dateDebut >= :dateDebut')
            ->andWhere('s.dateDebut <= :dateFin')
            ->setParameter('site', $site)
            ->setParameter('search', $search)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
    * @return Sortie[] Returns an array of Sortie objects
    */
    public function findActual($etatOuvert, $etatEnCours, $etatEnCreation, $etatAnnulee)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.etat = :etatOuvert OR s.etat = :etatEnCours OR s.etat = :etatEnCreation OR s.etat = :etatAnnulee')
            ->setParameter('etatOuvert', $etatOuvert)
            ->setParameter('etatEnCours', $etatEnCours)
            ->setParameter('etatEnCreation', $etatEnCreation)
            ->setParameter('etatAnnulee', $etatAnnulee)
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?Sortie
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
