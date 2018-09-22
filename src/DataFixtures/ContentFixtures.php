<?php

declare(strict_types=1);

namespace Bolt\DataFixtures;

use Bolt\Configuration\Config;
use Bolt\Content\FieldFactory;
use Bolt\Entity\Content;
use Bolt\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ContentFixtures extends Fixture
{
    /** @var UserPasswordEncoderInterface */
    private $passwordEncoder;

    /** @var \Faker\Generator */
    private $faker;

    /** @var Config */
    private $config;

    private $lastTitle = null;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, Config $config)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->faker = Factory::create();
        $this->config = $config->get('contenttypes');
    }

    public function load(ObjectManager $manager)
    {
        $this->loadUsers($manager);
        $this->loadContent($manager);

        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager)
    {
        foreach ($this->getUserData() as [$fullname, $username, $password, $email, $roles]) {
            $user = new User();
            $user->setFullName($fullname);
            $user->setUsername($username);
            $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
            $user->setEmail($email);
            $user->setRoles($roles);

            $manager->persist($user);
            $this->addReference($username, $user);
        }

        $manager->flush();
    }

    private function getUserData(): array
    {
        return [
            // $userData = [$fullname, $username, $password, $email, $roles];
            ['Jane Doe', 'jane_admin', 'kitten', 'jane_admin@symfony.com', ['ROLE_ADMIN']],
            ['Tom Doe', 'tom_admin', 'kitten', 'tom_admin@symfony.com', ['ROLE_ADMIN']],
            ['John Doe', 'john_user', 'kitten', 'john_user@symfony.com', ['ROLE_USER']],
        ];
    }

    private function loadContent(ObjectManager $manager)
    {
        foreach ($this->config as $contentType) {
            $amount = $contentType['singleton'] ? 1 : 15;

            foreach (range(1, $amount) as $i) {
                $author = $this->getReference(['jane_admin', 'tom_admin'][0 === $i ? 0 : random_int(0, 1)]);

                $content = new Content();
                $content->setContenttype($contentType['slug']);
                $content->setAuthor($author);
                $content->setStatus($this->getRandomStatus());
                $content->setCreatedAt($this->faker->dateTimeBetween('-1 year'));
                $content->setModifiedAt($this->faker->dateTimeBetween('-1 year'));
                $content->setPublishedAt($this->faker->dateTimeBetween('-1 year'));
                $content->setDepublishedAt($this->faker->dateTimeBetween('-1 year'));

                $sortorder = 1;
                foreach ($contentType->fields as $name => $fieldType) {
                    $field = FieldFactory::get($fieldType['type']);
                    $field->setName($name);
                    $field->setValue($this->getValuesforFieldType($name, $fieldType));
                    $field->setSortorder($sortorder++ * 5);

                    $content->addField($field);
                }

                $manager->persist($content);
            }
        }
    }

    private function getRandomStatus()
    {
        $statuses = ['published', 'published', 'published', 'held', 'draft', 'timed'];

        return $statuses[array_rand($statuses)];
    }

    private function getValuesforFieldType($name, $field)
    {
        switch ($field['type']) {
            case 'html':
            case 'textarea':
            case 'markdown':
                $data = [$this->faker->paragraphs(3, true)];
                break;
            case 'image':
                $data = ['filename' => 'kitten.jpg', 'alt' => 'A cute kitten'];
                break;
            case 'slug':
                $data = $this->lastTitle ?? $this->faker->sentence(3, true);
                break;
            case 'text':
                $data = [$this->faker->sentence(6, true)];
                break;
            default:
                $data = [$this->faker->sentence(6, true)];
        }

        if ($name === 'title') {
            $this->lastTitle = $data;
        }

        return $data;
    }
}