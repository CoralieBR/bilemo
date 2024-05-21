<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\{CustomerRepository, PlatformRepository};
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
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
    
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private TagAwareCacheInterface $cache,
        private UrlGeneratorInterface $router,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/customers', name: 'customer_show_all', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Vous n\'avez pas les droits suffisants pour consulter les clients.')]
    #[OA\Tag(name: 'Client.es')]
    #[OA\Parameter(
        name: 'page',
        description: "Page demandée",
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'limit',
        description: "Nombre de client.es par page",
        in: 'query'
    )]
    #[OA\Response(
        response: 200,
        description: 'Une réponse réussie contient une liste de client.es',
        content: []
    )]
    public function getAllCustomers(CustomerRepository $customerRepository, Request $request): JsonResponse
    {
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 3);
        $platform = $this->getUser();

        $idCache = 'getPlatform' . $platform->getId() . 'Customers-' . $page . '-' . $limit;

        $jsonCustomerList = $this->cache->get($idCache, function (ItemInterface $item) use ($customerRepository, $page, $limit, $platform) {
            $item->tag('customersCache');
            $customerList = $customerRepository->findCustomersWithPagination($platform, $page, $limit);

            if ($page > 1) {
                $customerList['_links']['previous']['href'] = $this->router->generate('customer_show_all', [
                    'page' => $page - 1,
                    'limit' => $limit,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
            $customerList['_links']['next']['href'] = $this->router->generate('customer_show_all', [
                'page' => $page + 1,
                'limit' => $limit,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->serializer->serialize($customerList, 'json', ['groups' =>'getCustomers']);  
        });

        return new JsonResponse($jsonCustomerList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/customers/{id}', name: 'customer_show', methods: ['GET'])]
    #[IsGranted('access', 'customer', 'Client.e non trouvé.e', 404)]
    #[OA\Tag(name: 'Client.es')]
    #[OA\Parameter(
        name: 'id',
        description: "Id du client ou de la cliente recherché.e.",
        in: 'path'
    )]
    #[OA\Response(
        response: 200,
        description: 'Une réponse réussie!',
        content: new Model(
            type: Customer::class
        )
    )]
    public function getDetailCustomer(Customer $customer): JsonResponse
    {
        $jsonCustomer = $this->serializer->serialize($customer, 'json', ['groups' =>'getCustomers']);
        return new JsonResponse($jsonCustomer, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/customers/{id}', name: 'customer_delete', methods: ['DELETE'])]
    #[OA\Tag(name: 'Client.es')]
    #[IsGranted('access', 'customer', 'Client.e non trouvé.e', 404)]
    public function deleteCustomer(Customer $customer): JsonResponse
    {
        $this->cache->invalidateTags(['customersCache']);

        $this->em->remove($customer);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('api/customers', name:'customer_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Vous n\'avez pas les droits suffisants pour ajouter un.e client.e.')]
    #[OA\Tag(name: 'Client.es')]
    // #[OA\Parameter(
    //     name: 'email',
    //     in: 'query'
    // )]
    // #[OA\Parameter(
    //     name: 'order',
    //     in: 'query',
    //     description: 'The field used to order rewards',
    //     schema: new OA\Schema(type: 'string')
    // )]
    public function createCustomer(Request $request): JsonResponse
    {
        $this->cache->invalidateTags(['customersCache']);

        $customer = $this->serializer->deserialize($request->getContent(), Customer::class, 'json');

        $errors = $this->validator->validate($customer);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $customer->setPlatform($this->getUser());

        $this->em->persist($customer);
        $this->em->flush();

        $jsonCustomer = $this->serializer->serialize($customer, 'json', ['groups' => 'getCustomers']);

        $location = $this->router->generate('customer_show', ['id' => $customer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonCustomer, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('api/customers/{id}', name:'customer_update', methods: ['PUT'])]
    #[IsGranted('access', 'currentCustomer', 'Client.e non trouvé.e', 404)]
    #[OA\Tag(name: 'Client.es')]
    #[OA\Parameter(
        name: 'id',
        description: "Id du client ou de la cliente à modifier.",
        in: 'path'
    )]
    // #[OA\RequestBody(new Model(groups: ["create"]))]
    // #[OA\RequestBody(
    //     groups: ['create'],
    // )]
    public function updateCustomer(
        Customer $currentCustomer,
        PlatformRepository $platformRepository,
        Request $request,
    ): JsonResponse
    {
        $this->cache->invalidateTags(['customersCache']);

        $updatedCustomer = $this->serializer->deserialize($request->getCOntent(), Customer::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentCustomer]);

        $errors = $this->validator->validate($updatedCustomer);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        
        $updatedCustomer->setUpdatedAt(new \DateTimeImmutable());
        
        $content = $request->toArray();
        $idPlatform = $content['idPlatform'] ?? -1;
        $updatedCustomer->setPlatform($platformRepository->find($idPlatform));

        $this->em->flush();

        $jsonCustomer = $this->serializer->serialize($updatedCustomer, 'json', ['groups' => 'getCustomers']);

        $location = $this->router->generate('customer_show', ['id' => $updatedCustomer->getId()], UrlGenerator::ABSOLUTE_URL);

        return new JsonResponse($jsonCustomer, Response::HTTP_OK, ['Location' => $location], true);
    }
}
