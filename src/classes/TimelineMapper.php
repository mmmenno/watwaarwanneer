<?php
class TimelineMapper extends Mapper
{
    public function getEvents($filter) {
        //print_r($filter);
        $sql = "SELECT e.* FROM events AS e 
                LEFT JOIN event_x_eventtype AS xet ON e.id = xet.event_id
                LEFT JOIN locations AS l ON e.location = l.wdid";
        $sql .= " WHERE e.id>0";
        if(isset($filter['eventtype']) && $filter['eventtype'] !=""){
            $sql .= " AND xet.eventtype_wdid = '" . $filter['eventtype'] . "'";
        }
        if(isset($filter['province']) && $filter['province'] !=""){
            $sql .= " AND l.province = '" . $filter['province'] . "'";
        }
        $sql .= " ORDER BY start ASC";
        $stmt = $this->db->query($sql);
        $results = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }

    public function getEvent($id) {
        $sql = "SELECT  e.*,
                        t.wdid AS typewdid, 
                        t.label AS typelabel,
                        t.description AS typedescription, 
                        l.wdid AS locationwdid,
                        l.label,
                        l.class,
                        l.classlabel,
                        l.description,
                        l.municipality,
                        l.municipalitylabel,
                        l.province,
                        l.provincelabel,
                        l.lat,
                        l.lon,
                        l.wikipedia AS locationwiki
                FROM events AS e 
                LEFT JOIN event_x_eventtype as x ON e.id = x.event_id
                LEFT JOIN eventtypes as t ON x.eventtype_wdid = t.wdid
                LEFT JOIN locations AS l ON e.location = l.wdid
                WHERE e.id = " . $id;
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row;
    }

    public function getChos($id) {
        $sql = "SELECT * FROM chobjects WHERE event_id = " . $id;
        $stmt = $this->db->query($sql);
        $results = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }

    public function getIncoming($limit = 1) {
        $sql = "SELECT * FROM incoming WHERE processed <> 'yes' LIMIT " . $limit;
        $stmt = $this->db->query($sql);
        $results = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }

    public function getActor($id) {
        $sql = "SELECT * FROM actors WHERE wdid = '" . $id . "'";
        $stmt = $this->db->query($sql);
        $results = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }

    public function getLocation($wdid) {
        $sql = "SELECT * FROM locations WHERE wdid = '" . $wdid . "'";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
        return $row;
    }

    public function getEventtype($id) {
        $sql = "SELECT * FROM eventtypes WHERE wdid = '" . $id . "'";
        $stmt = $this->db->query($sql);
        $results = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }


    public function getActors($eventid) {
        $sql = "SELECT a.* FROM event_x_actor AS x
                LEFT JOIN actors AS a ON x.actor_wdid = a.wdid 
                WHERE x.event_id = '" . $eventid . "'";
        $stmt = $this->db->query($sql);
        $results = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }


    public function getTypes() {
        $sql = "SELECT DISTINCT(wdid), label FROM eventtypes GROUP BY wdid ORDER BY label ASC";
        $stmt = $this->db->query($sql);
        $results = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }


    public function getProvinces() {
        $sql = "SELECT DISTINCT(province), provincelabel FROM locations GROUP BY province ORDER BY provincelabel ASC";
        $stmt = $this->db->query($sql);
        $results = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }

}