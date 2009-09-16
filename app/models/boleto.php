<?php
class Boleto extends AppModel {
	var $name		= 'Boleto';
	var $useTable	= false;



	/**
	 * Gera número de documento do boleto de acordo com a regra de negócio
	 * de formação dos boletos do condomínio.
	 *
	 * @param quadra	número da quadra com dois dígitos
	 * @param lote		número do lote com dois dígitos
	 * @param mes		mês em questão com dois dígitos
	 * @param ano		ano em questão com dois dígitos
	 * @param tipo		tipo de lancamento (0- taxacondominial, 1- taxaextra, 2-multa)
	 *
	 * @return um número de documento com 13 dígitos e zero padded ou false caso os parâmetros sejam loucos
	 */
	function gerarNumeroDocumento($quadra, $lote, $mes, $ano, $tipo= 0) {

		if( $mes < 1 || $mes > 12 || !is_numeric($mes) ) {
			return false;

		} elseif( $ano < 00 || !is_numeric($ano) ) {
			return false;

		} elseif( !is_numeric($quadra) || !is_numeric($lote) ) {
			return false;
		}

		$quadra	= substr(str_pad($quadra, 2, '0', STR_PAD_LEFT), -2);
		$lote	= substr(str_pad($lote, 2, '0', STR_PAD_LEFT), -2);
		$mes	= substr(str_pad($mes, 2, '0', STR_PAD_LEFT), -2);
		$ano	= substr(str_pad($ano, 2, '0', STR_PAD_LEFT), -2);

		$numero_documento	= $tipo . $quadra . $lote . $mes . $ano;
		$numero_documento	= str_pad($numero_documento, 13, '0', STR_PAD_LEFT);

		return $numero_documento;
	}



	/**
	 * Gera o nosso número, com os devidos dígitos verificadores, a partir
	 * do número de documento.  Apenas para tipo 4, que vincula número de
	 * documento, agência e data de vencimento.
	 *
	 * @param numero_documento	o número de documento (possivelmente gerado a partir do método dado acima)
	 * @param agencia			o número da agência (os 6 dígitos do código do cedente - sem o "8" dado - ver manual da cnr)
	 * @param vencimento		a data de vencimento, no formato dd/mm/aa (ou dd/mm/aaaa)
	 *
	 * @return o nosso número (assim chamado o número do documento acrescido de três dígitos) ou false caso
	 */
	function gerarNossoNumero($numero_documento, $agencia, $vencimento) {

		if( strlen($numero_documento) != 13 ) {
			return false;
		}

		if( !is_numeric($agencia) ) {
			return false;

		} elseif( strlen($agencia) == 7 ) {
			$cod_cedente	= $agencia;

		} elseif( strlen($agencia) == 6 ) {
			$cod_cedente	= '8' . $agencia;

		} else {
			return false;
		}

		if( (strpos($vencimento, '/') != 2) || (strrpos($vencimento, '/') != 5) ) {
			return false;
		}
		list($dia, $mes, $ano)	= split('/', $vencimento);
		$ano	= substr(str_pad($ano, 2, '0', STR_PAD_LEFT), -2);

		$d1		= $this->modulo11($numero_documento);
		$d2		= 4;	// cte
		$num	= ($numero_documento . $d1 . $d2) + $cod_cedente + ($dia . $mes . $ano);
		$d3		= $this->modulo11($num);
		$ret	= str_pad($numero_documento.$d1.$d2.$d3, 16, '0', STR_PAD_LEFT);

		return $ret;
	}



	/**
	 * Calcula a diferença, em dias, entre duas datas.
	 *
	 * @param data1		uma data
	 * @param data2		outra data
	 *
	 * @return a quantidade absoluta de dias entre a data1 e a data2, ou -1 caso não sejam datas que existam
	 */
	function dateDiff($data1, $data2) {

		if( !$this->dataValida($data1) || !$this->dataValida($data2)) {
			return -1;
		}

		list($dia1, $mes1, $ano1)	= explode('/', $data1);
		list($dia2, $mes2, $ano2)	= explode('/', $data2);

		$time1	= mktime(0, 0, 0, $mes1, $dia1, $ano1);
		$time2	= mktime(0, 0, 0, $mes2, $dia2, $ano2);

		$timeA	= max($time1, $time2);
		$timeB	= min($time1, $time2);

		$dias	= ($timeA - $timeB) / 86400;
		$dias	= ceil($dias);
		return $dias;
	}


