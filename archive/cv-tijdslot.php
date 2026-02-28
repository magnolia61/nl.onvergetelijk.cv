<?php

			#####################################################################################################
			# 4.9 BEPAAL VELDEN RONDOM TIJDSLOT BRENGEN & HALEN
			if ($extdebug >= 0) { watchdog('php','<pre>### 4.9 BEPAAL VELDEN RONDOM TIJDSLOT BRENGEN & HALEN: '.$part_eventid.'] ###</pre>',NULL,WATCHDOG_DEBUG); }
			#####################################################################################################

			if ((count(array_intersect($part_role_id, array("7", "8"))) > 0 ) OR in_array($part_role_id, array("7", "8"))) {
				// ROLE_ID = DEELNEMER of DEELNEMER TOPKAMP
				# M61: HIERBOVEN COMPLEXE MANIER OM twee arrrays te intersecten maar ook om te gaan als part_role_id geen array is
				$part_functie 					= 'deelnemer';
				$part_groepklas 				= $result_part[0]['PART_DEEL.Groep_klas'];
				$part_groepsvoorkeur 			= $result_part[0]['PART_DEEL.Voorkeur'];
				$part_brengen_tijdslot 			= $result_part[0]['PART_DEEL.Tijdslot_brengen'];
				$part_halen_tijdslot 			= $result_part[0]['PART_DEEL.Tijdslot_halen'];

				if ($part_halen_tijdslot)	{
					// RETREIVE DESCRIPTION WAARIN TIJDSLOT MINUS 10 MINUTEN STAAN
					$params_optionshalen = [
						'checkPermissions' => FALSE,
							'debug' => $apidebug,
							'select' => [
								'label', 
								'description', 
								'value',
							],
							'where' => [
								['option_group_id', '=', 560], 
								['value', '=', $part_halen_tijdslot],
							],
					];
		  			if ($extdebug >= 3) { watchdog('php','<pre>params_optionshalen: '.print_r($params_optionshalen,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  			$result 		= civicrm_api4('OptionValue', 'get', $params_optionshalen);
		  			if ($extdebug >= 4) { watchdog('php','<pre>result_optionshalen: '.print_r($result,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

		  			$part_halen_tijdslot 	= $result[0]['label'];
		  			$part_halen_aankomst 	= $result[0]['description'];

					if ($extdebug >= 2) { watchdog('php','<pre>part_groepklas        : '.print_r($part_groepklas,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
					if ($extdebug >= 2) { watchdog('php','<pre>part_groepsvoorkeur   : '.print_r($part_groepsvoorkeur,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
					if ($extdebug >= 2) { watchdog('php','<pre>part_brengen_tijdslot : '.print_r($part_brengen_tijdslot,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
					if ($extdebug >= 2) { watchdog('php','<pre>part_halen_tijdslot   : '.print_r($part_halen_tijdslot,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
					if ($extdebug >= 2) { watchdog('php','<pre>part_halen_aankomst   : '.print_r($part_halen_aankomst,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
				}
			}