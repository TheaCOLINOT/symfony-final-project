<?php

namespace App\Controller;

use App\Entity\Cat;
use App\Entity\Service;
use App\Entity\User;
use App\Form\CatServiceSelectionType;
use App\Form\CatType;
use App\Repository\CatRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CAT')]
final class CatController extends AbstractController
{
    #[Route('/espace-cat', name: 'app_cat')]
    public function index(
        CatRepository $catRepository,
        ServiceRepository $serviceRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $cat = $catRepository->findOneByUser($user);
        $profileForm = null;
        $serviceForm = null;

        if ($cat === null) {
            $profileForm = $this->createForm(CatType::class, new Cat(), [
                'action' => $this->generateUrl('app_cat_profile'),
                'method' => 'POST',
            ])->createView();
        } elseif ($cat->hasLocations()) {
            $serviceForm = $this->createForm(CatServiceSelectionType::class, [
                'services' => $cat->getServices()->toArray(),
            ], [
                'action' => $this->generateUrl('app_cat_services'),
                'method' => 'POST',
                'services' => $serviceRepository->findGlobalServices(),
            ])->createView();
        }

        return $this->render('cat/index.html.twig', [
            'user' => $user,
            'cat' => $cat,
            'profile_form' => $profileForm,
            'available_services' => $serviceRepository->findGlobalServices(),
            'service_form' => $serviceForm,
        ]);
    }

    #[Route('/espace-cat/profil', name: 'app_cat_profile', methods: ['POST'])]
    public function createProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        CatRepository $catRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($catRepository->findOneByUser($user) !== null) {
            $this->addFlash('error', 'Votre profil chat existe déjà.');

            return $this->redirectToRoute('app_cat');
        }

        $cat = new Cat();
        $cat->setUser($user);
        $form = $this->createForm(CatType::class, $cat);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Impossible de créer votre profil chat.');

            return $this->redirectToRoute('app_cat');
        }

        $entityManager->persist($cat);
        $entityManager->flush();

        $this->addFlash('success', 'Votre profil masseur chat a été créé. Un manager de salon pourra vous recruter.');

        return $this->redirectToRoute('app_cat');
    }

    #[Route('/espace-cat/services', name: 'app_cat_services', methods: ['POST'])]
    public function updateServices(
        Request $request,
        EntityManagerInterface $entityManager,
        CatRepository $catRepository,
        ServiceRepository $serviceRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $cat = $catRepository->findOneByUser($user);
        if ($cat === null || !$cat->hasLocations()) {
            $this->addFlash('error', 'Vous devez être rattaché à au moins un salon avant de choisir vos services.');

            return $this->redirectToRoute('app_cat');
        }

        $form = $this->createForm(CatServiceSelectionType::class, [
            'services' => $cat->getServices()->toArray(),
        ], [
            'services' => $serviceRepository->findGlobalServices(),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Impossible de mettre à jour vos services.');

            return $this->redirectToRoute('app_cat');
        }

        foreach ($cat->getServices()->toArray() as $service) {
            $cat->removeService($service);
        }

        /** @var list<Service> $selectedServices */
        $selectedServices = $form->get('services')->getData();

        foreach ($selectedServices as $service) {
            $cat->addService($service);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Vos services proposés ont été mis à jour.');

        return $this->redirectToRoute('app_cat');
    }
}
