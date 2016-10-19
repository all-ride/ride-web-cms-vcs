# Ride: CMS Version Control

This module adds version control integration for the CMS structure of a Ride application.

It's used when you have to maintain multiple installations of your CMS site, for example a test instance or a developer instance.

This module will block structural changes on your CMS nodes when you don't have the latest version.
A message is displayed to update your repository with a link to the page where you can perform the update action.

_Note: this module has a negative performance impact on your backend._

## Parameters

* __cms.repository.branch__: Name of the branch inside the repository eg. content-dev
* __cms.repository.private.key__: Path to the private key used to access the repository (optional)
* __cms.repository.url__: URL to the repository eg. git@github.com:all-ride/ride-web-cms-vcs.git

## Usage

You can browse to _/sites/repository_ to check the status of your repository.
This page allows you to pull the latest changes into your local installation.

When you try to save a CMS node while the local copy of the repository is outdated, your action is blocked and a message is displayed to perform an update action first.
This way, you never have to solve merge conflicts.

## Related Modules

- [ride/app](https://github.com/all-ride/ride-app)
- [ride/app-vcs](https://github.com/all-ride/ride-app-varnish)
- [ride/lib-vcs](https://github.com/all-ride/ride-lib-varnish)
- [ride/web](https://github.com/all-ride/ride-web)
- [ride/web-base](https://github.com/all-ride/ride-web-base)
- [ride/web-cms](https://github.com/all-ride/ride-web-cms)

## Installation

You can use [Composer](http://getcomposer.org) to install this module.

```
composer require ride/web-cms-vcs
```
