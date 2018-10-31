<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/27/18
 * Time: 5:20 PM
 */

namespace Model\Entity;


use Component\Collection;
use Model\Contract\HasId;

class RecepieCollection extends Collection
{

    private $statusCode;

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param mixed $statusCode
     */
    public function setStatusCode($statusCode): void
    {
        $this->statusCode = $statusCode;
    }



    public function buildEntity(): HasId
    {
        // TODO: Implement buildEntity() method.
    }


    public function addEntity(HasId $entity, $key = null)
    {
        return parent::addEntity($entity, $key); // TODO: Change the autogenerated stub
    }


}