<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/27/18
 * Time: 4:49 PM
 */

namespace Model\Mapper;

use PDO;
use PDOException;
use Component\DataMapper;
use Model\Entity\Recepie;
use Model\Entity\RecepieCollection;
use Model\Entity\Shared;

class RecepiesMapper extends DataMapper
{

    public function getConfiguration()
    {
        return $this->configuration;
    }


    /**
     * Get recepie mapper
     *
     * @param Recepie $recepie
     * @return Recepie
     */
    public function getRecepie(Recepie $recepie):Recepie {

        // create response object
        $response = new Recepie();

        try {
            // set database instructions
            $sql = "SELECT 
                        r.id,
                        r.state,
                        r.behavior,
                        rt.title,
                        rt.text,
                        rt.thumbnail,
                        rt.language,
                        GROUP_CONCAT(rta.tag_id) as tags
                    FROM recepie AS r
                    LEFT JOIN recepie_text AS rt ON r.id = rt.recepie_parent
                    LEFT JOIN recepie_tags AS rta ON r.id = rta.recepie_parent
                    WHERE r.state = ?
                    AND r.id = ?
                    AND rt.language = ?
                    GROUP BY r.id";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $recepie->getState(),
                $recepie->getId(),
                $recepie->getLang()
            ]);

            // fetch work data
            $data = $statement->fetch();

