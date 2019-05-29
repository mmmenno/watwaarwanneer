<?php
class AdamlinkMapper {

	public function getEvent($id) {

		$sparqlquery = '
			PREFIX foaf: <http://xmlns.com/foaf/0.1/>
			PREFIX dc: <http://purl.org/dc/elements/1.1/>
			PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
			PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
			PREFIX wd: <http://www.wikidata.org/entity/>
			PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>
			PREFIX www: <https://watwaarwanneer.info/event/>
			SELECT ?label ?begin ?end ?place ?placelabel ?cho ?img ?title ?description ?article WHERE {
			  www:' . $id . ' rdfs:label ?label .
			  www:' . $id . ' sem:hasEarliestBeginTimeStamp ?begin .
			  www:' . $id . ' sem:hasLatestEndTimeStamp ?end .
			  www:' . $id . ' sem:hasPlace ?place .
			  OPTIONAL{ 
			  	?cho dc:subject www:' . $id . ' .
			  	?cho foaf:depiction ?img .
			  	OPTIONAL{ 
				  	?cho dc:title ?title .
				  	?cho dc:description ?description .
				  }
			  }
			  ?place rdfs:label ?placelabel .
			  OPTIONAL{ 
			  	?place foaf:isPrimaryTopicOf ?article .
			  }
			  
			} 
			LIMIT 100
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
        $values = array("venues"=>array(),"imgs"=>array());
        $rec = $data['results']['bindings'][0];
        $values['event'] = array(
			"label" => $rec['label']['value'],
			"begin" => $rec['begin']['value'],
			"end" => $rec['end']['value']
		);
        $beenthere = array();
        foreach ($data['results']['bindings'] as $rec) {
        	if(!in_array($rec['place']['value'], $beenthere)){
		    	$values['venues'][] = array(
		    		"place" => $rec['place']['value'],
		    		"placelabel" => $rec['placelabel']['value'],
		    		"article" => $rec['article']['value']
		    	);
        	}
        	$beenthere[]=$rec['place']['value'];
        }
        foreach ($data['results']['bindings'] as $rec) {
        	if(strlen($rec['label']['value'])){
	        	$values["imgs"][] = array(
	        		"label" => $rec['label']['value'],
	        		"begin" => $rec['begin']['value'],
	        		"end" => $rec['end']['value'],
	        		"cho" => $rec['cho']['value'],
	        		"img" => $rec['img']['value'],
	        		"title" => $rec['title']['value'],
	        		"description" => $rec['description']['value']
	        	);
	        }
        }
        return $values;
    }

}