<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes


$app->get('/', function (Request $request, Response $response, $args) {

	
	$mapper = new TimelineMapper($this->pdo);
    $events = $mapper->getEvents();

    $response = $this->view->render(
        $response,
        'timeline.twig',
        ["events"=>$events]
    );
    return $response;
})->setName('home');


$app->get('/parts/presentation/{id}', function (Request $request, Response $response, $args) {

	$mapper = new TimelineMapper($this->pdo);
    $event = $mapper->getEvent($args['id']);
	$chobjects = $mapper->getChos($args['id']);
	$actors = $mapper->getActors($args['id']);

	$data = array("event"=>$event,"chobjects"=>$chobjects,"actors"=>$actors);
	
	$response = $this->view->render(
        $response,
        'presentation.twig',
        [
        	"data"=>$data
    	]
    );
    return $response;
});


$app->get('/parts/data/{id}', function (Request $request, Response $response, $args) {

	$mapper = new TimelineMapper($this->pdo);
    $event = $mapper->getEvent($args['id']);
	$chobjects = $mapper->getChos($args['id']);
	$actors = $mapper->getActors($args['id']);


	$turtle = "<https://watwaarwanneer.info/event/" . $event['id'] . ">\n";
	$turtle .= "\tsem:eventType wd:" . $event['typewdid'] . " ;\n";
	$turtle .= "\trdfs:label \"" . addslashes($event['title']) . "\" ;\n";
	$turtle .= "\tsem:hasBeginTimeStamp \"" . addslashes($event['start']) . "\"^^xsd:date ;\n";
	$turtle .= "\tsem:hasEndTimeStamp \"" . addslashes($event['end']) . "\"^^xsd:date ;\n";
	if(count($actors)){
		$turtle .= "\tsem:hasActor ";
		$wdids = array();
		foreach($actors as $actor){
			$wdids[] = "wd:" . $actor['wdid'];
		}
		$turtle .= implode(", ", $wdids) . " ;\n";
	}
	$turtle .= "\trdf:type sem:Event .\n\n";
	
	$turtle .= "# type\n";
	$turtle .= "wd:" . $event['typewdid'] . "\n";
	if($event['typedescription']!=""){
		$turtle .= "\tdc:description \"" . addslashes($event['typedescription']) . "\" ;\n";
	}
	$turtle .= "\trdfs:label \"" . addslashes($event['typelabel']) . "\" ;\n";
	$turtle .= "\trdf:type sem:EventType .\n\n";

	$turtle .= "# actors\n";
	foreach($actors as $actor){
		$turtle .= "wd:" . $actor['wdid'] . "\n";
		$turtle .= "\trdfs:label \"" . addslashes($actor['label']) . "\" ;\n";
		if($actor['description']!=""){
			$turtle .= "\tdc:description \"" . addslashes($actor['description']) . "\" ;\n";
		}
		if($actor['wikipedia']!=""){
			$turtle .= "\tfoaf:isPrimaryTopicOf \"" . addslashes($actor['wikipedia']) . "\" ;\n";
		}
		$turtle .= "\trdf:type sem:Actor .\n\n";
	}
	
	


	
	$data = array("event"=>$event,"turtle"=>$turtle);
	
	$response = $this->view->render(
        $response,
        'data.twig',
        [
        	"data"=>$data
    	]
    );
    return $response;
});


