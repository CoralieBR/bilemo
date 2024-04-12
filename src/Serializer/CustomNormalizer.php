<?php
namespace App\Serializer;

use App\Entity\Customer;
use App\Entity\Item;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CustomNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,

        private UrlGeneratorInterface $router,
    ) {
    }

    public function normalize($entity, string $format = null, array $context = []): array
    { 
        $data = $this->normalizer->normalize($entity, $format, $context);

        switch ($entity) {
            case $entity instanceof Customer:
                $route = 'customer_show';
                break;
            case $entity instanceof Item:
                $route = 'item_show';
                break;
            
            default:
                break;
        }

        // Here, add, edit, or delete some data:
        $data['href']['self'] = $this->router->generate($route, [
            'id' => $entity->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Customer || $data instanceof Item;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Customer::class => true,
            Item::class => true,
        ];
    }
}