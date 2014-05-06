<?php

namespace ride\web\cms\controller;

use ride\library\vcs\exception\VcsException;

use ride\web\base\controller\AbstractController;
use ride\web\cms\VcsApplicationListener;

/**
 * Controller to handle the content repository of the CMS
 */
class RepositoryController extends AbstractController {

    /**
     * Action to show the last commits
     * @param  \ride\web\cms\VcsApplicationListener $vcs Instance of the version
     * control application listener
     * @return null
     */
    public function indexAction(VcsApplicationListener $vcs) {
        $vcs->ensureRepositoryExistance();

        $repository = $vcs->getRepository();

        try {
            $commits = $repository->getCommits(null, 10);
        } catch (VcsException $exception) {
            $commits = array();
        }

        $this->setTemplateView('cms/backend/repository', array(
            'commits' => $commits,
        ));
    }

    /**
     * Action to update the repository
     * @param  \ride\web\cms\VcsApplicationListener $vcs Instance of the version
     * control application listener
     * @return null
     */
    public function updateAction(VcsApplicationListener $vcs) {
        $vcs->updateRepository();

        $referer = $this->request->getQueryParameter('referer');
        if (!$referer) {
            $referer = $this->getUrl('cms.repository');
        }

        $this->response->setRedirect($referer);
    }

}
