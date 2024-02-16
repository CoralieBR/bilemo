<?php

namespace App\Controller;

use App\Entity\Item;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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

    #[Route('/api/items/{id}', name: 'deleteItem', methods: ['DELETE'])]
    public function deleteItem(Item $item, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($item);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('api/items', name:'createItem', methods: ['POST'])]
    public function createItem(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $item = $serializer->deserialize($request->getCOntent(), Item::class, 'json');
        $item->setCreatedAt(new \DateTimeImmutable());
        $item->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($item);
        $em->flush();

        $jsonItem = $serializer->serialize($item, 'json', ['groups' => 'getItems']);

        $location = $urlGenerator->generate('detailItem', ['id' => $item->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonItem, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('api/items/{id}', name:'updateItem', methods: ['PUT'])]
    public function updateItem(Item $currentItem, Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $updatedItem = $serializer->deserialize($request->getCOntent(), Item::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentItem]);
        
        $updatedItem->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($updatedItem);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
