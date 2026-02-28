<?php

			#####################################################################################################
			# 4.3 GET AMOUNT PAID & SALDO HORENDE BIJ DEZE PARTICIPANT & CONTRIBUTION
			if ($extdebug >= 0) { watchdog('php','<pre>### 4.3 GET AMOUNT PAID & SALDO HORENDE BIJ DEZE PARTICIPANT & CONTRIBUTION [groupID: '.$groupID.'] ###</pre>',NULL,WATCHDOG_DEBUG); }
			#####################################################################################################

	    	$params_saldo_get = [
	      		'checkPermissions' => FALSE,
	      		'debug'   => FALSE,
	      		'select'  => [
	      			'id',
	        		'paid_amount', 
	        		'balance_amount',
	        		'row_count',
	      		],
	      		'where' => [
	        		['contact_id', 			'=',  $contact_id],
	        		['financial_type_id', 	'IN', [4,14,15]],		
					['receive_date', 		'>=', $event_fiscalyear_start],
					['receive_date', 		'<=', $event_fiscalyear_einde],
	      		],
	    	];

	    	// M61: TODO URGENT: DIT IS NU NIET GEKOPPELDE BETALING VAN DE REGISTRATIE MAAR GEWOON KAMPGELD DIT JAAR
	    	// M61: HET WAS GELOOF IK NOG NIET MOGELIJK CONTRIB ID TE VERKRIJGEN VIA API (PARTICIPANT PAYMENT) 

			if ($extdebug >= 3) { watchdog('php','<pre>params_saldo_get: '.print_r($params_saldo_get,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }        	
	    	$result_saldo_get   = civicrm_api4('Contribution', 'get', $params_saldo_get);
			if ($extdebug >= 4) { watchdog('php','<pre>result_saldo_get: '.print_r($result_saldo_get,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }        	

	    	if ($extdebug >= 2) { watchdog('php','<pre>part_kampgeld_contribid  : '.$part_kampgeld_contribid.'</pre>',NULL,WATCHDOG_DEBUG);	}

			$contrib_count 	= $result_saldo_get->count();
	    	if ($extdebug >= 2) { watchdog('php','<pre>contrib_count            : '.$contrib_count.'</pre>',NULL,WATCHDOG_DEBUG);	}	

			if ($contrib_count == 1) {

		    	$contrib_id     = $result_saldo_get[0]['id'];
				$saldo_bedrag   = $part_kampgeld_fee;
			   	$saldo_paid     = $result_saldo_get[0]['paid_amount'];
		    	$saldo_balance  = $result_saldo_get[0]['balance_amount'];

		    	if ($extdebug >= 2) { watchdog('php','<pre>contrib_id               : '.$contrib_id.'</pre>',NULL,WATCHDOG_DEBUG);		}
		    	if ($extdebug >= 2) { watchdog('php','<pre>saldo_bedrag             : '.$saldo_bedrag.'</pre>',NULL,WATCHDOG_DEBUG);	}    	
		    	if ($extdebug >= 2) { watchdog('php','<pre>saldo_paid               : '.$saldo_paid.'</pre>',NULL,WATCHDOG_DEBUG);		}
		    	if ($extdebug >= 2) { watchdog('php','<pre>saldo_balance            : '.$saldo_balance.'</pre>',NULL,WATCHDOG_DEBUG);

				if ($extdebug >= 1) { watchdog('php','<pre>part_kampgeld_fee        : '.print_r($part_kampgeld_fee,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);  }
				if ($extdebug >= 1) { watchdog('php','<pre>part_kampgeld_balance    : '.print_r($part_kampgeld_balance,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);  }    	

				$saldo_bedrag	= number_format( $saldo_bedrag,	 2, '.', ''  );
	    		$saldo_paid		= number_format( $saldo_paid, 	 2, '.', ''  );
	    		$saldo_balance	= number_format( $saldo_balance, 2, '.', ''  );

		    	if ($extdebug >= 2) { watchdog('php','<pre>contrib_id               : '.$contrib_id.'</pre>',NULL,WATCHDOG_DEBUG);		}
		    	if ($extdebug >= 2) { watchdog('php','<pre>saldo_bedrag             : '.$saldo_bedrag.'</pre>',NULL,WATCHDOG_DEBUG);	}    	
		    	if ($extdebug >= 2) { watchdog('php','<pre>saldo_paid               : '.$saldo_paid.'</pre>',NULL,WATCHDOG_DEBUG);		}
		    	if ($extdebug >= 2) { watchdog('php','<pre>saldo_balance            : '.$saldo_balance.'</pre>',NULL,WATCHDOG_DEBUG);	}


		    	if ($extdebug >= 2) { watchdog('php','<pre>kampgeldregeling         : '.$kampgeldregeling.'</pre>',NULL,WATCHDOG_DEBUG);		}
		    	if ($extdebug >= 2) { watchdog('php','<pre>part_kampgeld_regeling   : '.$part_kampgeld_regeling.'</pre>',NULL,WATCHDOG_DEBUG);	}

				$event_start_datum = date ( 'Y-m-d' , strtotime($event_start_date) );

		 		if ($contrib_id) {
					$params_contrib_update = [
						'checkPermissions' => FALSE,
						'debug' => $apidebug,
		 				'where' => [
							['id', '=', $contrib_id],
						],
						'values' => [
							'CONT_KAMPGELD.datum_kamp' 		=> $event_start_datum,
							'CONT_KAMPGELD.kamp_kort' 		=> $welkkampkort,
							'CONT_KAMPGELD.kamp_lang' 		=> $welkkamplang,
							'CONT_KAMPGELD.bedrag' 			=> $saldo_bedrag,
							'CONT_KAMPGELD.betaald' 		=> $saldo_paid,
							'CONT_KAMPGELD.saldo' 			=> $saldo_balance,
						],
					];
				}

				### ZET DE REGELING VAN DE CONTRIBUTIE OP STGAVE INDIEN NODIG EN GEBRUIK ANDERS DE REGELING UIT PART KAMPGELD

				if ($kampgeldregeling) {
					$params_contrib_update['values']['CONT_KAMPGELD.regeling'] 	= $kampgeldregeling;
				} elseif ($part_kampgeld_regeling) {
					$params_contrib_update['values']['CONT_KAMPGELD.regeling'] 	= $part_kampgeld_regeling;
				}

				if ($extdebug >= 3) { watchdog('php','<pre>params_contrib_update: '.print_r($params_contrib_update,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

				if ($contrib_id AND $event_start_datum AND $event_kampjaar != 2015) {
					$result_contrib_update = civicrm_api4('Contribution', 'update', $params_contrib_update);
					if ($extdebug >= 4) { watchdog('php','<pre>result_contrib_update: '.print_r($result_contrib_update,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
					if ($extdebug >= 2) { watchdog('php','<pre>params_contrib_update EXECUTED [groupID: '.$groupID.']</pre>',NULL,WATCHDOG_DEBUG); }
				}
			}
