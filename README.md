# Cross-App Authorization Demo Apps

This is a simple demo application that implements both the Requesting Application and Resource Application described in [this spec](https://oktadev.github.io/draft-parecki-oauth-cross-domain-authorization/draft-parecki-oauth-cross-domain-authorization.html).

Note: This application does not use production-grade security mechanisms and should not be used as a reference for basic security things. In particular, this stores client secrets as plaintext in the database. This is to make debugging easier but should not be done in production.


## Getting Started

### Docker

```
docker-compose up
```

This will spin up 2 copies of the stack, one for the wiki app and one for the todo app.

Task0: http://localhost:9090/

TinyWiki: http://localhost:7070/

Create the database tables with the following commands

```
docker-compose exec wiki php sql/initdb.php
docker-compose exec todo php sql/initdb.php
```

Pick an email domain to use for IdP routing on the login page. When you enter an email address at this domain in the login page, the app will route you to the configured OIDC connection for this domain.

Find your org issuer URL (available in the top right dropdown menu in the admin dashboard) which you'll need when configuring the integrations.

Create two applications in your Okta org:

* Name: TinyWiki
* Application Type: Native
* Redirect URI: `http://localhost:7070/openid/callback/1`
* Add a client secret
* Enable the "Token Exchange" grant type

Run the following command with the client ID and secret for this app, and the email domain and issuer from the earlier steps.

```
docker-compose exec wiki php scripts/create-org.php example.com dev-XXXXXXX.okta.com CLIENT_ID CLIENT_SECRET
```

* Name: Task0
* Application Type: Web
* Redirect URI: `http://localhost:9090/openid/callback/1`

Run the following command with the client ID and secret for this app, and the email domain and issuer from the earlier steps.

```
docker-compose exec todo php scripts/create-org.php example.com dev-XXXXXXX.okta.com CLIENT_ID CLIENT_SECRET
```

Now you can log in!


### Manual Setup

#### Dependencies

* [MariaDB](https://mariadb.org)
* [PHP 8.2](https://php.net/)
* [Composer](https://getcomposer.org)

#### Initial Setup

* Set up MySQL, create a database, and grant a user permissions to the database
    * `CREATE DATABASE todo_app; GRANT ALL PRIVILEGES ON todo_app.* TO 'todo_app'@'127.0.0.1' IDENTIFIED BY 'todo_app';`
    * `CREATE DATABASE wiki_app; GRANT ALL PRIVILEGES ON wiki_app.* TO 'wiki_app'@'127.0.0.1' IDENTIFIED BY 'wiki_app';`
* Copy `.todo.env.example` to `.todo.env`
* Copy `.wiki.env.example` to `.wiki.env`
* Create the database tables in both databases by running the SQL code in `sql/schema.sql`
* Install the dependencies: `composer install`
* Start the app with the built-in PHP server
    * `./todo.sh`
    * `./wiki.sh`

Note: The built-in PHP server will think that URLs ending in `.json` are files and won't route them to the application. If you are trying to fetch a URL like `http://localhost:8080/todo/1.json`, there is an additional route for that at `http://localhost:8080/todo/1_json`.

#### Identity Provider Configuration

* Create an application at the IdP for the todo app and the wiki app
* Add a record to the `orgs` table with the IdP config and client ID and secret
    * You'll also need to set the email domain for IdP discovery. If you enter `example.com` in the `domain` column, then you'll be routed to that org's IdP when you enter `user@example.com` in the login box.
* Use the redirect URI for each app:
    * Todo: `http://localhost:7070/openid/callback/1`
    * Wiki: `http://localhost:8080/openid/callback/1`

### Resource Application Configuration

When running the resource application, you'll need to have a client registered for the Requesting Application. Create an entry in the `clients` table generating a random client ID and secret (leave `user_id` and `org_id` blank).

## Usage

* Log in to the Resource Application (Task0) and create one or more todo items
* Copy the URL of the todo item
* Log in to the Requesting Application (TinyWiki)
* Edit the home page and paste in the todo item URL
* The wiki should obtain an ACDC from the IdP, then exchange that for an access token at the Resource App, then fetch the todo item and render it with the name and icon in the wiki page


