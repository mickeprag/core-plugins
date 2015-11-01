<?php

require_once INCLUDE_DIR . 'class.plugin.php';

class FacebookPluginConfig extends PluginConfig {

	// Provide compatibility function for versions of osTicket prior to
	// translation support (v1.9.4)
	function translate() {
		if (!method_exists('Plugin', 'translate')) {
			return array(
				function($x) { return $x; },
				function($x, $y, $n) { return $n != 1 ? $y : $x; },
			);
		}
			return Plugin::translate('origin-facebook');
	}

	function getOptions() {
			list($__, $_N) = self::translate();
			return array(
					'facebook' => new SectionBreakField(array(
							'label' => $__('Facebook integration'),
					)),
					'f-page-id' => new TextboxField(array(
							'label' => $__('Company Page ID'),
							'configuration' => array('size'=>60, 'length'=>100),
					)),
					'f-app-id' => new TextboxField(array(
							'label' => $__('App ID'),
							'configuration' => array('size'=>60, 'length'=>100),
					)),
					'f-app-secret' => new TextboxField(array(
							'label' => $__('App Secret'),
							'configuration' => array('size'=>60, 'length'=>100),
					)),
					'f-access-token' => new TextboxField(array(
							'label' => $__('Access Token'),
							'configuration' => array('size'=>100, 'length'=>250),
					)),
					'f-help-topic' => new TextboxField(array(
							'label' => $__('Help topic'),
							'configuration' => array('size'=>10, 'length'=>5),
					)),
			);
	}
}
