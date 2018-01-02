<?php
	include_once('include/config.inc.php');
	include_once('lib/adodb5/adodb.inc.php');
    error_reporting(E_ALL);
	ini_set('display_errors', 1);
    $nombre_archivo = $argv[1];
	$folio          = $argv[2];
    $mysqli = new mysqli(_database_host, _database_user, _database_password, _database_name);

	if ($mysqli->connect_errno) {
    	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    	return;
	}

	$archivo =   dirname(__FILE__).'/Datos/'.$nombre_archivo.'.csv';
 	$c = 0;
	if (($fichero = fopen($archivo, "r")) !== FALSE) {
    while (($datos = fgetcsv($fichero, 0)) !== FALSE) {
    	$c = $c + 1;
        $sql = "INSERT INTO recibos_nomina(emisor_rfc, emisor_nombre, folio, emisor_regimenfiscal, receptor_rfc, receptor_nombre, importe,descuento, fechapago, fechainicialpago, fechafinalpago, numdiaspagados, totalpercepciones, totaldeducciones, totalotrospagos, registropatronal, receptor_curp, numseguridadsocial, fechainiciorellaboral, numempleado, salariobasecotapor, salariodiariointegrado, totalsueldos, totalgravado, totalimpuestosretenidos, subsidiocausado) values ('{$datos[0]}','{$datos[1]}','{$folio}','{$datos[2]}','{$datos[3]}',\"{$datos[4]}\",'{$datos[5]}','{$datos[6]}','{$datos[7]}','{$datos[8]}','{$datos[9]}','{$datos[10]}','{$datos[11]}','{$datos[12]}','{$datos[13]}','{$datos[14]}','{$datos[15]}', '{$datos[16]}','{$datos[17]}','{$datos[18]}','{$datos[19]}','{$datos[20]}','{$datos[21]}','{$datos[22]}','{$datos[23]}','{$datos[24]}');";
        	$mysqli->query($sql);
        	$folio += 1;
        	if ($mysqli->error) {
        		echo $datos[4].'\n';
    			echo $mysqli->error;

                echo $datos[4];
                echo escape($datos[4]);
    	
			}

    }


    var_dump("El Archivo ".$nombre_archivo." se pasó a la BD con éxito.");
}

    function escape($data) {
        return str_replace(["'"], ["\\'"], $data);
    }

