<?php

require_once(INCLUDE_DIR.'class.plugin.php');
require_once(INCLUDE_DIR.'class.osticket.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once('config.php');
require_once('lib/Facebook/autoload.php');

class FacebookPlugin extends Plugin {
	public $config_class = "FacebookPluginConfig";
	private $_fb = NULL;

	function fb() {
		if (is_null($this->_fb)) {
			$config = $this->getConfig();
			$this->_fb = new Facebook\Facebook([
				'app_id' => $config->get('f-app-id'),
				'app_secret' => $config->get('f-app-secret'),
				'default_graph_version' => 'v2.5',
			]);
			$this->_fb->setDefaultAccessToken($config->get('f-access-token'));
		}
		return $this->_fb;
	}

	function bootstrap() {
		// Fetch new data from Facebook on cron
		Signal::connect('cron', array($this, 'fetch'));
		// Post back replies to Facebook
		Signal::connect('model.created', array($this, 'modelCreated'));
	}

	function modelCreated($entry) {
		if (!$entry instanceof ThreadEntry) {
			return;
		}
		if ($entry->getType() != 'R') {
			// Only post back staff replies
			return;
		}
		if ($entry->getStaffId() == 0) {
			// This entry was fetch _from_ Facebook. Do not post it back again
			return;
		}
		$m = FacebookConversationModel::lookup(array('ticket_id' => $entry->getTicketId()));
		if (!$m) {
			return;
		}
		$data = array('message' => (string)$entry->getBody());
		$response = $this->fb()->post(sprintf('/%s/messages', $m->facebook_id), $data);
		$graphObject = $response->getGraphObject();
		$m = FacebookThreadModel::create();
		$m->facebook_id = $graphObject->getField('id');
		$m->ticket_thread_id = $entry->id;
		$m->save();
	}

	function fetch() {
		$config = $this->getConfig();
		$response = $this->fb()->get(sprintf('/%s/conversations?fields=updated_time,message_count,snippet', $config->get('f-page-id')));
		$graphEdge  = $response->getGraphEdge();
		foreach ($graphEdge as $graphNode) {
			$this->parseMid($graphNode->getField('id'), $graphNode->getField('snippet'), $graphNode->getField('updated_time'));
		}
	}

	function parseMid($mid, $snippet, $updatedTime) {
		$m = FacebookConversationModel::lookup(array('facebook_id' => $mid));
		if ($m == NULL) {
			$this->createTicket($mid, $snippet, $updatedTime);
		} else if ($updatedTime->format('Y-m-d H:i:s') != $m->last_updated) {
			$ticket = Ticket::lookup($m->ticket_id);
			if (!$ticket) {
				return;
			}
			$response = $this->fb()->get(sprintf('/%s/messages?fields=created_time,from,to,message', $mid));
			$graphEdge = $response->getGraphEdge();
			$responses = array();
			// Reverse the resonses
			foreach ($graphEdge as $graphNode) {
				array_unshift($responses, $graphNode);
			}
			if ($this->updateResponses($ticket, $responses)) {
				$m->last_updated = $updatedTime->format('Y-m-d H:i:s');
				$m->save();
			}
		}
	}

	function createTicket($mid, $subject, $updatedTime) {
		$response = $this->fb()->get(sprintf('/%s/messages?fields=created_time,from,to,message', $mid));
		$graphEdge = $response->getGraphEdge();
		$responses = array();
		// Reverse the resonses
		foreach ($graphEdge as $graphNode) {
			array_unshift($responses, $graphNode);
		}
		$first = array_shift($responses);
		$config = $this->getConfig();
		$message = $first->getField('message');
		if (strlen($message) == 0) {
			$message = "(empty)";
		}
		if (strlen($subject) == 0) {
			$subject = "(empty)";
		}
		$data = array(
			'name'      =>      $first->getField('from')['name'],
			'email'     =>      $first->getField('from')['email'],
			'subject'   =>      $subject,
			'message'   =>      $message,
			'topicId'   =>      $config->get('f-help-topic'),
		);
		$ticket = Ticket::create($data, $errors, 'api', false, false);
		if (!$ticket) {
			return;
		}
		$thread = $ticket->getThread();
		// Add thread id to facebook table
		foreach($thread->getMessages() as $threadEntry) {
			$m = FacebookThreadModel::create();
			$m->facebook_id = $first->getField('id');
			$m->ticket_thread_id = $threadEntry['id'];
			$m->save();
		}

		$this->updateResponses($ticket, $responses);
		$m = FacebookConversationModel::create();
		$m->facebook_id = $mid;
		$m->ticket_id = $ticket->id;
		$m->last_updated = $updatedTime->format('Y-m-d H:i:s');
		$m->save();
	}

	function updateResponses($ticket, $responses) {
		$config = $this->getConfig();
		foreach($responses as $msg) {
			$msgId = $msg->getField('id');
			if (FacebookThreadModel::lookup($msgId)) {
				continue;
			}
			$from = $msg->getField('from');
			$data = array(
				'poster'    => $from['name'],
			);
			if ($from['id'] == $config->get('f-page-id')) {
				// From staff
				$data['response'] = $msg->getField('message');
				$response = $ticket->getThread()->addResponse($data, $errors);
			} else {
				// From customer
				$customerResponded = true;
				$data['message'] = $msg->getField('message');
				$user = User::lookupByEmail($from['email']);
				if ($user) {
					$data['userId'] = $user->id;
				}
				$response = $ticket->postMessage($data);
			}
			$m = FacebookThreadModel::create();
			$m->facebook_id = $msgId;
			$m->ticket_thread_id = $response->id;
			$m->save();
		}
		return true;
	}
}

class FacebookConversationModel extends VerySimpleModel {
	static $meta = array(
		'table' => 'ost_facebook_conversation',
		'pk' => array('facebook_id'),
	);
	function getId() {
			return $this->facebook_id;
	}
}

class FacebookThreadModel extends VerySimpleModel {
	static $meta = array(
		'table' => 'ost_facebook_thread',
		'pk' => array('facebook_id'),
	);
	function getId() {
			return $this->facebook_id;
	}
}

