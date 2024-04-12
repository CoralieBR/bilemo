<?php
namespace App\Serializer;

use App\Entity\Customer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CustomerNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,

        private UrlGeneratorInterface $router,
    ) {
    }

    public function normalize($customer, string $format = null, array $context = []): array
    { 
        $data = $this->normalizer->normalize($customer, $format, $context);

        // Here, add, edit, or delete some data:
        $data['href']['self'] = $this->router->generate('customer_show', [
            'id' => $customer->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Customer;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Customer::class => true,
        ];
    }
}