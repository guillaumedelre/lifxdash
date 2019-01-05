<?php

namespace App\Controller;

use GuzzleHttp\Client;
use MischiefCollective\ColorJizz\Formats\HSV;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(Client $lifx)
    {
        $response = $lifx->get('/v1/lights/all');
        $jsonEncoder = new JsonEncoder();
        $data = $jsonEncoder->decode($response->getBody()->getContents(), JsonEncoder::FORMAT);
        $colors = [];
        foreach ($data as $light) {
            $color = new HSV(
                $light['color']['hue'],
                $light['color']['saturation'] * 100,
                $light['brightness'] * 100
            );
            $colors[$light['id']] = $color->toHex()->__toString();
        }

        return $this->render(
            'dashboard.html.twig',
            [
                'lights' => $data,
                'colors' => $colors,
            ]
        );
    }
}
