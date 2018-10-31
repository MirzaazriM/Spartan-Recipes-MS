<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/27/18
 * Time: 4:49 PM
 */

namespace Application\Controller;


use Model\Entity\Names;
use Model\Entity\NamesCollection;
use Model\Entity\Recepie;
use Model\Entity\RecepieContentCollection;
use Model\Entity\ResponseBootstrap;
use Model\Service\RecepiesService;
use Symfony\Component\HttpFoundation\Request;

class RecepiesController
{

    private $recepiesService;

    public function __construct(RecepiesService $recepiesService)
    {
        $this->recepiesService = $recepiesService;
    }


    /**
     * Get single recepie
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(Request $request):ResponseBootstrap {
        // get data
        $id = $request->get('id');
        $lang = $request->get('lang');
        $state = $request->get('state');

        // create response object
        $response = new ResponseBootstrap();

        // check if parameters are present
        if(isset($id) && isset($lang) && isset($state)){
            return $this->recepiesService->getRecepie($id, $lang, $state);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get list of receepies
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function getList(Request $request):ResponseBootstrap {
        // get data
        $from = $request->get('from');
        $limit = $request->get('limit');
        $state = $request->get('state');
        $lang = $request->get('lang');

        // create response object
        $response = new ResponseBootstrap();

        // check if parameters are present
        if(isset($from) && isset($limit)){
            return $this->recepiesService->getListOfRecepies($from, $limit, $state, $lang);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get recepies
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getRecepies(Request $request):ResponseBootstrap {
        // get data
        $lang = $request->get('lang');
        $app = $request->get('app');
        $like = $request->get('like');
        $state = $request->get('state');

        // create response object
        $response = new ResponseBootstrap();

        // check if data is present
        if(!empty($lang) && !empty($state)){
            return $this->recepiesService->getRecepies($lang, $app, $like, $state);
        }else{
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get recepies by ids
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getIds(Request $request):ResponseBootstrap {
        // get data
        $ids = $request->get('ids');
        $lang = $request->get('lang');
        $state = $request->get('state');

        // to array
        $ids = explode(',', $ids);

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(!empty($ids) && !empty($lang) && !empty($state)){
            return $this->recepiesService->getRecepiesById($ids, $lang, $state);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Delete recepie
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function delete(Request $request):ResponseBootstrap {
        // get data
        $id = $request->get('id');

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($id)){
            return $this->recepiesService->deleteRecepie($id);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return response
        return $response;
    }


    /**
     * Release recepie
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function postRelease(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];

        // create response object in case of failure
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($id)){
            return $this->recepiesService->releaseRecepie($id);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return response
        return $response;
    }


    /**
     * Add recepie
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function post(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $names = $data['names'];
        $tags = $data['tags'];
        $thumbnail = $data['thumbnail'];
        $recepies = $data['recepies'];
        $behavior = $data['behavior'];

        // create names collection
        $namesCollection = new NamesCollection();
        // set names into names collection
        foreach($names as $name){
            $temp = new Names();
            $temp->setName($name);

            $namesCollection->addEntity($temp);
        }

        // create recepies collection
        $recepiesCollection = new RecepieContentCollection();
        // set recepie into recepies collection
        foreach($recepies as $recepie){
            $temp = new Recepie();
            $temp->setRecipieContent($recepie);

            $recepiesCollection->addEntity($temp);
        }

        // create response object
        $response = new ResponseBootstrap();

        // check data
        if(isset($namesCollection) && isset($recepiesCollection) && isset($tags) && isset($thumbnail) && isset($behavior) && (count($names) == count($recepies))){
            return $this->recepiesService->createRecepie($namesCollection, $recepiesCollection, $tags, $thumbnail, $behavior);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return response
        return $response;
    }


    /**
     * Edit recepie
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function put(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        $names = $data['names'];
        $tags = $data['tags'];
        $thumbnail = $data['thumbnail'];
        $recepies = $data['recepies'];
        $behavior = $data['behavior'];

        // create collections
        $namesCollection = new NamesCollection();
        // set names into names collection
        foreach($names as $name){
            $temp = new Names();
            $temp->setName($name);

            $namesCollection->addEntity($temp);
        }

        $recepiesCollection = new RecepieContentCollection();
        // set recepie into collection
        foreach($recepies as $recepie){
            $temp = new Recepie();
            $temp->setRecipieContent($recepie);

            $recepiesCollection->addEntity($temp);
        }

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($id) && isset($namesCollection) && isset($recepiesCollection) && isset($tags) && isset($thumbnail) && isset($behavior) && (count($names) == count($recepies))){
            return $this->recepiesService->editRecepie($id, $namesCollection, $recepiesCollection, $tags, $thumbnail, $behavior);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get total number of recepies
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function getTotal(Request $request):ResponseBootstrap {
        // call service for response
        return $this->recepiesService->getTotal();
    }

}