<?php

use App\Entity\Customer;
use App\Entity\Platform;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CustomerVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';

    public function __construct(
        private Security $security
    )
    {
        
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT])) {
            return false;
        }

        if (!$subject instanceof Customer) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Platform) {
            return false;
        }

        /** @var Customer $customer */
        $customer = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($customer, $user),
            self::EDIT => $this->canEdit($customer, $user),
            default => throw new \LogicException('Accès interdit.')
        };
    }

    private function canView(Customer $customer, Platform $platform): bool
    {
        if ($this->canEdit($customer, $platform)) {
            return true;
        }

        if ($customer->getPlatform() !== $platform) {
            return false;
        }

        return true;
    }

    private function canEdit(Customer $customer, Platform $platform): bool
    {
        

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        if ($customer->getPlatform() !== $platform) {
            return false;
        }

        return true; 
    }
}