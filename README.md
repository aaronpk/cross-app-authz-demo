# Cross-App Authorization Demo Apps

This is a simple demo application that implements both the Requesting Application and Resource Application described in [this spec](https://github.com/oktadev/draft-enterprise-cross-app-authz).

Note: This application does not use production-grade security mechanisms and should not be used as a reference for basic security things. In particular, this stores client secrets as plaintext in the database. This is to make debugging easier but should not be done in production.


## Getting Started

### Initial Setup

* Copy `.env.example` to `.env` and fill out the details
    * Choose whether you are running this as the Requesting Application (wiki) or Resource Application (todo)
* Set up MySQL, create a database, and grant a user permissions to the database
    * `CREATE DATABASE demo; GRANT ALL PRIVILEGES ON demo.* TO 'demo'@'127.0.0.1' IDENTIFIED BY 'demo';`
* Create the database tables by running the SQL code in `sql/schama.sql`
* Start the app with the built-in PHP server
    * `php -S 127.0.0.1:8080 -t public`

Note: The built-in PHP server will think that URLs ending in `.json` are files and won't route them to the application. If you are trying to fetch a URL like `http://localhost:8080/todo/1.json`, there is an additional route for that at `http://localhost:8080/todo/1_json`.

### Identity Provider Configuration

* Create an application at the IdP for the todo app or wiki app
* Add a record to the `orgs` table with the IdP config and client ID and secret
    * You'll also need to set the email domain for IdP discovery. If you enter `example.com` in the `domain` column, then you'll be routed to that org's IdP when you enter `user@example.com` in the login box.

### Resource Application Configuration

When running the resource application, you'll need to have a client registered for the Requesting Application. Create an entry in the `clients` table generating a random client ID and secret (leave user_id and org_id blank).

## Usage

* Log in to the Resource Application (todo app) and create one or more todo items
* Copy the URL of the todo item
* Log in to the Requesting Application (wiki app)
* Edit the home page and paste in the todo item URL
* The wiki should obtain an ACDC from the IdP, then exchange that for an access token at the Resource App, then fetch the todo item and render it with the name and icon in the wiki page


