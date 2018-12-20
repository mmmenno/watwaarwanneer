<?php
class TimelineMapper extends Mapper
{
    public function getEvents() {
        $sql = "SELECT * FROM events ORDER BY start ASC";
        $stmt = $this->db->query($sql);
        $results = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }

    public function getEvent($id) {
        $sql = "SELECT e.*,t.wdid AS typewdid, t.label AS typelabel,t.description AS typedescription FROM events AS e 
                LEFT JOIN event_x_eventtype as x ON e.id = x.event_id
                LEFT JOIN eventtypes as t ON x.eventtype_wdid = t.wdid
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

    public function getIncoming() {
        $sql = "SELECT * FROM incoming WHERE processed <> 'yes'";
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

}