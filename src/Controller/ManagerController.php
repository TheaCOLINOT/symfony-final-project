<?php

namespace App\Controller;

use App\Entity\Cat;
use App\Entity\Service;
use App\Entity\User;
use App\Form\CatServiceSelectionType;
use App\Form\CatType;
use App\Form\ServiceType;
use App\Repository\CatRepository;
use App\Repository\ServiceRepository;
use App\Service\ManagerContextService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MANAGER')]
final class ManagerController extends AbstractController
{
    #[Route('/manager', name: 'app_manager')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ManagerContextService $managerContext,
        ServiceRepository $serviceRepository,
        CatRepository $catRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $managedLocation = $managerContext->getManagedLocation($user);
        $isGlobalAdmin = $managerContext->isGlobalAdmin($user);

        if ($managedLocation === null) {
            return $this->render('manager/index.html.twig', [
                'user' => $user,
                'managed_location' => null,
                'is_global_admin' => false,
                'no_location' => true,
            ]);
        }

        $service = new Service();
        $service->setIsGlobal(true);
        $serviceForm = $this->createForm(ServiceType::class, $service, [
            'action' => $this->generateUrl('app_manager_service_create'),
        ]);

        $cat = new Cat();
        $catForm = $this->createForm(CatType::class, $cat, [
            'action' => $this->generateUrl('app_manager_cat_create'),
        ]);

        $cats = $isGlobalAdmin ? [] : $catRepository->findByLocation($managedLocation);
        $serviceForms = [];

        foreach ($cats as $existingCat) {
            $serviceForms[$existingCat->getId()] = $this->createForm(CatServiceSelectionType::class, [
                'services' => $existingCat->getServices()->toArray(),
            ], [
                'action' => $this->generateUrl('app_manager_cat_services', ['id' => $existingCat->getId()]),
                'method' => 'POST',
                'services' => $serviceRepository->findGlobalServices(),
            ])->createView();
        }

        return $this->render('manager/index.html.twig', [
            'user' => $user,
            'managed_location' => $managedLocation,
            'is_global_admin' => $isGlobalAdmin,
            'no_location' => false,
            'service_form' => $serviceForm->createView(),
            'cat_form' => $isGlobalAdmin ? null : $catForm->createView(),
            'services' => $serviceRepository->findGlobalServices(),
            'cats' => $cats,
            'service_forms' => $serviceForms,
        ]);
    }

    #[Route('/manager/services/create', name: 'app_manager_service_create', methods: ['POST'])]
    public function createService(
        Request $request,
        EntityManagerInterface $entityManager,
        ManagerContextService $managerContext,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($managerContext->getManagedLocation($user) === null) {
            throw $this->createAccessDeniedException();
        }

        $service = new Service();
        $service->setIsGlobal(true);
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Impossible de créer ce service.');

            return $this->redirectToRoute('app_manager');
        }

        $entityManager->persist($service);
        $entityManager->flush();

        $this->addFlash('success', 'Le service global a bien été créé.');

        return $this->redirectToRoute('app_manager');
    }

    #[Route('/manager/cats/create', name: 'app_manager_cat_create', methods: ['POST'])]
    public function createCat(
        Request $request,
        EntityManagerInterface $entityManager,
        ManagerContextService $managerContext,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $managedLocation = $managerContext->getManagedLocation($user);
        if ($managedLocation === null || $managedLocation->isGlobal()) {
            $this->addFlash('error', 'Seuls les managers de salon peuvent créer des chats.');

            return $this->redirectToRoute('app_manager');
        }

        $cat = new Cat();
        $form = $this->createForm(CatType::class, $cat);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Impossible de créer ce chat.');

            return $this->redirectToRoute('app_manager');
        }

        $cat->setLocation($managedLocation);
        $entityManager->persist($cat);
        $entityManager->flush();

        $this->addFlash('success', 'Le chat a bien été ajouté à votre salon.');

        return $this->redirectToRoute('app_manager');
    }

    #[Route('/manager/cats/{id}/services', name: 'app_manager_cat_services', methods: ['POST'])]
    public function updateCatServices(
        Cat $cat,
        Request $request,
        EntityManagerInterface $entityManager,
        ManagerContextService $managerContext,
        ServiceRepository $serviceRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($cat->getLocation() === null || !$managerContext->canManageLocation($user, $cat->getLocation())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CatServiceSelectionType::class, [
            'services' => $cat->getServices()->toArray(),
        ], [
            'services' => $serviceRepository->findGlobalServices(),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Impossible de mettre à jour les services de ce chat.');

            return $this->redirectToRoute('app_manager');
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

        $this->addFlash('success', 'Les services proposés par ce chat ont été mis à jour.');

        return $this->redirectToRoute('app_manager');
    }
}
