<?php

namespace OCA\Chat\OCH\Commands;

use \OCA\Chat\OCH\ChatAPI;
use \OCA\Chat\OCH\Db\Conversation;
use \OCA\Chat\OCH\Db\ConversationMapper;
use \OCA\Chat\OCH\Commands\Join;
use \OCA\Chat\OCH\Commands\Invite;

class StartConv extends ChatAPI {
    
    public function setRequestData(array $requestData) {
        $this->requestData = $requestData;
    }
    
    public function execute(){
        
        // (1) generate a conv id
        $id = $this->generateConvId(array(
            $this->requestData['user']['backends']['och']['value'], 
            $this->requestData['user_to_invite']['backends']['och']['value'])
        );

        // (2) check if conv id exists
        $convMapper = new ConversationMapper($this->api);
        if($convMapper->exists($id)){
            // (3) join the already existing conv
            $join = new Join($this->api);
            $this->requestData['conv_id'] = $id;
            $join->setRequestData($this->requestData);
            $join->execute();
            
             // (5) invite the user_to_invite since we just created the conv
            $invite = new Invite($this->api);
            $invite->setRequestData($this->requestData);
            $invite->execute();
            
        } else {
            // (3) Create the conv
            $conversation = new Conversation();
            $conversation->setConversationId($id);
            $mapper = new ConversationMapper($this->api); 
            $mapper->insert($conversation);
           
            // (4) join the just created conv
            $join = new Join($this->api);
            $this->requestData['conv_id'] = $id;
            $join->setRequestData($this->requestData);
            $join->execute();
            
            // (5) invite the user_to_invite since we just created the conv
            $invite = new Invite($this->api);
            $invite->setRequestData($this->requestData);
            $invite->execute();
        }
        return array("conv_id" => $id);
        
    }
    
    private function generateConvId($users){
        
        $id = '';
        foreach($users as $user){
            $id .= $user;
        }
        
        $id = str_split($id);
        sort($id);
        $id = implode($id);
        
        return $id;
        
    }
    
}
