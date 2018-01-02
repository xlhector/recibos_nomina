<?php
     include_once('cfdi33.php');
     include_once('lib/nusoap/lib/nusoap.php');
	include_once('include/config.inc.php');
	include_once('lib/adodb5/adodb.inc.php');
		$base = dirname(__FILE__).'/base/base1.xml';
    error_reporting(E_ALL);
	ini_set('display_errors', 1);
	set_time_limit(0);
	$url ="https://timbrado.pade.mx/servicio/Timbrado3.3?wsdl";
	$carpeta_empresa = '';
	$rfc_empresa = $argv[1];
	$folio_init  = $argv[2];
	$folio_fin   = $argv[3];
	
	if ( ! $cliente = new SoapClient($url, array('exceptions' => true))) {
            throw new Exception('Emisor Prodigia fallo al instanciarse');
     }
	
	$mysqli = new mysqli(_database_host, _database_user, _database_password, _database_name);

	if ($mysqli->connect_errno) {
    	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    	return;
	}

	$sql = "SELECT * from recibos_nomina where estatus  = 0 and emisor_rfc = '$rfc_empresa' and folio BETWEEN $folio_init AND $folio_fin order by id";
	$res = $mysqli->query($sql);
	$folio = 0;
	while($row = $res->fetch_assoc()){
		$xml_temp = new DOMdocument('1.0', 'UTF-8');
		$xml_temp->loadXML(file_get_contents($base));


		$folio = $row['folio'];
		echo $folio;
		if (!$folio) {
			echo "El  folio 0 no es permitido.";
			continue;
		}

		$SubTotal = number_format( $row['importe'],2,".","");
		$Descuento = $row['descuento'];
		$Total = $SubTotal - $Descuento;
		
		$LugarExpedicion = '77500';
		$Fecha = '2017-12-31';
		$NoCertificado = '00001000000401576416';
		$RegimenFiscal = $row['emisor_regimenfiscal'];

		$rfc_receptor = $row['receptor_rfc'];

		$rfc_emisor = $row['emisor_rfc'];
		$sql = "SELECT * FROM empresa where rfc='$rfc_emisor'";
		$resempresa = $mysqli->query($sql);
		if($rowempresa = $resempresa->fetch_assoc()) {
			$NoCertificado = $rowempresa['certificado'];
			$LugarExpedicion = $rowempresa['codigo_postal'];
			$sql = "UPDATE empresa set folio  = folio + 1 where rfc = '$rfc_emisor'";
			$mysqli->query($sql);	
		}
		else {
			echo "Ocurrió un error al momento de obtener el emisor"; exit();
		}


		$nombre_emisor = $row['emisor_nombre'];
		if($carpeta_empresa == '') {
			$carpeta_empresa = dirname(__FILE__).'/'.$rfc_emisor.'/';
		}
		$archivo_certificado = dirname(__FILE__).'/certificados/'.$rfc_emisor;


		$sql = "SELECT count(*) as total from  recibos_nomina 
		        where receptor_rfc = '$rfc_receptor' and mensaje='NOM138'";
		
		$resreceptor = $mysqli->query($sql);
		if($rowreceptor = $resreceptor->fetch_assoc()) {		
			if($rowreceptor['total'] >0){
				echo 'Marcado';
				$sql  = "UPDATE recibos_nomina SET mensaje = 'NOM138', estatus = 3, updated_at = now() where receptor_rfc = '{$rfc_receptor}'";	
			 	$mysqli->query($sql);			 	
			 	continue;
			}
		}  

		$nombre_receptor = $row['receptor_nombre'];

		$FechaPago = $row['fechapago'];
		$FechaInicialPago = $row['fechainicialpago'];
		$FechaFinalPago = $row['fechafinalpago'];
		/*
		 , totalgravado, totalimpuestosretenidos, subsidiocausado'*/
// $comprobante->setAttribute('Fecha', $Fecha);

		$TotalPercepciones = number_format( utf8_encode($row['totalpercepciones']), 2,".", "");
		$TotalDeducciones = utf8_encode($row['totaldeducciones']);
		$TotalOtrosPagos = utf8_encode($row['totalotrospagos']);

	    

	    $comprobante = $xml_temp->getElementsByTagName('Comprobante')->item(0);
	   	$emisor = $comprobante->getElementsByTagName('Emisor')->item(0);
        $receptor = $comprobante->getElementsByTagName('Receptor')->item(0);
        $Conceptos = $comprobante->getElementsByTagName('Conceptos')->item(0);
        $concepto = $Conceptos->getElementsByTagName('Concepto')->item(0);
        $complemento = $comprobante->getElementsByTagName('Complemento')->item(0);
        $nominaNodo = $complemento->getElementsByTagName('Nomina')->item(0);
        $emisorcomplemento = $nominaNodo->getElementsByTagName('Emisor')->item(0);
		
		$receptorcomplemento = $nominaNodo->getElementsByTagName('Receptor')->item(0);
		$percepciones = $nominaNodo->getElementsByTagName('Percepciones')->item(0);

		$nominaNodo->setAttribute('FechaPago', utf8_encode( $FechaPago));
		$nominaNodo->setAttribute('FechaInicialPago', utf8_encode($FechaInicialPago));
		$nominaNodo->setAttribute('FechaFinalPago', utf8_encode($FechaFinalPago));
		$nominaNodo->setAttribute('NumDiasPagados', number_format(utf8_encode($row['numdiaspagados']), 3, ".", ""));
		$nominaNodo->setAttribute('TotalPercepciones',utf8_encode( $TotalPercepciones));
		$nominaNodo->setAttribute('TotalDeducciones', utf8_encode($TotalDeducciones));
		$nominaNodo->setAttribute('TotalOtrosPagos', utf8_encode($TotalOtrosPagos));

	    $comprobante->setAttribute('Folio', utf8_encode($folio));
	    $comprobante->setAttribute('SubTotal', utf8_encode($SubTotal));
	    $comprobante->setAttribute('Descuento', utf8_encode($Descuento));
	    $comprobante->setAttribute('Total', utf8_encode($Total));
	    $comprobante->setAttribute('LugarExpedicion', utf8_encode($LugarExpedicion));
	    $comprobante->setAttribute('NoCertificado', utf8_encode($NoCertificado));
	    
	    $emisor->setAttribute('Rfc', utf8_encode($rfc_emisor));
	    $emisor->setAttribute('Nombre', utf8_encode($nombre_emisor));
	    $emisor->setAttribute('RegimenFiscal', utf8_encode($RegimenFiscal));

		$receptor->setAttribute('Rfc', utf8_encode($rfc_receptor));
	    $receptor->setAttribute('Nombre', utf8_encode($nombre_receptor));

	    $concepto->setAttribute('ValorUnitario', utf8_encode($SubTotal));
	    $concepto->setAttribute('Importe', utf8_encode($SubTotal));
	    $concepto->setAttribute('Descuento', utf8_encode($Descuento));

	    $emisorcomplemento->setAttribute('RegistroPatronal', utf8_encode($row['registropatronal']));
	    $receptorcomplemento->setAttribute('Curp', utf8_encode($row['receptor_curp']));
	    $receptorcomplemento->setAttribute('NumSeguridadSocial', utf8_encode($row['numseguridadsocial']));
	    $receptorcomplemento->setAttribute('FechaInicioRelLaboral', utf8_encode($row['fechainiciorellaboral']));
	    $receptorcomplemento->setAttribute('SalarioBaseCotApor', number_format( $row['salariobasecotapor'], 2, ".", ""));
	    $receptorcomplemento->setAttribute('SalarioDiarioIntegrado', number_format( utf8_encode( $row['salariodiariointegrado']), 2, ".", ""));
	    $receptorcomplemento->setAttribute('NumEmpleado', utf8_encode($row['numempleado']));

	    $percepciones->setAttribute('TotalSueldos', number_format( utf8_encode($row['totalsueldos']),2,".", ""));
	    $percepciones->setAttribute('TotalGravado', number_format(utf8_encode($row['totalgravado']),2, ".", ""));

	    $percepcion = $percepciones->getElementsByTagName('Percepcion')->item(0);
	    $percepcion->setAttribute('ImporteGravado', number_format(utf8_encode($row['totalgravado']),2,".", ""));

		$deducciones = $nominaNodo->getElementsByTagName('Deducciones')->item(0);

		$deducciones->setAttribute('TotalImpuestosRetenidos', utf8_encode( $row['totalimpuestosretenidos']));

		$deduccion = $deducciones->getElementsByTagName('Deduccion')->item(0);
		$deduccion->setAttribute('Importe', utf8_encode( $row['totalimpuestosretenidos']));

		$otros = $nominaNodo->getElementsByTagName('OtrosPagos')->item(0);

		$otro = $nominaNodo->getElementsByTagName('OtroPago')->item(0);
		$otro->setAttribute('Importe', utf8_encode( $row['subsidiocausado']));

		$subsidio = $otro->getElementsByTagName('SubsidioAlEmpleo')->item(0);

		$subsidio->setAttribute('SubsidioCausado', utf8_encode($row['subsidiocausado']));



	    if(! file_exists($carpeta_empresa) ) {
	    	mkdir($carpeta_empresa);	
	    }



		$xml_temp->save($carpeta_empresa."{$rfc_receptor}_{$folio}.xml");

		$cfdi33 = new cfdi33();
		error_reporting(0);

// Notificar solamente errores de ejecución
			error_reporting(E_ERROR );
		ini_set('display_errors', 0);
		$comprobante = $xml_temp->getElementsByTagName('Comprobante')->item(0);

		$cfdi33->sellar($comprobante, $cfdi33->genera_cadena_original($xml_temp), $archivo_certificado );
		
		$xml_temp->save($carpeta_empresa."{$rfc_receptor}_{$folio}.xml");
    	error_reporting(E_ALL);
		ini_set('display_errors', 1);
     	$respuesta = $cliente->timbrado(array(
                'contrato' => '36d45fe0-c33f-11e2-8b8b-0800200c9a66',
                'usuario' => 'clickbalance',
                'passwd' => 'pa55w0rdSegur4*',
                'cfdiXml' => $xml_temp->saveXML())
        );
		$xml_temp_timbrado = 	 new DOMDocument('1.0','UTF-8');
		if(!$respuesta){
			$sql  = "UPDATE recibos_nomina SET mensaje = 'Fallo de internet', estatus = 3, updated_at = now() where id = {$row['id']}";	
			 $mysqli->query($sql);
			continue;			
		}
		$xml_temp_timbrado->loadXML($respuesta->return);
		
        $servicio_timbrado = $xml_temp_timbrado->getElementsByTagName("servicioTimbrado")->item(0);
         $timbrado_ok = $servicio_timbrado->getElementsByTagName("timbradoOk")->item(0)->nodeValue;



		if($timbrado_ok === 'false'){
			$mensaje=$servicio_timbrado->getElementsByTagName("codigo")->item(0)->nodeValue;
			$sql  = "UPDATE recibos_nomina SET mensaje = '{$mensaje}', estatus = 3, updated_at = now() where id = {$row['id']}";	
			 $mysqli->query($sql);

			 if($mensaje == 'NOM138') {
				$sql  = "UPDATE recibos_nomina SET mensaje = '{$mensaje}', estatus = 3, updated_at = now() where receptor_rfc = '{$rfc_receptor}'";	
			 		$mysqli->query($sql);			 	
			 }

		}else{
	

				$archivo_timbrado=$carpeta_empresa."{$rfc_receptor}_{$folio}.xml";
 				 $xpath = new DOMXPath($xml_temp);
                $elemento = $xpath->query('//cfdi:Complemento');

                if($elemento->length >= 1)
                {
                    $nodo_complemento = $xml_temp->getElementsByTagName("Complemento")->item(0);                        
                }
                else
                {
                    $nodo_comprobante = $xml_temp->getElementsByTagName("Comprobante")->item(0);
                    $nodo_complemento = $xml_temp->createElement("cfdi:Complemento");
                    $nodo_complemento = $nodo_comprobante->appendChild($nodo_complemento);
                }                

                $nodo_timbre_fiscal_digital = $xml_temp->createElement("tfd:TimbreFiscalDigital");
                $nodo_timbre_fiscal_digital = $nodo_complemento->appendChild($nodo_timbre_fiscal_digital);
                $nodo_timbre_fiscal_digital->setAttribute('xsi:schemaLocation', 'http://www.sat.gob.mx/TimbreFiscalDigital http://www.sat.gob.mx/sitio_internet/TimbreFiscalDigital/TimbreFiscalDigital.xsd');
                $nodo_timbre_fiscal_digital->setAttribute('xmlns:tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');   
                $nodo_timbre_fiscal_digital->setAttribute('version', $servicio_timbrado->getElementsByTagName("version")->item(0)->nodeValue);                
                $nodo_timbre_fiscal_digital->setAttribute('selloSAT', $servicio_timbrado->getElementsByTagName("selloSAT")->item(0)->nodeValue);
                $nodo_timbre_fiscal_digital->setAttribute('noCertificadoSAT', $servicio_timbrado->getElementsByTagName("noCertificadoSAT")->item(0)->nodeValue);
                $nodo_timbre_fiscal_digital->setAttribute('selloCFD', $servicio_timbrado->getElementsByTagName("selloCFD")->item(0)->nodeValue);
                $nodo_timbre_fiscal_digital->setAttribute('FechaTimbrado', $servicio_timbrado->getElementsByTagName("FechaTimbrado")->item(0)->nodeValue);
                $nodo_timbre_fiscal_digital->setAttribute('UUID', $servicio_timbrado->getElementsByTagName("UUID")->item(0)->nodeValue);

                $xml_temp->formatOutput = true;
                


			
				$xml_temp->save($archivo_timbrado);

			$sql  = "UPDATE recibos_nomina SET mensaje = '{$archivo_timbrado}', estatus = 2, updated_at = now() where id = {$row['id']}";	
			$mysqli->query($sql);	
			echo '   ok  ';		
			
		}

	}

	


	

	






	
 

