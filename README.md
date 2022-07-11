# Laravel gitlab deploy

This package was created to optimize and improve a company's CI/CD in a Gitlab environment.

# Requirements

This package requires **PHP 7.4**  and **Laravel 8.0**.

Available SSH agents on local machine and remote server.

Remote server must use GNU/Linux.

# Installation

## Add dependency 

You can install this package via composer using:

```shell
composer require --dev hexide-digital/gitlab-deploy:^0
```

or manually add line to `composer.json`

```json
{
    "require-dev": {
        "hexide-digital/gitlab-deploy": "^0"
    }
}
```

then run:

```shell
composer install
```

## Publish files and examples

The package will automatically register its service provider.

Examples of files to be copied can be viewed in
[this folder](https://github.com/hexidedigital/laravel-gitlab-deploy/tree/master/examples).

After installing, you maybe want to publish files for deployment and sample files, right? Just do this:

```shell
php artisan vendor:publish --tag="gitlab-deploy" --force
```

# Usage

## Prepare

Open _(after publishing)_ or create the file
[`deploy/deploy-prepare.yml`](https://github.com/hexidedigital/laravel-gitlab-deploy/blob/master/examples/deploy-prepare.example.yml)
and fill all needed options.

For most cases only need to be specified next options:

- **access token** for project repository ([see tip](#gitlab-api-access-token))
- project **full name** or project **id** ([see tip](#project-full-name-or-id))
- access for the **server**
- access for the **database**

But for every stage are available next options:

- repository url
- executor paths for `php` and `composer`
- access for the server
- access for the database
- access for the mail host (but can be missed)

## Running configuration command

### Basic launch

You can begin configuring your project deployment for specific stage (i.e. for `dev` branch) by running:

```shell
php artisan deploy:gitlab dev
```

Important - see what todo [After command executing](#after-command-executing)

> By default, stage are same git branch.

#### Only-print option

If you want manually execute commands or just prepare to future deployment, set a `--only-print` option when calling
command. All command examples will be written to `deploy/dep-log.log` file. All that remains is to copy and execute
commands from the file.

#### Aliases

For more convenient use of the Laravel **artisan** command, you can add command line aliases to `~/.bashrc`
(or `~/.bash_aliases`). So you can append to file or print a log file with a `--aliases` option when calling command.

## After command executing

If all tasks completely executed, to enable auto-deployment go to
`Settings` -> `CI/CD` -> `Variables` and change value for `CI_ENABLED` to `1`. After that, when you edit
branch with configured deployment, Gitlab will run CI/CD Pipelines automatically

# Tips for Gitlab

## Gitlab API access token

In order for variables and other deployment options to be created, you need to grant access to the repository settings.
This package uses the **Gitlab API** method using **Access Tokens**.

To get Access Token follow this path `Settings` -> `Access Tokens`.

Fill next options like bellow:

- **Token name** - i.e. `deploy_dev` or `deploy_prod`
- **Expiration date** - recommended to set 1-2 days (this will be enough)
- **Role** - `mainterner` - for ability to change repository settings
- **Scopes** - only `api`

Then click `Create project access token` to see the token. **Make sure you save it - you won't be able to access it
again.**

## Project full name or ID

### Project full name

You can get full name in different ways, but most simple is copy from browser url.

I.e. url looks like `https://gitlab.com/namespace/project_name`, so **full name** will be `namespace/project_name`

### Project ID

Project ID are placed under this path `Settings` -> `General` -> `Naming, topics, avatar (alredy open)`.

But for using project ID you must wrap number to quotes like `"XXXXXXXXXX"` to mark value as string.

# Can I hire you guys?

Yes! Say hi: [hello@hexide-digital.com](mailto:hello@hexide-digital.com)
We will be happy to work with you! Other [work we’ve done](https://hexide-digital.com)

## Follow us

Follow us on [LinkedIn](https://www.linkedin.com/company/hexide-digital)
or [Facebook](https://www.facebook.com/hexide.digital)
