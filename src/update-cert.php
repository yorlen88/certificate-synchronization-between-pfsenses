<?php 
/*
* DESCRIPCION
* Actualiza los certificados a partir de un XML dado
* Actualiza la CA 
*
* Variables a definir
* $ficheroPath --- ruta del fichero del cual va a extraer los certificados. (Fichero resultante del Otro Script)
* $ficheroConfigPath --- ruta del fichero de configuracion que en el que se van a actualizar los certificados
*
*
* @Author: 	YORLEN GUIRADO MAS <yorlen88@gmail.com>
*			LUIS ENRIQUE 
*/

print_r("Iniciando el script \r\n");

/*CONFIGURACION INICIAL*/
$ficheroConfigPath='/cf/conf/config.xml';

$workDir= dirname(__FILE__);
$ficheroPath = "$workDir/cert.xml";
/*FIN DE LA CONFIGURACION INICIAL*/

if(file_exists($ficheroPath) && file_exists($ficheroConfigPath)){
	//crear copia del fichero a modificar
	print_r(" Creando copia del fichero de configuracion en $ficheroConfigPath.backup \r\n");
	if(!copy($ficheroConfigPath, $ficheroConfigPath.'.backup')){
		print_r(" Error al crear la copia del fichero $ficheroConfigPath \r\n");
	}
	//sleep(1);
	
	$xmlSource = simplexml_load_file($ficheroPath);
	$domDoc = new DOMDocument();
	$domDoc->preserveWhiteSpace = false;
	$domDoc->formatOutput= true;	
	
	$domDoc->load($ficheroConfigPath);
		
	
	/* SECCION CERTIFICADOS*/
	$cantCertificadosToUpdate = count($xmlSource->cert);
	print_r(" Hay $cantCertificadosToUpdate certificados para ser actualizados. \r\n");
	
	$cantUpdates = 0;
	$cantInsert = 0;	
	for($i=0;$i<$cantCertificadosToUpdate;$i++){
		$certSource = $xmlSource->cert[$i];		
		$certificados = $domDoc->getElementsByTagName("cert");	
		$certExist = false;	

		foreach($domDoc->getElementsByTagName("cert") as $key=>$cert){
			if($cert->getElementsByTagName("descr")[0]->nodeValue==(string)$certSource->descr){
				print_r(" - Actualizado el certificado de $certSource->descr \r\n");
				$certExist = true;
				$cantUpdates++;
				$cert->getElementsByTagName("prv")[0]->nodeValue=$certSource->prv;
				$cert->getElementsByTagName("crt")[0]->nodeValue=$certSource->crt;
			}
		}		
		$cantCertificados = $certificados->length;	
		if($certExist===false){
			print_r(" El certificado $certSource->descr no existe, procediendo a añadirlo \r\n");
			$cantInsert++;
			$domDoc->preserveWhiteSpace = false;
			$domDoc->formatOutput= true;	

			$newCert = $domDoc->createElement('cert');			
			$refid = $domDoc->createElement('refid',$certSource->refid);
			$descr = $domDoc->createElement('descr');
			$prv = $domDoc->createElement('prv',$certSource->prv);
			$crt = $domDoc->createElement('crt',$certSource->crt);
			$caref = $domDoc->createElement('caref',$certSource->caref);
			
			$cdataDescr = $domDoc->createCDATASection((string)$certSource->descr);
			$descr->appendChild($cdataDescr);
			
			$newCert->appendChild($refid);			
			$newCert->appendChild($descr);
			$newCert->appendChild($prv);
			$newCert->appendChild($crt);
			$newCert->appendChild($caref);						
			$domDoc->getElementsByTagName("cert")[$cantCertificados-1]->parentNode->insertBefore($newCert,$domDoc->getElementsByTagName("cert")[$cantCertificados-1]);				
		}
	}
	/* FIN SECCION CERTIFICADOS*/
	/* SECCION CA*/

	$cantCAToUpdate = count($xmlSource->ca);
	print_r(" Hay $cantCAToUpdate CA para ser actualizados. \r\n");
	
	$cantCAUpdates = 0;
	$cantCAInsert = 0;	
	for($i=0;$i<$cantCAToUpdate;$i++){
		$caSource = $xmlSource->ca[$i];		
		$ca = $domDoc->getElementsByTagName("ca");	
		$caExist = false;	

		foreach($domDoc->getElementsByTagName("ca") as $key=>$ca){
			if($ca->getElementsByTagName("descr")[0]->nodeValue==(string)$caSource->descr){
				print_r(" - Actualizado la CA de $caSource->descr \r\n");
				$caExist = true;
				$cantCAUpdates++;
				$ca->getElementsByTagName("crt")[0]->nodeValue=$caSource->crt;
			}
		}		
		$cantCA = $ca->length;	
		if($caExist===false){
			print_r(" La CA $caSource->descr no existe, procediendo a añadirla \r\n");
			$cantCAInsert++;
			$domDoc->preserveWhiteSpace = false;
			$domDoc->formatOutput= true;	

			$newCA = $domDoc->createElement('ca');			
			$refid = $domDoc->createElement('refid',$caSource->refid);
			$descr = $domDoc->createElement('descr');
			$crt = $domDoc->createElement('crt',$caSource->crt);
			$serial = $domDoc->createElement('serial',$caSource->serial);
			
			$cdataDescr = $domDoc->createCDATASection((string)$caSource->descr);
			$descr->appendChild($cdataDescr);
			
			$newCA->appendChild($refid);			
			$newCA->appendChild($descr);
			$newCA->appendChild($crt);
			$newCA->appendChild($serial);						
			$domDoc->getElementsByTagName("pfsense")[0]->appendChild($newCA);				
		}
	}
	/* FIN SECCION CA*/	
	
	$domDoc->save($ficheroConfigPath);
	print_r(" Limpiando cache y reiniciando webgui  \r\n");
	exec ("rm /tmp/config.cache && /etc/rc.restart_webgui"); //si se necesita ejecutar algún otro comando (como reiniciar portal captivo), hacerlo aquí
}else{
	print_r("Los ficheros no existen \r\n");
}	
	print_r(" RESULTADO CERTIFICADOS: $cantUpdates actualizaciones y $cantInsert insersiones. \r\n RESULTADO CA: $cantCAUpdates actualizaciones y $cantCAInsert insersiones.\r\nScript terminado. \r\n ");

?>