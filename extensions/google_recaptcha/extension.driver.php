<?php
class extension_google_recaptcha extends Extension {
	// Keep track of values
	private static $cache = array();
	
	/*-------------------------------------------------------------------------
		Extension definition
	-------------------------------------------------------------------------*/
	public function about() {
		return array( 'name' => 'google_recaptcha',
			'version' => '0.1',
			'release-date' => '2017-04-27',
			'author' => array( 'name' => 'Sagara Dayananda',
				'website' => 'http://www.eyes-down.net/',
				'email' => 'sagara@eyes-down.net' ),
			'description' => 'Insert and process reCaptcha field for form submission.'
		);
	}

	public function uninstall() {
		# Remove preferences
		Symphony::Configuration()->remove( 'google_recaptcha' );
		Administration::instance()->saveConfig();
	}

	public function install() {
		return true;
	}

	public function getSubscribedDelegates() {
		return array(

			array(
				'page' => '/blueprints/events/new/',
				'delegate' => 'AppendEventFilter',
				'callback' => 'addFilterToEventEditor'
			),
			array(
				'page' => '/blueprints/events/edit/',
				'delegate' => 'AppendEventFilter',
				'callback' => 'addFilterToEventEditor'
			),

			array(
				'page' => '/system/preferences/',
				'delegate' => 'Save',
				'callback' => 'save_preferences'
			),
			array(
				'page' => '/system/preferences/success/',
				'delegate' => 'Save',
				'callback' => 'save_preferences'
			),
			array(
				'page' => '/system/preferences/',
				'delegate' => 'AddCustomPreferenceFieldsets',
				'callback' => 'append_preferences'
			),
			array(
				'page' => '/frontend/',
				'delegate' => 'FrontendParamsResolve',
				'callback' => 'addReCaptchaParams'
			),

			array(
				'page' => '/frontend/',
				'delegate' => 'EventPreSaveFilter',
				'callback' => 'processEventData'
			),

		);
	}

	public function addFilterToEventEditor( $context ) {
		$context[ 'options' ][] = array( 'google_recaptcha', @in_array( 'google_recaptcha', $context[ 'selected' ] ), 'Google reCaptcha Verification' );
	}

	/*-------------------------------------------------------------------------
		Append reCaptcha Params 
		-------------------------------------------------------------------------*/

	public function addReCaptchaParams( array $context = null ) {
		$context[ 'params' ][ 'recaptcha-sitekey' ] = $this->_get_sitekey();
	}

	/*-------------------------------------------------------------------------
		Preferences
		-------------------------------------------------------------------------*/

	public function append_preferences( $context ) {
		# Add new fieldset
		$group = new XMLElement( 'fieldset' );
		$group->setAttribute( 'class', 'settings' );
		$group->appendChild( new XMLElement( 'legend', 'Google reCaptcha' ) );

		# Add reCaptcha secret ID field
		$label = Widget::Label( 'Google reCaptcha secret key' );
		$label->appendChild( Widget::Input( 'settings[google_recaptcha][recaptcha-secret-id]', General::Sanitize( $this->_get_secret() ) ) );


		$group->appendChild( $label );
		$group->appendChild( new XMLElement( 'p', 'The secret ID from your reCaptcha settings.', array( 'class' => 'help' ) ) );

		# Add reCaptcha site key field
		$label = Widget::Label( 'Google reCaptcha site key' );
		$label->appendChild( Widget::Input( 'settings[google_recaptcha][recaptcha-sitekey]', General::Sanitize( $this->_get_sitekey() ) ) );
		$group->appendChild( $label );
		$group->appendChild( new XMLElement( 'p', 'The site key from your reCaptcha settings.', array( 'class' => 'help' ) ) );

		$context[ 'wrapper' ]->appendChild( $group );
	}


	/*-------------------------------------------------------------------------
		Helpers
	-------------------------------------------------------------------------*/

	private function _get_secret() {
		return Symphony::Configuration()->get( 'recaptcha-secret-id', 'google_recaptcha' );
	}

	private function _get_sitekey() {
		return Symphony::Configuration()->get( 'recaptcha-sitekey', 'google_recaptcha' );
	}

	public function validateChallenge($user_response)
	{
		//Get recaptcha-secret-id from config
		$s_id = Symphony::Configuration()->get( 'recaptcha-secret-id', 'google_recaptcha' );
		
		$status = false;
		if (!isset(self::$cache[$user_response])) {
			//Google api call for check reCaptcha
			$ch = new Gateway();
			$ch->init('https://www.google.com/recaptcha/api/siteverify');
			$ch->setopt('POST', 1);
			$ch->setopt('POSTFIELDS', array(
				'secret' => $s_id,
				'response' => $user_response,
				'remoteip' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'],
			));
			$content = @$ch->exec();
			if ($content) {
				$content = @json_decode($content);
				$status = $content && $content->success;
			}
			self::$cache[$user_response] = $status;
			
		} else {
			$status = self::$cache[$user_response];
		}
		return $status;
	}

	/**
	 * perform event filter
	 */

	public function processEventData( $context ) {
		if (in_array('google_recaptcha', $context['event']->eParamFILTERS)) {
			//Get response code
			$user_response = $context['fields']['google_recaptcha'];
			
			$status = $this->validateChallenge($user_response);
			
			$context['messages'][] = array('google_recaptcha', $status, (!$status ? 'reCAPTCHA field is required.' : null));
		}
	}
}
