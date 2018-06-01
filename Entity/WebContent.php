<?php
/**
 * Created by PhpStorm.
 * User: stevewinter
 * Date: 31/05/2018
 * Time: 17:12
 */

namespace MSDev\FileMakerDataAPIBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * WebContent
 *
 * @ORM\Entity
 * @ORM\Table(name="WebContent")
 */
class WebContent
{
    /**
     * @var int
     *
     * @ORM\Column(name="recordId", type="integer")
     * @ORM\Id
     */
    private $recordId;

    /**
     * @var int
     *
     * @ORM\Column(name="ID", type="string")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Content", type="text")
     */
    private $content;

    /**
     * @return int
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}