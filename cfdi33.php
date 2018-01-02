<?php
    ini_set ('error_reporting', E_ALL & ~E_NOTICE);
    ini_set('display_errors', '0');
class cfdi33 {


function genera_cadena_original($xml) {
	$ruta = dirname(__FILE__).'/xslt/';
	$file = $ruta.'cadenaoriginal_3_3.xslt';      // Ruta al archivo

	$paso = new DOMDocument('1.0','UTF-8');
	$paso->loadXML($xml->saveXML());
	$xsl = new DOMDocument('1.0','UTF-8');
	
	$xsl->load($file);
	
	$proc = new XSLTProcessor;
	$proc->importStyleSheet($xsl);

	$res =$proc->transformToXML($paso);
	
	return $res;
}
function my_openssl_sign($data, &$signature, $priv_key_id, $signature_alg = 'sha256WithRSAEncryption') {
    $pinfo = openssl_pkey_get_details($priv_key_id);
    $hash = hash('sha256', $data);
    $t = '3031300d060960864801650304020105000420'; # sha256
    $t .= $hash;
    $pslen = $pinfo['bits']/8 - (strlen($t)/2 + 3);

    $eb = '0001' . str_repeat('FF', $pslen) . '00' . $t;
    $eb = pack('H*', $eb);

    return openssl_private_encrypt($eb, $signature, $priv_key_id, OPENSSL_NO_PADDING); 
}

function sellar( $root, $cadena_original, $certificado) {
	$file =  strtolower( $certificado.'.key.pem');      // Ruta al archivo

  	$pkeyid = openssl_get_privatekey(file_get_contents($file));

	$this->my_openssl_sign($cadena_original, $crypttext, $pkeyid, OPENSSL_ALGO_SHA256);


	openssl_free_key($pkeyid);
	$sello = base64_encode($crypttext);      // lo codifica en formato base64
	$root->setAttribute('Sello', utf8_encode( $sello));
	$file = strtolower( $certificado.'.cer.pem');      // Ruta al archivo de Llave publica
	$datos = file($file);
	$certificado = ''; $carga = false;
	for ($i=0; $i < sizeof($datos); $i++) {
    	if (strstr($datos[$i],'END CERTIFICATE')) {
    		$carga=false;
    	}
    	if ($carga) {
    		$certificado .= trim($datos[$i]);
    	}
    	if (strstr($datos[$i],'BEGIN CERTIFICATE')) {
    		$carga = true;
    	}
	}
	$root->setAttribute('Certificado',  utf8_encode($certificado));
}
 
function cargar_atributo(&$nodo, $attr) {
    global $xml, $cadena_original;
    $quitar = array('sello'=>1,'noCertificado'=>1,'certificado'=>1,'serie'=>1,'folio'=>1);

    foreach ($attr as $key => $val) {
        if(!is_array($val)){
	        $val = str_replace('\t', ' ', str_replace('\n', ' ', $val));  // Regla 5a
	    	$val = trim($val);                                            // Regla 5b
	        $val = preg_replace('/\s\s+/', ' ', $val);                    // Regla 5c
	        if (mb_strlen($val) > 0) {

	            $val = str_replace('|', '/', $val);                       // Regla 6
	            $nodo->setAttribute($key, $val);                          // Regla 1

	            if (!isset($quitar[$key]))
	                if (mb_substr($key,0,3) != 'xml' && mb_substr($key,0,4) != 'xsi:')
	                    $cadena_original .= $val . '|';
	        }
    	}
    }
}


}	