$app->get('/datamagic/new', function (Request $request, Response $response, $args) {

	$mapper = new TimelineMapper($this->pdo);
    $events = $mapper->getIncoming();

    foreach ($events as $k => $event) {
    	echo "processing event " . $event['title'] . "\n";
    	// event itself
    	// todo: check if existing event (if title is watwaarwanner.info uri)
    	$stmt = $this->pdo->prepare(
	        "INSERT INTO events (title,type,start,end,fictional,wdid,wdid_broader) VALUES (
	        :ti,:ty,:st,:en,:fi,:wd,:wdb)"
	    );
	    $stmt->execute([
	        ':ti' => $event['title'],
	        ':ty' => $event['type'],
	        ':st' => $event['start'],
	        ':en' => $event['end'],
	        ':fi' => $event['fictional'],
	        ':wd' => $event['wdid'],
	        ':wdb' => $event['wdid_broader']
	    ]);
	    $eventid = $this->pdo->lastInsertId();

	    // event type
	    $eventtypevalues = explode("|",$event['type']);
    	$eventtypes = array();
    	foreach ($eventtypevalues as $eventtypevalue) {
    		if(trim($eventtypevalue)!=""){
    			$eventtypes[] = trim($eventtypevalue);
    		}
    	}
    	foreach ($eventtypes as $wdeventtype) {
			$existingeventtypes = $mapper->getEventtype($wdeventtype);
    		
			if(count($existingeventtypes)){
				$stmt = $this->pdo->prepare(
			        "INSERT INTO event_x_eventtype (event_id,eventtype_wdid) VALUES (
			        :ev,:wd)"
			    );
			    $stmt->execute([
			        ':ev' => $eventid,
			        ':wd' => $existingeventtypes[0]['wdid']
			    ]);
			}else{
				$wiki = new Wikidata($wdeventtype);
				$eventtypedata = $wiki->getEventtypedata();
				
				$stmt = $this->pdo->prepare(
			        "INSERT INTO eventtypes (wdid,label,description) VALUES (
			        :wd,:lb,:ds)"
			    );
			    $stmt->execute([
			        ':wd' => $wdeventtype,
			        ':lb' => $eventtypedata['label'],
			        ':ds' => $eventtypedata['description']
			    ]);
			    $stmt = $this->pdo->prepare(
			        "INSERT INTO event_x_eventtype (event_id,eventtype_wdid) VALUES (
			        :ev,:wd)"
			    );
			    $stmt->execute([
			        ':ev' => $eventid,
			        ':wd' => $wdeventtype
			    ]);
			}
		}

    	// cho
    	$chopids = explode("|",$event['cho_pid']);
    	$pids = array();
    	foreach ($chopids as $pid) {
    		$pids[] = trim($pid);
    	}

    	$images = explode("|",$event['cho_img']);
    	$imgs = array();
    	foreach ($images as $img) {
    		$imgs[] = trim($img);
    	}

    	for($i=0; $i<count($pids); $i++){
    		if(!isset($pids[$i]) || !isset($imgs[$i]) || $imgs[$i]=="" || $pids[$i]==""){
    			echo "count of cho's and imgs not matching in " . $event['title'];
    			die;
    		}
			$stmt = $this->pdo->prepare(
		        "INSERT INTO chobjects (event_id,cho,img,provider) VALUES (
		        :ev,:cho,:img,:pr)"
		    );
		    $stmt->execute([
		        ':ev' => $eventid,
		        ':cho' => $pids[$i],
		        ':img' => $imgs[$i],
		        ':pr' => $event['provider']
		    ]);
		}

    	// actors
    	$actorvalues = explode("|",$event['actor']);
    	$actors = array();
    	foreach ($actorvalues as $actorvalue) {
    		if(trim($actorvalue)!=""){
    			$actors[] = trim($actorvalue);
    		}
    	}
    	
    	foreach ($actors as $wdactor) {
			$existingactors = $mapper->getActor($wdactor);
			
			if(count($existingactors)){
				$stmt = $this->pdo->prepare(
			        "INSERT INTO event_x_actor (event_id,actor_wdid) VALUES (
			        :ev,:wd)"
			    );
			    $stmt->execute([
			        ':ev' => $eventid,
			        ':wd' => $wdactor
			    ]);
			}else{
				$wiki = new Wikidata($wdactor);
				$actordata = $wiki->getActordata();
				print_r($actordata);
				$stmt = $this->pdo->prepare(
			        "INSERT INTO actors (wdid,label,description,wikipedia) VALUES (
			        :wd,:lb,:ds,:wp)"
			    );
			    $stmt->execute([
			        ':wd' => $wdactor,
			        ':lb' => $actordata['label'],
			        ':ds' => $actordata['description'],
			        ':wp' => $actordata['wikipedia']
			    ]);
			    $stmt = $this->pdo->prepare(
			        "INSERT INTO event_x_actor (event_id,actor_wdid) VALUES (
			        :ev,:wd)"
			    );
			    $stmt->execute([
			        ':ev' => $eventid,
			        ':wd' => $wdactor
			    ]);
			}
		}

    	// location

    	//print_r($event);
    }
    

    // 

    die;

    $status = array("status"=>"succes");
    header('Content-Type: application/json');
    die(json_encode($status));
});


