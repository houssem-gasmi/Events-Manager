<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, EventRepository $eventRepository, CategoryRepository $categoryRepository, LocationRepository $locationRepository): Response
    {
        $events = $eventRepository->findFiltered(
            $request->query->get('date'),
            $request->query->get('location'),
            $request->query->get('category')
        );

        return $this->render('home/index.html.twig', [
            'events' => $events,
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
            'locations' => $locationRepository->findBy([], ['name' => 'ASC']),
            'filters' => [
                'date' => $request->query->get('date', 'upcoming'),
                'location' => $request->query->get('location', ''),
                'category' => $request->query->get('category', ''),
            ],
        ]);
    }
}
