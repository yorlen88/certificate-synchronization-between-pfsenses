# certificate-synchronization-between-pfsenses 
Sincronización de certificados entre PfSenses

### El problema
En una organización tenemos varias subredes y servidores. Empleamos PfSense para administrar el tráfico entre estas. En cada subred tenemos un PfSense. Los certificados (validados por Let´s Encript) y la autoridad de certificación (CA) de la organización son generados en uno de estos PfSense y renovados cada cierto tiempo. Necesitamos una uniformidad entre los PfSense para que cada uno posea y emplee estos certificados. 
### Solución
2 script, que haciendo uso de bash y php, se encargan de extraer los certificados y CA, deseados, desde el fichero de configuración del PfSense que los genera, copiarlos hacia los demás servidores y actualizar los ficheros de configuración de cada uno de los PfSenses con los certificados. 
*Esto requiere que inicialmente en cada uno de estos servidores se haya importado manualmente los certificados y CA (Acción a realizarse una única vez). Además de que cada servidor debe tener en la misma estructura de directorios, el fichero __update-cert.php__ *
* __extract_copy_cert.php__ extrae los certificados y los almacena en un .XML que copia hacia los servidores especificados (en la misma estructura de directorios en la que se encuentra __extract_copy_cert.php__), luego ejecuta el script que los actualiza
* __update-cert.php__ actualiza los certificados y CA copiados en el fichero de configuración del PfSense y ejecuta algunos comandos para aplicar los cambios como limpiar la cache y reiniciar el PfSense

Para ejecutar los scripts es necesario hacerlo como root 