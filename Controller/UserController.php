<?php

namespace Diamante\FrontBundle\Controller;

use Diamante\FrontBundle\Api\Command\ConfirmCommand;
use Diamante\FrontBundle\Api\Command\RegisterCommand;
use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Patch;

/**
 * @RouteResource("User")
 * @NamePrefix("diamante_front_api_")
 */
class UserController extends FOSRestController
{
    /**
     * Register new Diamante User
     *
     * @Post("/user")
     * @ApiDoc(
     *      description="Register (create) new Diamante User",
     *      resource=true
     * )
     */
    public function registerAction()
    {
        $command = new RegisterCommand();
        $command->email = $this->getRequest()->get('email');
        $command->password = $this->getRequest()->get('password');
        $command->firstname = $this->getRequest()->get('firstname');
        $command->lastname = $this->getRequest()->get('lastname');

        $errors = $this->get('validator')->validate($command);

        if (count($errors)) {
            return $this->response($this->view(null, Codes::HTTP_BAD_REQUEST));
        }

        try {
            $this->get('diamante.front.registration.service')->register($command);
            $view = $this->view(null, Codes::HTTP_CREATED);
        } catch (\Exception $e) {
            $view = $this->view(null, Codes::HTTP_BAD_REQUEST);
        }
        return $this->response($view);
    }

    /**
     * Confirm new Diamante User registration
     *
     * @Patch("/user/confirm")
     * @ApiDoc(
     *      description="Confirm new Diamante User registration",
     *      resource=true
     * )
     */
    public function confirmAction()
    {
        $command = new ConfirmCommand();
        $command->hash = $this->getRequest()->get('hash');

        $errors = $this->get('validator')->validate($command);

        if (count($errors)) {
            return $this->response($this->view(null, Codes::HTTP_BAD_REQUEST));
        }

        try {
            $this->get('diamante.front.registration.service')->confirm($command);
            $view = $this->view(null, Codes::HTTP_OK);
        } catch (\Exception $e) {
            $view = $this->view(null, Codes::HTTP_BAD_REQUEST);
        }
        return $this->response($view);
    }

    /**
     * @param View $view
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function response(View $view)
    {
        return $this->get('fos_rest.view_handler')->handle($view);
    }
}
