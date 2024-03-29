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
use Symfony\Contracts\Cache\{ItemInterface, TagAwareCacheInterface};

class CustomerController extends AbstractController
{
    #[Route('/api/customers', name: 'customer', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Vous n\'avez pas les droits suffisants pour consulter les clients.')]
    public function getAllCustomers(CustomerRepository $customerRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        $platform = $this->getUser();

        $idCache = 'getPlatform' . $platform->getId() . 'Customers-' . $page . '-' . $limit;

        $jsonCustomerList = $cache->get($idCache, function (ItemInterface $item) use ($customerRepository, $page, $limit, $serializer) {
            $item->tag('customersCache');
            $customerList = $customerRepository->findCustomersWithPagination($this->getUser(), $page, $limit);
            return $serializer->serialize($customerList, 'json', ['groups' =>'getCustomers']);
        });

        return new JsonResponse($jsonCustomerList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/customers/{id}', name: 'detailCustomer', methods: ['GET'])]
    #[IsGranted('view', 'customer', 'Client.e non trouvé.e', 404)]
    public function getDetailCustomer(Customer $customer, SerializerInterface $serializer): JsonResponse
    {
        $jsonCustomer = $serializer->serialize($customer, 'json', ['groups' =>'getCustomers']);
        return new JsonResponse($jsonCustomer, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/customers/{id}', name: 'deleteCustomer', methods: ['DELETE'])]
    #[IsGranted('access', 'customer', 'Client.e non trouvé.e', 404)]
    public function deleteCustomer(Customer $customer, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(['customersCache']);

        $em->remove($customer);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('api/customers', name:'createCustomer', methods: ['POST'])]
    #[IsGranted('access', 'customer', 'Client.e non trouvé.e', 404)]
    public function createCustomer(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(['customersCache']);

        $customer = $serializer->deserialize($request->getContent(), Customer::class, 'json');

        $errors = $validator->validate($customer);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $customer->setPlatform($this->getUser());

        $em->persist($customer);
        $em->flush();

        $jsonCustomer = $serializer->serialize($customer, 'json', ['groups' => 'getCustomers']);

        $location = $urlGenerator->generate('detailCustomer', ['id' => $customer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonCustomer, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('api/customers/{id}', name:'updateCustomer', methods: ['PUT'])]
    #[IsGranted('access', 'customer', 'Client.e non trouvé.e', 404)]
    public function updateCustomer(Customer $currentCustomer, Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, PlatformRepository $platformRepository, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(['customersCache']);

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
