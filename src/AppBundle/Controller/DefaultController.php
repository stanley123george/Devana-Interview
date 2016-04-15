<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Exception\ClientException;

use Symfony\Component\HttpFoundation\StreamedResponse;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


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
     * @Route("/redirect", name="redirect")
     */
    public function redirectAction(Request $request)
    {
        return $this->render('default/index.html.twig');
	}

    /**
     * @Route("/redirect1", name="redirect1")
     */
    public function redirect1Action(Request $request)
    {
        return $this->redirectToRoute('redirect');
	}
    /**
     * @Route("/redirect2", name="redirect2")
     */
    public function redirect2Action(Request $request)
    {
        return $this->redirectToRoute('redirect1');
	}


	/**
     * @Route("/check-urls", name="checkUrls")
     */
	public function checkUrlsAction(Request $request){
		$onRedirectFunction = function(RequestInterface $request,ResponseInterface $response, UriInterface $uri) {
								    $array = array("status"=>$response->getStatusCode(), "url" => (string)$request->getUri(), "length"=>$response->getBody()->getSize(), "nextUrl"=>(string)$uri);
								    echo json_encode($array);
								};

		$urlsString = $request->request->get('urls', array("http://www.google.com"));
		$parent = $request->request->get('parent', 0);
		$urls = explode(",", $urlsString);
		$count = count($urls);  //obrada requesta

		$response = new StreamedResponse();
		$response->headers->set('Content-Type', 'text/event-stream');
		$response->setCallback(function () use ($urls, $count, $parent, $onRedirectFunction) {
			$client = new GuzzleClient();
			
			$requests = function ($total, $urls) {  //pool request funkcija
			    for ($i = 0; $i < $total; $i++) {
			    	$uri = $urls[$i];
			    	yield new GuzzleRequest('GET', $uri);		
			    }
			};

			$pool = new Pool($client, $requests($count, $urls), [
			    'concurrency' => 1000,
			    'fulfilled' => function ($response, $index) use ($urls, $parent){ //dobijen $response za url sa $index
			    	if ($response->getHeaderLine('X-Guzzle-Redirect-History') === ""){
			        	$array = array("url" => $urls[$index], "status"=>$response->getStatusCode(), "length"=>$response->getBody()->getSize(), "parent"=>$parent, "index"=>$index);
			    	}else{
			    		$helpArray = explode(', ',$response->getHeaderLine('X-Guzzle-Redirect-History'));
			    		$array = array("url" => array_slice($helpArray, -1)[0], "status"=>$response->getStatusCode(), "length"=>$response->getBody()->getSize(), "parent"=>$parent, "index"=>$index);
			    	}
			        echo json_encode($array);
			        flush();
			    },
			    'rejected' => function ($reason, $index) use ($urls, $parent){ 
			    	$response = $reason->getResponse();
			    	if ($reason->getResponse()){
				    	$array = array("url" => $urls[$index], "status"=>$reason->getResponse()->getStatusCode(), "parent"=>$parent, "index"=>$index);
			    	}else{
			    		$array = array("url" => $urls[$index], "status"=>500, "parent"=>$parent, "index"=>$index);
			    	}
			        echo json_encode($array);
			        flush();
			    },
			    'options' => [
						    'allow_redirects' => [
						        'max'             => 10,
						        'on_redirect' => $onRedirectFunction,
						        'track_redirects' => true
						        ]
						    ]
			]);

			$promise = $pool->promise();  //trazi da svi urlovi iz pola dobiju odgovor
			$promise->wait();
		});

		return $response;
	}



}
