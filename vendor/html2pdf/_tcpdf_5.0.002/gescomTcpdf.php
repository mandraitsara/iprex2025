<?php
require_once('config/lang/eng.php');
require_once('tcpdf.php');

class gescomTcpdf extends TCPDF {

	public function Header() {
		$this->writeHTML(utf8_decode('toto header'));;
	}


	public function Footer() {
		$this->writeHTML(utf8_decode('toto footer'));;
	}
}