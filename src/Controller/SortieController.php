<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use App\Repository\VilleRepository;
use App\Repository\LieuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @Route("/sortie")
 */
class SortieController extends AbstractController
{
    /**
     * @Route("/", name="sortie_index", methods={"GET","POST"})
     */
    public function index(Request $request, SortieRepository $sortieRepository, EtatRepository $etatRepository, SiteRepository $sitesRepository): Response
    {
        $msg = $request->query->get('msg');
        $sorties = $sortieRepository->findAll();
        $sites = $sitesRepository->findAll();

        /* Test cloture */
        foreach ($sorties as $s) {
            if ($s->getDateDebut() == new \DateTime() &&
                $s->getEtat()->getLibelle() == "Ouvert") {

                $etat = $etatRepository->findOneBy(
                    ['libelle' => "En cours"]
                );
                $s->setEtat($etat);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($s);
                $entityManager->flush();
            }
            $duree = $s->getDuree();
            if ($duree == null) {
                $duree = 1;
            }
            if ($s->getDateCloture()->add(new \DateInterval('P' . $duree . 'D')) < new \DateTime() &&
                $s->getEtat()->getLibelle() != "Annulee") {

                $etat = $etatRepository->findOneBy(
                    ['libelle' => "Terminee"]
                );
                $s->setEtat($etat);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($s);
                $entityManager->flush();
            }
        }

        // On affiche seulement les sorties Ouverte ou en cours
        $etatOuvert = $etatRepository->findOneBy(['libelle' => "Ouvert"]);
        $etatEnCours = $etatRepository->findOneBy(['libelle' => "En cours"]);
        $sorties = $sortieRepository->findActual($etatOuvert->getId(), $etatEnCours->getId());

        // Filtre de recherche
        $formSubmit = $request->request->get('_submit');
        if($formSubmit){

            $user = $this->getUser();

            $site = $request->request->get('_site');
            $search = $request->request->get('_search');
            $dateDebut = $request->request->get('_dateDebut');
            $dateFin = $request->request->get('_dateFin');

            $sorties = $sortieRepository->findByFilters(
                $site,
                $search,
                $dateDebut,
                $dateFin
            );

            // Sorties dont je suis l'organisateur
            $check1 = $request->request->get('_check1');
            if($check1 != null){ $check1 = true; }else{ $check1 = false; }
            // Sorties auxquelles je suis inscrit
            $check2 = $request->request->get('_check2');
            if($check2 != null){ $check2 = true; }else{ $check2 = false; }
            // Sorties auxquelles je ne suis pas inscrit
            $check3 = $request->request->get('_check3');
            if($check3 != null){ $check3 = true; }else{ $check3 = false; }
            // Sorties passées
            $check4 = $request->request->get('_check4');
            if($check4 != null){ $check4 = true; }else{ $check4 = false; }
            $etatTerminee = $etatRepository->findOneBy(['libelle' => "Terminee"]);

            if($dateDebut == ""){
                $dateDebut = new \DateTime('1970-01-01');
                $dateDebut = $dateDebut->format('Y-m-d');
            }
            if($dateFin == ""){
                $dateFin = new \DateTime('2100-01-01');
                $dateFin = $dateFin->format('Y-m-d');
            }

            // Application des filtres
            foreach($sorties as $key => $s){
                if($check1){
                    if($s->getOrganisateur()->getId() != $user->getId()){
                        unset($sorties[$key]);
                    }
                }
                if($check2 && !$check3){
                    if(!$s->testParticipant($user)){
                        unset($sorties[$key]);
                    }
                }
                if($check3 && !$check2){
                    if($s->testParticipant($user)){
                        unset($sorties[$key]);
                    }
                }
                if($check4){
                    if($s->getEtat()->getId() != $etatTerminee->getId()){
                        unset($sorties[$key]);
                    }
                }
            }
        }
        else{
            $site = "";
            $search = "";
            $dateDebut = "";
            $dateFin = "";
            $check1 = false;
            $check2 = false;
            $check3 = false;
            $check4 = false;
        }

        if(count($sorties) == 0){
            $warning = "Aucune sortie ne correspond aux filtres";
        }
        else{
            $warning = null;
        }

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
            'sites' => $sites,
            'siteValue' => $site,
            'searchValue' => $search,
            'dateDebutValue' => $dateDebut,
            'dateFinValue' => $dateFin,
            'check1' => $check1,
            'check2' => $check2,
            'check3' => $check3,
            'check4' => $check4,
            'success_message' => $msg,
            'warning_message' => $warning
        ]);
    }

    /**
     * @Route("/new", name="sortie_new", methods={"GET","POST"})
     *
     * Permet de créer une sortie avec l'état "Ouvert" (et non modifiable)
     */
    public function new(VilleRepository $villeRepository, EtatRepository $etatRepository, LieuRepository $lieuRepository, Request $request): Response
    {
        $sortie = new Sortie();
        $participant = $this->getUser();

        $sortie->setOrganisateur($participant);

        $etat = $etatRepository->findOneBy(
            ['libelle' => "Ouvert"]
        );
        $sortie->setEtat($etat);

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Récupération du lieu

            // Test si c'est un nouveau lieu
            $newLieu = $request->request->get('_nomLieu');
            if($newLieu){
                $lieu = new Lieu();

                // Get Datas
                $nom = $request->request->get('_nomLieu');
                $rue = $request->request->get('_rueLieu');
                $longitude = $request->request->get('_longitudeLieu');
                $latitude = $request->request->get('_latitudeLieu');
                
                $lieu->setNom($nom);
                $lieu->setRue($rue);

                if(strlen($longitude) > 0 && strlen($latitude) > 0){
                    $lieu->setLongitude(floatval($longitude));
                    $lieu->setLatitude(floatval($latitude));
                }

                // Ville
                $villeId = $request->request->get('_villes');
                $ville = $villeRepository->findOneBy(
                    ['id' => $villeId]
                );
                $lieu->setVille($ville);

                // Push
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($lieu);
                $entityManager->flush();

                $sortie->setLieu($lieu);
            }
            else{
                $lieuId = $request->request->get('_lieux');
                $lieu = $lieuRepository->findOneBy(
                    ['id' => $lieuId]
                );
                $sortie->setLieu($lieu);
            }

            //Champs du formulaire à tester
            $dateSortie = $form["dateDebut"]->getData();
            $dateLimiteInscription = $form["dateCloture"]->getData();
            $duree = $form["duree"]->getData();
            $nbInscriptionsMax = $form["nbInscriptionsMax"]->getData();

            //Validation de la durée de la sortie
            if ($duree < 1 && ($duree != null || $duree == 0)) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "La durée de la sortie ne peut être inférieur à 1 jour",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Validation du nombre d'inscriptions max de la sortie
            if ($nbInscriptionsMax < 2) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "Le nombre d'inscriptions maximum ne peut être inférieur à 2",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Validation de la date de sortie
            if ($dateSortie < new \DateTime()) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "La date de la sortie ne doit pas être antérieur à aujourd'hui",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Validation de la date limite d'inscription
            if ($dateLimiteInscription < new \DateTime()) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "La date limite d'inscription ne doit pas être antérieur à aujourd'hui",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Contrôle cohérence des dates
            if ($dateLimiteInscription > $dateSortie) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "La date de limite d'inscription doit être antérieur à la date de la sortie",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            // Mise en base de la sortie
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('sortie_index');
        }

        return $this->renderForm('sortie/new.html.twig', [
            'error_message' => null,
            'villes' => $villeRepository->findAll(),
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/save", name="sortie_save", methods={"GET","POST"})
     *
     * Permet de créer une sortie avec l'état "En création" (et modifiable)
     */
    public function save(VilleRepository $villeRepository, EtatRepository $etatRepository, LieuRepository $lieuRepository, Request $request): Response
    {
        $sortie = new Sortie();
        $participant = $this->getUser();

        $sortie->setOrganisateur($participant);

        $etat = $etatRepository->findOneBy(
            ['libelle' => "En creation"]
        );
        $sortie->setEtat($etat);

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Récupération du lieu

            // Test si c'est un nouveau lieu
            $newLieu = $request->request->get('_nomLieu');
            if($newLieu){
                $lieu = new Lieu();

                // Get Datas
                $nom = $request->request->get('_nomLieu');
                $rue = $request->request->get('_rueLieu');
                $longitude = $request->request->get('_longitudeLieu');
                $latitude = $request->request->get('_latitudeLieu');
                
                $lieu->setNom($nom);
                $lieu->setRue($rue);

                if(strlen($longitude) > 0 && strlen($latitude) > 0){
                    $lieu->setLongitude(floatval($longitude));
                    $lieu->setLatitude(floatval($latitude));
                }

                // Ville
                $villeId = $request->request->get('_villes');
                $ville = $villeRepository->findOneBy(
                    ['id' => $villeId]
                );
                $lieu->setVille($ville);

                // Push
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($lieu);
                $entityManager->flush();

                $sortie->setLieu($lieu);
            }
            else{
                $lieuId = $request->request->get('_lieux');
                $lieu = $lieuRepository->findOneBy(
                    ['id' => $lieuId]
                );
                $sortie->setLieu($lieu);
            }

            //Champs du formulaire à tester
            $dateSortie = $form["dateDebut"]->getData();
            $dateLimiteInscription = $form["dateCloture"]->getData();
            $duree = $form["duree"]->getData();
            $nbInscriptionsMax = $form["nbInscriptionsMax"]->getData();

            //Validation de la durée de la sortie
            if ($duree < 1 && ($duree != null || $duree == 0)) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "La durée de la sortie ne peut être inférieur à 1 jour",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Validation du nombre d'inscriptions max de la sortie
            if ($nbInscriptionsMax < 2) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "Le nombre d'inscriptions maximum ne peut être inférieur à 2",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Validation de la date de sortie
            if ($dateSortie < new \DateTime()) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "La date de la sortie ne doit pas être antérieur à aujourd'hui",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Validation de la date limite d'inscription
            if ($dateLimiteInscription < new \DateTime()) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "La date limite d'inscription ne doit pas être antérieur à aujourd'hui",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Contrôle cohérence des dates
            if ($dateLimiteInscription > $dateSortie) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "La date de limite d'inscription doit être antérieur à la date de la sortie",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            // Mise en base de la sortie
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('sortie_index');
        }

        return $this->renderForm('sortie/new.html.twig', [
            'error_message' => null,
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
        if ($motif != null) {

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

        if ($this->getUser()->getId() == $sortie->getOrganisateur()->getId() &&
            $sortie->getEtat()->getLibelle() == "Ouvert") {

            return $this->render('sortie/annuler.html.twig', [
                'sortie' => $sortie,
            ]);
        } else {
            return $this->redirectToRoute('sortie_index');
        }
    }

    /**
     * @Route("/register/{id}", name="sortie_register", methods={"GET"})
     */
    public function register(Sortie $sortie, Request $request): Response
    {
        $participant = $this->getUser();
        if ($participant->getId() != $sortie->getOrganisateur()->getId() &&
            $sortie->getEtat()->getLibelle() == "Ouvert" &&
            $sortie->getNbInscriptionsMax() > count($sortie->getParticipants())) {

            $sortie->addParticipant($participant);
            $participant->addEstInscrit($sortie);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sortie);
            $entityManager->persist($participant);
            $entityManager->flush();
        }
        return new RedirectResponse($this->generateUrl('sortie_index') . "?msg=Inscription%20réussie");
    }

    /**
     * @Route("/unsubscribe/{id}", name="sortie_unsubscribe", methods={"GET"})
     */
    public function unsubscribe(Sortie $sortie): Response
    {
        $participant = $this->getUser();
        if ($participant->getId() != $sortie->getOrganisateur()->getId() &&
            $participant->testEstInscrit($sortie) &&
            $sortie->getEtat()->getLibelle() == "Ouvert") {

            $sortie->removeParticipant($participant);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sortie);
            $entityManager->flush();
        }
        return new RedirectResponse($this->generateUrl('sortie_index') . "?msg=Désnscription%20réussie");
    }

    /**
     * @Route("/{id}/edit", name="sortie_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Sortie $sortie, VilleRepository $villeRepository, LieuRepository $lieuRepository): Response
    {
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Récupération du lieu

            // Test si c'est un nouveau lieu
            $newLieu = $request->request->get('_nomLieu');
            if($newLieu){
                $lieu = new Lieu();

                // Get Datas
                $nom = $request->request->get('_nomLieu');
                $rue = $request->request->get('_rueLieu');
                $longitude = $request->request->get('_longitudeLieu');
                $latitude = $request->request->get('_latitudeLieu');
                
                $lieu->setNom($nom);
                $lieu->setRue($rue);

                if(strlen($longitude) > 0 && strlen($latitude) > 0){
                    $lieu->setLongitude(floatval($longitude));
                    $lieu->setLatitude(floatval($latitude));
                }

                // Ville
                $villeId = $request->request->get('_villes');
                $ville = $villeRepository->findOneBy(
                    ['id' => $villeId]
                );
                $lieu->setVille($ville);

                // Push
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($lieu);
                $entityManager->flush();

                $sortie->setLieu($lieu);
            }
            else{
                $lieuId = $request->request->get('_lieux');
                $lieu = $lieuRepository->findOneBy(
                    ['id' => $lieuId]
                );
                $sortie->setLieu($lieu);
            }

            //Champs du formulaire à tester
            $dateSortie = $form["dateDebut"]->getData();
            $dateLimiteInscription = $form["dateCloture"]->getData();
            $duree = $form["duree"]->getData();
            $nbInscriptionsMax = $form["nbInscriptionsMax"]->getData();

            //Validation de la durée de la sortie
            if ($duree < 1 && ($duree != null || $duree == 0)) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "La durée de la sortie ne peut être inférieur à 1 jour",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Validation du nombre d'inscriptions max de la sortie
            if ($nbInscriptionsMax < 2) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "Le nombre d'inscriptions maximum ne peut être inférieur à 2",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Validation de la date de sortie
            if ($dateSortie < new \DateTime()) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "La date de la sortie ne doit pas être antérieur à aujourd'hui",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Validation de la date limite d'inscription
            if ($dateLimiteInscription < new \DateTime()) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "La date limite d'inscription ne doit pas être antérieur à aujourd'hui",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Contrôle cohérence des dates
            if ($dateLimiteInscription > $dateSortie) {
                return $this->renderForm('sortie/new.html.twig', [
                    'error_message' => "La date de limite d'inscription doit être antérieur à la date de la sortie",
                    'villes' => $villeRepository->findAll(),
                    'sortie' => $sortie,
                    'form' => $form,
                ]);
            }

            //Update de la sortie
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('sortie_index');

        }

        return $this->renderForm('sortie/edit.html.twig', [
            'error_message' => null,
            'villes' => $villeRepository->findAll(),
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

        foreach ($lieux as $lieu) {
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
