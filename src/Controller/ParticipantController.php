<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Site;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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
    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder, SiteRepository $siteRepository): Response
    {
        $user = new Participant();
        $form = $this->createForm(ParticipantType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('participant_index', [], Response::HTTP_SEE_OTHER);
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
    public function edit(Request $request, Participant $participant, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $form = $this->createForm(ParticipantType::class, $participant);
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
                            'form' => $form,
                        ]);
                    }
                }
                

                $this->getDoctrine()->getManager()->flush();
                return $this->renderForm('participant/edit.html.twig', [
                    'success_message' => "Profil mis à jour",
                    'error_message' => null,
                    'participant' => $participant,
                    'form' => $form,
                ]);
            }
            else{
                return $this->renderForm('participant/edit.html.twig', [
                    'success_message' => null,
                    'error_message' => 'Mot de passe incorrect',
                    'participant' => $participant,
                    'form' => $form,
                ]);
            }
        }

        if($participant->getId() == $this->getUser()->getId()){
            return $this->renderForm('participant/edit.html.twig', [
                'success_message' => null,
                'error_message' => null,
                'participant' => $participant,
                'form' => $form,
            ]);
        }
        else{
            return $this->redirectToRoute('participant_index', [], Response::HTTP_SEE_OTHER);
        }
    }

    /**
     * @Route("/{id}", name="participant_delete", methods={"POST"})
     */
    public function delete(Request $request, Participant $participant): Response
    {
        if ($this->isCsrfTokenValid('delete' . $participant->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($participant);
            $entityManager->flush();
        }

        return $this->redirectToRoute('participant_index', [], Response::HTTP_SEE_OTHER);
    }
}
