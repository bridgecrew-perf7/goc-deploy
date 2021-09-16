# goc-deploy
A non-reusable Laravel package that automates the workflow of marging, tagging, packaging and deploying applications to secured environments through a dockerized OpenConnect VPN tunnel via SCP.

## Installation
- Edit `composer.json` and add:
```    
"repositories": [
    {
        "type": "path",
        "url": "./packages/goc-deploy",
        "options": {
            "symlink": true
        }
    }
],
```
- Issue the `composer require marcth/goc-deploy --dev` command
- Issue the `php artisan vendor:publish` command and enter the number associated with Provider:

      Marcth\GocDeploy\GocDeployServiceProvider


## Learning Resources

- [How To Create A Highly Configurable Laravel Package](https://dev.to/devingray/how-to-create-a-highly-configurable-laravel-package-4pj0)
- https://www.atlassian.com/git/tutorials/inspecting-a-repository/git-tag
- https://symfony.com/doc/current/components/process.html#usage

Todd re-add
    "require-dev": {
        "orchestra/testbench": "^6.0",
        "phpunit/phpunit": "^9.3"
    },

