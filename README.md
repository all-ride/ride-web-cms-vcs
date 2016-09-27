# Ride: CMS Version Control

This module adds version control integration for the CMS structure of a Ride application.

It's used when you have to maintain multiple installations of your CMS site, for example a test instance or a developer instance.

This module will block structural changes on your CMS nodes when you don't have the latest version.
A message is displayed to update your repository with a link to the page where you can perform the update action.

_Note: this module has a negative performance impact on your backend._

## Setup

To use this module, you need to set 2 parameters.

* __cms.repository.url__: URL to the repository eg. git@github.com:all-ride/ride-web-cms-vcs.git
* __cms.repository.branch__: Name of the branch inside the repository eg. content-dev

If you are using a private key to authenticate yourself with the repository, you can use the _cms.repository.private.key_ parameter.
This parameter contains the path to the private key file.

## Usage

You can browse to _/sites/repository_ to check the status of your repository.
This page allows you to pull the latest changes into your local installation.

When you try to save a CMS node while the local copy of the repository is outdated, your action is blocked and a message is displayed to perform an update action first.
This way, you never have to solve merge conflicts.

## Installation

You can use [Composer](http://getcomposer.org) to install this module.

```
composer require ride/web-cms-vcs
```
