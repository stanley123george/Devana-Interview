<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

use Symfony\Component\HttpFoundation\StreamedResponse;


class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig');
	}

	/**
     * @Route("/check-urls", name="checkUrls")
     */
	public function checkUrlsAction(Request $request){
		$urlsString = $request->request->get('urls', array("http://www.google.com"));
		$parent = $request->request->get('parent', 0);
		$urls = explode(",", $urlsString);
		$count = count($urls);  //obrada requesta

		$response = new StreamedResponse();
		$response->headers->set('Content-Type', 'text/event-stream');
		$response->setCallback(function () use ($urls, $count, $parent) {
			$client = $this->get('guzzle.client.api_crm');
			
			$requests = function ($total, $urls) {  //pool request funkcija
			    for ($i = 0; $i < $total; $i++) {
			    	$uri = $urls[$i];
			        yield new GuzzleRequest('GET', $uri);
			    }
			};

			$pool = new Pool($client, $requests($count, $urls), [
			    'concurrency' => 1000,
			    'fulfilled' => function ($response, $index) use ($urls, $parent){ //dobijen $response za url sa $index
			        $array = array("url" => $urls[$index], "status"=>$response->getStatusCode(), "length"=>$response->getBody()->getSize(), "parent"=>$parent, "index"=>$index);
			        echo json_encode($array);
			        flush();
			    },
			    'rejected' => function ($reason, $index) { 
			    	echo 'data: Index: '.$index.PHP_EOL;
			    	echo 'data: Reason: '.$reason.PHP_EOL;
			        flush();
			    },
			]);

			$promise = $pool->promise();  //trazi da svi urlovi iz pola dobiju odgovor
			$promise->wait();
		});

		return $response;
	}
}
