<?php

require_once 'errors.php';
//  dbpassword.php defines:
//  $DBSTRING
//  $DBUSER
//  $DBPASSWORD
require_once 'dbpassword.php';

$pdo = new PDO($DBSTRING, $DBUSER, $DBPASSWORD, 
        array(PDO::ATTR_PERSISTENT => false));
if (!$pdo) {
    errors_fatal('Could not connect to database. Please contact the administrator.');
}

$stmtcache = array();

function _db_prep($txt, array $args) {
    global $pdo;
    global $stmtcache;
    $q = null;
    if (!array_key_exists($txt, $stmtcache)) {
        $q = $pdo->prepare($txt);
        if (!$q) {
            logerror("Query prepare error", "$txt", print_r($pdo->errorinfo(), true));
            return false;
        }
        $stmtcache[$txt] = $q;
    } else {
        $q = $stmtcache[$txt];
    }
    if (!$q->execute($args)) {
        $einfo = $q->errorinfo();
        if ($einfo[0]) {
            logerror("Query error; $txt", print_r($einfo, true));
            return false;
        }
    }
    return $q;
}

function db_query($txt, array $args) {
    $q = _db_prep($txt, $args);
    if (!$q) {
        return null;
    }
    if ((strpos($txt, 'UPDATE') === 0) ||
        (strpos($txt, 'INSERT') === 0) ||
        (strpos($txt, 'DELETE') === 0)) {
        return true;
    }
    $ret = $q->fetchAll(PDO::FETCH_ASSOC);
    $q->closeCursor();
    return $ret;
}

function db_insert($table, $args, $idcol) {
    global $pdo;
    $txt = "INSERT INTO $table";
    $sep = "(";
    foreach ($args as $k => $v) {
        $txt .= "$sep$k";
        $sep = ',';
    }
    $txt .= ") VALUES";
    $sep = '(';
    foreach ($args as $k => $v) {
        $txt .= "$sep:$k";
        $sep = ',';
    }
    $txt .= ")";
    $q = _db_prep($txt, $args);
    if (!$q) {
        return null;
    }
    $ret = $pdo->lastInsertId($idcol);
    $q->closeCursor();
    return $ret;
}
