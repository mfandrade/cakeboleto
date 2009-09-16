<?php
App::import('Vendor','tcpdf/tcpdf');

define('DEFAULT_CELL_H', 7);
define('DEFAULT_AUTHOR', Configure::read('boletos_dados_cedente'));
define('ESPECIE_MOEDA_REAL', 9);

class CakeBoletoComponent extends Object {

	// -----------------------------------------------------------------
	function startup(&$controller) {

		$this->Boleto	= $controller->Boleto;
	}


	function initialize(&$component, $params= array()) {

		Configure::load('cake_boleto.config');

		$this->caminhoImagens	= Configure::read('boletos_arquivo_diretorioimagens');

		// TODO: melhorar
		if( !empty($params) ) {
		} else {
		}

		$title			= Configure::read('boletos_arquivo_pdf_titulo');
		$orientation	= 'P';
		$unit			= 'mm';
		$format			= 'A4';
		$margins		= array('left'=> 10, 'top'=> 10, 'right'=> -1);

		$this->tcpdf = new TCPDF($orientation, $unit, $format, true, 'UTF-8', false);

		if( is_array($margins) && (isset($margins['left']) && isset($margins['top']) && isset($margins['right'])) ) {
			$this->tcpdf->SetMargins($margins['left'], $margins['top'], $margins['right']);
		}

		$this->tcpdf->SetPrintHeader(false);
		$this->tcpdf->SetPrintFooter(false);
		$this->tcpdf->SetAutoPageBreak(false);
		$this->tcpdf->SetImageScale(PDF_IMAGE_SCALE_RATIO);
		//$this->tcpdf->SetLanguageArray($l);
		$this->tcpdf->SetTitle($title);
		$this->tcpdf->SetAuthor(DEFAULT_AUTHOR);
		$this->tcpdf->SetCreator(Configure::read('boletos_arquivo_pdf_criador'));
	}


	// -----------------------------------------------------------------
	function novaPagina($banco, $dados, $web= false) {
		$this->bancoLocal			= 'PAGAR PREFERENCIALMENTE EM AGÊNCIAS ';
		switch( $banco ) {
			case 'HSBC':	// TODO: melhorar
			default:
				$this->bancoLogo	= Configure::read('boletos_dados_banco_imagemlogo');
				$this->bancoCodigo	= Configure::read('boletos_dados_banco_codigo');
				$this->bancoLocal	.= 'DO HSBC';
				break;
		}

		$this->tcpdf->AddPage();

		$this->__trataDados($dados);
		$this->__primeiraParte($dados);
		$this->__segundaParte($dados);
		$this->__terceiraParte($dados);
	}

	function __trataDados(&$dados) {

		if( !in_array('parcela', $dados) || empty($dados['parcela']) ) {
			$dados['parcela']	= '001/001';
		}
		if( !in_array('aceite', $dados) || empty($dados['aceite']) ) {
			$dados['aceite']	= 'NÃO';
		}
		if( !in_array('especie', $dados) || empty($dados['especie']) ) {
			$dados['especie']	= ESPECIE_MOEDA_REAL . ' - REAL';
		}
		if( !in_array('carteira', $dados) || empty($dados['carteira']) ) {
			$dados['carteira']	= 'CNR';
		}
		if( !in_array('quantidade', $dados) || empty($dados['quantidade']) ) {
			$dados['quantidade']	= '';
		}
		if( !in_array('valor_unitario', $dados) || empty($dados['valor_unitario']) ) {
			$dados['valor_unitario']	= '';
		}

		$dados['endereco_completo_sacado']	= sprintf("%s\n%s\n%s - %s - %s, %s", $dados['sacado'] . ' - ' . 'Q'.$dados['quadra'].'L'.$dados['lote'], $dados['sacado_endereco'], $dados['sacado_cep'], $dados['sacado_bairro'], $dados['sacado_cidade'], $dados['sacado_uf']);
	}



