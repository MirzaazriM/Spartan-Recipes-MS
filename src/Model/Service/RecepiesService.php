<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/27/18
 * Time: 4:53 PM
 */

namespace Model\Service;

use Component\LinksConfiguration;
use Model\Core\Helper\Monolog\MonologSender;
use Model\Entity\NamesCollection;
use Model\Entity\Recepie;
use Model\Entity\RecepieContentCollection;
use Model\Entity\ResponseBootstrap;
use Model\Mapper\RecepiesMapper;
use Model\Service\Facade\GetRecepieFacade;

class RecepiesService extends LinksConfiguration
{

    private $recepiesMapper;
    private $configuration;
    private $monologHelper;

    public function __construct(RecepiesMapper $recepiesMapper)
    {
        $this->recepiesMapper = $recepiesMapper;
        $this->configuration = $recepiesMapper->getConfiguration();
        $this->monologHelper = new MonologSender();
    }


    /**
     * Get recepie service
     *
     * @param int $id
     * @param string $lang
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getRecepie(int $id, string $lang, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Recepie();
            $entity->setId($id);
            $entity->setLang($lang);
            $entity->setState($state);

            // get response
            $res = $this->recepiesMapper->getRecepie($entity);
            $id = $res->getId();

            // get tags
            $tagIds = $res->getTags();

            // create guzzle client and call Tags MS for data
            $client = new \GuzzleHttp\Client();
            $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
            $tags = $result->getBody()->getContents();

            // check data and set response
            if(isset($id)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData([
                    'id' => $res->getId(),
                    'lang' => $res->getLang(),
                    'state' => $res->getState(),
                    'title' => $res->getTitle(),
                    'text' => $res->getText(),
                    'thumbnail' => $res->getThumbnail(),
                    'behavior' => $res->getBehavior(),
                    'tags' => json_decode($tags)

                ]);
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get recepie service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }

    }


    /**
     * Get list of recepies
     *
     * @param int $from
     * @param int $limit
     * @return ResponseBootstrap
     */
    public function getListOfRecepies(int $from, int $limit, string $state = null, string $lang = null):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Recepie();
            $entity->setFrom($from);
            $entity->setLimit($limit);
            $entity->setState($state);
            $entity->setLang($lang);

            // call mapper for data
            $data = $this->recepiesMapper->getList($entity);

            // set response according to data content
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get recepies list service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get recepies service
     *
     * @param string $lang
     * @param string|null $app
     * @param string|null $like
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getRecepies(string $lang, string $app = null, string $like = null, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create facade and call its functions for data
            $facade = new GetRecepieFacade($lang, $app, $like, $state, $this->recepiesMapper);
            $res = $facade->handleRecipies();

            // check how to format data
            if(gettype($res) === 'object'){
                // convert data to array for appropriate response
                $data = [];

                for($i = 0; $i < count($res); $i++){
                    $data[$i]['id'] = $res[$i]->getId();
                    $data[$i]['language'] = $res[$i]->getLang();
                    $data[$i]['state'] = $res[$i]->getState();
                    $data[$i]['title'] = $res[$i]->getTitle();
                    $data[$i]['text'] = $res[$i]->getText();
                    $data[$i]['thumbnail'] = $res[$i]->getThumbnail();
                    $data[$i]['behavior'] = $res[$i]->getBehavior();

                    // get tags
                    $tagIds = $res[$i]->getTags();

                    // create guzzle client and call MS for data
                    $client = new \GuzzleHttp\Client();
                    $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);

                    $tags = $result->getBody()->getContents();

                    $data[$i]['tags'] = json_decode($tags);
                }
            }else if(gettype($res) === 'array'){
                $data = $res;
            }

            // Check Data and Set Response
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get recepies service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get recepies by ids
     * 
     * @param array $ids
     * @param string $lang
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getRecepiesById(array $ids, string $lang, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Recepie();
            $entity->setIds($ids);
            $entity->setLang($lang);
            $entity->setState($state);

            // get response
            $res = $this->recepiesMapper->getRecepiesById($entity);

            // convert data to array for appropriate response
            $data = [];

            for($i = 0; $i < count($res); $i++){
                $data[$i]['id'] = $res[$i]->getId();
                $data[$i]['language'] = $res[$i]->getLang();
                $data[$i]['state'] = $res[$i]->getState();
                $data[$i]['title'] = $res[$i]->getTitle();
                $data[$i]['text'] = $res[$i]->getText();
                $data[$i]['thumbnail'] = $res[$i]->getThumbnail();
                $data[$i]['behavior'] = $res[$i]->getBehavior();
                $data[$i]['version'] = $res[$i]->getVersion();

                // get tags
                $tagIds = $res[$i]->getTags();

                // create guzzle client and call MS for data
                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
                $tags = $result->getBody()->getContents();

                $data[$i]['tags'] = json_decode($tags);
            }

            // Check Data and Set Response
            if($res->getStatusCode() == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get recepies by ids service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }

    }


    /**
     * Delete recepie service
     *
     * @param int $id
     * @return ResponseBootstrap
     */
    public function deleteRecepie(int $id):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Recepie();
            $entity->setId($id);

            // get response
            $res = $this->recepiesMapper->deleteRecepie($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Delete recepie service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Release recepie service
     *
     * @param int $id
     * @return ResponseBootstrap
     */
    public function releaseRecepie(int $id):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Recepie();
            $entity->setId($id);

            // get response
            $res = $this->recepiesMapper->releaseRecepie($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Release recepie service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Add recepie
     *
     * @param NamesCollection $names
     * @param RecepieContentCollection $recepieCollection
     * @param array $tags
     * @param string $thumbnail
     * @param string $behavior
     * @return ResponseBootstrap
     */
    public function createRecepie(NamesCollection $names, RecepieContentCollection $recepieCollection, array $tags, string $thumbnail, string $behavior):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Recepie();
            $entity->setTags($tags);
            $entity->setThumbnail($thumbnail);
            $entity->setNames($names);
            $entity->setRecipieContent($recepieCollection);
            $entity->setBehavior($behavior);

            // get response
            $res = $this->recepiesMapper->createRecepie($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Create recepie service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Edit recepie
     *
     * @param int $id
     * @param NamesCollection $names
     * @param RecepieContentCollection $recepieCollection
     * @param array $tags
     * @param string $behavior
     * @param $thumbnail
     * @return ResponseBootstrap
     */
    public function editRecepie(int $id, NamesCollection $names, RecepieContentCollection $recepieCollection, array $tags, $thumbnail, string $behavior):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Recepie();
            $entity->setId($id);
            $entity->setTags($tags);
            $entity->setThumbnail($thumbnail);
            $entity->setNames($names);
            $entity->setRecipieContent($recepieCollection);
            $entity->setBehavior($behavior);

            // get response
            $res = $this->recepiesMapper->editRecepie($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Edit recepie service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }

    }


    /**
     * Get total
     *
     * @return ResponseBootstrap
     */
    public function getTotal():ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // call mapper for data
            $data = $this->recepiesMapper->getTotal();

            // check data and set response
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData([
                    $data
                ]);
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get total recepies service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }
}