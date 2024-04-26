<?php

namespace App\Security\Voter;

use App\Entity\Customer;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class CustomerVoter extends Voter
{
    public const ACCESS = 'access';

    public function __construct(
        private Security $security
    )
    { }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::ACCESS
            && $subject instanceof \App\Entity\Customer;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Customer $customer */
        $customer = $subject;

        return match($attribute) {
            self::ACCESS => $this->canAccess($customer, $user),
            default => throw new \LogicException('Accès interdit.')
        };
    }

    private function canAccess(Customer $customer, UserInterface $platform): bool
    {
        if ($customer->getPlatform() !== $platform) {
            return false;
        }

        return true;
    }
}
