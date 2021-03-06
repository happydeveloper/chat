<?php
namespace OCA\Chat\Core;

use \OCA\Chat\Db\Backend;
use \OCA\Chat\Db\BackendMapper;
use \OCP\AppFramework\IAppContainer;

class AppApi {
	
    protected $app;

    public function __construct(IAppContainer $app){
        $this->app = $app;
    }

    public function registerBackend($displayName, $name, $protocol, $enabled){
        $backendMapper = new BackendMapper($this->app->getCoreApi());
        if($backendMapper->exists($name)){
            // Only execute when there are no backends registered i.e. on first run
            $backend = new Backend();
            $backend->setDisplayname($displayName);
            $backend->setName($name);
            $backend->setProtocol($protocol);
            $backend->setEnabled($enabled);
            $backendMapper->insert($backend);
        }
    }

    public function getEnabledBackends(){
        $backendMapper = new BackendMapper($this->app->getCoreApi());
        return $backendMapper->getAll();
    }

    public function getContacts(){
        $cm = \OC::$server->getContactsManager();
        // The API is not active -> nothing to do
        if (!$cm->isEnabled()) {
            $receivers = null;
            $error = 'Please enable the contacts app.';
        }

        $result = $cm->search('',array('FN'));
        $receivers = array();
        $contactList = array();
        list($addressBookBackend, $addressBookId) = explode(':', $result['key']);
        foreach ($result as $r) {
            $data = array();

            $contactList[] = $r['FN'];

            $data['id'] = $r['id'];
            $data['displayname'] = $r['FN'];

            $data['backends'] =  $this->contactBackendToBackend($r['EMAIL'], $r['IMPP']);
            $data['address_book_id'] = $addressBookId;
            $data['address_book_backend'] = $addressBookBackend;			
            $receivers[] = $data;
        }
        return array('contacts' => $receivers, 'contactsList' => $contactList);
    }

    public function getBackends(){
        $backendMapper = new BackendMapper($this->app->getCoreApi());
        $backends = $backendMapper->getAllEnabled();

        $result = array();
        foreach($backends as $backend){
            $result[$backend->getName()] = $backend;
        }

        return $result;
    }

    private function contactBackendToBackend($emails, $IMPPS){
        /*
         * backends : [
         *   0 : {
         *     id : 0,1,2
         *     displayname : "ownCloud Handle",
         *     protocol : "x-owncloud-handle" ,
         *     namespace : "och",
         *     value : "derp" // i.e. the owncloud username
         *   },
         *   1 {
         *     id : null,
         *     displayname : "E-mail",
         *     protocl : "email",
         *     namespace : "email",
         *     value : "name@domain.tld"
         *   }
         * ]
         */
        $backends = array();

        if(is_array($emails)){
            $backend = array();
            $backend['id'] = null;
            $backend['displayname'] = 'E-mail';
            $backend['protocol'] = 'email';
            $backend['namespace'] = ' email';
            $backend['value'] = array($emails);		
            $backends['email'] = $backend;
        }

        if(isset($IMPPS)){
            foreach($IMPPS as $IMPP){
                $backend = array();
                $exploded = explode(":", $IMPP);
                $info = $this->getBackendInfo($exploded[0]);
                $backend['id'] = null;
                $backend['displayname'] = $info['displayname'];
                $backend['protocol'] = $exploded[0];
                $backend['namespace'] = $info['namespace'];
                $backend['value'] = $exploded[1];
                $backends[$info['namespace']] = $backend;
            }
        }

        return $backends;
    }

    private function getBackendInfo($protocol){
        $backendMapper = new BackendMapper($this->app->getCoreApi());
        $backend = $backendMapper->findByProtocol($protocol);
        $info = array();
        $info['displayname'] = $backend->getDisplayname();
        $info['namespace'] = $backend->getName(); // TODO change name to namespace
        return $info;
    }

    public function getCurrentUser(){
        $cm = \OC::$server->getContactsManager();
        // The API is not active -> nothing to do
        if (!$cm->isEnabled()) {
            $receivers = null;
            $error = 'Please enable the contacts app.';
        }

        $result = $cm->search(\OCP\User::getDisplayName(), array('FN'));
        list($addressBookBackend, $addressBookId) = explode(':', $result['key']);
        $r = $result[0];
        $data = array();
        $data['id'] = $r['id'];
        $data['displayname'] = $r['FN'];
        $data['backends'] =  $this->contactBackendToBackend($r['EMAIL'], $r['IMPP']);
        $data['address_book_id'] = $addressBookId;
        $data['address_book_backend'] = $addressBookBackend;
        return $data;
    }
}