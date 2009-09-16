<?php
App::import('Model', 'Boleto');
class BoletoTestCase extends CakeTestCase {

	function start() {

		$this->Boleto	=& ClassRegistry::init('Boleto');

		$this->quadra	= '03';
		$this->lote		= '05';
		$this->mes		= '07';
		$this->ano		= '2009';
		$this->tipo		= 0;
	}

	function end() {}

	function testGerarNumeroDocumento() {

		$tipo	= 0;
		$gerado	= $this->Boleto->gerarNumeroDocumento(
			  $this->quadra, $this->lote, $this->mes, $this->ano, $tipo);
		$esperado	= '0000003050709';

		$this->assertEqual($gerado, $esperado);


		$tipo	= 1;
		$gerado	= $this->Boleto->gerarNumeroDocumento(
			  $this->quadra, $this->lote, $this->mes, $this->ano, $tipo);
		$esperado	= '0000103050709';

		$this->assertEqual($gerado, $esperado);


		$tipo	= 2;
		$gerado	= $this->Boleto->gerarNumeroDocumento(
			  $this->quadra, $this->lote, $this->mes, $this->ano, $tipo);
		$esperado	= '0000203050709';

		$this->assertEqual($gerado, $esperado);
	}

	function testGerarNossoNumero() {

		$ndoc		= $this->Boleto->gerarNumeroDocumento(
			  $this->quadra, $this->lote, $this->mes, $this->ano);
		$agencia	= '3432599';
		$vencimento	= '08/07/2009';
		$gerado		= $this->Boleto->gerarNossoNumero($ndoc, $agencia, $vencimento);
		$esperado	= '0000003050709040';

		$this->assertEqual($gerado, $esperado);
	}

	function testDateDiff() {

		$semana		= $this->Boleto->dateDiff(date('d/m/Y'), date('d/m/Y', strtotime('+1 week')));
		$this->assertEqual($semana, 7);

		$ano		= $this->Boleto->dateDiff('31/12/2008', '31/12/2009');
		$this->assertEqual($ano, 365);

		$bissexto	= $this->Boleto->dateDiff('01/02/2000', '01/03/2000');
		$this->assertEqual($bissexto, 29);
	}

	function testDateJulian() {

		$juliana	= $this->Boleto->dateJulian('31/01/2009');
		$this->assertEqual($juliana, '0319');

		$juliana	= $this->Boleto->dateJulian('11/02/2000');
		$this->assertEqual($juliana, '0420');

		$juliana	= $this->Boleto->dateJulian('31/12/2000');
		$this->assertEqual($juliana, '3660');

		$juliana	= $this->Boleto->dateJulian('31/12/2001');
		$this->assertEqual($juliana, '3651');
	}

	function testEnxugaVencimento() {

		$enxuta	= $this->Boleto->enxugaVencimento('01/02/2003');
		$this->assertEqual($enxuta, '010203');
	}

	function testMontarCodigoBarras() {
		// exemplo do manual novo (pdf email)
		$m['nosso_numero']	= '0000239104761';
		$m['cod_cedente']	= '8351202';
		$m['vencimento']	= '04/07/2008';
		$m['valor']			= '0000120000';

		$barras		= $this->Boleto->montarCodigoBarras($m['nosso_numero'], $m['cod_cedente'], $m['vencimento'], $m['valor']);

		$this->assertEqual(strlen($barras), 44, 'O código de barras não tem 44 posições.');


		$cod_hsbc	= substr($barras, 0, 3);
		$this->assertEqual($cod_hsbc, '399', 'Código do HSBC não confere.');

		$moeda		= substr($barras, 3, 1);
		$this->assertEqual($moeda, '9', 'Código de moeda não confere.');

		$fator		= substr($barras, 5, 4);
		$this->assertEqual($fator, '3923', 'Fator de vencimento não confere.');

		$valor		= substr($barras, 9, 10);
		$this->assertEqual($valor, $m['valor'], 'Valor do documento não confere.');

		$cedente	= substr($barras, 19, 07);
		$this->assertEqual($cedente, $m['cod_cedente'], 'Código do cedente não confere.');

		$documento	= substr($barras, 26, 13);
		$this->assertEqual($documento, $m['nosso_numero'], 'Nosso número/Cod. documento não confere.');

		$data	= substr($barras, 39, 4);
		$this->assertEqual($data, '1868', 'Data juliana não confere.');

		$app	= substr($barras, 43, 1);
		$this->assertEqual($app, '2', 'Código do aplicativo não confere.');

		$esperado	= '39994392300001200008351202000023910476118682';
		$this->assertEqual($barras, $esperado);
	}
}









