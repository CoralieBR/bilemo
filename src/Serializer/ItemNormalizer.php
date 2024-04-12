<?php
namespace App\Serializer;

use App\Entity\Item;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ItemNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,

        private UrlGeneratorInterface $router,
    ) {
    }

    public function normalize($item, string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($item, $format, $context);

        // Here, add, edit, or delete some data:
        $data['href']['self'] = $this->router->generate('item_show', [
            'id' => $item->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Item;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Item::class => true,
        ];
    }
}