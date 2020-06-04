<?php

namespace DV\Mvc\Controller ;

use DV\Mvc\Controller\ActionController as CAction ;

class AuthenticatedActionController extends CAction
{
	/**
	 * This logic here has been moved into an Event Subscriber to make it effective
	 *
	 * @throws \Exception
	 * @internal param array $redirector
	 */
	protected function _check_authentication()
	{
		throw new \RuntimeException('Logic has been transffered into an EventSubscriber instead');
	}

}
