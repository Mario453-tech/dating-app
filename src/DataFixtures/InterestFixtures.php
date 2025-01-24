<?php

namespace App\DataFixtures;

use App\Entity\Interest;
use App\Entity\InterestCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class InterestFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            'Sporty i aktywność fizyczna' => [
                'icon' => 'fa-running',
                'interests' => [
                    'Piłka nożna', 'Koszykówka', 'Siatkówka', 'Jazda na rowerze',
                    'Pływanie', 'Bieganie', 'Wspinaczka', 'Joga', 'Fitness',
                    'Sporty zimowe'
                ]
            ],
            'Hobby' => [
                'icon' => 'fa-palette',
                'interests' => [
                    'Gotowanie', 'Czytanie książek', 'Rysowanie', 'Malowanie',
                    'Rękodzieło', 'Fotografia', 'Ogrodnictwo', 'Gry planszowe',
                    'Majsterkowanie'
                ]
            ],
            'Muzyka i sztuka' => [
                'icon' => 'fa-music',
                'interests' => [
                    'Rock', 'Jazz', 'Muzyka klasyczna', 'Pop', 'Folk',
                    'Gra na gitarze', 'Gra na pianinie', 'Śpiew', 'Teatr',
                    'Taniec'
                ]
            ],
            'Podróże i przygody' => [
                'icon' => 'fa-plane',
                'interests' => [
                    'Zwiedzanie', 'Turystyka górska', 'Podróże kulinarne',
                    'Camping', 'Żeglarstwo', 'Wycieczki rowerowe',
                    'Odkrywanie lokalnej kultury'
                ]
            ],
            'Technologia i gry' => [
                'icon' => 'fa-gamepad',
                'interests' => [
                    'Gry komputerowe', 'Programowanie', 'Nowinki technologiczne',
                    'E-sport', 'Modelowanie 3D', 'Astronomia'
                ]
            ],
            'Kulinaria' => [
                'icon' => 'fa-utensils',
                'interests' => [
                    'Degustacja win', 'Eksperymenty kulinarne', 'Kuchnia włoska',
                    'Kuchnia azjatycka', 'Zdrowe odżywianie', 'Pieczenie'
                ]
            ],
            'Przyroda i zwierzęta' => [
                'icon' => 'fa-paw',
                'interests' => [
                    'Opieka nad zwierzętami', 'Obserwacja ptaków',
                    'Ochrona środowiska', 'Wędkowanie', 'Jeździectwo'
                ]
            ],
            'Rozwój osobisty' => [
                'icon' => 'fa-brain',
                'interests' => [
                    'Nauka języków', 'Psychologia', 'Medytacja',
                    'Kursy i warsztaty', 'Pisanie'
                ]
            ],
            'Społeczność i wolontariat' => [
                'icon' => 'fa-hands-helping',
                'interests' => [
                    'Wydarzenia charytatywne', 'Spotkania społecznościowe',
                    'Pomoc w schroniskach', 'Działalność non-profit'
                ]
            ],
            'Pasje alternatywne' => [
                'icon' => 'fa-star',
                'interests' => [
                    'Manga i anime', 'Cosplay', 'Astrologia',
                    'Survival', 'Tworzenie podcastów', 'Escape room'
                ]
            ],
        ];

        foreach ($categories as $categoryName => $categoryData) {
            $category = new InterestCategory();
            $category->setName($categoryName);
            $category->setIcon($categoryData['icon']);
            $manager->persist($category);

            foreach ($categoryData['interests'] as $interestName) {
                $interest = new Interest();
                $interest->setName($interestName);
                $interest->setCategory($category);
                $manager->persist($interest);
            }
        }

        $manager->flush();
    }
}