	/**
	 * Converte uma data no calendário gregoriano para uma data no formato juliano
	 *
	 * @param data	uma data no formato dd/mm/aaaa
	 *
	 * @return a data no formato juliano ou -1 caso não seja válida
	 */
	function dateJulian($data) {

		if( !$this->dataValida($data) ) {
			return -1;
		}
		list($dia, $mes, $ano)	= explode('/', $data);
		$d	= 1+date('z', mktime(0, 0, 0, $mes, $dia, $ano)); // 0-based
		$d	= str_pad($d, 3, '0', STR_PAD_LEFT);
		$a	= substr($ano, -1);
		$res	= $d.$a;
		return $res;
	}

	function enxugaVencimento($data) {

		list($dia, $mes, $ano)	= split('/', $data);

		if( checkdate($mes, $dia, $ano) ) {
			$ano	= substr($ano, 2);
			return $dia . $mes . $ano;
		}
		return false;
	}

	function modulo11($numero) { // decrescente rtl - so para o nosso numero

		$fator	= 9;
		$soma	= 0;

		for( $i= strlen($numero); $i > 0; $i-- ) {
			$digito	= substr($numero, $i-1, 1);
			$soma  += $digito * $fator;

			//if( $i==2 ) { e("d:$digito p:".($digito * $fator)."  s:$soma"); exit; }

			$fator--;
			if( $fator == 1 ) $fator = 9;
		}

		$resto	= $soma % 11;

		//e( "s:$soma r:$resto" ); exit;

		if( $resto == 10 ) {
			$resto= 0;
		}

		return $resto;
	}

	function modulo11invertido($numero) { // crescente rtl - so para o codigo de barras

		$fator	= 2;
		$soma	= 0;

		for( $i= strlen($numero); $i > 0; $i-- ) {
			$digito	= substr($numero, $i-1, 1);
			$soma  += $digito * $fator;

			$fator++;
			if( $fator == 10 ) $fator = 2;
		}

		$resto = $soma % 11;

		if( $resto == 0 || $resto == 10 ) {
			return 1;
		}

		return 11 - $resto;
	}


	/**
	 * Retorna a representação numérica do código de barras.
	 *
	 * @param numero_documento	o número do documento em questão
	 * @param codigo_cedente	a "agência" (sempre em 7 dígito)
	 * @param vencimento		a data de vencimento no formato dd/mm/aaaa
	 * @param valor				o valor a ser representado
	 * @param codigo_banco		o código correspondente ao banco (amarrei em 399 do HSBC) TODO: melhorar
	 * @param codigo_moeda		o código correspondente à moeda (o boleto só trata de 9, real)
	 */
	function montarCodigoBarras($numero_documento, $codigo_cedente, $vencimento, $valor, $codigo_banco= 399, $codigo_moeda= 9) {
		// 44 posicoes
		// 1-3	- codigo hsbc (399)
		// 4	- moeda real (9)
		// 5	- dv código de barras (a calcular)
		// 6-9	- fator de vencimento
		// 10-19- valor do documento com zeros à esquerda
		// 20-26- codigo_cedente
		// 27-39- numero_documento
		// 40-43- vencimento juliano
		// 44	- codigo aplicativo (2)

		if( !$this->dataValida($vencimento) ) {
			return false;
		}
		$valor				= str_replace(array(',', '.'), '', $valor);
		$valor				= str_pad($valor, 10, '0', STR_PAD_LEFT);
		$codigo_cedente		= str_pad($codigo_cedente, 7, '0', STR_PAD_LEFT);
		$numero_documento	= str_pad($numero_documento, 13, '0', STR_PAD_LEFT);
		list($mes, $dia, $ano)	= explode('/', $vencimento);
		$dataj	= $this->dateJulian($vencimento);//gregoriantojd($mes, $dia, $ano);
		$dias 	= $this->dateDiff($vencimento, '03/07/2000');
		$fator	= 1000 + $dias;

		$cod	= $codigo_banco . $codigo_moeda . 'X' . $fator . $valor . $codigo_cedente . $numero_documento . $dataj . '2';

		list($p1, $p2)	= split('X', $cod);
		$d		= $this->modulo11invertido($p1.$p2);
		$cod	= str_replace('X', $d, $cod);

		return $cod;
	}


