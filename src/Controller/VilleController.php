<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/ville")
 */
class VilleController extends AbstractController
{
    /**
     * @Route("/", name="ville_index", methods={"GET", "POST"})
     */
    public function index(Request $request, VilleRepository $villeRepository): Response
    {
        $error_message = null;

        // ADD
        $formSubmit = $request->request->get('_submit');
        if($formSubmit){
            
            $nom = $request->request->get('_nom');
            $cp = $request->request->get('_cp');

            if(strlen($nom) > 0 && strlen($cp) > 0 && ctype_digit($cp)){
                $ville = new Ville();
                $ville->setNom($nom);
                $ville->setCodePostal($cp);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($ville);
                $entityManager->flush();
            }
            else{
                $error_message = "Erreur de saisie";
            }
        }

        // EDIT
        $formSave = $request->request->get('_save');
        if($formSave){

            $id = $request->request->get('_idVille');
            $nom = $request->request->get('_nom');
            $cp = $request->request->get('_cp');

            if(strlen($nom) > 0 && strlen($cp) > 0 && ctype_digit($cp)){
                $ville = $villeRepository->findOneBy(
                    ['id' => $id]
                );

                $ville->setNom($nom);
                $ville->setCodePostal($cp);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($ville);
                $entityManager->flush();
            }
            else{
                $error_message = "Erreur de saisie";
            }
        }

        return $this->render('ville/index.html.twig', [
            'villes' => $villeRepository->findAll(),
            'error_message' => $error_message
        ]);
    }

    /**
     * @Route("/delete/{id}", name="ville_delete", methods={"GET"})
     */
    public function delete(Request $request, Ville $ville): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($ville);
        $entityManager->flush();

        return $this->redirectToRoute('ville_index', [], Response::HTTP_SEE_OTHER);
    }
}
