# Laravel gitlab deploy

This package created to optimize and improve company CI/CD in Gitlab environment.

# Requirements

This package requires **PHP 7.4**  and **Laravel 8.0**.

Available SSH agents on local machine and remote server.

Remote server must use GNU/Linux

# Installation

You can install this package via composer using:

```shell
composer required --dev hexide-digital/hexide-admin-panel:^0.3
```

The package will automatically register its service provider.

After installing, you maybe want to publish files for deploy and sample files, right? Just do this:

```shell
php artisan vendor:publish --tag="gitlab-deploy" --force
```

Source of all publishable files are copied
from [this folder](https://github.com/hexidedigital/laravel-gitlab-deploy/tree/0.x/examples)
to places and folder described
in [service provider](https://github.com/hexidedigital/laravel-gitlab-deploy/blob/0.x/src/GitlabDeployServiceProvider.php#L25-L31)

# Usage

## Prepare

Open _(after publishing)_ or create file
[`deploy/deploy-prepare.yml`](https://github.com/hexidedigital/laravel-gitlab-deploy/blob/0.x/examples/deploy-prepare.example.yml)
and fill all needed options:

- api key for gitlab
- gitlab domain
- project full name or project id (if put id you must wrap number to quotes like `"000...000"`)

For every stage are available next options:

- repository url
- executor paths for `php` and `composer`
- access for server
- access for database
- access for mail host (but can be missed)

## Running configuration command

### Basic launch

You can begin configuring your project deploy for specific stage (i.e. for `dev` branch) by running:

```shell
php artisan deploy:gitlab dev
```

> By default, stage are same git branch.

#### Only-print option

If you want manually execute commands or just prepare to future deploy, set `--only-print` option when calling command.
All command examples will be written to `deploy/dep-log.log` file. All that remains is to copy and execute commands.

#### Aliases

For more convenient use of the laravel artisan command, you can add aliases for the command line into `~/.bashrc`
(or `~/.bash_aliases`). So, you can append to file or print to log file with `--aliases` option when calling command.

# Can I hire you guys?

Yes! Say hi: [hello@hexide-digital.com](mailto:hello@hexide-digital.com)
We will be happy to work with you! Other [work we’ve done](https://hexide-digital.com/)

## Follow us

Follow us on [LinkedIn](https://www.linkedin.com/company/hexide-digital)
or [Facebook](https://www.facebook.com/hexide.digital)
