<?php
class Wikidata {

	public function __construct($wdids) {
		if(is_array($wdids)){
        	$this->wdids = $wdids;
    	}else{
    		$this->wdid = $wdids;
    	}
    }
    
    public function getActorsdata() {

		$itemlist = "wd:" . implode(" wd:", $this->wdids);

        $sparql = 'PREFIX wd: <http://www.wikidata.org/entity/>
					PREFIX wdt: <http://www.wikidata.org/prop/direct/>
					PREFIX schema: <http://schema.org/>
					PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

					SELECT ?item ?label ?description ?wikipedia WHERE {
					  VALUES ?item {' . $itemlist . '}
					  ?item rdfs:label ?label .
					  OPTIONAL{ 
					    ?item schema:description ?description .
					    FILTER (LANG(?description) = "nl")
					  }
					  OPTIONAL {
					    ?wikipedia schema:about ?item .
					    ?wikipedia schema:inLanguage "nl" .
					    ?wikipedia schema:isPartOf <https://nl.wikipedia.org/> .
					  }
					  FILTER (LANG(?label) = "nl")
					}
					LIMIT 100';

		$encoded = urlencode($sparql);
		 $json = file_get_contents("https://query.wikidata.org/sparql?query=" . $encoded . "&format=json");
        $data = json_decode($json,true);
        
        // fill array
        $values = array();
        foreach ($data['results']['bindings'] as $rec) {
        	$id = str_replace("http://www.wikidata.org/entity/", "", $rec['item']['value']);
        	$values[$id] = array(
			        		"wikipedia" => $rec['wikipedia']['value'],
			        		"label" => $rec['label']['value'],
			        		"description" => $rec['description']['value']
			        	);
        }
        return $values;
    }

    public function getEventtypesdata() {

        $itemlist = "wd:" . implode(" wd:", $this->wdids);

        $sparql = 'PREFIX wd: <http://www.wikidata.org/entity/>
					PREFIX wdt: <http://www.wikidata.org/prop/direct/>
					PREFIX schema: <http://schema.org/>
					PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

					SELECT ?item ?label ?description WHERE {
					  VALUES ?item {' . $itemlist . '}
					  ?item rdfs:label ?label .
					  OPTIONAL{ 
					    ?item schema:description ?description .
					    FILTER (LANG(?description) = "nl")
					  }
					  FILTER (LANG(?label) = "nl")
					}
					LIMIT 100';

		$encoded = urlencode($sparql);
		 $json = file_get_contents("https://query.wikidata.org/sparql?query=" . $encoded . "&format=json");
        $data = json_decode($json,true);
        
        // fill array
        $values = array();
        foreach ($data['results']['bindings'] as $rec) {
        	$id = str_replace("http://www.wikidata.org/entity/", "", $rec['item']['value']);
        	if(!isset($rec['label']['value'])){
        		$rec['label']['value'] = "";
        	}
        	if(!isset($rec['description']['value'])){
        		$rec['description']['value'] = "";
        	}
        	$values[$id] = array(
			        		"label" => $rec['label']['value'],
			        		"description" => $rec['description']['value']
			        	);
        }
        return $values;
    }

    public function getLocationsdata() {

        $itemlist = "wd:" . implode(" wd:", $this->wdids);

        $sparql = 'PREFIX wd: <http://www.wikidata.org/entity/>
					PREFIX wdt: <http://www.wikidata.org/prop/direct/>
					PREFIX schema: <http://schema.org/>
					PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

					SELECT DISTINCT ?item ?label (SAMPLE(?class) AS ?class) (SAMPLE(?classLabel) AS ?classLabel) ?municipality ?municipalityLabel ?province ?provinceLabel ?coords ?description ?wikipedia WHERE {
					  VALUES ?item {' . $itemlist . '}
					  ?item rdfs:label ?label .
					  ?item wdt:P31 ?class .
					  ?class rdfs:label ?classLabel .
					  FILTER (LANG(?classLabel) = "nl")
					  OPTIONAL{ 
					    ?item schema:description ?description .
					    FILTER (LANG(?description) = "nl")
					  }
					  OPTIONAL{ 
					    ?item wdt:P625 ?coords .
					  }
					  OPTIONAL{ 
					    ?item wdt:P131 ?municipality .
					    ?municipality wdt:P31 wd:Q2039348 .
					    ?municipality wdt:P131 ?province .
					  }
					  OPTIONAL{ 
					    ?item wdt:P131 ?province .
					  }
					  OPTIONAL {
					    ?wikipedia schema:about ?item .
					    ?wikipedia schema:inLanguage "nl" .
					    ?wikipedia schema:isPartOf <https://nl.wikipedia.org/> .
					  }
					  FILTER (LANG(?label) = "nl")
					  SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],nl". }
					}
					GROUP BY ?item ?label ?municipality ?municipalityLabel ?province ?provinceLabel ?coords ?description ?wikipedia
					LIMIT 100';

		$encoded = urlencode($sparql);
		 $json = file_get_contents("https://query.wikidata.org/sparql?query=" . $encoded . "&format=json");
        $data = json_decode($json,true);
        
        // fill array
        $values = array();
        foreach ($data['results']['bindings'] as $rec) {
        	$id = str_replace("http://www.wikidata.org/entity/", "", $rec['item']['value']);
        	$prov = str_replace("http://www.wikidata.org/entity/", "", $rec['province']['value']);
        	$mun = str_replace("http://www.wikidata.org/entity/", "", $rec['municipality']['value']);
        	$class = str_replace("http://www.wikidata.org/entity/", "", $rec['class']['value']);
        	$lonlat = str_replace(array("Point(",")"), "", $rec['coords']['value']);
        	$coords = explode(" ", $lonlat);
        	$lat = $coords[1];
        	$lon = $coords[0];

        	if(!isset($rec['wikipedia']['value'])){
        		$rec['wikipedia']['value'] = "";
        	}
        	if(!isset($rec['municipalityLabel']['value'])){
        		$rec['municipalityLabel']['value'] = "";
        	}
        	if(!isset($rec['label']['value'])){
        		$rec['label']['value'] = "";
        	}
        	$values[$id] = array(
			        		"label" => $rec['label']['value'],
			        		"description" => $rec['description']['value'],
			        		"municipality" => $mun,
			        		"municipalityLabel" => $rec['municipalityLabel']['value'],
			        		"province" => $prov,
			        		"provinceLabel" => $rec['provinceLabel']['value'],
			        		"wikipedia" => $rec['wikipedia']['value'],
			        		"class" => $class,
			        		"classLabel" => $rec['classLabel']['value'],
			        		"lat" => $lat,
			        		"lon" => $lon,
			        	);
        }
        return $values;
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