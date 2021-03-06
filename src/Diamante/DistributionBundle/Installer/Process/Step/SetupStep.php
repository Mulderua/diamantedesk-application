<?php
/**
 * Created by PhpStorm.
 * User: s3nt1nel
 * Date: 7/1/15
 * Time: 4:44 PM
 */

namespace Diamante\DistributionBundle\Installer\Process\Step;


use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\InstallerBundle\Process\Step\AbstractStep;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;

class SetupStep extends AbstractStep
{
    public function displayAction(ProcessContextInterface $context)
    {
        $form = $this->createForm('diamante_installer_setup');

        $form->get('organization_name')->setData('DiamanteDesk');
        $form->get('application_url')->setData('http://localhost/diamantedesk');

        return $this->render(
            'DiamanteDistributionBundle:Process/Step:setup.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }

    public function forwardAction(ProcessContextInterface $context)
    {
        $adminUser = $this
            ->getDoctrine()
            ->getRepository('OroUserBundle:User')
            ->findOneBy(array('username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME));

        if (!$adminUser) {
            throw new \RuntimeException("Admin user wasn't loaded in fixtures.");
        }

        $form = $this->createForm('diamante_installer_setup');
        $form->setData($adminUser);

        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {
            $context->getStorage()->set(
                'loadFixtures',
                false
            );

            $this->get('oro_user.manager')->updateUser($adminUser);

            /** @var ConfigManager $configManager */
            $configManager           = $this->get('oro_config.global');
            $defaultOrganizationName = $configManager->get('oro_ui.organization_name');
            $organizationName        = $form->get('organization_name')->getData();
            if (!empty($organizationName) && $organizationName !== $defaultOrganizationName) {
                $organizationManager = $this->get('oro_organization.organization_manager');
                $organization        = $organizationManager->getOrganizationByName('default');
                $organization->setName($organizationName);

                $organizationManager->updateOrganization($organization);
            }

            $defaultAppURL       = $configManager->get('oro_ui.application_url');
            $applicationURL      = $form->get('application_url')->getData();
            if (!empty($applicationURL) && $applicationURL !== $defaultAppURL) {
                $configManager->set('oro_ui.application_url', $applicationURL);
            }
            $configManager->flush();

            $this->runCommand('diamante:desk:data');
            $this->runCommand('oro:migration:data:load', ['--bundles' => ['DiamanteDistributionBundle']]);

            return $this->complete();
        }

        return $this->render(
            'DiamanteDistributionBundle:Process/Step:setup.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }
}
