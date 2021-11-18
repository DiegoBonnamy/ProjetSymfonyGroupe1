<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Site;
use App\Form\EditParticipantType;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/participant")
 */
class ParticipantController extends AbstractController
{
    /**
     * @Route("/", name="participant_index", methods={"GET"})
     */
    public function index(ParticipantRepository $participantRepository): Response
    {
        return $this->render('participant/index.html.twig', [
            'participants' => $participantRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="participant_new", methods={"GET","POST"})
     */
    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder, SiteRepository $siteRepository, SluggerInterface $slugger): Response
    {
        $user = new Participant();
        $form = $this->createForm(ParticipantType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            // Récupération du site
            $siteId = $request->get('_sites');
            $site = $siteRepository->findOneBy(
                ['id' => $siteId]
            );
            $user->setSite($site);

            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $photo = $form->get('photo')->getData();
            if ($photo) {
                $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photo->guessExtension();

                try {
                    $photo->move(
                        $this->getParameter('photo_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                   
                }
                $user->setPhoto($newFilename);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('participant/new.html.twig', [
            'sites' => $siteRepository->findAll(),
            'participant' => $user,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="participant_show", methods={"GET"})
     */
    public function show(Participant $participant): Response
    {
        return $this->render('participant/show.html.twig', [
            'participant' => $participant,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="participant_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Participant $participant, UserPasswordEncoderInterface $passwordEncoder, SluggerInterface $slugger, SiteRepository $sitesRepository): Response
    {
        $sites = $sitesRepository->findAll();
        $form = $this->createForm(EditParticipantType::class, $participant, array('role' => $this->getUser()->getRoles()));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Vérification du mot de passe
            $oldPassword = $request->request->get('_oldPassword');
            $newPassword = $form["plainPassword"]->getData();
            $confirm = $request->request->get('_confirm');

            if(password_verify($oldPassword,$participant->getPassword())){
               
                if($newPassword != null){
                    if($newPassword == $confirm){
                        $participant->setPassword(
                            $passwordEncoder->encodePassword(
                                $participant,
                                $newPassword
                            )
                        );
                    }
                    else{
                        dump($newPassword);
                        return $this->renderForm('participant/edit.html.twig', [
                            'success_message' => null,
                            'error_message' => 'Les mots de passe ne correspondent pas',
                            'participant' => $participant,
                            'sites' => $sites,
                            'form' => $form,
                        ]);
                    }
                }

                $photo = $form->get('photo')->getData();
                if ($photo) {
                    $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$photo->guessExtension();

                    try {
                        $photo->move(
                            $this->getParameter('photo_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                    
                    }
                    $participant->setPhoto($newFilename);
                }
                

                $this->getDoctrine()->getManager()->flush();
                return $this->renderForm('participant/edit.html.twig', [
                    'success_message' => "Profil mis à jour",
                    'error_message' => null,
                    'participant' => $participant,
                    'sites' => $sites,
                    'form' => $form,
                ]);
            }
            else{
                return $this->renderForm('participant/edit.html.twig', [
                    'success_message' => null,
                    'error_message' => 'Mot de passe incorrect',
                    'participant' => $participant,
                    'sites' => $sites,
                    'form' => $form,
                ]);
            }
        }

        if($participant->getId() == $this->getUser()->getId() || $this->isGranted('ROLE_ADMIN')){
            return $this->renderForm('participant/edit.html.twig', [
                'success_message' => null,
                'error_message' => null,
                'participant' => $participant,
                'sites' => $sites,
                'form' => $form,
            ]);
        }
        else{
            return $this->redirectToRoute('participant_index', [], Response::HTTP_SEE_OTHER);
        }
    }

    /**
     * @Route("/{id}/admin/edit", name="admin_participant_edit", methods={"GET","POST"})
     */
    public function adminEdit(Request $request, SiteRepository $sitesRepository, Participant $participant): Response
    {
        $sites = $sitesRepository->findAll();
        $form = $this->createForm(EditParticipantType::class, $participant, array('role' => $this->getUser()->getRoles()));
        $form->handleRequest($request);

        return $this->renderForm('participant/edit.html.twig', [
            'success_message' => null,
            'error_message' => '',
            'participant' => $participant,
            'sites' => $sites,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/delete/{id}", name="participant_delete", methods={"GET"})
     */
    public function delete(Request $request, Participant $participant, EtatRepository $etatRepository, SortieRepository $sortieRepository): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        // Suppression de ses inscriptions
        foreach($participant->getEstInscrit() as $sortie){
            $sortie->removeParticipant($participant);
        }

        // Suppression de ses sorties en tant qu'organisateur
        $sorties = $sortieRepository->findAll();
        foreach($sorties as $sortie){
            if($sortie->getOrganisateur()->getId() == $participant->getId()){
                $sortie->removeAllParticipant();
                $entityManager->remove($sortie);
            }
        }

        // Suppression du participant
        $entityManager->remove($participant);

        $entityManager->flush();

        return $this->redirectToRoute('participant_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/desactive/{id}", name="participant_desactive", methods={"GET"})
     */
    public function desactive(Request $request, Participant $participant): Response
    {
        $participant->setActif(false);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($participant);
        $entityManager->flush();

        return $this->redirectToRoute('participant_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/active/{id}", name="participant_active", methods={"GET"})
     */
    public function active(Request $request, Participant $participant): Response
    {
        $participant->setActif(true);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($participant);
        $entityManager->flush();

        return $this->redirectToRoute('participant_index', [], Response::HTTP_SEE_OTHER);
    }
}
