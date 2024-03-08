<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\{CustomerRepository, PlatformRepository};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\{UrlGenerator, UrlGeneratorInterface};
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerController extends AbstractController
{
    #[Route('/api/customers', name: 'customer', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour consulter les clients.')]
    public function getAllCustomers(CustomerRepository $customerRepository, SerializerInterface $serializer): JsonResponse
    {
        $customerList = $customerRepository->findAll();
        $jsonCustomerList = $serializer->serialize($customerList, 'json', ['groups' =>'getCustomers']);

        return new JsonResponse($jsonCustomerList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/customers/{id}', name: 'detailCustomer', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour consulter ce client.')]
    public function getDetailCustomer(Customer $customer, SerializerInterface $serializer): JsonResponse
    {
        $jsonCustomer = $serializer->serialize($customer, 'json', ['groups' =>'getCustomers']);
        return new JsonResponse($jsonCustomer, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/customers/{id}', name: 'deleteCustomer', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer ce client.')]
    public function deleteCustomer(Customer $customer, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($customer);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('api/customers', name:'createCustomer', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour ajouter un client.')]
    public function createCustomer(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, PlatformRepository $platformRepository, ValidatorInterface $validator): JsonResponse
    {
        $customer = $serializer->deserialize($request->getContent(), Customer::class, 'json');

        $errors = $validator->validate($customer);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();

        $idPlatform = $content['idPlatform'] ?? -1;
        $customer->setPlatform($platformRepository->find($idPlatform));

        $em->persist($customer);
        $em->flush();

        $jsonCustomer = $serializer->serialize($customer, 'json', ['groups' => 'getCustomers']);

        $location = $urlGenerator->generate('detailCustomer', ['id' => $customer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonCustomer, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('api/customers/{id}', name:'updateCustomer', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier ce client.')]
    public function updateCustomer(Customer $currentCustomer, Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, PlatformRepository $platformRepository, ValidatorInterface $validator): JsonResponse
    {
        $updatedCustomer = $serializer->deserialize($request->getCOntent(), Customer::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentCustomer]);

        $errors = $validator->validate($updatedCustomer);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        
        $updatedCustomer->setUpdatedAt(new \DateTimeImmutable());
        
        $content = $request->toArray();
        $idPlatform = $content['idPlatform'] ?? -1;
        $updatedCustomer->setPlatform($platformRepository->find($idPlatform));

        $em->flush();

        $jsonCustomer = $serializer->serialize($updatedCustomer, 'json', ['groups' => 'getCustomers']);

        $location = $urlGenerator->generate('detailCustomer', ['id' => $updatedCustomer->getId()], UrlGenerator::ABSOLUTE_URL);

        return new JsonResponse($jsonCustomer, Response::HTTP_OK, ['Location' => $location], true);
    }
}
