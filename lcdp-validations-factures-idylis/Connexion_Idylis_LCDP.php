<?php
//Connection a la BD Idylis
$oWS= new SoapClient("http://exe.idylis.com/idylisapi.asmx?wsdl");
$oSession= $oWS->authentification1(array('_codeAbonne'=>'****', '_identifiant'=>'*******', '_motdePasse'=>'***********'));
$oAuth['SessionID']= $oSession->AuthentificationAvec3Parametres1Result;
$oHeader= new SoapHeader('https://www.idylis.com/Idylisapi.asmx/','SessionIDHeader',$oAuth, false);
$oWS->__setSoapHeaders(array($oHeader));
?>