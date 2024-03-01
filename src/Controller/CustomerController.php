<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Repository\PlatformRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerController extends AbstractController
{
    #[Route('/api/customers', name: 'customer', methods: ['GET'])]
    public function getAllCustomers(CustomerRepository $customerRepository, SerializerInterface $serializer): JsonResponse
    {
        $customerList = $customerRepository->findAll();
        $jsonCustomerList = $serializer->serialize($customerList, 'json', ['groups' =>'getCustomers']);

        return new JsonResponse($jsonCustomerList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/customers/{id}', name: 'detailCustomer', methods: ['GET'])]
    public function getDetailCustomer(Customer $customer, SerializerInterface $serializer): JsonResponse
    {
        $jsonCustomer = $serializer->serialize($customer, 'json', ['groups' =>'getCustomers']);
        return new JsonResponse($jsonCustomer, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/customers/{id}', name: 'deleteCustomer', methods: ['DELETE'])]
    public function deleteCustomer(Customer $customer, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($customer);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('api/customers', name:'createCustomer', methods: ['POST'])]
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
    public function updateCustomer(Customer $currentCustomer, Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, PlatformRepository $platformRepository): JsonResponse
    {
        $updatedCustomer = $serializer->deserialize($request->getCOntent(), Customer::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentCustomer]);
        
        $content = $request->toArray();
        
        $updatedCustomer->setUpdatedAt(new \DateTimeImmutable());
        $idPlatform = $content['idPlatform'] ?? -1;
        $updatedCustomer->setPlatform($platformRepository->find($idPlatform));

        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
