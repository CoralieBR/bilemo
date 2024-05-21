<?php

namespace App\Controller;

use App\Entity\Item;
use App\Repository\ItemRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\{ItemInterface, TagAwareCacheInterface};

class ItemController extends AbstractController
{
    
    public function __construct(
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $router,
    ) {
    }

    #[Route('/api/items', name: 'item_show_all', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Vous n\'avez pas les droits suffisants pour consulter les produits.')]
    #[OA\Tag(name: 'Produits')]
    #[OA\Parameter(
        name: 'page',
        description: "Page demandée",
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'limit',
        description: "Nombre de produits par page",
        in: 'query'
    )]
    public function getAllItems(
        ItemRepository $itemRepository,
        Request $request,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = 'getAllItems-' . $page . '-' . $limit;

        $jsonItemList = $cache->get($idCache, function (ItemInterface $cachedItem) use ($itemRepository, $page, $limit) {
            $cachedItem->tag('itemsCache');
            $itemList = $itemRepository->findAllWithPagination($page, $limit);

            if ($page > 1) {
                $itemList['_links']['previous']['href'] = $this->router->generate('item_show_all', [
                    'page' => $page -1,
                    'limit' => $limit,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
            $itemList['_links']['next']['href'] = $this->router->generate('customer_show_all', [
                'page' => $page + 1,
                'limit' => $limit,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->serializer->serialize($itemList, 'json');
        });

        return new JsonResponse($jsonItemList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/items/{id}', name: 'item_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Vous n\'avez pas les droits suffisants pour consulter ce produit.')]
    #[OA\Tag(name: 'Produits')]
    #[OA\Parameter(
        name: 'id',
        description: "Id du produit recherché.",
        in: 'path'
    )]
    #[OA\Response(
        response: 200,
        description: 'Une réponse réussie!',
        content: new Model(
            type: Item::class
        )
    )]
    public function getDetailItem(Item $item): JsonResponse
    {
        $jsonItem = $this->serializer->serialize($item, 'json');
        return new JsonResponse($jsonItem, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
