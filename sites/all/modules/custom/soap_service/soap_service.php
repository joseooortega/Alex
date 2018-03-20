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


	/**
		* Authenticates the SOAP request. (This one is the key to the authentication, it will be called upon the server request)
		*
		* @param integer $username
		* @param integer $password
		* @return array
	**/
	public function authenticate($username, $password){

		$username = strip_tags($username);
		$password = strip_tags($password);

		if ($uid = user_authenticate($username, $password)) {

			$user_load = user_load($uid, TRUE);

			//Comprueba si el usuario está bloqueado
			if ($user_load->status == 0)
				throw new SoapFault('401', "This user is blocked!");

			if (!in_array('soap access', $user_load->roles))
				throw new SoapFault('401', "This user has no permission to access to this webservice");

			global $user;
			$user = $user_load;

			return $this->auth = true;
		} else {
			$this->write_log('Fallo de autenticación soap. user: '.$username.', pass: '.$password, 'Login error');
			throw new SoapFault('401', "Incorrect username and / or password");
		}
	}

	/**
	  * Modifica el estado y la URL de tracking de un pedido
	  *
		* @param integer $negocio
		* @param integer $instalacion
		* @param string $datos
	  * @return integer
	**/
	public function AddData($negocio){ //, $instalacion, $datos

		/*try {
			//$this->doAuthenticate();
			//$t = db_transaction();




			//$this->write_log('Dato añadido correctamente - order number: '.$order_number.' - Estado: '.$order_state.' - tracking: '.$url_tracking, 'OK');

			return 200;

		} catch (SoapFault $e) {
			//!empty($t) ? $t->rollBack() : '' ;
			//$this->write_log('Error al intentar modificar un pedido - '.$e->getMessage(), __FUNCTION__.' error');
			return $this->SoapFaultMessage(__FUNCTION__, $e);
		} catch (Exception $e){
			//!empty($t) ? $t->rollBack() : '';
			return $this->SoapFaultMessage(__FUNCTION__, $e);
		}*/

		return 111;
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
