<?php

namespace ride\web\cms;

use ride\library\event\Event;
use ride\library\event\EventManager;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\ValidationError;
use ride\library\vcs\exception\VcsException;
use ride\library\vcs\Repository;

use ride\web\base\menu\MenuItem;
use ride\web\WebApplication;

/**
 * Application listener to handle version control integration in the CMS
 */
class VcsApplicationListener {

    /**
     * Flag to see if the repository has been updated
     * @var boolean
     */
    private $isUpdated;

    /**
     * Commit messages of the updates
     * @var array
     */
    private $description;

    /**
     * Instance of the content repository
     * @var \ride\library\vcs\Repository
     */
    private $repository;

    /**
     * Branch in the repository
     * @var string
     */
    private $branch;

    /**
     * Adds a menuitem for the repository to the taskbar
     * @param \ride\library\event\Event $event
     * @return null
     */
    public function prepareTaskbar(Event $event) {
        $menuItem = new MenuItem();
        $menuItem->setTranslation('title.repository');
        $menuItem->setRoute('cms.repository');

        $taskbar = $event->getArgument('taskbar');
        $applicationMenu = $taskbar->getApplicationsMenu();
        $sitesMenu = $applicationMenu->getItem('label.sites');
        $sitesMenu->addMenuItem($menuItem);
    }

    /**
     * Sets the content repository
     * @param \ride\library\vcs\Repository
     * @param string $branch
     * @return null
     */
    public function setRepository(Repository $repository, $branch) {
        $this->repository = $repository;
        $this->branch = $branch;
    }

    /**
     * Gets the content repository
     * @return \ride\library\vcs\Repository
     */
    public function getRepository() {
        return $this->repository;
    }

    /**
     * Gets the branch in the content repository
     * @return string
     */
    public function getBranch() {
        return $this->branch;
    }

    /**
     * Handles a node save or remove action
     * @param \ride\library\event\Event $event Save or remove event
     * @param \ride\library\event\EventManager $eventManager Instance of the
     * event manager
     * @param \ride\web\WebApplication $web Instance of the web application
     * @return null
     */
    public function handleCmsAction(Event $event, EventManager $eventManager, WebApplication $web) {
        if (!$this->isValidRepository()) {
            return;
        }

        $this->description[$event->getArgument('description', 'Updated content')] = true;

        if (!$this->isUpdated) {
            // Update the repository, throws an exception when potential conflicts
            // arise
            $this->updateRepository($web->getUrl('cms.repository') . '?referer=' . urlencode($web->getRequest()->getUrl()));
        }

        if ($event->getArgument('action') == 'remove') {
            $nodes = $event->getArgument('nodes');
            foreach ($nodes as $node) {
                $this->repository->remove($node->getRootNodeId() . '/' . $node->getId() . '.ini');
            }
        }

        if (!$this->isUpdated) {
            // register event to commit when the controller has finished processing
            // the request
            $eventManager->addEventListener('app.response.pre', array($this, 'handleCommit'), 1);

            $this->isUpdated = true;
        }
    }

    /**
     * Performs a commit on the content repository
     * @param \ride\library\event\Event $event Pre response event
     * @return null
     */
    public function handleCommit(Event $event) {
        $description = implode(', ', array_keys($this->description));

        $this->repository->add();
        $this->repository->commit($description);
    }

    /**
     * Updates the repository
     * @param string $updateUrl URL where the update action is to be fired,
     * if provided, an exception will be thrown if an update is detected
     * @return null
     * @throws \ride\library\vcs\exception\VcsException when the repository is
     * not valid
     * @throws \ride\library\validation\exception\ValidationException when an
     * update is detected
     */
    public function updateRepository($updateUrl = null) {
        if (!$this->isValidRepository()) {
            throw new VcsException("Could not check revision: no repository or repository URL set");
        }

        $this->ensureRepositoryExistance($this->branch);

        // check for updates
        try {
            $oldRevision = $this->repository->getRevision();
        } catch (VcsException $exception) {
            $oldRevision = null;
        }

        $this->repository->update(array('origin' => 'origin', 'branch' => $this->branch));

        if (!$oldRevision) {
            // no old revision, nothing committed
            return;
        }

        $newRevision = $this->repository->getRevision();
        if (!$updateUrl || $oldRevision === $newRevision) {
            // no update URL and no conflicts, we're cool
            return;
        }

        // owh noooes ...
        $error = new ValidationError(
            'error.validation.cms.repository.outdated',
            'Your site is outdated, please <a href="%url%">update your content</a> first.',
            array(
                'url' => $updateUrl,
            )
        );

        $exception = new ValidationException('Could not perform action: nodes have been updated from the repository');
        $exception->addErrors('node', array($error));

        $this->repository->reset($oldRevision);

        throw $exception;
    }

    /**
     * Makes sure the repository is in the correct branch and ready to use
     * @param string $branch Name of the branch
     * @return null
     * @throws \ride\library\vcs\exception\VcsException when no repository or
     * repository URL has been set
     */
    public function ensureRepositoryExistance($branch = null) {
        if (!$this->isValidRepository()) {
            throw new VcsException("Could not ensure repository existance: no repository or repository URL set");
        }

        if (!$branch) {
            $branch = $this->branch;

            if (!$branch) {
                throw new VcsException("Could not ensure repository existance: no branch set");
            }
        }

        $isCreated = false;
        if (!$this->repository->isCreated()) {
            // repository is not set, initialize it and bring it up to date
            $this->repository->create();
            $this->repository->update(array('all' => true));
        }

        if ($this->repository->getBranch() == $branch) {
            // we are in the required branch
            return;
        }

        if ($this->repository->hasBranch($branch)) {
            // branch exists
            $workingCopy = $this->repository->getWorkingCopy();

            $files = $workingCopy->read();
            if (count($files) > 1) {
                // copy the current files to a backup and so the branch checkout
                // will not fail
                $backupWorkingCopy = $workingCopy->getCopyFile();
                $workingCopy->copy($backupWorkingCopy);

                foreach ($files as $file) {
                    if ($file->getName() == '.git') {
                        continue;
                    }

                    $file->delete();
                }
            }

            $this->repository->checkout(array(
                'branch' => $branch,
            ));
        } else {
            // branch does not exist, create a new orphan branch for the content
            $this->repository->checkout(array(
                'branch' => $branch,
                'orphan' => true,
            ));
        }
    }

    /**
     * Checks if there is a valid repository set
     * @return boolean
     */
    public function isValidRepository() {
        return $this->repository && $this->repository->getUrl();
    }

}
