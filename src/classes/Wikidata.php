<?php
class Wikidata {

	public function __construct($wdid) {
        $this->wdid = $wdid;
    }
    
    public function doStupid() {
        
        return $this->wdid . "dePiPo";
    }
	
	public function getActordata() {

        // sparql
        $sparql = 'PREFIX wd: <http://www.wikidata.org/entity/>
					PREFIX wdt: <http://www.wikidata.org/prop/direct/>
					PREFIX schema: <http://schema.org/>
					PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

					SELECT ?label ?description ?wikipedia WHERE {
					  wd:' . $this->wdid . ' rdfs:label ?label .
					  OPTIONAL{ 
					  	wd:' . $this->wdid . ' schema:description ?description .
					  	FILTER (LANG(?description) = "nl")
					  }
					  OPTIONAL {
					    ?wikipedia schema:about wd:' . $this->wdid . ' .
					    ?wikipedia schema:inLanguage "nl" .
					    ?wikipedia schema:isPartOf <https://nl.wikipedia.org/> .
					  }
					  FILTER (LANG(?label) = "nl")
					}
					LIMIT 100';

		$encoded = urlencode($sparql);
		//echo $sparql . "\n";
		//die;
		// query
        $json = file_get_contents("https://query.wikidata.org/sparql?query=" . $encoded . "&format=json");
        $data = json_decode($json,true);
        //print_r($data);
        //die;

        // fill array
        $values = array(
        	"wikipedia" => $data['results']['bindings'][0]['wikipedia']['value'],
        	"label" => $data['results']['bindings'][0]['label']['value'],
        	"description" => $data['results']['bindings'][0]['description']['value']
        );


        return $values;
    }
	
	public function getEventtypedata() {

        // sparql
        $sparql = 'PREFIX wd: <http://www.wikidata.org/entity/>
					PREFIX wdt: <http://www.wikidata.org/prop/direct/>
					PREFIX schema: <http://schema.org/>
					PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

					SELECT ?label ?description WHERE {
					  wd:' . $this->wdid . ' rdfs:label ?label .
					  OPTIONAL{ 
					  	wd:' . $this->wdid . ' schema:description ?description .
					  	FILTER (LANG(?description) = "nl")
					  }
					  FILTER (LANG(?label) = "nl")
					}
					LIMIT 10';

		$encoded = urlencode($sparql);
		$json = file_get_contents("https://query.wikidata.org/sparql?query=" . $encoded . "&format=json");
        $data = json_decode($json,true);
        
        // fill array
        $values = array(
        	"label" => "geen label",
        	"description" => ""
        );
        if(isset($data['results']['bindings'][0]['label']['value'])){
        	$values['label'] = $data['results']['bindings'][0]['label']['value'];
        }
        if(isset($data['results']['bindings'][0]['description']['value'])){
        	$values['description'] = $data['results']['bindings'][0]['description']['value'];
        }


        return $values;
    }

}