<?php

namespace Model\Service\Facade;

use Model\Entity\Recepie;
use Model\Entity\RecepieCollection;
use Model\Mapper\RecepiesMapper;

class GetRecepieFacade
{

    private $lang;
    private $app;
    private $like;
    private $state;
    private $recepiesMapper;
    private $configuration;

    public function __construct(string $lang, string $app = null, string $like = null, string $state, RecepiesMapper $recipiesMapper) {
        $this->lang = $lang;
        $this->app = $app;
        $this->like = $like;
        $this->state = $state;
        $this->recepiesMapper = $recipiesMapper;
        $this->configuration = $recipiesMapper->getConfiguration();
    }


    /**
     * Handle recepies
     *
     * @return mixed|RecepieCollection|null
     */
    public function handleRecipies() {
        $data = null;

        // Calling By App
        if(!empty($this->app)){
            $data = $this->getRecipiesByApp();
        }
        // Calling by Search
        else if(!empty($this->like)){
            $data = $this->searchRecipies();
        }
        // Calling by State
        else{
            $data = $this->getRecipies();
        }

        // return data
        return $data;
    }


    /**
     * Get recepies
     *
     * @return RecepieCollection
     */
    public function getRecipies():RecepieCollection {
        // create entity and set its values
        $entity = new Recepie();
        $entity->setLang($this->lang);
        $entity->setState($this->state);

        // call mapper for data
        $collection = $this->recepiesMapper->getRecepies($entity);

        // return data
        return $collection;
    }


    /**
     * Get recepies by app
     *
     * @return mixed
     */
    public function getRecipiesByApp() {
        // call apps MS for data
        $client = new \GuzzleHttp\Client();
        $result = $client->request('GET', $this->configuration['apps_url'] . '/apps/data?app=' . $this->app . '&lang=' . $this->lang . '&state=' . $this->state . '&type=recipes', []);
        $data = json_decode($result->getBody()->getContents(), true);

        // return data
        return $data;
    }


    /**
     * Search recepies
     *
     * @return RecepieCollection
     */
    public function searchRecipies():RecepieCollection {

        // create entity and set its values
        $entity = new Recepie();
        $entity->setLang($this->lang);
        $entity->setState($this->state);
        $entity->setLike($this->like);

        // call mapper for data
        $data = $this->recepiesMapper->searchRecepies($entity);

        // return data
        return $data;
    }

}