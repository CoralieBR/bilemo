<?php

namespace App\Controller;

use App\Entity\Platform;
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

class PlatformController extends AbstractController
{
    #[Route('/api/platforms', name: 'platform', methods: ['GET'])]
    public function getAllPlatforms(PlatformRepository $platformRepository, SerializerInterface $serializer): JsonResponse
    {
        $platformList = $platformRepository->findAll();
        $jsonPlatformList = $serializer->serialize($platformList, 'json', ['groups' =>'getPlatforms']);

        return new JsonResponse($jsonPlatformList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/platforms/{id}', name: 'detailPlatform', methods: ['GET'])]
    public function getDetailPlatform(Platform $platform, SerializerInterface $serializer): JsonResponse
    {
        $jsonPlatform = $serializer->serialize($platform, 'json', ['groups' =>'getPlatforms']);
        return new JsonResponse($jsonPlatform, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/platforms/{id}', name: 'deletePlatform', methods: ['DELETE'])]
    public function deletePlatform(Platform $platform, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($platform);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('api/platforms', name:'createPlatform', methods: ['POST'])]
    public function createPlatform(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, PlatformRepository $platformRepository): JsonResponse
    {
        $platform = $serializer->deserialize($request->getContent(), Platform::class, 'json');

        $platform->setCreatedAt(new \DateTimeImmutable());
        $platform->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($platform);
        $em->flush();

        $jsonPlatform = $serializer->serialize($platform, 'json', ['groups' => 'getPlatforms']);

        $location = $urlGenerator->generate('detailPlatform', ['id' => $platform->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonPlatform, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('api/platforms/{id}', name:'updatePlatform', methods: ['PUT'])]
    public function updatePlatform(Platform $currentPlatform, Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $updatedPlatform = $serializer->deserialize($request->getCOntent(), Platform::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentPlatform]);

        $updatedPlatform->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($updatedPlatform);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
