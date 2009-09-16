<?php
// boleto
// ---------------------------------------------------------------------
$config['boletos_arquivo_diretorioimagens']		= 'http://localhost/siac/img/boleto';
$config['boletos_arquivo_diretoriogravacao']	= '/tmp';
$config['boletos_arquivo_nomearquivo']			= 'boletos-{TIPO}-{ANOMES}.pdf';
$config['boletos_arquivo_pdf_titulo']			= 'BOLETOS';
$config['boletos_arquivo_pdf_criador']			= 'CAKEBOLETO';

$config['boletos_codigobarra_fatorlargura']		= 0.25;
$config['boletos_codigobarra_altura']			= 13;				// não mexa

$config['boletos_dados_banco_imagemlogo']		= 'logohsbc.jpg';	// não mexa
$config['boletos_dados_banco_codigo']			= '399';			// não mexa

$config['boletos_dados_agencia_cod_cedente']	= '3432599';		// não mexa
$config['boletos_dados_cedente']				= 'Empresa XPTO Ltda.';
$config['boletos_dados_cpfcnpj_cedente']		= 'CNPJ 1111.2222/0001-33';
