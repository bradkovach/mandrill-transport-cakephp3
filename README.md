# MandrillTransport plugin for CakePHP 3+

## Purpose

This is a fork of the CakePHP library that fixes some of the deprecation notices that you may receive with `DaoAndCo/mandrill-transport-cakephp3`.  It also fixes the attachments api, so that you don't need to use files to send attachments.

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require bradkovach/mandrill-transport-cakephp3
```

## Setting up your CakePHP application

### CakePHP 3.0 - 3.5: In your bootstrap.php

```php
Plugin::load('MandrillTransport');
```

### CakePHP 3.6+: In Application.php

```php
$this->addPlugin('MandrillTransport');
```

In your app.php file.

```
'EmailTransport' => [
  'Mandrill' => [
    'className'      => 'MandrillTransport.Mandrill',
    'api_key'        => 'YOUR_API_KEY',
    'api_key_test'   => 'YOUR_TEST_API_KEY',
    'from'           => 'no-reply@example.com',
    'merge_language' => 'handlebars', //optional, default is handlebars
    'inline_css'     => true, //optional, default is true
  ],
],
'Email' => [
    'mandrill' => [
        'transport' => 'Mandrill',
        'from' => 'you@localhost',
        //'charset' => 'utf-8',
        //'headerCharset' => 'utf-8',
    ],
],
```

## Utilisation

It can be used like normal Mail transport in cakephp.

If you want to use a template from mailchimp/mandrill, you just have to add the key '*template_name*' with the name of the template in your *viewVars*. And optionnaly other vars which are used in the template.

```
$email = new Email('mandrill');
$email->from(['me@example.com' => 'My Site'])
    ->to('you@example.com')
    ->cc('yourcc@exmaple.com') // optional
    ->bcc('yourbcc@exmaple.com') // optional
    ->attachments('/path/to/your/file') // optional
    ->viewVars([
      'template_name' => 'your template name at mandrill',
      'other_var' => 'values', // all the vars from yout template
      ...
    ])
    ->subject('About')  // optional, if missing it takes the template subject
    ->send('My message');
```