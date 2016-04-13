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
     * @Route("/check-emails", name="checkEmails")
     */
	public function checkEmailsAction(Request $request){
		$m = $request->request->get('emails', 'default value if bar does not exist');
		$emails = explode(",", $m);
		$count = count($emails);

		$response = new StreamedResponse();
		$response->headers->set('Content-Type', 'text/event-stream');

		$response->setCallback(function () use ($emails, $count) {
			$client = $this->get('guzzle.client.api_crm');
			
			$requests = function ($total, $emails) {
			    for ($i = 0; $i < $total; $i++) {
			    	$uri = $emails[$i];
			        yield new GuzzleRequest('GET', $uri);
			    }
			};

			$pool = new Pool($client, $requests($count, $emails), [
			    'concurrency' => 1000,
			    'fulfilled' => function ($response, $index) {
			        echo 'data: Index: '.$index.PHP_EOL;
			        echo 'data: Status code: '.$response->getStatusCode().PHP_EOL;
			        // echo 'data: Content length: '.$response->getHeader('Content-Length')[0].PHP_EOL;
			        echo 'data: Content length: '.$response->getBody()->getSize().PHP_EOL;
			        flush();
			    },
			    'rejected' => function ($reason, $index) {
			    	echo 'data: Reason: '.$reason.PHP_EOL;
			        flush();
			    },
			]);

			$promise = $pool->promise();

			$promise->wait();
		});
		return $response;
	}
}
