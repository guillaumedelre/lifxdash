<?php

namespace App\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use MischiefCollective\ColorJizz\Formats\HSV;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class LifxController extends AbstractController
{
    /**
     * @Route("/lifx/{id}/show", name="show")
     */
    public function show(Client $lifx, Request $request)
    {
        $lightId = $request->attributes->get('id');
        $response = $lifx->get("/v1/lights/$lightId");
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
            'lifx.html.twig',
            [
                'light' => current($data),
                'colors' => $colors,
            ]
        );
    }

    /**
     * @Route("/lifx/{id}/power", name="power")
     */
    public function power(Client $lifx, Request $request)
    {
        $lightId = $request->attributes->get('id');
        $actual = $request->query->get('power');
        $lifx->put(
            "/v1/lights/$lightId/state",
            [
                RequestOptions::JSON => [
                    'power' => ($actual == 'on') ? 'off' : 'on',
                    'fast'  => false,
                ],
            ]
        );

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/lifx/{id}/color/{color}", name="color")
     */
    public function color(Client $lifx, Request $request)
    {
        $lightId = $request->attributes->get('id');
        $lightColor = $request->attributes->get('color');
        preg_match_all('#rgba\((.*),(.*),(.*),(.*)\)$#', $lightColor, $matches);
        $color = "rgb:{$matches[1][0]},{$matches[2][0]},{$matches[3][0]} brightness:{$matches[4][0]}";
        $lifx->put(
            "/v1/lights/$lightId/state",
            [
                RequestOptions::JSON => [
                    'color' => $color,
                    'fast'  => false,
                ],
            ]
        );

        return $this->redirect($request->headers->get('referer'));
    }

}
