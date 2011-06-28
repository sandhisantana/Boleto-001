<?php
 /**
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This library is built based on Boletophp v0.17
 * Many thanks to the mantainers and collaborators of Boletophp project at boletophp.com.br.
 * 
 * @file Implementation of Bank 001 - Banco do Brasil SA
 * @copyright 2011 Drupalista.com.br
 * @author Francisco Luz <franciscoferreiraluz at yahoo dot com dot au>
 * @package Banco do Brasil
 * @version 1.001.0
 *
 *  --------------------------------C O N T R A T A C A O ---------------------------------------------------
 *  
 * - Estou disponível para trabalhos freelance, contrato temporario ou permanente. (falo ingles fluente)
 * - Tambem presto serviços de treinamento em Drupal para empresas e profissionais da área de
 *   desenvolvimento web ou para empresas / pessoas usuarias da plataforma Drupal que queiram capacitar
 *   sua equipe interna para tirar o maximo proveito do poder do Drupal.
 * - Trabalho com soluções como o Open Public (http://openpublicapp.com), ideal para prefeituras e
 *   autarquias publicas.
 * - Trabalho ainda com o Open Publish (http://openpublishapp.com), uma solucao completa de websites
 *   para canais de tv, jornais, revistas, notícias, etc...
 *
 *   Acesse o meu website http://www.drupalista.com.br para me contactar.
 *
 *   Francisco Luz
 *   Junho / 2011
 *    
 */
class Banco_001 extends Boleto{
    function setUp(){
        $this->bank_name  = 'Banco do Brasil SA';
    }
    
    //Implementation of Febraban free range set from position 20 to 44
    function febraban_20to44(){
       /** get convenio number (convention) and contract number **/
	//set empty variable to avoid any eventuality
	$convenio     = '';
	$nosso_numero = '';
	
	//see readme.txt
	$cc = explode('-', $this->arguments['carteira_nosso_numero']);
	
	$convenio = $this->computed['convenio'] = $cc[0];
	$contrato = $this->computed['contrato'] = '';
	$servico  = $this->computed['servico']  = '';
	if(isset($cc[1])){
	   $contrato = $this->computed['contrato'] = $cc[1]; 
	}
	if(isset($cc[2])){
	   $servico = $this->computed['servico'] = $cc[2];
	}

	//Carteira might come in this format xx-xxx so we gotta break it apart.
	$carteira     = explode('-', $this->arguments['carteira']);
	$carteira_sub = $this->computed['carteira_sub'] = '';
        if(isset($carteira[1])){
	     $carteira_sub = $this->computed['carteira_sub'] = $carteira[1];
	}
	//pre calculate nosso_numero check digit
	$checkDigit = $this->modulo_11($convenio.$this->arguments['nosso_numero']);
	$checkDigit['digito'] = '-'.$checkDigit['digito'];
	
	switch($carteira[0]){
	   case 18:
	      //now we need to know how many digits convenio number has
	      $conv_len     =  strlen($convenio);
	      switch($conv_len){
		 case 8:
		  // 20-33 -> Convenio                   14
		  // 34-42 -> Nosso Número (sem dígito)   9
		  // 43-44 -> Carteira                    2
		     $convenio     = str_pad($convenio, 14, 0, STR_PAD_LEFT);
		     $nosso_numero = str_pad($this->arguments['nosso_numero'], 9, 0, STR_PAD_LEFT);
		     
		     //25 digits long code
		     $code         = $convenio.$nosso_numero.$carteira[0];
		 break;
		 case 7:
		  // 20-32 -> Convenio                   13
		  // 33-42 -> Nosso Número (sem dígito)  10
		  // 43-44 -> Carteira                    2
		     $convenio     = str_pad($convenio, 13, 0, STR_PAD_LEFT);
		     $nosso_numero = str_pad($this->arguments['nosso_numero'], 10, 0, STR_PAD_LEFT);
		     
		     //25 digits long code
		     $code  = $convenio.$nosso_numero.$carteira[0];
		     
		     //no check digit for nosso_numero
		     $checkDigit['digito'] = '';
		     
		break;
		case 6:
		     if($servico == 21){
		      // 20-25 -> Convenio                   6
		      // 26-42 -> Nosso Número (sem dígito)  17
		      // 43-44 -> Servico                    2
			 $convenio     = str_pad($convenio, 6, 0, STR_PAD_LEFT);
			 $nosso_numero = str_pad($this->arguments['nosso_numero'], 17, 0, STR_PAD_LEFT);
			 
			 //25 digits long code
			 $code  = $convenio.$nosso_numero.$servico;
		     }else{
		      // 20-25 -> Convenio                   6
		      // 26-30 -> Nosso Número (sem dígito)  5
		      // 31-34 -> Agencia                    4
		      // 35-42 -> Conta                      8
		      // 43-44 -> Carteira                   2
			 $convenio     = str_pad($convenio, 6, 0, STR_PAD_LEFT);
			 $nosso_numero = str_pad($this->arguments['nosso_numero'], 5, 0, STR_PAD_LEFT);
			 
			 //25 digits long code
			 $code  = $convenio.$nosso_numero.$this->arguments['agencia'].$this->arguments['conta'].$carteira[0];		         
		     }
		break;
	      }
	   break;
	  /**
	   * TODO: Please donate your time by helping out implementing more carteiras
	   *       Documentation at www.bb.com.br/docs/pub/emp/empl/dwn/Doc5175Bloqueto.pdf
	   */
	  
	}
       //positons 20 to 44
       $this->febraban['20-44'] = $code;
       
       //save nosso_numero	
       $this->computed['nosso_numero'] = $convenio.$this->arguments['nosso_numero'].$checkDigit['digito'];
	
    }

    //customize object to meet specific needs
    function custom(){
        //calculates check digit for branch number
        $this->computed['agencia_dv'] = $this->arguments['agencia_dv'];
        if(empty($this->arguments['agencia_dv']) &&  $this->arguments['agencia_dv'] != '0'){
            $agencia_dv = $this->modulo_11($this->arguments['agencia']);
            $this->computed['agencia_dv'] = $agencia_dv['digito'];
        }
    }

    //manipulate output fields before them getting rendered. This method is called by output().
    public function outputValues(){
        $this->output['agencia_codigo_cedente'] = $this->arguments['agencia'].'-'.$this->computed['agencia_dv'].' / '.$this->arguments['conta'].'-'.$this->arguments['conta_dv'];
        
        $this->output['contrato']   = $this->computed['contrato'];
    }
}
?>