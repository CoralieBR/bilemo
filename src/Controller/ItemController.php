<?php

namespace App\Controller;

use App\Entity\Item;
use App\Repository\ItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ItemController extends AbstractController
{
    #[Route('/api/items', name: 'item', methods: ['GET'])]
    public function getAllItems(ItemRepository $itemRepository, SerializerInterface $serializer): JsonResponse
    {
        $itemList = $itemRepository->findAll();
        $jsonItemList = $serializer->serialize($itemList, 'json');

        return new JsonResponse($jsonItemList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/items/{id}', name: 'detailItem', methods: ['GET'])]
    public function getDetailItem(Item $item, SerializerInterface $serializer): JsonResponse
    {
        $jsonItem = $serializer->serialize($item, 'json');
        return new JsonResponse($jsonItem, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
