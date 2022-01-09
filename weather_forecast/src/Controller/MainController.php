<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/{page}", methods = "GET", name="spa", requirements = {"page"="^((?!api/)(?!static/)(?!icon.svg)).*$"})
     */
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }
}