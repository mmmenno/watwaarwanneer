<?php
class Time {

	public function __construct($start,$end) {
        $this->start = $start;
        $this->end = $end;
    }
    
    public function humanreadable() {
    	$startparts  = explode("-", $this->start);
        if($startparts[1]=="00"){
        	$startstring = date("Y",strtotime($startparts[0] . "-01-01"));
        }elseif($startparts[2]=="00"){
        	$startstring = date("M Y",strtotime($startparts[0] . "-" . $startparts[1] . "-01"));
        }else{
        	$startstring = date("j M Y",strtotime($this->start));
        }
        
        $endparts  = explode("-", $this->end);
        if($endparts[1]=="00"){
        	$endstring = date("Y",strtotime($endparts[0] . "-01-01"));
        }elseif($endparts[2]=="00"){
        	$endstring = date("M Y",strtotime($endparts[0] . "-" . $endparts[1] . "-01"));
        }else{
        	$endstring = date("j M Y",strtotime($this->end));
        }
        
        $hrp = $startstring . " - " . $endstring;

        if($startstring==$endstring){
        	$hrp = $startstring;
        }

        $from = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
        $to = array("januari","februari","maart","april","mei","juni","juli","augustus","september","oktober","november","december");
        $hrp = str_replace($from, $to, $hrp);

        return $hrp;
    }



    
    public function turtle() {

    	$daysinmonth = array(0,31,28,31,30,31,30,31,31,30,31,30,31);
    	$startparts  = explode("-", $this->start);
        if($startparts[1]=="00"){
        	$startmin = $startparts[0] . "-01-01";
        	$startmax = $startparts[0] . "-12-31";
        }elseif($startparts[2]=="00"){
        	$monthkey = (int)$startparts[1];
        	$startmin = $startparts[0] . "-" . $startparts[1] . "-01";
        	$startmax = $startparts[0] . "-" . $startparts[1] . "-" . $daysinmonth[$monthkey];
        }else{
        	$startmin = $startparts[0] . "-" . $startparts[1] . "-" . $startparts[2];
        	$startmax = $startparts[0] . "-" . $startparts[1] . "-" . $startparts[2];
        }
        
        $endparts  = explode("-", $this->end);
        if($endparts[1]=="00"){
        	$endmin = $endparts[0] . "-01-01";
        	$endmax = $endparts[0] . "-12-31";
        }elseif($endparts[2]=="00"){
        	$monthkey = (int)$endparts[1];
        	$endmin = $endparts[0] . "-" . $endparts[1] . "-01";
        	$endmax = $endparts[0] . "-" . $endparts[1] . "-" . $daysinmonth[$monthkey];
        }else{
        	$endmin = $endparts[0] . "-" . $endparts[1] . "-" . $endparts[2];
        	$endmax = $endparts[0] . "-" . $endparts[1] . "-" . $endparts[2];
        }
        

        $ttl = "\tsem:hasEarliestBeginTimeStamp \"" . $startmin . "\"^^xsd:date ;\n";
        $ttl .= "\tsem:hasLatestBeginTimeStamp \"" . $startmax . "\"^^xsd:date ;\n";
        $ttl .= "\tsem:hasEarliestEndTimeStamp \"" . $endmin . "\"^^xsd:date ;\n";
        $ttl .= "\tsem:hasLatestEndTimeStamp \"" . $endmax . "\"^^xsd:date ;\n";

        return $ttl;
    }

}