<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use App\Repository\VilleRepository;
use App\Repository\LieuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/sortie")
 */
class SortieController extends AbstractController
{
    /**
     * @Route("/", name="sortie_index", methods={"GET"})
     */
    public function index(SortieRepository $sortieRepository): Response
    {
        return $this->render('sortie/index.html.twig', ['sorties' => $sortieRepository->findAll()]);
    }

    /**
     * @Route("/new", name="sortie_new", methods={"GET","POST"})
     */
    public function new(VilleRepository $villeRepository, EtatRepository $etatRepository, LieuRepository $lieuRepository, Request $request): Response
    {
        $sortie = new Sortie();
        $participant = $this->getUser();

        //
        $sortie->setOrganisateur($participant);

        $etat = $etatRepository->findOneBy(
            ['id' => 1]
        );
        $sortie->setEtat($etat);

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $lieuId = $request->request->get('_lieux');
            dump($lieuId);
            $lieu = $lieuRepository->findOneBy(
                ['id' => $lieuId]
            );
            $sortie->setLieu($lieu);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sortie/new.html.twig', [
            'villes' => $villeRepository->findAll(),
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="sortie_show", methods={"GET"})
     */
    public function show(Sortie $sortie): Response
    {
        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    /**
     * @Route("/cancel/{id}", name="sortie_cancel", methods={"GET","POST"})
     */
    public function cancel(Request $request, Sortie $sortie, EtatRepository $etatRepository): Response
    {
        $motif = $request->request->get('_motif');
        if($motif != null){
            
            $sortie->setMotifAnnulation($motif);
            $etat = $etatRepository->findOneBy(
                ['libelle' => 'Annulee']
            );
            $sortie->setEtat($etat);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('sortie_index');
        }

        return $this->render('sortie/annuler.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    /**
     * @Route("/register/{id}", name="sortie_register", methods={"GET"})
     */
    public function register(Sortie $sortie): Response
    {
        $participant = $this->getUser();
        if ($participant->getId() != $sortie->getOrganisateur()->getId()) {
            $sortie->addParticipant($participant);
            $participant->addEstInscrit($sortie);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sortie);
            $entityManager->persist($participant);
            $entityManager->flush();
        }
        return $this->redirectToRoute('sortie_index');
    }

    /**
     * @Route("/unsubscribe/{id}", name="sortie_unsubscribe", methods={"GET"})
     */
    public function unsubscribe(Sortie $sortie): Response
    {
        $participant = $this->getUser();
        if ($participant->getId() != $sortie->getOrganisateur()->getId() && $participant->testEstInscrit($sortie)) {
            $sortie->removeParticipant($participant);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sortie);
            $entityManager->flush();
        }
        return $this->redirectToRoute('sortie_index');
    }

    /**
     * @Route("/{id}/edit", name="sortie_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Sortie $sortie): Response
    {
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sortie/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="sortie_delete", methods={"POST"})
     */
    public function delete(Request $request, Sortie $sortie): Response
    {
        if ($this->isCsrfTokenValid('delete' . $sortie->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($sortie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/api/villes/{id}", name="api_villes" ,methods={"GET"})
     */
    public function api_villes(Ville $ville, LieuRepository $repo): Response
    {
        $lieux = $repo->findBy(
            ['ville' => $ville->getId()]
        );

        foreach($lieux as $lieu){
            $lieu->removeAllSorties();
        }

        return $this->json($lieux);
    }

    /**
     * @Route("/api/lieux/{id}", name="api_lieux" ,methods={"GET"})
     */
    public function api_lieux(Lieu $lieu, SortieRepository $repo): Response
    {
        $unLieu = $repo->findOneBy(
            ['lieu' => $lieu->getId()]
        );

        $lieu->removeAllSorties();

        return $this->json($lieu);
    }
}