	function __primeiraParte($dados) {
		// primeira parte boleto (comprovante entrega)
		// --------------------------------------------------------------------
		// linha 1 - logo, codigo banco e titulo da parte
		$this->tcpdf->Cell(30, DEFAULT_CELL_H, '', 'RB', 0, 'L');
		$this->tcpdf->Image($this->caminhoImagens.DS.$this->bancoLogo, 10, 10, 29);
		$this->tcpdf->SetFont('helvetica', 'B', 16);		// fonte codigo banco
		$this->tcpdf->Cell(20, DEFAULT_CELL_H, $this->bancoCodigo . '-' . ESPECIE_MOEDA_REAL, 'RB', 0, 'C');
		$this->tcpdf->SetFont('helvetica', 'B', 10);		// fonte subtitulo
		$this->tcpdf->Cell(140, DEFAULT_CELL_H, 'Comprovante de Entrega', 'B', 0, 'R');
		$this->tcpdf->Ln();

		// linha 2 - cedente, agência/código cendente, nro. documento
		$this->tcpdf->SetFont('helvetica', '', 5);		// fonte titulos celulas
		$this->tcpdf->Text(10+.5, 3*DEFAULT_CELL_H-2-.4, 'Cedente');
		$this->tcpdf->SetFont('helvetica', 'B', 8);		// fonte valores
		$this->tcpdf->Cell(85, DEFAULT_CELL_H, sprintf('%s     (%s)', $dados['cedente'], $dados['cpf_cnpj_cedente']), 'BR', 0, 'L');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(95+.5, 3*DEFAULT_CELL_H-2-.4, 'Agência/Código Cedente');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(40, DEFAULT_CELL_H, $dados['agencia_cod_cedente'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(135+.5, 3*DEFAULT_CELL_H-2-.4, 'Nro.Documento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(25, DEFAULT_CELL_H, $dados['numero_documento'], 'BR', 0, 'C');
		$this->tcpdf->Ln();

		// linha 3 - sacado, vencimento, valor
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(10+.5, 4*DEFAULT_CELL_H-2-.4, 'Sacado');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(85, DEFAULT_CELL_H, $dados['sacado'] . ' - Q' . $dados['quadra'] . 'L' . $dados['lote'], 'BR', 0, 'L');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(95+.5, 4*DEFAULT_CELL_H-2-.4, 'Vencimento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(40, DEFAULT_CELL_H, $dados['data_vencimento'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(135+.5, 4*DEFAULT_CELL_H-2-.4, 'Valor do Documento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(25, 7, $dados['valor_documento'], 'BR', 0, 'C');
		$this->tcpdf->Ln();

		// linha 4 - recebemos o bloqueto acima, data, ass// data, entregador
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->MultiCell(70, 2*DEFAULT_CELL_H, "\nRecebi(emos) o bloqueto/título\ncom as características acima.\n", 'BR', 'L', 0, 0);

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(80+.5, 5*DEFAULT_CELL_H-2-.4, 'Data');
		$this->tcpdf->MultiCell(30, DEFAULT_CELL_H, '', 'BR', 'L', 0, 0, 80, 5*DEFAULT_CELL_H-4);

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(110+.5, 5*DEFAULT_CELL_H-2-.4, 'Entregador');
		$this->tcpdf->MultiCell(50, DEFAULT_CELL_H, '', 'BR', 'L', 0, 0, 110, 5*DEFAULT_CELL_H-4);

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(80+.5, 6*DEFAULT_CELL_H-2-.4, 'Data');
		$this->tcpdf->MultiCell(30, DEFAULT_CELL_H, '', 'BR', 'L', 0, 0, 80, 6*DEFAULT_CELL_H-4);

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(110+.5, 6*DEFAULT_CELL_H-2-.4, 'Assinatura');
		$this->tcpdf->MultiCell(50, DEFAULT_CELL_H, '', 'BR', 'L', 0, 0, 110, 6*DEFAULT_CELL_H-4);

		$this->tcpdf->SetFont('helvetica', '', 6);
		$this->tcpdf->MultiCell(40, 4*DEFAULT_CELL_H-1, "( )Mudou-se\n( )Ausente\n( )Não existe nº indicado\n( )Recusado\n( )Não procurado\n( )Endereço insuficiente\n( )Desconhecido\n( )Falecido\n( )Outros (anotar no verso)", 'B', 'L', 0, 0, 160, 3*DEFAULT_CELL_H-3);
		$this->tcpdf->Ln();

		$this->tcpdf->SetFont('helvetica', '', 7);
		$this->tcpdf->Cell(190, 4, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _');
		$this->tcpdf->Ln();
	}

	function __segundaParte($dados) {

		// linha 1 - logo, codigo banco e titulo da parte
		$this->tcpdf->Cell(30, DEFAULT_CELL_H, '', 'RB', 0, 'L');
		$this->tcpdf->Image($this->caminhoImagens . DS . $this->bancoLogo, 10, 7*DEFAULT_CELL_H, 29);
		$this->tcpdf->SetFont('helvetica', 'B', 16);		// fonte codigo banco
		$this->tcpdf->Cell(20, DEFAULT_CELL_H, $this->bancoCodigo . '-' . ESPECIE_MOEDA_REAL, 'RB', 0, 'C');
		$this->tcpdf->SetFont('helvetica', 'B', 10);		// fonte subtitulo
		$this->tcpdf->Cell(140, DEFAULT_CELL_H, 'Recido do Sacado', 'B', 0, 'R');
		$this->tcpdf->Ln();

		// linha 2 - local de pagamento, parcela, vencimento
		$this->tcpdf->SetFont('helvetica', '', 5);		// fonte titulos celulas
		$this->tcpdf->Text(10+.5, 9*DEFAULT_CELL_H-5-.4, 'Local de Pagamento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);		// fonte valores
		$this->tcpdf->Cell(145, DEFAULT_CELL_H, $this->bancoLocal, 'RB', 0, 'L');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 9*DEFAULT_CELL_H-5-.4, 'Parcela');
		$this->tcpdf->SetFont('helvetica', 'B', 8);		// fonte valores
		$this->tcpdf->Cell(20, DEFAULT_CELL_H, $dados['parcela'], 'RB', 0, 'C');

		$this->tcpdf->Line(155, 8*DEFAULT_CELL_H-.2, 200, 8*DEFAULT_CELL_H-.2);
		$this->tcpdf->Line(155, 17*DEFAULT_CELL_H+.2, 200, 17*DEFAULT_CELL_H+.2);
		$this->tcpdf->Line(155-.2, 8*DEFAULT_CELL_H-.2, 155-.2, 17*DEFAULT_CELL_H+.2);

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(175+.5, 9*DEFAULT_CELL_H-5-.4, 'Vencimento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);		// fonte valores
		$this->tcpdf->Cell(25, DEFAULT_CELL_H, $dados['data_vencimento'], 'B', 0, 'C');
		$this->tcpdf->Ln();

		// linha 3 - cedente e cnpj, agencia/codigo cedente
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(10+.5, 10*DEFAULT_CELL_H-5-.4, 'Cedente');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(145, DEFAULT_CELL_H, sprintf('%s                                   (%s)', $dados['cedente'], $dados['cpf_cnpj_cedente']), 'BR', 0, 'L');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 10*DEFAULT_CELL_H-5-.4, 'Agência/Código do Cedente');
		$this->tcpdf->SetFont('helvetica', 'B', 8);		// fonte valores
		$this->tcpdf->Cell(45, DEFAULT_CELL_H, $dados['agencia_cod_cedente'], 'B', 0, 'C');
		$this->tcpdf->Ln();

		// linha 4 - data da emissão, número do documento, espécie doc, aceite, data do processamento, nosso número/código do cocumento
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(10+.5, 11*DEFAULT_CELL_H-5-.4, 'Data de Emissão');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(35, DEFAULT_CELL_H, date('d/m/Y'), 'BR', 0, 'L');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(45+.5, 11*DEFAULT_CELL_H-5-.4, 'Número do Documento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(40, DEFAULT_CELL_H, $dados['numero_documento'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(85+.5, 11*DEFAULT_CELL_H-5-.4, 'Espécie Doc.');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(20, DEFAULT_CELL_H, '', 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(105+.5, 11*DEFAULT_CELL_H-5-.4, 'Aceite');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(15, DEFAULT_CELL_H, $dados['aceite'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(120+.5, 11*DEFAULT_CELL_H-5-.4, 'Data do Processamento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(35, DEFAULT_CELL_H, '', 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 11*DEFAULT_CELL_H-5-.4, 'Nosso Número/Código do Documento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(45, DEFAULT_CELL_H, $dados['nosso_numero'], 'B', 0, 'C');
		$this->tcpdf->Ln();

		// linha 5 - uso do banco, carteira, espécie, quantidade, valor, (=) valor do documento
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(10+.5, 12*DEFAULT_CELL_H-5-.4, 'Uso do Banco');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(35, DEFAULT_CELL_H, '', 'BR', 0, 'L');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(45+.5, 12*DEFAULT_CELL_H-5-.4, 'Carteira');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(20, DEFAULT_CELL_H, $dados['carteira'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(65+.5, 12*DEFAULT_CELL_H-5-.4, 'Espécie');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(20, DEFAULT_CELL_H, $dados['especie'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(85+.5, 12*DEFAULT_CELL_H-5-.4, 'Quantidade');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(35, DEFAULT_CELL_H, $dados['quantidade'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(120+.5, 12*DEFAULT_CELL_H-5-.4, 'Valor');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(35, DEFAULT_CELL_H, $dados['valor_unitario'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 12*DEFAULT_CELL_H-5-.4, '(=) Valor do Documento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(45, DEFAULT_CELL_H, $dados['valor_documento'], 'B', 0, 'C');
		$this->tcpdf->Ln();

		// linhona 6 - instrucoes (texto de responsabilidade do cedente) | ...
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(10+.5, 13*DEFAULT_CELL_H-5-.4, 'Instruções (texto de responsabilidade do cedente)');
		$this->tcpdf->SetFont('helvetica', '', 9);

		$this->tcpdf->MultiCell(145, 5*DEFAULT_CELL_H, "\n" . $dados['instrucoes'], 'BR', 'L', 0, 0, 10, 12*DEFAULT_CELL_H);

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 13*DEFAULT_CELL_H-5-.4, '(-) Descontos/Abatimentos');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->MultiCell(45, DEFAULT_CELL_H, '', 'B', 'L', 0, 0, 155, 12*DEFAULT_CELL_H);
		$this->tcpdf->Ln();

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 14*DEFAULT_CELL_H-5-.4, '(-) Outras Deduções');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->MultiCell(45, DEFAULT_CELL_H, '', 'B', 'L', 0, 0, 155, 13*DEFAULT_CELL_H);
		$this->tcpdf->Ln();

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 15*DEFAULT_CELL_H-5-.4, '(+) Multa/Mora');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->MultiCell(45, DEFAULT_CELL_H, '', 'B', 'L', 0, 0, 155, 14*DEFAULT_CELL_H);
		$this->tcpdf->Ln();

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 16*DEFAULT_CELL_H-5-.4, '(+) Outros Acréscimos');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->MultiCell(45, DEFAULT_CELL_H, '', 'B', 'L', 0, 0, 155, 15*DEFAULT_CELL_H);
		$this->tcpdf->Ln();

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 17*DEFAULT_CELL_H-5-.4, '(=) Valor Cobrado');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->MultiCell(45, DEFAULT_CELL_H, '', 'B', 'L', 0, 0, 155, 16*DEFAULT_CELL_H);
		$this->tcpdf->Ln();

		// linha x - nome e endereço do sacado
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(10+.5, 18*DEFAULT_CELL_H-5-.4, 'Sacado');
		$this->tcpdf->Text(10+.5, 19*DEFAULT_CELL_H+.1, 'Sacador/Avalista');
		$this->tcpdf->Text(170+.5, 19*DEFAULT_CELL_H+.1, 'Código de Baixa');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(190, 2*DEFAULT_CELL_H+1, '', 'B', 0, 'L');	// espaçador
		$this->tcpdf->MultiCell(180, 2*DEFAULT_CELL_H+1, $dados['endereco_completo_sacado'], 0, 'L', 0, 0, 20, 18*DEFAULT_CELL_H-6.5);
		$this->tcpdf->Ln();

		// linha ultima - (para dados de cheque)
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->MultiCell(145, 2*DEFAULT_CELL_H+1, "Recebimento através do cheque nº:\ndo Banco:\n\nEsta quitação só terá validade após\npagamento do cheque pelo Banco sacado.", 0, 'L', 0, 0, 10, 19*DEFAULT_CELL_H+1);
		$this->tcpdf->MultiCell(145, 2*DEFAULT_CELL_H+1, "__________________________________ Autenticação Mecânica __________________________________", 0, 'L', 0, 0, 115, 19*DEFAULT_CELL_H+1);
		$this->tcpdf->Ln();
		$this->tcpdf->Ln();
	}

	function __terceiraParte($dados) {

		$codigo_barras	= $this->Boleto->montarCodigoBarras($dados['numero_documento'], $dados['agencia_cod_cedente'], $dados['data_vencimento'], $dados['valor_documento']);

		// linha 1 - logo, codigo banco e titulo da parte
		$this->tcpdf->Cell(30, 8, '', 'RB', 0, 'L');
		$this->tcpdf->Image($this->caminhoImagens . DS . $this->bancoLogo, 10, 24*DEFAULT_CELL_H-3, 29);
		$this->tcpdf->SetFont('helvetica', 'B', 16);		// fonte codigo banco
		$this->tcpdf->Cell(20, 8, $this->bancoCodigo . '-' . ESPECIE_MOEDA_REAL, 'RB', 0, 'C');
		$this->tcpdf->SetFont('helvetica', '', 12);		// fonte subtitulo
		$this->tcpdf->Cell(140, 8, $this->Boleto->montarLinhaDigitavel($codigo_barras), 'B', 0, 'C');
		$this->tcpdf->Ln();

		// linha 2 - local de pagamento, parcela, vencimento
		$this->tcpdf->SetFont('helvetica', '', 5);		// fonte titulos celulas
		$this->tcpdf->Text(10+.5, 25*DEFAULT_CELL_H-1-.4, 'Local de Pagamento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);		// fonte valores
		$this->tcpdf->Cell(145, DEFAULT_CELL_H, $this->bancoLocal, 'RB', 0, 'L');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 25*DEFAULT_CELL_H-1-.4, 'Parcela');
		$this->tcpdf->SetFont('helvetica', 'B', 8);		// fonte valores
		$this->tcpdf->Cell(20, DEFAULT_CELL_H, $dados['parcela'], 'RB', 0, 'C');

		$this->tcpdf->Line(155, 25*DEFAULT_CELL_H-3-.2, 200, 25*DEFAULT_CELL_H-3-.2);
		$this->tcpdf->Line(155, 34*DEFAULT_CELL_H-3+.2, 200, 34*DEFAULT_CELL_H-3+.2);
		$this->tcpdf->Line(155-.2, 25*DEFAULT_CELL_H-3-.2, 155-.2, 34*DEFAULT_CELL_H-3-.2);

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(175+.5, 25*DEFAULT_CELL_H-1-.4, 'Vencimento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);		// fonte valores
		$this->tcpdf->Cell(25, DEFAULT_CELL_H, $dados['data_vencimento'], 'B', 0, 'C');
		$this->tcpdf->Ln();

		// linha 3 - cedente e cnpj, agencia/codigo cedente
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(10+.5, 26*DEFAULT_CELL_H-1-.4, 'Cedente');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(145, DEFAULT_CELL_H, sprintf('%s                                   (%s)', $dados['cedente'], $dados['cpf_cnpj_cedente']), 'BR', 0, 'L');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 26*DEFAULT_CELL_H-1-.4, 'Agência/Código do Cedente');
		$this->tcpdf->SetFont('helvetica', 'B', 8);		// fonte valores
		$this->tcpdf->Cell(45, DEFAULT_CELL_H, $dados['agencia_cod_cedente'], 'B', 0, 'C');
		$this->tcpdf->Ln();

		// linha 4 - data da emissão, número do documento, espécie doc, aceite, data do processamento, nosso número/código do cocumento
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(10+.5, 27*DEFAULT_CELL_H-1-.4, 'Data de Emissão');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(35, DEFAULT_CELL_H, date('d/m/Y'), 'BR', 0, 'L');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(45+.5, 27*DEFAULT_CELL_H-1-.4, 'Número do Documento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(40, DEFAULT_CELL_H, $dados['numero_documento'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(85+.5, 27*DEFAULT_CELL_H-1-.4, 'Espécie Doc.');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(20, DEFAULT_CELL_H, '', 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(105+.5, 27*DEFAULT_CELL_H-1-.4, 'Aceite');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(15, DEFAULT_CELL_H, $dados['aceite'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(120+.5, 27*DEFAULT_CELL_H-1-.4, 'Data do Processamento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(35, DEFAULT_CELL_H, '', 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 27*DEFAULT_CELL_H-1-.4, 'Nosso Número/Código do Documento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(45, DEFAULT_CELL_H, $dados['nosso_numero'], 'LB', 0, 'C');
		$this->tcpdf->Ln();

		// linha 5 - uso do banco, carteira, espécie, quantidade, valor, (=) valor do documento
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(10+.5, 28*DEFAULT_CELL_H-1-.4, 'Uso do Banco');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(35, DEFAULT_CELL_H, '', 'BR', 0, 'L');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(45+.5, 28*DEFAULT_CELL_H-1-.4, 'Carteira');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(20, DEFAULT_CELL_H, $dados['carteira'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(65+.5, 28*DEFAULT_CELL_H-1-.4, 'Espécie');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(20, DEFAULT_CELL_H, $dados['especie'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(85+.5, 28*DEFAULT_CELL_H-1-.4, 'Quantidade');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(35, DEFAULT_CELL_H, $dados['quantidade'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(120+.5, 28*DEFAULT_CELL_H-1-.4, 'Valor');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(35, DEFAULT_CELL_H, $dados['valor_unitario'], 'BR', 0, 'C');

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 28*DEFAULT_CELL_H-1-.4, '(=) Valor do Documento');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(45, DEFAULT_CELL_H, $dados['valor_documento'], 'B', 0, 'C');
		$this->tcpdf->Ln();

		// linhona 6 - instrucoes (texto de responsabilidade do cedente) | ...
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(10+.5, 29*DEFAULT_CELL_H-1-.4, 'Instruções (texto de responsabilidade do cedente)');
		$this->tcpdf->SetFont('helvetica', '', 9);

		$this->tcpdf->MultiCell(145, 5*DEFAULT_CELL_H, "\n" . $dados['instrucoes'], 'BR', 'L', 0, 0, 10, 29*DEFAULT_CELL_H-3);

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 29*DEFAULT_CELL_H-1-.4, '(-) Descontos/Abatimentos');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->MultiCell(45, DEFAULT_CELL_H, '', 'B', 'L', 0, 0, 155, 29*DEFAULT_CELL_H-3);
		$this->tcpdf->Ln();

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 30*DEFAULT_CELL_H-1-.4, '(-) Outras Deduções');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->MultiCell(45, DEFAULT_CELL_H, '', 'B', 'L', 0, 0, 155, 30*DEFAULT_CELL_H-3);
		$this->tcpdf->Ln();

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 31*DEFAULT_CELL_H-1-.4, '(+) Multa/Mora');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->MultiCell(45, DEFAULT_CELL_H, '', 'B', 'L', 0, 0, 155, 31*DEFAULT_CELL_H-3);
		$this->tcpdf->Ln();

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 32*DEFAULT_CELL_H-1-.4, '(+) Outros Acréscimos');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->MultiCell(45, DEFAULT_CELL_H, '', 'B', 'L', 0, 0, 155, 32*DEFAULT_CELL_H-3);
		$this->tcpdf->Ln();

		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(155+.5, 33*DEFAULT_CELL_H-1-.4, '(=) Valor Cobrado');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->MultiCell(45, DEFAULT_CELL_H, '', 'B', 'L', 0, 0, 155, 33*DEFAULT_CELL_H-3);
		$this->tcpdf->Ln();

		// linha x - nome e endereço do sacado
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->Text(10+.5, 34*DEFAULT_CELL_H-1-.4, 'Sacado');
		$this->tcpdf->Text(10+.5, 36*DEFAULT_CELL_H-3+.2, 'Sacador/Avalista');
		$this->tcpdf->Text(170+.5, 36*DEFAULT_CELL_H-3+.2, 'Código de Baixa');
		$this->tcpdf->SetFont('helvetica', 'B', 8);
		$this->tcpdf->Cell(190, 2*DEFAULT_CELL_H+1, '', 'B', 0, 'L');	// espaçador
		$this->tcpdf->MultiCell(180, 2*DEFAULT_CELL_H+1, $dados['endereco_completo_sacado'], 0, 'L', 0, 0, 20, 34*DEFAULT_CELL_H-2-.5);
		$this->tcpdf->Ln();

		// linha ultima - código de barra
		$this->tcpdf->SetFont('helvetica', '', 5);
		$this->tcpdf->MultiCell(145, 2*DEFAULT_CELL_H+1, "__________________________________ Autenticação Mecânica __________________________________", 0, 'L', 0, 0, 115, 36*DEFAULT_CELL_H-2);
		$this->tcpdf->Ln();
		//$this->tcpdf->Image($this->caminhoImagens . DS . '/codigobarras.jpg', 10, 37*DEFAULT_CELL_H-3, 103, 13);
		$this->__imprimeCodigoBarras(10, 39*DEFAULT_CELL_H, $codigo_barras);

		$this->tcpdf->SetFont('helvetica', 'B', 10);		// fonte subtitulo
		$this->tcpdf->Cell(190, DEFAULT_CELL_H, 'Ficha de Compensação', 0, 0, 'R');

	}

//	function download($filename) {
//		$this->tcpdf->Output($filename, 'I');
//	}

	function saveFile($filename) {
		$this->tcpdf->Output($filename, 'F');
	}


	// -----------------------------------------------------------------
	function __esquerda($entra, $comp){
		return substr($entra, 0, $comp);
	}

	function __direita($entra, $comp){
		return substr($entra, strlen($entra)-$comp, $comp);
	}

	function __imprimeCodigoBarras($x, $y, $codigo) {

		$fino		= Configure::read('boletos_codigobarra_fatorlargura');
		$largo		= 3 * $fino;
		$altura		= Configure::read('boletos_codigobarra_altura');

		$barcodes[0] = "00110" ;
		$barcodes[1] = "10001" ;
		$barcodes[2] = "01001" ;
		$barcodes[3] = "11000" ;
		$barcodes[4] = "00101" ;
		$barcodes[5] = "10100" ;
		$barcodes[6] = "01100" ;
		$barcodes[7] = "00011" ;
		$barcodes[8] = "10010" ;
		$barcodes[9] = "01010" ;

		for( $f1= 9; $f1 >= 0; $f1-- ) {
			for( $f2= 9; $f2 >= 0; $f2-- ) {
				$f		= ($f1 * 10) + $f2 ;
				$texto	= "" ;
					for( $i= 1; $i < 6; $i++ ) {
						$texto .=  substr($barcodes[$f1],($i-1),1) . substr($barcodes[$f2],($i-1),1);
					}
				$barcodes[$f]	= $texto;
			}
		}

		$pos= $x;
		//Guarda inicial
		$this->tcpdf->Image( $this->caminhoImagens . DS . 'p.png', $pos, $y, $fino, $altura); $pos+= $fino;
		$this->tcpdf->Image( $this->caminhoImagens . DS . 'b.png', $pos, $y, $fino, $altura); $pos+= $fino;
		$this->tcpdf->Image( $this->caminhoImagens . DS . 'p.png', $pos, $y, $fino, $altura); $pos+= $fino;
		$this->tcpdf->Image( $this->caminhoImagens . DS . 'b.png', $pos, $y, $fino, $altura); $pos+= $fino;

		$texto = $codigo ;
		if( (strlen($texto) % 2) <> 0 ){
			$texto = "0" . $texto;
		}

		// Draw dos dados
		while( strlen($texto) > 0 ) {
			$i = round($this->__esquerda($texto,2));
			$texto = $this->__direita($texto,strlen($texto)-2);
			$f = $barcodes[$i];
			for( $i= 1; $i < 11; $i+= 2 ) {
				if( substr($f,($i-1),1) == "0" ) {
					$f1 = $fino ;
				} else {
					$f1 = $largo;
				}
				$this->tcpdf->Image( $this->caminhoImagens . DS . 'p.png', $pos, $y, $f1, $altura); $pos+= $f1;

				if( substr($f,$i,1) == "0" ) {
					$f2 = $fino ;
				} else {
					$f2 = $largo ;
				}
				$this->tcpdf->Image( $this->caminhoImagens . DS . 'b.png', $pos, $y, $f2, $altura); $pos+= $f2;
			}
		}
		// Draw guarda final
		$this->tcpdf->Image( $this->caminhoImagens . DS . 'p.png', $pos, $y, $largo, $altura); $pos+= $largo;
		$this->tcpdf->Image( $this->caminhoImagens . DS . 'b.png', $pos, $y, $fino, $altura); $pos+= $fino;
		$this->tcpdf->Image( $this->caminhoImagens . DS . 'p.png', $pos, $y, $fino, $altura); $pos+= $fino;
	}
}
