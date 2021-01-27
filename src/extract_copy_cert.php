<?php 
/*
* DESCRIPCION
* En el fichero de configuración del PfSense, busca los certificados que contengan en la descripción el texto configurado.
* Crea un nuevo XML que contiene solo estos nodos. 
* Actualiza la CA 
* Copia el XML hacia los servidores especificados el certificado a actualizar
* Lanza el script de actualización
*
* $ficheroPath --- ruta del fichero del cual va a extraer los certificados (Fichero de configuración del PfSense).
* $certToCopy  --- Descripción de los certificados que va a extraer. 
* $newFileName --- nombre del nuevo XML
* $servers     --- Servidores donde debe copiar y ejecutar las actualizaciones
*
* generar par de llaves publica y privada
* ssh-keygen
* Y Luego Copiar llave ssh para que no pida clave
* ssh-copy-id -i ~/.ssh/id_rsa.pub root@host
*
* @Author: 	YORLEN GUIRADO MAS <yorlen88@gmail.com>
*		LUIS ENRIQUE 	<enriluis@gmail.com>
*
* Ejemplo de configuración:
*		$ficheroPath = '/cf/conf/config.xml';
*		$certToCopy = ['CERTIFICADO.CO.LOCAL','CERTIFICADO.LOCAL'];
*		$caToCopy = ["Acmecert: O=Let's Encrypt, CN=R3, C=US"];
*		$newFileName='MisCertificadosAActualizar';
*		$servers = ['10.0.0.1','10.0.0.2'];
*/

print_r("Iniciando el script \r\n");

/*CONFIGURACION*/
$ficheroPath = '';
$certToCopy = [''];
$caToCopy = [''];
$newFileName='';
$servers = [''];
/*FIN DE LA CONFIGURACION*/

if(file_exists($ficheroPath)){
	$xml = simplexml_load_file($ficheroPath);
	$domDoc = new DomDocument;
	$domDoc->preserveWhiteSpace = false;
	$domDoc->formatOutput= true;
	
	$rootNode = $domDoc->createElement('root');
	$domDoc->appendChild($rootNode);
	/* SECCION CERTIFICADOS*/	
	$cantCertificados = count($xml->cert);
	print_r("  $ficheroPath contiene $cantCertificados certificados. \r\n");
	print_r("  Se procederá a buscar los certificados ".implode(', ',$certToCopy).". \r\n");
	for($i=0;$i<$cantCertificados;$i++){		
		$cert=$xml->cert[$i];		
		if(in_array((string)$cert->descr, $certToCopy)){
			print_r("  - Encontrado el CERT ".(string)$cert->descr ."\r\n");
			$newCert = $domDoc->createElement('cert');
			
			$refid = $domDoc->createElement('refid',$cert->refid);
			$descr = $domDoc->createElement('descr');
			$prv = $domDoc->createElement('prv',$cert->prv);
			$crt = $domDoc->createElement('crt',$cert->crt);
			$caref = $domDoc->createElement('caref',$cert->caref);
			
			
			$cdataDescr = $domDoc->createCDATASection((string)$cert->descr);
			$descr->appendChild($cdataDescr);
			
			$newCert->appendChild($refid);
			$newCert->appendChild($descr);
			$newCert->appendChild($prv);
			$newCert->appendChild($crt);
			$newCert->appendChild($caref);		
			$rootNode->appendChild($newCert);			
		}
	}
	/* FIN SECCION CERTIFICADOS*/	
	/* SECCION CA*/
	$cantCA = count($xml->ca);
	print_r("  $ficheroPath contiene $cantCA CA. \r\n");
	print_r("  Se procederá a buscar las CA ".implode(', ',$caToCopy).". \r\n");
	for($i=0;$i<$cantCA;$i++){		
		$ca=$xml->ca[$i];		
		if(in_array((string)$ca->descr, $caToCopy)){
			print_r("  - Encontrada la CA ".(string)$ca->descr ."\r\n");
			$newCA = $domDoc->createElement('ca');
			
			$refid = $domDoc->createElement('refid',$ca->refid);
			$descr = $domDoc->createElement('descr');			
			$crt = $domDoc->createElement('crt',$ca->crt);
			$serial = $domDoc->createElement('serial',$ca->serial);
			
			
			$cdataDescr = $domDoc->createCDATASection((string)$ca->descr);
			$descr->appendChild($cdataDescr);
			
			$newCA->appendChild($refid);
			$newCA->appendChild($descr);
			$newCA->appendChild($crt);
			$newCA->appendChild($serial);		
			$rootNode->appendChild($newCA);		
		}
	}
	/* FIN SECCION CA*/

	$domDoc->save("$newFileName.xml");
	sleep (1);	
	print_r(" Fueron extraidos $cantCertificados certificados. Se procederá a copiarlos para: ".implode(', ',$servers)." \r\n");
	
	/* SECCION COPIA Y ACTUALIZACIÓN DE LOS CERTIFICADOS*/
	$workDir= dirname(__FILE__);
	for($i=0;$i<count($servers);$i++){	
		
		print_r(" - copiando para $servers[$i]\r\n");	

		//copia del certificado		
		exec("scp $newFileName.xml root@$servers[$i]:$workDir");
		
		sleep (2);
		
		print_r(" - Ejecutando proceso de actualizacion del certificado \r\n");		
		//Ejecución del script de actualización de certificados
		exec("ssh root@$servers[$i] php $workDir/update-cert.php", $retArr);
		
		echo "cantidad ". count($retArr);
		echo $retArr;
		
		//@TODO Verificar si es necesario hacer esta copia
		echo "Copiando Ficheros de Certificado a cada pfsense";
		exec("scp -r /cf/conf/acme  root@$servers[$i]:/cf/conf/");
	}
	/* FIN SECCION COPIA Y ACTUALIZACIÓN DE LOS CERTIFICADOS*/
}else{
	print_r(" No existe el fichero $ficheroPath \r\n");	
}
	print_r("Proceso terminado \r\n");

?>
