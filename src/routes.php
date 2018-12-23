<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes


$app->get('/', function (Request $request, Response $response, $args) {

	$mapper = new TimelineMapper($this->pdo);
    $events = $mapper->getEvents();

    for($i=0; $i<count($events); $i++){
    	$period = new Time($events[$i]['start'], $events[$i]['end']);
    	$hrperiod = $period->humanreadable();
    	$events[$i]['time'] = $hrperiod;
    }

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
	$location = $mapper->getLocation($event['location']);

	$larger = array("Q515","Q486972","Q532");
	if(in_array($location['class'],$larger)){
		$location['zoom'] = 11;
	}else{
		$location['zoom'] = 17;
	}

	$period = new Time($event['start'], $event['end']);
	$hrperiod = $period->humanreadable();
	$event['time'] = $hrperiod;

	$data = array("event"=>$event,"chobjects"=>$chobjects,"actors"=>$actors,"location"=>$location);
	
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

	$period = new Time($event['start'], $event['end']);
	$ttlperiod = $period->turtle();


	$turtle = "<https://watwaarwanneer.info/event/" . $event['id'] . ">\n";
	$turtle .= "\tsem:eventType wd:" . $event['typewdid'] . " ;\n";
	$turtle .= "\trdfs:label \"" . str_replace("\"", "'", $event['title']) . "\" ;\n";
	$turtle .= $ttlperiod;
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


$app->get('/datamagic/batch', function (Request $request, Response $response, $args) {

	$mapper = new TimelineMapper($this->pdo);
    $events = $mapper->getIncoming(100);

    $eventtypes = array();
    $actors = array();
    $locations = array();

    
    // get wikidata ids
    foreach ($events as $k => $event) {

	    // event type
	    $eventtypevalues = explode("|",$event['type']);
    	foreach ($eventtypevalues as $eventtypevalue) {
    		if(trim($eventtypevalue)!=""){
    			$eventtypes[] = trim($eventtypevalue);
    		}
    	}

    	// actors
    	$actorvalues = explode("|",$event['actor']);
    	foreach ($actorvalues as $actorvalue) {
    		if(trim($actorvalue)!=""){
    			$actors[] = trim($actorvalue);
    		}
    	}

    	// location
    	$locationvalues = explode("|",$event['location']);
    	foreach ($locationvalues as $locationvalue) {
    		if(trim($locationvalue)!=""){
    			$locations[] = trim($locationvalue);
    		}
    	}
    }

    // get additional info from wikidata
    $wiki = new Wikidata($eventtypes);
	$eventtypes = $wiki->getEventtypesdata();

	$wiki = new Wikidata($actors);
	$actors = $wiki->getActorsdata();

	$wiki = new Wikidata($locations);
	$locations = $wiki->getLocationsdata();

	 // insert events
	foreach ($events as $k => $event) {

		// event itself
    	// todo: check if existing event (if title is watwaarwanner.info uri)
    	$stmt = $this->pdo->prepare(
	        "INSERT INTO events (title,type,start,end,fictional,wdid,wdid_broader,location) VALUES (
	        :ti,:ty,:st,:en,:fi,:wd,:wdb,:lo)"
	    );
	    $stmt->execute([
	        ':ti' => $event['title'],
	        ':ty' => $event['type'],
	        ':st' => $event['start'],
	        ':en' => $event['end'],
	        ':fi' => $event['fictional'],
	        ':wd' => $event['wdid'],
	        ':wdb' => $event['wdid_broader'],
	        ':lo' => $event['location']
	    ]);
	    $eventid = $this->pdo->lastInsertId();

	    // event type
	    $typevalues = explode("|",$event['type']);
    	$types = array();
    	foreach ($typevalues as $typevalue) {
    		if(trim($typevalue)!=""){
    			$types[] = trim($typevalue);
    		}
    	}
    	foreach ($types as $wdid) {
			$stmt = $this->pdo->prepare(
		        "INSERT INTO event_x_eventtype (event_id,eventtype_wdid) VALUES (
		        :ev,:wd)"
		    );
		    $stmt->execute([
		        ':ev' => $eventid,
		        ':wd' => $wdid
		    ]);
		    $stmt = $this->pdo->prepare(
		        "INSERT INTO eventtypes 
		        	(wdid,label,description) 
		        VALUES 
		        	(:wd,:lb,:ds)
		        ON DUPLICATE KEY UPDATE
		        	label = VALUES(label),
		        	description = VALUES(description)"
		    );
		    $stmt->execute([
		        ':wd' => $wdid,
		        ':lb' => $eventtypes[$wdid]['label'],
		        ':ds' => $eventtypes[$wdid]['description']
		    ]);
    	}

    	// location
    	if($event['location']!=""){
		    $stmt = $this->pdo->prepare(
		        "INSERT INTO locations 
		        	(wdid,label,description,class,classlabel,municipality,municipalitylabel,province,provincelabel,lat,lon,wikipedia) 
		        VALUES 
		        	(:wd,:lb,:ds,:c,:cl,:m,:ml,:p,:pl,:lat,:lon,:wp)
		        ON DUPLICATE KEY UPDATE
		        	label = VALUES(label),
		        	class = VALUES(class),
		        	description = VALUES(description),
		        	classlabel = VALUES(classlabel),
		        	municipality = VALUES(municipality),
		        	municipalitylabel = VALUES(municipalitylabel),
		        	province = VALUES(province),
		        	provincelabel = VALUES(provincelabel),
		        	lat = VALUES(lat),
		        	lon = VALUES(lon),
		        	wikipedia = VALUES(wikipedia)"
		    );
		    $stmt->execute([
		        ':wd' => $event['location'],
		        ':lb' => $locations[$event['location']]['label'],
		        ':ds' => $locations[$event['location']]['description'],
		        ':c' => $locations[$event['location']]['class'],
		        ':cl' => $locations[$event['location']]['classLabel'],
		        ':m' => $locations[$event['location']]['municipality'],
		        ':ml' => $locations[$event['location']]['municipalityLabel'],
		        ':p' => $locations[$event['location']]['province'],
		        ':pl' => $locations[$event['location']]['provinceLabel'],
		        ':lat' => $locations[$event['location']]['lat'],
		        ':lon' => $locations[$event['location']]['lon'],
		        ':wp' => $locations[$event['location']]['wikipedia']
		    ]);
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
    	$actorids = array();
    	foreach ($actorvalues as $actorid) {
    		if(trim($actorid)!=""){
    			$actorids[] = trim($actorid);
    		}
    	}

    	foreach ($actorids as $wdactor) {
			
    		$stmt = $this->pdo->prepare(
		        "INSERT INTO actors 
		        	(wdid,label,description,wikipedia) 
		        VALUES 
		        	(:wd,:lb,:ds,:wp)
		        ON DUPLICATE KEY UPDATE
		        	label = VALUES(label),
		        	description = VALUES(description),
		        	wikipedia = VALUES(wikipedia)"
		    );
		    $stmt->execute([
		        ':wd' => $wdactor,
		        ':lb' => $actors[$wdactor]['label'],
		        ':ds' => $actors[$wdactor]['description'],
		        ':wp' => $actors[$wdactor]['wikipedia']
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
});




