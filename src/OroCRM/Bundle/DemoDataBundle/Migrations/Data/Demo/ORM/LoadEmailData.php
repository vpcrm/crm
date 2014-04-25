<?php

namespace OroCRM\Bundle\DemoDataBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;

use OroCRM\Bundle\ContactBundle\Entity\Contact;

class LoadEmailData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    protected $templates;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroCRM\Bundle\DemoDataBundle\Migrations\Data\Demo\ORM\LoadContactData',];
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $om)
    {
        $this->loadEmailTemplates();
        $this->loadEmailsDemo($om);
        $om->flush();
    }

    protected function loadEmailTemplates()
    {
        $handle = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'dictionaries' . DIRECTORY_SEPARATOR. "emails.csv", "r");
        if ($handle) {
            $headers = array();
            if (($data = fgetcsv($handle, 1000, ",")) !== false) {
                //read headers
                $headers = $data;
            }
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $this->templates[] = array_combine($headers, array_values($data));
            }
        }
    }

    /**
     * @param ObjectManager $om
     */
    protected function loadEmailsDemo(
        ObjectManager $om
    ) {
        $contacts = $om->getRepository('OroCRMContactBundle:Contact')->findAll();
        $contactCount = count($contacts);
        /** @var EmailEntityBuilder $emailsBuilder */
        $emailsBuilder = $this->container->get('oro_email.email.entity.builder');
        for ($i = 0; $i < 100; ++$i) {
            $contactRandom = rand(0, $contactCount - 1);
            /** @var Contact $contact */
            $contact = $contacts[$contactRandom];
            $origin = $om
                ->getRepository('OroEmailBundle:InternalEmailOrigin')
                ->findOneBy(array('internalName' => InternalEmailOrigin::BAP));

            $randTemplate = array_rand($this->templates);
            $email = $emailsBuilder->email(
                $this->templates[$randTemplate]['Subject'],
                $contact->getOwner()->getEmail(),
                $contact->getPrimaryEmail()->getEmail(),
                new \DateTime('now'),
                new \DateTime('now'),
                new \DateTime('now')
            );

            $email->addFolder($origin->getFolder(EmailFolder::SENT));

            $emailBody = $emailsBuilder->body(
                "Hi,\n" . $this->templates[$randTemplate]['Text'],
                false,
                true
            );
            $email->setEmailBody($emailBody);
            $email->setMessageId(sprintf('id.%s@%s', uniqid(), '@bap.migration.generated'));

            $emailsBuilder->getBatch()->persist($om);
        }
    }
}
