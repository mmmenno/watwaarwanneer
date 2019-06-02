<?php
class AdamlinkMapper {

	public function getEvent($id) {

		$sparqlquery = '
			PREFIX foaf: <http://xmlns.com/foaf/0.1/>
			PREFIX dc: <http://purl.org/dc/elements/1.1/>
			PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
			PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
			PREFIX geo: <http://www.opengis.net/ont/geosparql#>
			PREFIX wd: <http://www.wikidata.org/entity/>
			PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>
			PREFIX dbo: <http://dbpedia.org/ontology/>
			PREFIX www: <https://watwaarwanneer.info/event/>
			SELECT ?actor ?actorlabel ?actorrole ?actordescription ?actorpage ?type ?typelabel ?label ?begin ?end ?place ?placelabel ?placegeom ?cho ?img ?title ?description ?article WHERE {
			  www:' . $id . ' rdfs:label ?label .
			  www:' . $id . ' sem:hasEarliestBeginTimeStamp ?begin .
			  www:' . $id . ' sem:hasLatestEndTimeStamp ?end .
			  www:' . $id . ' sem:hasPlace ?place .
			  www:' . $id . ' sem:eventType ?type .
			  ?type rdfs:label ?typelabel .
			  OPTIONAL{ 
			  	?cho dc:subject www:' . $id . ' .
			  	OPTIONAL{ 
			  		?cho foaf:depiction ?img .
			  	}
			  	OPTIONAL{ 
				  	?cho dc:title ?title .
				  	?cho dc:description ?description .
				  }
			  }
			  ?place rdfs:label ?placelabel .
			  OPTIONAL{ 
			  	?place geo:hasGeometry/geo:asWKT ?placegeom .
			  }
			  OPTIONAL{ 
			  	?place foaf:isPrimaryTopicOf ?article .
			  }
			  OPTIONAL{ 
			  	www:' . $id . ' sem:hasActor ?actorbn .
			  	?actorbn rdf:value ?actor .
			  	?actor rdfs:label ?actorlabel .
			  	OPTIONAL{
			  		?actor dc:description ?actordescription .
			  	}
			  	OPTIONAL{
			  		?actorbn dbo:role ?actorrole .
			  	}
			  	OPTIONAL{
			  		?actor foaf:isPrimaryTopicOf ?actorpage .
			  	}
			  }
			  
			} 
			LIMIT 200
		';

		$url = "https://api.druid.datalegend.net/datasets/adamnet/all/services/endpoint/sparql?query=" . urlencode($sparqlquery) . "";

		$querylink = "https://druid.datalegend.net/AdamNet/all/sparql/endpoint#query=" . urlencode($sparqlquery) . "&endpoint=https%3A%2F%2Fdruid.datalegend.net%2F_api%2Fdatasets%2FAdamNet%2Fall%2Fservices%2Fendpoint%2Fsparql&requestMethod=POST&outputFormat=table";


		// Druid does not like url parameters, send accept header instead
		$opts = [
		    "http" => [
		        "method" => "GET",
		        "header" => "Accept: application/sparql-results+json\r\n"
		    ]
		];
		$context = stream_context_create($opts);
		$json = file_get_contents($url, false, $context);
		$data = json_decode($json,true);

		// fill array
        $values = array("venues"=>array(),"imgs"=>array(),"actors"=>array());
        $rec = $data['results']['bindings'][0];
        $values['event'] = array(
			"label" => $rec['label']['value'],
	        "type" => $rec['type']['value'],
	        "typelabel" => $rec['typelabel']['value'],
			"begin" => $rec['begin']['value'],
			"end" => $rec['end']['value']
		);
        $beenthere = array();
        foreach ($data['results']['bindings'] as $rec) {
        	if(!in_array($rec['place']['value'], $beenthere)){
        		$points = str_replace(array("Point(",")"),"",$rec['placegeom']['value']);
        		$latlon = explode(" ", $points);
		    	$values['venues'][] = array(
		    		"place" => $rec['place']['value'],
		    		"placelabel" => $rec['placelabel']['value'],
		    		"lat" => $latlon[1],
		    		"lon" => $latlon[0],
		    		"article" => $rec['article']['value']
		    	);
        	}
        	$beenthere[]=$rec['place']['value'];
        }
        $beenthere = array();
        foreach ($data['results']['bindings'] as $rec) {
        	if(strlen($rec['label']['value']) && !in_array($rec['img']['value'], $beenthere)){
	        	$values["imgs"][] = array(
	        		"begin" => $rec['begin']['value'],
	        		"end" => $rec['end']['value'],
	        		"cho" => $rec['cho']['value'],
	        		"img" => $rec['img']['value'],
	        		"title" => $rec['title']['value'],
	        		"description" => $rec['description']['value']
	        	);
	        	$beenthere[]=$rec['img']['value'];
	        }
        }
        $beenthere = array();
        foreach ($data['results']['bindings'] as $rec) {
        	if(strlen($rec['actor']['value']) && !in_array($rec['actor']['value'], $beenthere)){
	        	$values["actors"][] = array(
	        		"actorlabel" => $rec['actorlabel']['value'],
	        		"actor" => $rec['actor']['value'],
	        		"actorrole" => $rec['actorrole']['value'],
	        		"actorpage" => $rec['actorpage']['value'],
	        		"actordescription" => $rec['actordescription']['value']
	        	);
	        	$beenthere[]=$rec['actor']['value'];
	        }
        }
        return $values;
    }

}