	/**
	 * Retorna a linha digitável do boleto a partir do código de barras.
	 *
	 * @param codigo	o código de barras correspondente gerado
	 *
	 * @return uma representação da linha digitável (com dois espaços separando os grupos)
	 */
	function montarLinhaDigitavel($codigo) {
		// Posição 	Conteúdo

		// 1. Campo - composto pelo código do banco, código da moeda e DV, as cinco primeiras posições
		// do campo livre e DV (modulo10) deste campo
		$campo1 = substr($codigo,0,4) . substr($codigo,19,5);
		$campo1 = $campo1 . $this->modulo_10($campo1);
		$campo1A= substr($campo1,0,5);
		$campo1B= substr($campo1,5,5);
		$campo1	= str_pad($campo1A, 5, '0', STR_PAD_LEFT) . '.' . str_pad($campo1B, 5, '0', STR_PAD_LEFT);

		// 2. Campo - composto pelas posiçoes 6 a 15 do campo livre
		// e DV deste campo
		$campo2 = substr($codigo,24,2) . substr($codigo,26,8);
		$campo2 = $campo2 . $this->modulo_10($campo2);
		$campo2A= substr($campo2,0,5);
		$campo2B= substr($campo2,5,6);
		$campo2	= str_pad($campo2A, 5, '0', STR_PAD_LEFT) . '.' . str_pad($campo2B, 6, '0', STR_PAD_LEFT);

		// 3. Campo composto pelas posicoes 16 a 25 do campo livre
		// e DV deste campo
		$campo3 = substr($codigo,34,5) . substr($codigo,39,4) . substr($codigo,43,1);
		$campo3 = $campo3 . $this->modulo_10($campo3);
		$campo3A= substr($campo3,0,5);
		$campo3B= substr($campo3,5,6);
		$campo3	= str_pad($campo3A, 5, '0', STR_PAD_LEFT) . '.' . str_pad($campo3B, 6, '0', STR_PAD_LEFT);

		// 4. Campo - digito verificador do codigo de barras
		$campo4 = substr($codigo, 4, 1);
		$campo4 = str_pad($campo4, 1, '0', STR_PAD_LEFT);

		// 5. Campo composto pelo fator vencimento e valor nominal do documento, sem
		// indicacao de zeros a esquerda e sem edicao (sem ponto e virgula). Quando se
		// tratar de valor zerado, a representacao deve ser 000 (tres zeros).
		$campo5 = substr($codigo, 5, 4) . substr($codigo, 9, 10);
		$campo5	= str_pad($campo5, 14, '0', STR_PAD_LEFT);

		return "$campo1  $campo2  $campo3  $campo4  $campo5";
	}

	function modulo_10($num) {
		$numtotal10 = 0;
        $fator = 2;

        // Separacao dos numeros
        for ($i = strlen($num); $i > 0; $i--) {
            // pega cada numero isoladamente
            $numeros[$i] = substr($num,$i-1,1);
            // Efetua multiplicacao do numero pelo (falor 10)
            // 2002-07-07 01:33:34 Macete para adequar ao Mod10 do Itaú
            $temp = $numeros[$i] * $fator;
            $temp0=0;
            foreach (preg_split('//',$temp,-1,PREG_SPLIT_NO_EMPTY) as $k=>$v) { $temp0+=$v; }
            $parcial10[$i] = $temp0; //$numeros[$i] * $fator;
            // monta sequencia para soma dos digitos no (modulo 10)
            $numtotal10 += $parcial10[$i];

            if ($fator == 2) {
                $fator = 1;
            } else {
                $fator = 2; // intercala fator de multiplicacao (modulo 10)
            }
        }
        // várias linhas removidas, vide função original
        // Calculo do modulo 10
        $resto = $numtotal10 % 10;
        $digito = 10 - $resto;

        if ($resto == 0) {
            $digito = 0;
        }

        return $digito;
	}


	/**
	 * Indica se a data está num formato dd/mm/aaaa válido e se se refere
	 * a uma data realmente existente.
	 *
	 * @param data	data no formato dd/mm/aaaa
	 *
	 * @return true se o formato é válido e se a data existe, false em caso contrário
	 */
	function dataValida($data) {

		if( (strpos($data, '/') != 2) || (strrpos($data, '/') != 5) ) {
			return false;
		}


		list($dia, $mes, $ano)	= explode('/', $data);

		if( !checkdate($mes, $dia, $ano) ) {
			return false;
		}

		return true;
	}
}
