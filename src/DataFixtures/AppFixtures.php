<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Item;
use App\Entity\Platform;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i=0; $i < 5; $i++) { 
            $platform = new Platform;
            $platform->setName('Plateforme n°' . $i);
            $platform->setSlug('platforme-n-' . $i);
            $platform->setCreatedAt(new \DateTimeImmutable());
            $platform->setUpdatedAt(new \DateTimeImmutable());

            $manager->persist($platform);

            for ($ii=0; $ii < 3; $ii++) {
                $customer = new Customer;
                $customer->setEmail('customer' . $i . '@email.fr');
                $customer->setCreatedAt(new \DateTimeImmutable());
                $customer->setUpdatedAt(new \DateTimeImmutable());
                $customer->setPlatform($platform);
    
                $manager->persist($customer);
            }
        }

        for ($i=0; $i < 20; $i++) { 
            $item = new Item;
            $item->setName("Item n°" . $i);
            $item->setSlug('item-n-' . $i);
            $item->setCreatedAt(new \DateTimeImmutable());
            $item->setUpdatedAt(new \DateTimeImmutable());

            $manager->persist($item);
        }

        $manager->flush();
    }
}
