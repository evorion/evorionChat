<?php
namespace Evorion\Evchat\Controller;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014 Vlatko Šurlan <vlatko.surlan@evorion.hr>, Evorion mediji j.d.o.o.
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * EventController
 */
class EventController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * eventRepository
	 *
	 * @var \Evorion\Evchat\Domain\Repository\EventRepository
	 * @inject
	 */
	protected $eventRepository = NULL;

	/**
	 * DBTracker Service keeps track of new messages accross processes
	 *
	 * @var \Evorion\Evchat\Domain\Service\DBTrackerService
	 * @inject
	 */
	protected $dbTrackerService = NULL;

	/**
	 * action list
	 *
	 * @return 	void
	 */
	public function listAction() {
		// Long poll for new messages
		$start = time();
		$UITracker = $this->request->getArgument('tracker');
		if (!count($UITracker)) {
			return json_encode(FALSE);
		}
		while (time() - $start < 10 && !($trackerUpdate = $this->dbTrackerService->haveNew($UITracker))) {
			usleep(50000);
		}
		// If no new data just return an empty response
		if (!$trackerUpdate) {
			return json_encode(FALSE);
		}
		// We have new data
		$events = $this->eventRepository->findByTracker($trackerUpdate);
		$json = array();
		foreach ($events as $event) {
			if (!is_array($json[$event->getObject()])) {
				$json[$event->getObject()] = array();
			}
			$json[$event->getObject()][$event->getUid()] = $event->getEvent();
		}
		return json_encode($json);
	}

	/**
	 * action new
	 *
	 * @param \Evorion\Evchat\Domain\Model\Event $newEvent
	 * @ignorevalidation $newEvent
	 * @return void
	 */
	public function newAction(\Evorion\Evchat\Domain\Model\Event $newEvent = NULL) {
		$this->view->assign('newEvent', $newEvent);
	}

	/**
	 * action create
	 *
	 * @param \Evorion\Evchat\Domain\Model\Event $newEvent
	 * @return void
	 */
	public function createAction(\Evorion\Evchat\Domain\Model\Event $newEvent) {
		$this->addFlashMessage('The object was created. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		$this->eventRepository->add($newEvent);
		$this->redirect('list');
	}

	/**
	 * action delete
	 *
	 * @param \Evorion\Evchat\Domain\Model\Event $event
	 * @return void
	 */
	public function deleteAction(\Evorion\Evchat\Domain\Model\Event $event) {
		$this->addFlashMessage('The object was deleted. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		$this->eventRepository->remove($event);
		$this->redirect('list');
	}

}

