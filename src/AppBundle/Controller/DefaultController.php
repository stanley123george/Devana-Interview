<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use GuzzleHttp\Client;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
    	$client = $this->get('guzzle.client.api_crm');
        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://tivat.sistem48.me');
		$promise = $client->sendAsync($request)->then(function ($response) {
    		echo 'I completed! '.$response->getStatusCode();
		});
		// $promise->wait();
        
        return $this->render('default/index.html.twig');
    }
}