            // set recepie values
            if($statement->rowCount() > 0){
                $response->setId($data['id']);
                $response->setTitle($data['title']);
                $response->setText($data['text']);
                $response->setLang($data['language']);
                $response->setState($data['state']);
                $response->setThumbnail($this->configuration['asset_link'] . $data['thumbnail']);
                $response->setBehavior($data['behavior']);
                $response->setTags($data['tags']);
            }

        }catch(PDOException $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get recepie mapper: " . $e->getMessage());
        }

        // return fetched data
        return $response;
    }


    /**
     * Get list of recepies
     *
     * @param Recepie $recepie
     * @return array
     */
    public function getList(Recepie $recepie){

        try {


            $state = $recepie->getState();

            if($state === null or $state === ''){
                // set database instructions
                $sql = "SELECT
                       r.id,
                       r.state,
                       rt.language,
                       r.behavior,
                       r.version,
                       rt.title,
                       rt.thumbnail
                    FROM recepie AS r 
                    LEFT JOIN recepie_text AS rt ON r.id = rt.recepie_parent
                    LIMIT :from,:limit";
                // set statement
                $statement = $this->connection->prepare($sql);
                // set from and limit as core variables
                $from = $recepie->getFrom();
                $limit = $recepie->getLimit();
                // bind parametars
                $statement->bindParam(':from', $from, PDO::PARAM_INT);
                $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
                // execute query
                $statement->execute();
            }else {
                // set database instructions
                $sql = "SELECT
                       r.id,
                       r.state,
                       rt.language,
                       r.behavior,
                       r.version,
                       rt.title,
                       rt.thumbnail
                    FROM recepie AS r 
                    LEFT JOIN recepie_text AS rt ON r.id = rt.recepie_parent
                    WHERE r.state = :state AND rt.language = :lang
                    LIMIT :from,:limit";
                // set statement
                $statement = $this->connection->prepare($sql);
                // set from and limit as core variables
                $from = $recepie->getFrom();
                $limit = $recepie->getLimit();
                $language = $recepie->getLang();

                // bind parametars
                $statement->bindParam(':from', $from, PDO::PARAM_INT);
                $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
                $statement->bindParam(':state', $state);
                $statement->bindParam(':lang', $language);
                // execute query
                $statement->execute();
            }


            // set data
            $data = $statement->fetchAll(PDO::FETCH_ASSOC);

            // create formatted data variable
            $formattedData = [];

            // loop through data and add link prefixes
            foreach($data as $item){
                $item['thumbnail'] = $this->configuration['asset_link'] . $item['thumbnail'];

                // add formatted item in new array
                array_push($formattedData, $item);
            }

        }catch (PDOException $e){
            $formattedData = [];
            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get recepies list mapper: " . $e->getMessage());
        }

        // return data
        return $formattedData;
    }


    /**
     * Get recepies by language and state
     * @param Recepie $recepie
     * @return RecepieCollection
     */
    public function getRecepies(Recepie $recepie):RecepieCollection {

        // create response object
        $recepieCollection = new RecepieCollection();

        try {
            // set database instructions
            $sql = "SELECT 
                        r.id,
                        r.state,
                        r.behavior,
                        rt.title,
                        rt.text,
                        rt.thumbnail,
                        rt.language,
                        GROUP_CONCAT(rta.tag_id) as tags
                    FROM recepie AS r
                    LEFT JOIN recepie_text AS rt ON r.id = rt.recepie_parent
                    LEFT JOIN recepie_tags AS rta ON r.id = rta.recepie_parent
                    WHERE r.state = ?
                    AND rt.language = ?
                    GROUP BY r.id";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $recepie->getState(),
                $recepie->getLang()
            ]);

            // fetch recepies data
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

            // loop through data, set recepie values and add recepie to recepie collection
            foreach($rows as $row) {
                // create recepie
                $recepie = new Recepie();

                // set recepie values
                $recepie->setId($row['id']);
                $recepie->setLang($row['language']);
                $recepie->setState($row['state']);
                $recepie->setTitle($row['title']);
                $recepie->setText($row['text']);
                $recepie->setThumbnail($this->configuration['asset_link'] . $row['thumbnail']);
                $recepie->setBehavior($row['behavior']);
                $recepie->setTags($row['tags']);

                // add recepie to recepie collection
                $recepieCollection->addEntity($recepie);
            }

            // set status according to results of previous actions
            if($statement->rowCount() == 0){
                $recepieCollection->setStatusCode(204);
            }else {
                $recepieCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $recepieCollection->setStatusCode(204);

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get recepies mapper: " . $e->getMessage());
        }

        // return data
        return $recepieCollection;
    }


    /**
     * Search recepies by term
     *
     * @param Recepie $recepie
     * @return RecepieCollection
     */
    public function searchRecepies(Recepie $recepie):RecepieCollection {

        // create response object
        $recepieCollection = new RecepieCollection();

        try {
            // set database instructions
            $sql = "SELECT 
                        r.id,
                        r.state,
                        r.behavior,
                        rt.title,
                        rt.text,
                        rt.thumbnail,
                        rt.language,
                        GROUP_CONCAT(rta.tag_id) as tags
                    FROM recepie AS r
                    LEFT JOIN recepie_text AS rt ON r.id = rt.recepie_parent
                    LEFT JOIN recepie_tags AS rta ON r.id = rta.recepie_parent
                    WHERE rt.language = ?
                    AND r.state = ?
                    AND rt.title LIKE ?
                    GROUP BY r.id";
            $title = '%' . $recepie->getLike() . '%';
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $recepie->getLang(),
                $recepie->getState(),
                $title
            ]);

            // fetch recepie data
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

            // loop through data, set recepie values and add recepie to recepie collection
            foreach($rows as $row) {
                // create entity
                $recepie = new Recepie();

                // set recepie values
                $recepie->setId($row['id']);
                $recepie->setLang($row['language']);
                $recepie->setState($row['state']);
                $recepie->setTitle($row['title']);
                $recepie->setText($row['text']);
                $recepie->setThumbnail($this->configuration['asset_link'] . $row['thumbnail']);
                $recepie->setBehavior($row['behavior']);
                $recepie->setTags($row['tags']);

                // add to collection
                $recepieCollection->addEntity($recepie);
            }

            // set response according to results of previous actions
            if($statement->rowCount() == 0){
                $recepieCollection->setStatusCode(204);
            }else {
                $recepieCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $recepieCollection->setStatusCode(204);

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Search recepies mapper: " . $e->getMessage());
        }

        // return data
        return $recepieCollection;
    }


    /**
     * Get recepies by ids
     *
     * @param Recepie $recepie
     * @return RecepieCollection
     */
    public function getRecepiesById(Recepie $recepie):RecepieCollection {

        // Create response object
        $recepiesCollection = new RecepieCollection();

        // call helper function for transformin array of ids into comma separated string
        $whereIn = $this->sqlHelper->whereIn($recepie->getIds());

        try {
            // set database instructions
            $sql = "SELECT
                        r.id,
                      
                        r.version,
                        r.state,
                        r.behavior,
                        rt.title,
                        rt.text,
                        rt.thumbnail,
                        rt.language,
                        GROUP_CONCAT(rta.tag_id) as tags
                    FROM recepie AS r
                    LEFT JOIN recepie_text AS rt ON rt.recepie_parent = r.id
                    LEFT JOIN recepie_tags AS rta ON r.id = rta.recepie_parent
                    WHERE r.id IN(".$whereIn.")
                    AND r.state = ?
                    AND rt.language = ?
                    GROUP BY r.id";
            $statement = $this->connection->prepare($sql);
            $statement->execute(
                [
                    $recepie->getState(),
                    $recepie->getLang()
                ]
            );

            // fetch recepies data
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

            // loop through data, set recepie values and add recepie to recepie collection
            foreach($rows as $row) {
                // create new entity
                $recepie = new Recepie();

                // set recepie values
                $recepie->setId($row['id']);
                $recepie->setLang($row['language']);
                $recepie->setState($row['state']);
                $recepie->setTitle($row['title']);
                $recepie->setText($row['text']);
                $recepie->setThumbnail($this->configuration['asset_link'] . $row['thumbnail']);
                $recepie->setBehavior($row['behavior']);
                $recepie->setVersion($row['version']);
                $recepie->setTags($row['tags']);

                // add recepie to the collection
                $recepiesCollection->addEntity($recepie);
            }

            // set response status according to results of previous actions
            if($statement->rowCount() == 0){
                $recepiesCollection->setStatusCode(204);
            }else {
                $recepiesCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $recepiesCollection->setStatusCode(204);

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get recepies by ids mapper: " . $e->getMessage());
        }

        // return data
        return $recepiesCollection;
    }


    /**
     * Delete recepie mapper
     *
     * @param Recepie $recepie
     * @return Shared
     */
    public function deleteRecepie(Recepie $recepie):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // set database instructions
            $sql = "DELETE 
                        r.*,
                        rt.*,
                        ra.*,
                        rta.*,
                        rti.*
                    FROM recepie AS r 
                    LEFT JOIN recepie_text AS rt ON r.id = rt.recepie_parent
                    LEFT JOIN recepie_audit AS ra ON r.id = ra.recepie_parent
                    LEFT JOIN recepie_text_audit AS rta ON rt.id = rta.recepie_text_parent    
                    LEFT JOIN recepie_tags AS rti ON r.id = rti.recepie_parent
                    WHERE r.id = ?
                    AND r.state != 'R'";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $recepie->getId()
            ]);

            // set response status according to result of the query
            if($statement->rowCount() > 0){
                $shared->setResponse([200]);
            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback evertyhing in case of any failure
            $this->connection->rollBack();
            $shared->setResponse([304]);
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Delete recepie mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Release recepie
     *
     * @param Recepie $recepie
     * @return Shared
     */
    public function releaseRecepie(Recepie $recepie):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // set database instructions
            $sql = "UPDATE 
                      recepie  
                    SET state = 'R'
                    WHERE id = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $recepie->getId()
            ]);

            // set response values
            if($statement->rowCount() > 0){
                // set response status
                $shared->setResponse([200]);

                // get latest version value
                $version = $this->lastVersion();

                // set new version of the recepie
                $sql = "UPDATE recepie SET version = ? WHERE id = ?";
                $statement = $this->connection->prepare($sql);
                $statement->execute(
                    [
                        $version,
                        $recepie->getId()
                    ]
                );

            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of failure
            $this->connection->rollBack();
            $shared->setResponse([304]);
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Release recepie mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Add recepie
     *
     * @param Recepie $recepie
     * @return Shared
     */
    public function createRecepie(Recepie $recepie):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // get version
            $version = $this->lastVersion();

            // set database instructions for main recepie table
            $sql = "INSERT INTO
                      recepie (state, behavior, version)
                    VALUES (?,?,?)";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                'P',
                $recepie->getBehavior(),
                $version
            ]);

            // insert recepie data into appropriate tables
            if($statement->rowCount() > 0){
                // get last inserted id for the value of recepie_parent
                $lastId = $this->connection->lastInsertId();

                // set database instructions
                $sql = "INSERT INTO
                          recepie_text (title, text, language, thumbnail, recepie_parent)
                        VALUES (?,?,?,?,?)";
                $statement = $this->connection->prepare($sql);

                // loop through given data
                $titles = $recepie->getNames();
                $texts = $recepie->getRecipieContent();
                for($i = 0; $i < count($titles); $i++){

                    // get title and text values
                    $title = $titles[$i]->getName();
                    $text = $texts[$i];

                    // insert values into database
                    $statement->execute([
                        $title['name'],
                        $text->getRecipieContent(),
                        $title['lang'],
                        $recepie->getThumbnail(),
                        $lastId
                    ]);
                }

                // set database instructions for recepie tags
                $sql = "INSERT INTO
                          recepie_tags (tag_id, recepie_parent)
                        VALUES (?,?)";
                $statement = $this->connection->prepare($sql);
                $tags = $recepie->getTags();

                // insert tag ids
                foreach($tags as $tag){
                    $statement->execute([
                        $tag,
                        $lastId
                    ]);
                }

                // set response
                $shared->setResponse([200]);

            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Create recepie mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Edit recepie
     *
     * @param Recepie $recepie
     * @return Shared
     */
    public function editRecepie(Recepie $recepie):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // set database instructions for updating behavior of the recepie
            $sql = "UPDATE recepie SET behavior = ? WHERE id = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $recepie->getBehavior(),
                $recepie->getId()
            ]);

            // if behavior is changed, update version
            if($statement->rowCount() > 0){
                // get last version
                $lastVersion = $this->lastVersion();

                // set database instructions
                $sql = "UPDATE recepie SET version = ? WHERE id = ?";
                $statement = $this->connection->prepare($sql);
                $statement->execute([
                    $lastVersion,
                    $recepie->getId()
                ]);
            }


            // first delete tag ids
            $sqlDelete = "DELETE FROM recepie_tags WHERE recepie_parent = ?";
            $statementDelete = $this->connection->prepare($sqlDelete);
            $statementDelete->execute([
                $recepie->getId()
            ]);

            // set database instructions for updating titles and texts
            $sql = "INSERT INTO
                        recepie_text (recepie_parent, title, text, thumbnail, language)
                        VALUES (?,?,?,?,?)
                    ON DUPLICATE KEY
                    UPDATE
                        title = VALUES(title),
                        text = VALUES(text),
                        thumbnail = VALUES(thumbnail),
                        language = VALUES(language)";
            $statement = $this->connection->prepare($sql);

            // get titles and texts
            $titles = $recepie->getNames();
            $texts = $recepie->getRecipieContent();

            // loop through given data
            for($i = 0; $i < count($titles); $i++){
                // extract title and text
                $title = $titles[$i]->getName();
                $text = $texts[$i];

                // insert data
                $statement->execute([
                    $recepie->getId(),
                    $title['name'],
                    $text->getRecipieContent(),
                    $recepie->getThumbnail(),
                    $title['lang']
                ]);
            }

            // set database instructions for inserting or updating tags
            $sql = "INSERT INTO
                        recepie_tags (recepie_parent, tag_id)
                        VALUES (?,?)
                    ON DUPLICATE KEY
                    UPDATE
                        recepie_parent = VALUES(recepie_parent),
                        tag_id = VALUES(tag_id)";
            $statement = $this->connection->prepare($sql);
            $tags = $recepie->getTags();
            foreach($tags as $tag){
                $statement->execute([
                    $recepie->getId(),
                    $tag
                ]);
            }

            // set response
            $shared->setResponse([200]);

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of any failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Edit recepie mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Get last version number
     *
     * @return string
     */
    public function lastVersion(){
        // set database instructions
        $sql = "INSERT INTO version VALUES(null)";
        $statement = $this->connection->prepare($sql);
        $statement->execute([]);

        // fetch id
        $lastId = $this->connection->lastInsertId();

        // return last id
        return $lastId;
    }


    /**
     * Get total number of recepies
     *
     * @return mixed
     */
    public function getTotal() {

        try {
            // set database instructions
            $sql = "SELECT COUNT(*) as total FROM recepie";
            $statement = $this->connection->prepare($sql);
            $statement->execute();

            // set total number
            $total = $statement->fetch(PDO::FETCH_ASSOC)['total'];

        }catch(PDOException $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get total recepies mapper: " . $e->getMessage());
        }

        // return data
        return $total;
    }

}