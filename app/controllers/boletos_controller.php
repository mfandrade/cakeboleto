<?php
class BoletosController extends AppController {
	var $name		= 'Boletos';
	var $components	= array('CakeBoleto');
	var $uses		= array('Boleto');

	/**
	 * Action responsável diretamente pela geração do boleto.  Só deve
	 * ser chamada via requisição Ajax.  Lê as chaves de sessão
	 * LANCAMENTOS.DADOS (vinda de @see taxacondominial e etc) e escreve
	 * as LANCAMENTOS.PAGINAS e LANCAMENTOS.PAGINA, sobre o andamento do
	 * boleto pdf (para @see ajaxatualizarstatusboletos).
	 *
	 * @param unidade_id	id da unidade específica a gerar (null = todas)
	 */
	function ajaxgerarboletos($unidade_id= null) {

		if( !$this->RequestHandler->isAjax() || !$this->Session->check('LANCAMENTOS.DADOS') ) {
			$this->redirect(array('controller'=> 'menus', 'action'=> 'index'));
		}
		$dados	= $this->Session->read('LANCAMENTOS.DADOS');
/*		$dados['Lancamento']['tipo_lancamento_id']	= 0; //$this->Lancamento->TipoLancamento::TAXACONDOMINIAL;
		$dados['Lancamento']['usuario_id']			= 1; // TODO: pegar o auth
		$dados['Lancamento']['valor_documento']		= '280,00';
		$dados['Lancamento']['instrucao_boleto_id']	= 1;
		$dados['Lancamento']['mes_ano']				= '10/2009';
*/
		switch( $dados['Lancamento']['tipo_lancamento_id'] ) {
			default:
			case 0:	$dia	= Configure::read('lancamentos_taxacondominial_vencimento_dia');
					$tipoArq= 'taxacondominial';
					break;
			case 1:	$dia	= Configure::read('lancamentos_taxaextra_vencimento_dia');
					$tipoArq= 'taxaextra';
					break;
			case 2:	$dia	= Configure::read('lancamentos_multainfracao_vencimento_dia');
					$tipoArq= 'multainfracao';
					break;
		}
		list($mes, $ano)	= explode('/', $dados['Lancamento']['mes_ano']);
		$vencimento			= sprintf('%s/%s/%s', $dia, $mes, $ano);

		$boleto['data_vencimento']		= $vencimento;
		$boleto['agencia_cod_cedente']	= Configure::read('boletos_dados_agencia_cod_cedente');
		$boleto['cedente']				= Configure::read('boletos_dados_cedente');
		$boleto['cpf_cnpj_cedente']		= Configure::read('boletos_dados_cpfcnpj_cedente');
		$boleto['valor_documento']		= number_format($dados['Lancamento']['valor_documento'], 2, ',', '.');

		$this->Lancamento->InstrucaoBoleto->id	= $dados['Lancamento']['instrucao_boleto_id'];
		$instrucoes								= $this->Lancamento->InstrucaoBoleto->read();
		$boleto['instrucoes']					= $instrucoes['InstrucaoBoleto']['texto'];

		$this->Lancamento->Unidade->contain(array('Proprietario.nome', 'Proprietario.endereco', 'Proprietario.cep', 'Proprietario.bairro', 'Proprietario.cidade', 'Proprietario.uf'));

		if( $unidade_id == null ) {
			$unidades	= $this->Lancamento->Unidade->find('all', array('order'=> array('quadra_id', 'lote')));

		} else {
			$this->Lancamento->Unidade->id	= $unidade_id;
			$unidades[]						= $this->Lancamento->Unidade->read();
		}
		$this->Session->id('LANCAMENTOS');
		$this->Session->write('LANCAMENTOS.PAGINAS', sizeof($unidades));
		$i= 0;
		foreach( $unidades as $unidade ) {

			$quadra	= str_pad($unidade['Unidade']['quadra_id'], 2, '0', STR_PAD_LEFT);
			$lote	= str_pad($unidade['Unidade']['lote'], 2, '0', STR_PAD_LEFT);

			$boleto['numero_documento']	= $this->Boleto->gerarNumeroDocumento($quadra, $lote, $mes, $ano, $dados['Lancamento']['tipo_lancamento_id']);
			$boleto['nosso_numero']		= $this->Boleto->gerarNossoNumero($boleto['numero_documento'], Configure::read('boletos_dados_agencia_cod_cedente'), $vencimento);

			$boleto['sacado']			= $unidade['Proprietario']['nome'];
			$boleto['sacado_endereco']	= $unidade['Proprietario']['endereco'];
			$boleto['sacado_cep']		= $unidade['Proprietario']['cep'];
			$boleto['sacado_bairro']	= $unidade['Proprietario']['bairro'];
			$boleto['sacado_cidade']	= $unidade['Proprietario']['cidade'];
			$boleto['sacado_uf']		= $unidade['Proprietario']['uf'];
			$boleto['quadra']			= $quadra;
			$boleto['lote']				= $lote;

			$this->Session->write('LANCAMENTOS.PAGINA', ++$i);

			$this->CakeBoleto->novaPagina('HSBC', $boleto);
		}
		$diretorio		= Configure::read('boletos_arquivo_diretoriogravacao');
		$nomeArquivo	= Configure::read('boletos_arquivo_nomearquivo');
		// XXX: chaves: {TIPO}, {ANOMES}...
		$s	= array('{TIPO}', '{ANOMES}');
		$r	= array($tipoArq, $ano . $mes);
		$nomeArquivo	= str_replace($s, $r, $nomeArquivo);

		$this->CakeBoleto->saveFile($diretorio . DS . $nomeArquivo);
		//$this->CakeBoleto->download($nomeArquivo);

		$this->set(compact('nomeArquivo', 'diretorio'));
	}
}
