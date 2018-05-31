<?php
/**
 * Created by PhpStorm.
 * User: stevewinter
 * Date: 31/05/2018
 * Time: 14:25
 */

namespace MSDev\FileMakerDataAPIBundle\Service;


class FileMakerDataAPI
{
    /** @var string */
    private $host;

    public function __construct($host)
    {
        $this->host = $host;
    }

    public function findAll($layout)
    {
        dump($this->host);
        dump($layout);
    }
}