<?php
/**
 * Created by PhpStorm.
 * User: stevewinter
 * Date: 31/05/2018
 * Time: 14:25
 */

namespace MSDev\FileMakerDataAPIBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use GuzzleHttp\Client;
use MSDev\FileMakerDataAPIBundle\Exception\AuthenticationException;
use pmill\Doctrine\Hydrator\ArrayHydrator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class FileMakerDataAPI
{
    /** @var EntityManagerInterface  */
    private $entityManager;

    /** @var SessionInterface */
    private $session;

    /** @var string */
    private $baseURI;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string */
    private $token;

    private $retried = false;

    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session, $host, $database, $username, $password)
    {
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->username = $username;
        $this->password = $password;

        $this->setBaseURL($host, $database);
    }

    /**
     * @param $layout
     * @param bool|object $class
     * 
     * @return array
     * @throws QueryException
     */
    public function findAll($layout, $class = false)
    {
        $this->setOrFetchToken($this->session);

        // by default API only returns 100 records at a time, so we need to keep getting records till we run out
        $offset = 1;
        $retrieved = 100;
        $results = [];
        
        while($retrieved == 100) {
            $uri = $this->baseURI . sprintf('layouts/%s/records?_offset=%s', $layout, $offset);
            $records = $this->performFMRequest('GET', $uri, []);
            $retrieved = count($records);
            $offset += 100;

            if($class) {
                $objects = $this->hydrateObject($records, $class);
                $results = array_merge($results, $objects);
            } else {
                $results = array_merge($results, $records);
            }
        }

        return $results;
    }


    public function findOneBy($layout, $query, $class = false)
    {
        $records = $this->find($layout, $query, $class = false);

        if(empty($records)) {
            return [];
        }

        return $records[0];
    }

    public function find($layout, $query, $class = false)
    {
        $this->setOrFetchToken($this->session);
        $body = json_encode([
            'query' => [$query]
        ]);

        $uri = $this->baseURI . sprintf('layouts/%s/_find', $layout);
        $records = $this->performFMRequest("POST", $uri, ['body' => $body]);

        if($class) {
            return $this->hydrateObject($records, $class);
        }

        return $records;
    }


    private function hydrateObject($records, $class)
    {
        $hydrator = new ArrayHydrator($this->entityManager);
        $hydrator->setHydrateBy(ArrayHydrator::HYDRATE_BY_COLUMN);
        $hydrator->setHydrateId(true);

        $objects = [];
        foreach($records as $record) {
            $object = $hydrator->hydrate($class, $record);
            $objects[] = $object;

        }

        return $objects;
    }

    private function performFMRequest($method, $uri, $options)
    {
        $client = new Client();
        $headers = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->token),
            ]
        ];

        try {
            $response = $client->request($method, $uri, array_merge($headers, $options));

            $content = json_decode($response->getBody()->getContents(), true);
            return $this->flattenRecords($content['response']['data']);

        } catch (\Exception $e) {
            $content = json_decode($e->getResponse()->getBody()->getContents());
            if(401 == $content->messages[0]->code) {
                // no records found
                return [];
            }

            // if the token has expired or is invalid then in theory 952 will come back
            // but sometimes you get 105 missing layout (go figure), so try a token refresh
            if(in_array($content->messages[0]->code, [105, 952]) && !$this->retried) {
                $this->retried = true;
                $this->fetchToken($this->session);
                $this->performFMRequest($method, $uri, $options);
            }

            throw new QueryException($content->messages[0]->message, $content->messages[0]->code);
        }
    }

    private function flattenRecords(array $records) {
        $resp = [];
        foreach($records as $record) {
            $resp[] = array_merge([
                'portalData' => $record['portalData'],
                'recordId' => $record['recordId'],
                'modId' => $record['modId'],
            ], $record['fieldData']);
        }

        return $resp;
    }

    private function setBaseURL($host, $database)
    {
        $this->baseURI =
            ('http' == substr($host, 4) ? $host : 'https://' . $host) .
            ('/' == substr($host, -1) ? '' : '/') .
            'fmi/data/v1/databases/' .
            $database . '/';
    }

    private function setOrFetchToken(SessionInterface $session)
    {
        $token = $session->get('fm-data-api-token');
        if($token) {
            return $this->token = $token;
        }

        $this->fetchToken($session);
    }

    private function fetchToken(SessionInterface $session)
    {
        $client = new Client();
        try {
            $response = $client->request('POST', $this->baseURI . 'sessions', [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'auth' => [$this->username, $this->password]
            ]);

            $content = json_decode($response->getBody()->getContents());
            $this->token = $content->response->token;
            $session->set('fm-data-api-token', $this->token);

        } catch (\Exception $e) {
            $content = json_decode($e->getResponse()->getBody()->getContents());
            throw new AuthenticationException($content->messages[0]->message, $content->messages[0]->code);
        }
    }

}