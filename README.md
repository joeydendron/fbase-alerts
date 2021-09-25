# joeydendron/fbase-alerts

## Intro

I'd written a simple Alerter class, so that during PHP project development I could send informative alerts to a Slack channel.

But occasionally there are situations where code peppers the Slack channel with multiple alerts, and it's annoying to have to wade through and delete them in Slack's UI.

So as an exercise in getting to know Firebase I thought I'd replicate the functionality but with a Firebase backend... so I can build something like a Vue front-end with quick and easy alert deletion.

## Requirements

- PHP >= 7.3
- A Firebase project with a real-time database that has an *alerts* key at the top level.

## Installation

`composer require joeydendron/fbase-alerts`

## Relies on env variables for Firebase credentials

As per [kreait/firebase setup instructions](https://firebase-php.readthedocs.io/en/stable/setup.html), the package requires you to download from Firebase a private key JSON file, which you need to save to your server in a safe location. You'll also need your real-time database URI, which I found in a copy-paste Javascript snippet in the General tab of my project's Settings.

Using a package like vlucas/dotenv, or PHP's putenv() function:

- Put the path to the JSON file in an environment variable named JD_FIREBASE_PATH_TO_CREDENTIALS
- Put the real-time database URI into an environment variable named JD_FIREBASE_DB_URI

### Why use env variables?

It means I can create an instance of Alerter without any external parameters, for simplicity of use (see below).

## How to Use

There are 3 static functions. Each calls for an instance of Alerter, which contains a reference to a Firebase DB object, and pushes an alert onto the DB's alerts collection.

**`Alerter::alert($subject, $body)`**

`$body`can be a string, number, array or object. It's converted to a string via print_r() if required. 

Prepends server hostname to `$subject`.

Then a new alert is pushed... as an associative array:

`[ 'subject' => $subject, 'body' => $body, 'created_at' => mktime() ]`

Firebase creates an ordered, alphanumeric ID for the alert.

**`Alerter::alertThrowable($subject, Throwable $throwable, $extraContent = [])`**

Calls Alerter::alert() with `$body`set to a sringified version of the Throwable. You can pass an optional 
array of values, and these will also be displayed in the resulting alert. So... you can write something 
like

`Alerter::alertThrowable('Oh dear', $throwable, [ 'important_variable' => $myVar ]);`

**`Alerter::alertException($subject, Exception $e, $extraContent = [])`**

Calls `Alerter::alertThrowable($subject, $e)`.
