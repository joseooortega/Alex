<?php


class soap_service {

	private $auth = FALSE;

	private function hasPermissions($rule){
		if ($this->auth == FALSE){
			throw new SoapFault('401', "You have to be logged before execute a method");
		}
	}
	private function SoapFaultMessage($name, $e){

		$this->write_log($name.' - Message: '.$e->getMessage().' - line: '.$e->getLine(), 'error');
		return $e->faultcode;

	}
	private function write_log($message, $type = 'info'){
		soap_error_log($type, $message);
	}
	private function numberValidation($n){
		$n = strip_tags(trim($n));

		if (!is_int($n * 1))
			throw new SoapFault('400', "Wrong data type");

		return $n;
	}

	/**
	  * Modifica el estado y la URL de tracking de un pedido
	  *
		* @param integer $instalacion
		* @param array $datos
	  * @return integer
	**/
	public function AddData($instalacion, $datos){

		try {
			$this->doAuthenticate();
			$t = db_transaction();


			// Validaciones
			$instalacion = node_load($this->numberValidation($instalacion), NULL, TRUE);
			if ($instalacion == FALSE)
				throw new SoapFault('400', "There is no content with this ID");


			// Creamos la entidad Datos
			$entity = entity_create('datos_instalaciones', array('type' => 'datos'));
	    $wrapper = entity_metadata_wrapper('datos_instalaciones', $entity);

			foreach ($datos as $key => $dato) {
				$wrapper->{$key}->set($dato);
			}
	    $wrapper->save();

			$instalacion->field_ct_i_datos['und'][]['target_id'] = $wrapper->getIdentifier();

			return 200;

		} catch (SoapFault $e) {
			!empty($t) ? $t->rollBack() : '' ;
			$this->write_log('Error al intentar añadir datos - '.$e->getMessage(), __FUNCTION__.' error');
			return $this->SoapFaultMessage(__FUNCTION__, $e);
		} catch (Exception $e){
			!empty($t) ? $t->rollBack() : '';
			$this->write_log('Error al intentar añadir datos - '.$e->getMessage(), __FUNCTION__.' error');
			return $this->SoapFaultMessage(__FUNCTION__, $e);
		}
	}

	private function doAuthenticate(){

		if(isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW'])){

			$username = strip_tags($_SERVER['PHP_AUTH_USER']);
			$password = strip_tags($_SERVER['PHP_AUTH_PW']);

			if ($uid = user_authenticate($username, $password)) {

				$user_load = user_load($uid, TRUE);

				//Comprueba si el usuario está bloqueado
				if ($user_load->status == 0)
					throw new SoapFault('401', "This user is blocked!");

				if (user_access('soap access'))
					throw new SoapFault('401', "This user has no permission to access to webservice");

				global $user;
				$user = $user_load;

				return $this->auth = true;
			} else {
				$this->write_log('Fallo de autenticación soap. user: '.$username.', pass: '.$password, 'Login error');
				throw new SoapFault('401', "Incorrect username and / or password");
			}
		}else{
			return false;
		}
	}

}
