<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UsersController  extends AbstractController
{

    public function login()
    {
        return $this->render('Users/login.html.twig');
    }
}